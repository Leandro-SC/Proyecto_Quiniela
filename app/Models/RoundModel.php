<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class RoundModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    private function normalizeStatus(string $status): string
    {
        return match (strtoupper(trim($status))) {
            'OPEN' => 'OPEN',
            'CLOSED' => 'CLOSED',
            'FINISHED' => 'FINISHED',
            'CANCELLED' => 'CANCELLED',
            'LIVE' => 'CLOSED',
            'PENDING' => 'DRAFT',
            default => 'DRAFT',
        };
    }

    private function baseSelect(): string
    {
        return '
            SELECT
                r.*,
                COALESCE(l.name, "Sin Liga") AS league_name,
                l.slug AS league_slug,
                COALESCE(rpc.total_pool_percent, 45.00) AS prize_pool_percent,
                COALESCE(rpc.first_place_percent, 30.00) AS first_place_percent,
                COALESCE(rpc.second_place_percent, 15.00) AS second_place_percent
            FROM rounds r
            LEFT JOIN leagues l ON l.id = r.league_id
            LEFT JOIN round_prize_config rpc ON rpc.round_id = r.id
        ';
    }

    public function getOpenRoundsByLeague(string $leagueSlug): array
    {
        $sql = $this->baseSelect() . '
            WHERE l.slug = :slug
              AND r.status = "OPEN"
              AND r.close_at > NOW()
            ORDER BY r.close_at ASC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':slug' => $leagueSlug]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getRankingRounds(string $leagueSlug): array
    {
        $sql = $this->baseSelect() . '
            WHERE l.slug = :slug
              AND r.status IN ("OPEN", "CLOSED", "FINISHED")
            ORDER BY r.close_at DESC, r.id DESC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':slug' => $leagueSlug]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $sql = $this->baseSelect() . '
            WHERE r.id = :id
            LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function getAllWithLeague(): array
    {
        $sql = $this->baseSelect() . '
            ORDER BY r.open_at DESC, r.id DESC
        ';

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getCurrentRoundForLeagueSlug(string $leagueSlug): ?array
    {
        $sql = $this->baseSelect() . '
            WHERE l.slug = :slug
              AND r.status = "OPEN"
              AND NOW() >= r.open_at
              AND NOW() <= r.close_at
            ORDER BY r.close_at ASC
            LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':slug' => $leagueSlug]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function create(array $data): int
    {
        $this->pdo->beginTransaction();

        try {
            $sql = '
                INSERT INTO rounds (
                    league_id,
                    season_id,
                    name,
                    custom_title,
                    round_number,
                    status,
                    open_at,
                    close_at,
                    ticket_cost_mxn,
                    ticket_cost_usd,
                    max_tickets_per_player,
                    is_featured,
                    created_at,
                    updated_at
                )
                VALUES (
                    :league_id,
                    NULL,
                    :name,
                    :custom_title,
                    :round_number,
                    :status,
                    :open_at,
                    :close_at,
                    :ticket_cost_mxn,
                    :ticket_cost_usd,
                    NULL,
                    0,
                    NOW(),
                    NOW()
                )
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':league_id' => (int)$data['league_id'],
                ':name' => trim((string)$data['name']),
                ':custom_title' => $data['custom_title'] ?? null,
                ':round_number' => (int)$data['round_number'],
                ':status' => $this->normalizeStatus((string)($data['status'] ?? 'DRAFT')),
                ':open_at' => $data['open_at'],
                ':close_at' => $data['close_at'],
                ':ticket_cost_mxn' => (float)($data['ticket_cost_mxn'] ?? 200.00),
                ':ticket_cost_usd' => (float)($data['ticket_cost_usd'] ?? 10.00),
            ]);

            $roundId = (int)$this->pdo->lastInsertId();

            $this->savePrizeConfig($roundId, $data);

            $this->pdo->commit();

            return $roundId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function update(array $data): bool
    {
        $id = (int)($data['id'] ?? 0);

        if ($id <= 0) {
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            $sql = '
                UPDATE rounds
                SET
                    league_id = :league_id,
                    name = :name,
                    custom_title = :custom_title,
                    round_number = :round_number,
                    status = :status,
                    open_at = :open_at,
                    close_at = :close_at,
                    ticket_cost_mxn = :ticket_cost_mxn,
                    ticket_cost_usd = :ticket_cost_usd,
                    updated_at = NOW()
                WHERE id = :id
            ';

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':id' => $id,
                ':league_id' => (int)$data['league_id'],
                ':name' => trim((string)$data['name']),
                ':custom_title' => $data['custom_title'] ?? null,
                ':round_number' => (int)$data['round_number'],
                ':status' => $this->normalizeStatus((string)($data['status'] ?? 'DRAFT')),
                ':open_at' => $data['open_at'],
                ':close_at' => $data['close_at'],
                ':ticket_cost_mxn' => (float)($data['ticket_cost_mxn'] ?? 200.00),
                ':ticket_cost_usd' => (float)($data['ticket_cost_usd'] ?? 10.00),
            ]);

            $this->savePrizeConfig($id, $data);

            $this->pdo->commit();

            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function savePrizeConfig(int $roundId, array $data): void
    {
        $sql = '
            INSERT INTO round_prize_config (
                round_id,
                total_pool_percent,
                first_place_percent,
                second_place_percent,
                notes,
                created_at,
                updated_at
            )
            VALUES (
                :round_id,
                :total_pool_percent,
                :first_place_percent,
                :second_place_percent,
                NULL,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                total_pool_percent = VALUES(total_pool_percent),
                first_place_percent = VALUES(first_place_percent),
                second_place_percent = VALUES(second_place_percent),
                updated_at = NOW()
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':round_id' => $roundId,
            ':total_pool_percent' => (float)($data['prize_pool_percent'] ?? 45.00),
            ':first_place_percent' => (float)($data['first_place_percent'] ?? 30.00),
            ':second_place_percent' => (float)($data['second_place_percent'] ?? 15.00),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM rounds WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function getPrizePercents(int $roundId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                first_place_percent,
                second_place_percent
            FROM round_prize_config
            WHERE round_id = :round_id
            LIMIT 1
        ');

        $stmt->execute([':round_id' => $roundId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'first' => 30.00,
                'second' => 15.00,
            ];
        }

        return [
            'first' => (float)$row['first_place_percent'],
            'second' => (float)$row['second_place_percent'],
        ];
    }

    public function refreshRoundStatus(int $roundId): void
    {
        $round = $this->findById($roundId);

        if (!$round || in_array($round['status'], ['CLOSED', 'FINISHED', 'CANCELLED'], true)) {
            return;
        }

        $shouldClose = false;

        if (new \DateTime() >= new \DateTime((string)$round['close_at'])) {
            $shouldClose = true;
        }

        if (!$shouldClose) {
            $stmt = $this->pdo->prepare('
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status IN ("FINISHED", "CANCELLED") THEN 1 ELSE 0 END) AS completed
                FROM matches
                WHERE round_id = :round_id
            ');

            $stmt->execute([':round_id' => $roundId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            if ((int)$stats['total'] > 0 && (int)$stats['total'] === (int)$stats['completed']) {
                $shouldClose = true;
            }
        }

        if ($shouldClose) {
            $stmt = $this->pdo->prepare('
                UPDATE rounds
                SET status = "CLOSED", updated_at = NOW()
                WHERE id = :id
            ');

            $stmt->execute([':id' => $roundId]);
        }
    }

    public function refreshAllStatuses(): int
    {
        $stmt = $this->pdo->query('
            SELECT id
            FROM rounds
            WHERE status = "OPEN"
        ');

        $rounds = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $updated = 0;

        foreach ($rounds as $round) {
            $before = $this->findById((int)$round['id']);
            $this->refreshRoundStatus((int)$round['id']);
            $after = $this->findById((int)$round['id']);

            if ($before && $after && $before['status'] !== $after['status']) {
                $updated++;
            }
        }

        return $updated;
    }
}