<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\RoundModel;
use App\Models\MatchModel;
use App\Services\RankingService;
use PDO;
use RuntimeException;
use Throwable;

class RoundMatchesAdminController extends BaseAdminController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $roundId = (int)($_GET['round_id'] ?? 0);

        if ($roundId <= 0) {
            header('Location: /admin/rounds');
            exit;
        }

        $roundModel = new RoundModel();
        $matchModel = new MatchModel();

        $round = $roundModel->findById($roundId);

        if (!$round) {
            header('Location: /admin/rounds');
            exit;
        }

        $matches = $matchModel->getByRound($roundId);

        $this->render('admin/matches/index', [
            'pageTitle' => 'Partidos de la jornada',
            'round' => $round,
            'league' => [
                'name' => $round['league_name'] ?? '',
            ],
            'matches' => $matches,
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

    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $matchId = (int)($_POST['id'] ?? 0);
        $roundId = (int)($_POST['round_id'] ?? 0);

        if ($matchId > 0) {
            try {
                $matchModel = new MatchModel();
                $matchModel->delete($matchId);

                $rankingService = new RankingService();
                $rankingService->recomputeRound($roundId);

                $roundModel = new RoundModel();
                $roundModel->refreshRoundStatus($roundId);
            } catch (Throwable $e) {
                error_log('Error eliminando partido: ' . $e->getMessage());
            }
        }

        header('Location: /admin/rounds/matches?round_id=' . $roundId);
        exit;
    }

    public function refreshStatuses(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $roundModel = new RoundModel();
        $totalUpdated = $roundModel->refreshAllStatuses();

        header('Location: /admin/rounds?refreshed=' . $totalUpdated);
        exit;
    }

    private function renderForm(int $roundId, ?int $matchId): void
    {
        if ($roundId <= 0) {
            header('Location: /admin/rounds');
            exit;
        }

        $roundModel = new RoundModel();
        $round = $roundModel->findById($roundId);

        if (!$round) {
            header('Location: /admin/rounds');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                $this->handleSave($round, $matchId);

                header('Location: /admin/rounds/matches?round_id=' . $roundId);
                exit;
            } catch (Throwable $e) {
                error_log('Error guardando partido: ' . $e->getMessage());

                $this->render('admin/matches/form', [
                    'pageTitle' => $matchId ? 'Editar partido' : 'Nuevo partido',
                    'round' => $round,
                    'match' => $_POST,
                    'clubs' => $this->getTeamsForSelect((int)$round['league_id']),
                    'countries' => $this->getCountries(),
                    'error' => $e->getMessage(),
                ]);
                return;
            }
        }

        $match = null;

        if ($matchId !== null && $matchId > 0) {
            $match = $this->getMatchForForm($matchId);
        }

        $this->render('admin/matches/form', [
            'pageTitle' => $matchId ? 'Editar partido' : 'Nuevo partido',
            'round' => $round,
            'match' => $match,
            'clubs' => $this->getTeamsForSelect((int)$round['league_id']),
            'countries' => $this->getCountries(),
        ]);
    }

    private function handleSave(array $round, ?int $matchId): void
    {
        $roundId = (int)$round['id'];
        $leagueId = (int)$round['league_id'];

        $homeTeamName = trim((string)($_POST['home_team_name'] ?? ''));
        $awayTeamName = trim((string)($_POST['away_team_name'] ?? ''));

        $homeTeamId = (int)($_POST['home_team_id'] ?? 0);
        $awayTeamId = (int)($_POST['away_team_id'] ?? 0);

        $homeCountryId = (int)($_POST['home_country_id'] ?? 0);
        $awayCountryId = (int)($_POST['away_country_id'] ?? 0);

        $homeLogo = $this->uploadLogo('home_logo_file') ?: trim((string)($_POST['home_team_logo'] ?? ''));
        $awayLogo = $this->uploadLogo('away_logo_file') ?: trim((string)($_POST['away_team_logo'] ?? ''));

        if ($homeTeamName === '' && $homeTeamId <= 0) {
            throw new RuntimeException('El equipo local es obligatorio.');
        }

        if ($awayTeamName === '' && $awayTeamId <= 0) {
            throw new RuntimeException('El equipo visitante es obligatorio.');
        }

        $homeTeamId = $this->ensureTeam(
            $homeTeamId,
            $homeTeamName,
            $leagueId,
            $homeCountryId,
            $homeLogo !== '' ? $homeLogo : null
        );

        $awayTeamId = $this->ensureTeam(
            $awayTeamId,
            $awayTeamName,
            $leagueId,
            $awayCountryId,
            $awayLogo !== '' ? $awayLogo : null
        );

        if ($homeTeamId === $awayTeamId) {
            throw new RuntimeException('El equipo local y visitante no pueden ser el mismo.');
        }

        $status = strtoupper(trim((string)($_POST['status'] ?? 'SCHEDULED')));

        if (!in_array($status, ['SCHEDULED', 'LIVE', 'FINISHED', 'POSTPONED', 'CANCELLED'], true)) {
            $status = 'SCHEDULED';
        }

        $kickoffAt = trim((string)($_POST['kickoff_at'] ?? ''));

        if ($kickoffAt === '') {
            throw new RuntimeException('La fecha y hora del partido son obligatorias.');
        }

        $kickoffAt = str_replace('T', ' ', $kickoffAt);

        if (strlen($kickoffAt) === 16) {
            $kickoffAt .= ':00';
        }

        $homeScore = $_POST['home_score'] ?? null;
        $awayScore = $_POST['away_score'] ?? null;

        $homeScore = $homeScore === '' ? null : (int)$homeScore;
        $awayScore = $awayScore === '' ? null : (int)$awayScore;

        $resultOutcome = strtoupper(trim((string)($_POST['result_outcome'] ?? '')));

        if (!in_array($resultOutcome, ['L', 'E', 'V'], true)) {
            $resultOutcome = null;
        }

        if ($homeScore !== null && $awayScore !== null) {
            if ($homeScore > $awayScore) {
                $resultOutcome = 'L';
            } elseif ($homeScore === $awayScore) {
                $resultOutcome = 'E';
            } else {
                $resultOutcome = 'V';
            }

            if ($status === 'SCHEDULED') {
                $status = 'FINISHED';
            }
        }

        $data = [
            'round_id' => $roundId,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'home_team_name' => $homeTeamName,
            'away_team_name' => $awayTeamName,
            'kickoff_at' => $kickoffAt,
            'status' => $status,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'result_outcome' => $resultOutcome,
            'external_event_id' => $_POST['external_event_id'] ?? null,
            'display_order' => (int)($_POST['display_order'] ?? 0),
        ];

        $matchModel = new MatchModel();

        if ($matchId === null || $matchId <= 0) {
            $matchModel->create($data);
        } else {
            $matchModel->update($matchId, $data);
        }

        $rankingService = new RankingService();
        $rankingService->recomputeRound($roundId);

        $roundModel = new RoundModel();
        $roundModel->refreshRoundStatus($roundId);
    }

    private function getMatchForForm(int $matchId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                m.*,
                ht.name AS home_team_name,
                at.name AS away_team_name,
                ht.logo_path AS home_team_logo,
                at.logo_path AS away_team_logo,
                ht.country_id AS home_country_id,
                at.country_id AS away_country_id
            FROM matches m
            INNER JOIN teams ht ON ht.id = m.home_team_id
            INNER JOIN teams at ON at.id = m.away_team_id
            WHERE m.id = :id
            LIMIT 1
        ');

        $stmt->execute([
            ':id' => $matchId,
        ]);

        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        return $match ?: null;
    }

    private function getTeamsForSelect(int $leagueId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                id,
                name,
                country_id,
                logo_path AS badge_path
            FROM teams
            WHERE is_active = 1
              AND (league_id = :league_id OR league_id IS NULL)
            ORDER BY name ASC
        ');

        $stmt->execute([
            ':league_id' => $leagueId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getCountries(): array
    {
        $stmt = $this->pdo->query('
            SELECT id, name
            FROM countries
            ORDER BY name ASC
        ');

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function ensureTeam(
        int $teamId,
        string $teamName,
        int $leagueId,
        int $countryId,
        ?string $logoPath
    ): int {
        if ($teamId > 0) {
            $this->updateTeamMetadata($teamId, $countryId, $logoPath);
            return $teamId;
        }

        $teamName = trim($teamName);

        if ($teamName === '') {
            throw new RuntimeException('Nombre de equipo inválido.');
        }

        $stmt = $this->pdo->prepare('
            SELECT id
            FROM teams
            WHERE league_id = :league_id
              AND LOWER(TRIM(name)) = LOWER(TRIM(:name))
            LIMIT 1
        ');

        $stmt->execute([
            ':league_id' => $leagueId,
            ':name' => $teamName,
        ]);

        $existingId = $stmt->fetchColumn();

        if ($existingId !== false) {
            $id = (int)$existingId;
            $this->updateTeamMetadata($id, $countryId, $logoPath);
            return $id;
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO teams (
                country_id,
                league_id,
                name,
                short_name,
                slug,
                logo_path,
                is_active,
                created_at,
                updated_at
            )
            VALUES (
                :country_id,
                :league_id,
                :name,
                :short_name,
                :slug,
                :logo_path,
                1,
                NOW(),
                NOW()
            )
        ');

        $stmt->execute([
            ':country_id' => $countryId > 0 ? $countryId : null,
            ':league_id' => $leagueId,
            ':name' => $teamName,
            ':short_name' => $teamName,
            ':slug' => $this->slugify($teamName),
            ':logo_path' => $logoPath,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    private function updateTeamMetadata(int $teamId, int $countryId, ?string $logoPath): void
    {
        $fields = [];
        $params = [
            ':id' => $teamId,
        ];

        if ($countryId > 0) {
            $fields[] = 'country_id = :country_id';
            $params[':country_id'] = $countryId;
        }

        if ($logoPath !== null && trim($logoPath) !== '') {
            $fields[] = 'logo_path = :logo_path';
            $params[':logo_path'] = $logoPath;
        }

        if ($fields === []) {
            return;
        }

        $fields[] = 'updated_at = NOW()';

        $sql = 'UPDATE teams SET ' . implode(', ', $fields) . ' WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    private function uploadLogo(string $inputName): ?string
    {
        if (
            !isset($_FILES[$inputName]) ||
            !isset($_FILES[$inputName]['error']) ||
            $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK
        ) {
            return null;
        }

        $uploadDir = dirname(__DIR__, 3) . '/assets/uploads/clubs/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalName = basename((string)$_FILES[$inputName]['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            throw new RuntimeException('Formato de imagen no permitido.');
        }

        $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
        $fileName = time() . '_' . bin2hex(random_bytes(4)) . '_' . $cleanName;

        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file((string)$_FILES[$inputName]['tmp_name'], $targetPath)) {
            throw new RuntimeException('No se pudo subir el escudo.');
        }

        return '/assets/uploads/clubs/' . $fileName;
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text) ?? '';
        $text = trim($text, '-');

        return $text !== '' ? $text : 'equipo-' . time();
    }
}