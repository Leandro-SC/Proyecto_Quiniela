<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\RoundModel;
use App\Models\MatchModel;
use App\Models\ClubModel;
use App\Services\RankingService;
use PDO;

class RoundMatchesAdminController extends BaseAdminController
{
    private PDO $pdo;

    // FIX: Constructor sin parámetros para ser compatible con el Router
    public function __construct()
    {
        // No llamamos a parent::__construct($config) porque no existe en el padre
        $this->pdo = Database::getConnection();
    }

    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $roundId = (int)($_GET['round_id'] ?? 0);
        
        $roundModel = new RoundModel();
        $round = $roundModel->findById($roundId);
        
        if (!$round) {
            header('Location: /admin/rounds');
            exit;
        }

        $matchModel = new MatchModel();
        $matches = $matchModel->getByRound($roundId);

        $this->render('admin/matches/index', [
            'pageTitle' => 'Partidos de la jornada',
            'round'     => $round,
            'league'    => ['name' => $round['league_name'] ?? ''],
            'matches'   => $matches,
        ]);
    }

    public function create(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $roundId = (int)($_GET['round_id'] ?? 0);
        $this->renderForm($roundId, null);
    }

    public function edit(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $roundId = (int)($_GET['round_id'] ?? 0);
        $matchId = (int)($_GET['match_id'] ?? 0);
        $this->renderForm($roundId, $matchId);
    }

    private function renderForm(int $roundId, ?int $matchId): void
    {
        $roundModel = new RoundModel();
        $round = $roundModel->findById($roundId);
        
        if (!$round) {
            header('Location: /admin/rounds');
            exit;
        }
        
        $countries = $this->pdo->query('SELECT id, name FROM countries ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $this->handleSave($roundId, $matchId);
        header('Location: /admin/rounds/matches?round_id=' . $roundId);
        exit;
    }

        $match = null;
        if ($matchId) {
            $stmt = $this->pdo->prepare('SELECT * FROM matches WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $matchId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $clubModel = new ClubModel();
        $clubs = $clubModel->getAllWithCountry();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handleSave($roundId, $matchId);
            header('Location: /admin/rounds/matches?round_id=' . $roundId);
            exit;
        }

        $this->render('admin/matches/form', [
            'pageTitle' => $matchId ? 'Editar partido' : 'Nuevo partido',
            'round'     => $round,
            'match'     => $match,
            'clubs'     => $clubs,
            'countries' => $countries
        ]);
    }

// Modificar solo este método en RoundMatchesAdminController.php

private function handleSave(int $roundId, ?int $matchId): void
{
    $homeName = trim($_POST['home_team_name'] ?? '');
    $awayName = trim($_POST['away_team_name'] ?? '');
    
    // NUEVO: Capturar países del formulario
    $homeCountryId = (int)($_POST['home_country_id'] ?? 0);
    $awayCountryId = (int)($_POST['away_country_id'] ?? 0);

    // 1. Subida / Obtención de Logos
    $homeLogo = $this->uploadLogo('home_logo_file') ?? trim($_POST['home_team_logo'] ?? '') ?: null;
    $awayLogo = $this->uploadLogo('away_logo_file') ?? trim($_POST['away_team_logo'] ?? '') ?: null;

    // 2. SINCRONIZACIÓN: Ahora pasamos el country_id
    $clubModel = new ClubModel();
    if ($homeName !== '') {
        $clubModel->sync($homeName, $homeLogo, $homeCountryId);
    }
    if ($awayName !== '') {
        $clubModel->sync($awayName, $awayLogo, $awayCountryId);
    }

    // ... (El resto del código de handleSave se mantiene igual)
    $status   = trim($_POST['status'] ?? 'SCHEDULED');
    $kickoff  = trim($_POST['kickoff_at'] ?? '') ?: null;
    $homeScore = $_POST['home_score'] ?? '';
    $awayScore = $_POST['away_score'] ?? '';
    $hScoreVal = null;
    $aScoreVal = null;
    $outcome   = null;

    if ($homeScore !== '' && $awayScore !== '') {
        $hScoreVal = (int)$homeScore;
        $aScoreVal = (int)$awayScore;
        if ($hScoreVal > $aScoreVal) $outcome = 'L';
        elseif ($aScoreVal > $hScoreVal) $outcome = 'V';
        else $outcome = 'E';
        if ($status === 'SCHEDULED') $status = 'FINISHED';
    }

    $model = new MatchModel();
    $data = [
        'round_id'       => $roundId,
        'home_team_name' => $homeName,
        'away_team_name' => $awayName,
        'home_team_logo' => $homeLogo,
        'away_team_logo' => $awayLogo,
        'kickoff_at'     => $kickoff,
        'status'         => $status,
        'home_score'     => $hScoreVal,
        'away_score'     => $aScoreVal,
        'result_outcome' => $outcome
    ];

    if ($matchId === null) {
        $model->create($data);
    } else {
        $model->update($matchId, $data);
    }

    $rankingService = new RankingService();
    $rankingService->recomputeRound($roundId);
    
    // NUEVO: Verificar si la jornada debe cerrarse automáticamente
    $roundModel = new RoundModel();
    $roundModel->refreshRoundStatus($roundId);
}

    private function uploadLogo(string $inputName): ?string
    {
        if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../assets/uploads/clubs/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
            
            $name = basename($_FILES[$inputName]['name']);
            $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
            $fileName = time() . '_' . $cleanName;
            
            if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $uploadDir . $fileName)) {
                return '/assets/uploads/clubs/' . $fileName;
            }
        }
        return null;
    }
    
    /**
     * Eliminar un partido existente.
     * POST /admin/rounds/matches/delete
     */
    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $matchId = (int)($_POST['id'] ?? 0);
        $roundId = (int)($_POST['round_id'] ?? 0);

        if ($matchId > 0) {
            try {
                $matchModel = new MatchModel();
                
                // Opción A: Si tu MatchModel tiene un método delete() genérico
                // $matchModel->delete($matchId); 
                
                // Opción B: Si no tienes método delete, usa SQL directo (más seguro para este caso)
                $db = \App\Core\Database::getInstance();
                $db->query("DELETE FROM matches WHERE id = :id", [':id' => $matchId]);
                
            } catch (Throwable $e) {
                // Opcional: Manejar error (ej. si hay tickets comprados asociados a este partido)
                // En tu BD, si borras un match, asegúrate de que no rompa la tabla ticket_items
            }
        }

        // Redireccionar de vuelta a la lista de la jornada
        header('Location: /admin/rounds/matches?round_id=' . $roundId);
        exit;
    }
    
    
    
    public function refreshStatuses(Request $request, Response $response): void
{
    $this->requireAdmin();

    $model = new RoundModel();
    $totalUpdated = $model->refreshAllStatuses();

    // Puedes redirigir con un mensaje o simplemente volver al index
    header('Location: /admin/rounds?refreshed=' . $totalUpdated);
    exit;
}
}