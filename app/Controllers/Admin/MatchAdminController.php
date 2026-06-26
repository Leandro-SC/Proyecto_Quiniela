<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\RoundModel;
use App\Models\MatchModel;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

/**
 * Módulo admin para gestionar partidos de una jornada.
 */
class MatchAdminController extends BaseAdminController
{
    /**
     * Pantalla para gestionar partidos de una jornada.
     * GET /admin/matches/manage?round_id=ID
     */
    public function manage(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $roundId = (int)($_GET['round_id'] ?? 0);
        if ($roundId <= 0) {
            header('Location: /admin/rounds');
            exit;
        }

        $roundModel = new RoundModel();
        $matchModel = new MatchModel();

        // CORRECCIÓN 1: El método 'findByIdWithLeague' no existe en RoundModel.
        // Usamos 'findById' que ya incluye el join con la liga.
        $round = $roundModel->findById($roundId);
        
        if ($round === null) {
            header('Location: /admin/rounds');
            exit;
        }

        // CORRECCIÓN 2: El método 'getByRoundId' no existe en MatchModel.
        // El nombre correcto según tu archivo es 'getByRound'.
        $matches = $matchModel->getByRound($roundId);

        // CORRECCIÓN 3: La columna se llama 'name', no 'round_name' en el resultado de findById.
        $this->render('admin/matches/manage', [
            'pageTitle' => 'Admin · Partidos de ' . ($round['name'] ?? 'Jornada'),
            'round'     => $round,
            'matches'   => $matches,
        ]);
    }

    /**
     * Guardar un partido nuevo.
     * POST /admin/matches/store
     */
    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $roundId    = (int)($_POST['round_id'] ?? 0);
        $homeTeam   = trim((string)($_POST['home_team'] ?? ''));
        $awayTeam   = trim((string)($_POST['away_team'] ?? ''));
        $kickoffStr = (string)($_POST['kickoff_at'] ?? '');

        try {
            if ($roundId <= 0) {
                throw new RuntimeException('Jornada inválida.');
            }
            if ($homeTeam === '' || $awayTeam === '') {
                throw new RuntimeException('Los equipos local y visitante son obligatorios.');
            }

            $kickoff = self::parseDateTimeLocal($kickoffStr);

            $matchModel = new MatchModel();
            
            // CORRECCIÓN 4: El método 'createMatch' no existe y la firma era incorrecta.
            // MatchModel usa 'create' y espera un array asociativo.
            $matchModel->create([
                'round_id'       => $roundId,
                'home_team_name' => $homeTeam,
                'away_team_name' => $awayTeam,
                'kickoff_at'     => $kickoff->format('Y-m-d H:i:s'),
                // Campos opcionales requeridos por el modelo para evitar undefined index warnings
                'status'            => 'SCHEDULED', 
                'external_match_id' => null,
                'home_team_logo'    => null,
                'away_team_logo'    => null
            ]);

            header('Location: /admin/matches/manage?round_id=' . $roundId);
            exit;
        } catch (Throwable $e) {
            $roundModel = new RoundModel();
            // Corrección repetida: usar findById
            $round      = $roundModel->findById($roundId); 
            $matchModel = new MatchModel();
            // Corrección repetida: usar getByRound
            $matches    = $matchModel->getByRound($roundId);

            $this->render('admin/matches/manage', [
                'pageTitle' => 'Admin · Partidos',
                'round'     => $round,
                'matches'   => $matches,
                'error'     => $e->getMessage(),
                'old'       => $_POST,
            ]);
        }
    }

    private static function parseDateTimeLocal(string $value): DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            throw new RuntimeException('Fecha y hora son obligatorias.');
        }

        $value = str_replace('T', ' ', $value) . ':00';
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        if ($dt === false) {
            throw new RuntimeException('Formato de fecha/hora inválido.');
        }

        return $dt;
    }
    
    public function delete(Request $request, Response $response): void
{
    $id = (int)$request->getPost('id');
    $roundId = (int)$request->getPost('round_id'); // Para regresar a la jornada

    if ($id > 0) {
        $matchModel = new \App\Models\MatchModel();
        $matchModel->delete($id);
    }

    // Redireccionar de vuelta a la gestión de partidos de esa jornada
    header("Location: /admin/rounds/matches?round_id=" . $roundId);
    exit;
}
}