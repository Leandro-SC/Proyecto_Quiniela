<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

class MatchModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    private function baseSelect(): string
    {
        return '
            SELECT
                m.*,
                ht.name AS home_team_name,
                at.name AS away_team_name,
                ht.logo_path AS home_team_logo,
                at.logo_path AS away_team_logo,
                l.name AS league_name
            FROM matches m
            INNER JOIN teams ht ON ht.id = m.home_team_id
            INNER JOIN teams at ON at.id = m.away_team_id
            INNER JOIN rounds r ON r.id = m.round_id
            INNER JOIN leagues l ON l.id = r.league_id
        ';
    }

    public function getByRound(int $roundId): array
    {
        $sql = $this->baseSelect() . '
            WHERE m.round_id = :rid
            ORDER BY m.kickoff_at ASC, m.id ASC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':rid' => $roundId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPublicMatchesByRound(int $roundId): array
    {
        $sql = $this->baseSelect() . '
            WHERE m.round_id = :rid
              AND m.status NOT IN ("FINISHED", "CANCELLED", "POSTPONED")
            ORDER BY m.kickoff_at ASC, m.id ASC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':rid' => $roundId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int
    {
        $homeTeamId = $this->resolveTeamId($data['home_team_id'] ?? null, $data['home_team_name'] ?? null);
        $awayTeamId = $this->resolveTeamId($data['away_team_id'] ?? null, $data['away_team_name'] ?? null);

        if ($homeTeamId === $awayTeamId) {
            throw new RuntimeException('El equipo local y visitante no pueden ser el mismo.');
        }

        $leagueId = $this->getLeagueIdByRound((int)$data['round_id']);

        $sql = '
            INSERT INTO matches (
                round_id,
                league_id,
                season_id,
                home_team_id,
                away_team_id,
                kickoff_at,
                status,
                home_score,
                away_score,
                result_outcome,
                external_event_id,
                display_order,
                created_at,
                updated_at
            )
            VALUES (
                :round_id,
                :league_id,
                NULL,
                :home_team_id,
                :away_team_id,
                :kickoff_at,
                :status,
                :home_score,
                :away_score,
                :result_outcome,
                :external_event_id,
                :display_order,
                NOW(),
                NOW()
            )
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':round_id' => (int)$data['round_id'],
            ':league_id' => $leagueId,
            ':home_team_id' => $homeTeamId,
            ':away_team_id' => $awayTeamId,
            ':kickoff_at' => $data['kickoff_at'] ?? date('Y-m-d H:i:s'),
            ':status' => $this->normalizeStatus((string)($data['status'] ?? 'SCHEDULED')),
            ':home_score' => $this->nullableScore($data['home_score'] ?? null),
            ':away_score' => $this->nullableScore($data['away_score'] ?? null),
            ':result_outcome' => $this->normalizeOutcome($data['result_outcome'] ?? null),
            ':external_event_id' => $data['external_event_id'] ?? null,
            ':display_order' => (int)($data['display_order'] ?? 0),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $homeTeamId = $this->resolveTeamId($data['home_team_id'] ?? null, $data['home_team_name'] ?? null);
        $awayTeamId = $this->resolveTeamId($data['away_team_id'] ?? null, $data['away_team_name'] ?? null);

        if ($homeTeamId === $awayTeamId) {
            throw new RuntimeException('El equipo local y visitante no pueden ser el mismo.');
        }

        $leagueId = $this->getLeagueIdByRound((int)$data['round_id']);

        $sql = '
            UPDATE matches
            SET
                round_id = :round_id,
                league_id = :league_id,
                home_team_id = :home_team_id,
                away_team_id = :away_team_id,
                kickoff_at = :kickoff_at,
                status = :status,
                home_score = :home_score,
                away_score = :away_score,
                result_outcome = :result_outcome,
                external_event_id = :external_event_id,
                display_order = :display_order,
                updated_at = NOW()
            WHERE id = :id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':round_id' => (int)$data['round_id'],
            ':league_id' => $leagueId,
            ':home_team_id' => $homeTeamId,
            ':away_team_id' => $awayTeamId,
            ':kickoff_at' => $data['kickoff_at'] ?? date('Y-m-d H:i:s'),
            ':status' => $this->normalizeStatus((string)($data['status'] ?? 'SCHEDULED')),
            ':home_score' => $this->nullableScore($data['home_score'] ?? null),
            ':away_score' => $this->nullableScore($data['away_score'] ?? null),
            ':result_outcome' => $this->normalizeOutcome($data['result_outcome'] ?? null),
            ':external_event_id' => $data['external_event_id'] ?? null,
            ':display_order' => (int)($data['display_order'] ?? 0),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM matches WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    private function resolveTeamId(mixed $teamId, ?string $teamName): int
    {
        if ((int)$teamId > 0) {
            return (int)$teamId;
        }

        $name = trim((string)$teamName);

        if ($name === '') {
            throw new RuntimeException('Debe seleccionar un equipo válido.');
        }

        $stmt = $this->pdo->prepare('
            SELECT id
            FROM teams
            WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name))
            LIMIT 1
        ');

        $stmt->execute([':name' => $name]);
        $id = $stmt->fetchColumn();

        if ($id === false) {
            throw new RuntimeException('No se encontró el equipo: ' . $name);
        }

        return (int)$id;
    }

    private function getLeagueIdByRound(int $roundId): int
    {
        $stmt = $this->pdo->prepare('
            SELECT league_id
            FROM rounds
            WHERE id = :id
            LIMIT 1
        ');

        $stmt->execute([':id' => $roundId]);
        $leagueId = $stmt->fetchColumn();

        if ($leagueId === false) {
            throw new RuntimeException('No se encontró la ronda seleccionada.');
        }

        return (int)$leagueId;
    }

    private function normalizeStatus(string $status): string
    {
        return match (strtoupper(trim($status))) {
            'LIVE' => 'LIVE',
            'FINISHED' => 'FINISHED',
            'CANCELLED' => 'CANCELLED',
            'POSTPONED' => 'POSTPONED',
            default => 'SCHEDULED',
        };
    }

    private function normalizeOutcome(mixed $outcome): ?string
    {
        $value = strtoupper(trim((string)$outcome));

        return in_array($value, ['L', 'E', 'V'], true) ? $value : null;
    }

    private function nullableScore(mixed $score): ?int
    {
        if ($score === null || $score === '') {
            return null;
        }

        return (int)$score;
    }
}