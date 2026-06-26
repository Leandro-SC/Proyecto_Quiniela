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

class MatchAdminController extends BaseAdminController
{
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

        $round = $roundModel->findById($roundId);

        if ($round === null) {
            header('Location: /admin/rounds');
            exit;
        }

        $matches = $matchModel->getByRound($roundId);

        $this->render('admin/matches/manage', [
            'pageTitle' => 'Admin · Partidos de ' . ($round['name'] ?? 'Jornada'),
            'round' => $round,
            'matches' => $matches,
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $roundId = (int)($_POST['round_id'] ?? 0);

        try {
            $data = $this->sanitizeMatchData($_POST);

            $matchModel = new MatchModel();
            $matchModel->create($data);

            $this->redirectToManage($roundId);
        } catch (Throwable $e) {
            error_log('Error creando partido: ' . $e->getMessage());

            $roundModel = new RoundModel();
            $matchModel = new MatchModel();

            $round = $roundId > 0 ? $roundModel->findById($roundId) : null;
            $matches = $roundId > 0 ? $matchModel->getByRound($roundId) : [];

            $this->render('admin/matches/manage', [
                'pageTitle' => 'Admin · Partidos',
                'round' => $round,
                'matches' => $matches,
                'error' => $e->getMessage(),
                'old' => $_POST,
            ]);
        }
    }

    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $roundId = (int)($_POST['round_id'] ?? 0);

        try {
            if ($id <= 0) {
                throw new RuntimeException('ID de partido inválido.');
            }

            $data = $this->sanitizeMatchData($_POST);

            $matchModel = new MatchModel();
            $matchModel->update($id, $data);

            $this->redirectToManage($roundId);
        } catch (Throwable $e) {
            error_log('Error actualizando partido: ' . $e->getMessage());

            $roundModel = new RoundModel();
            $matchModel = new MatchModel();

            $round = $roundId > 0 ? $roundModel->findById($roundId) : null;
            $matches = $roundId > 0 ? $matchModel->getByRound($roundId) : [];

            $this->render('admin/matches/manage', [
                'pageTitle' => 'Admin · Partidos',
                'round' => $round,
                'matches' => $matches,
                'error' => $e->getMessage(),
                'old' => $_POST,
            ]);
        }
    }

    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $roundId = (int)($_POST['round_id'] ?? 0);

        if ($id > 0) {
            $matchModel = new MatchModel();
            $matchModel->delete($id);
        }

        $this->redirectToManage($roundId);
    }

    private function sanitizeMatchData(array $input): array
    {
        $roundId = (int)($input['round_id'] ?? 0);

        if ($roundId <= 0) {
            throw new RuntimeException('Jornada inválida.');
        }

        $homeTeamId = (int)($input['home_team_id'] ?? 0);
        $awayTeamId = (int)($input['away_team_id'] ?? 0);

        $homeTeamName = trim((string)($input['home_team_name'] ?? $input['home_team'] ?? ''));
        $awayTeamName = trim((string)($input['away_team_name'] ?? $input['away_team'] ?? ''));

        if ($homeTeamId <= 0 && $homeTeamName === '') {
            throw new RuntimeException('El equipo local es obligatorio.');
        }

        if ($awayTeamId <= 0 && $awayTeamName === '') {
            throw new RuntimeException('El equipo visitante es obligatorio.');
        }

        $kickoffAt = $this->parseDateTimeLocal((string)($input['kickoff_at'] ?? ''));

        $homeScore = $input['home_score'] ?? null;
        $awayScore = $input['away_score'] ?? null;

        $status = strtoupper(trim((string)($input['status'] ?? 'SCHEDULED')));

        if (!in_array($status, ['SCHEDULED', 'LIVE', 'FINISHED', 'POSTPONED', 'CANCELLED'], true)) {
            $status = 'SCHEDULED';
        }

        $resultOutcome = strtoupper(trim((string)($input['result_outcome'] ?? '')));

        if (!in_array($resultOutcome, ['L', 'E', 'V'], true)) {
            $resultOutcome = null;
        }

        if ($resultOutcome === null && $homeScore !== null && $homeScore !== '' && $awayScore !== null && $awayScore !== '') {
            $home = (int)$homeScore;
            $away = (int)$awayScore;

            if ($home > $away) {
                $resultOutcome = 'L';
            } elseif ($home === $away) {
                $resultOutcome = 'E';
            } else {
                $resultOutcome = 'V';
            }
        }

        return [
            'round_id' => $roundId,
            'home_team_id' => $homeTeamId > 0 ? $homeTeamId : null,
            'away_team_id' => $awayTeamId > 0 ? $awayTeamId : null,
            'home_team_name' => $homeTeamName,
            'away_team_name' => $awayTeamName,
            'kickoff_at' => $kickoffAt->format('Y-m-d H:i:s'),
            'status' => $status,
            'home_score' => $homeScore === '' ? null : $homeScore,
            'away_score' => $awayScore === '' ? null : $awayScore,
            'result_outcome' => $resultOutcome,
            'external_event_id' => $input['external_event_id'] ?? $input['external_match_id'] ?? null,
            'display_order' => (int)($input['display_order'] ?? 0),
        ];
    }

    private function parseDateTimeLocal(string $value): DateTimeImmutable
    {
        $value = trim($value);

        if ($value === '') {
            throw new RuntimeException('Fecha y hora son obligatorias.');
        }

        if (str_contains($value, 'T')) {
            $value = str_replace('T', ' ', $value);
        }

        if (strlen($value) === 16) {
            $value .= ':00';
        }

        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

        if ($dt === false) {
            throw new RuntimeException('Formato de fecha/hora inválido.');
        }

        return $dt;
    }

    private function redirectToManage(int $roundId): void
    {
        if ($roundId > 0) {
            header('Location: /admin/rounds/matches?round_id=' . $roundId);
            exit;
        }

        header('Location: /admin/rounds');
        exit;
    }
}