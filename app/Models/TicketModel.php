<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

class TicketModel
{
    private PDO $pdo;
    private PlayerModel $playerModel;
    private PurchaseSessionModel $purchaseSessionModel;
    private TicketItemModel $ticketItemModel;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->playerModel = new PlayerModel();
        $this->purchaseSessionModel = new PurchaseSessionModel();
        $this->ticketItemModel = new TicketItemModel();
    }

    public function createTicket(array $data): int
    {
        $roundId = (int)($data['round_id'] ?? $data['round_id'] ?? 0);
        $items = $data['items'] ?? [];

        if ($roundId <= 0) {
            throw new RuntimeException('Ronda inválida.');
        }

        if (!is_array($items) || $items === []) {
            throw new RuntimeException('Debes seleccionar al menos un partido.');
        }

        $this->validateRoundIsOpen($roundId);

        $this->pdo->beginTransaction();

        try {
            $playerId = $this->playerModel->findOrCreate([
                'full_name' => $data['user_name'] ?? $data['full_name'] ?? $data['name'] ?? '',
                'phone' => $data['phone'] ?? '',
                'country_code' => $data['country_code'] ?? 'US',
                'email' => $data['email'] ?? null,
            ]);

            $round = $this->getRound($roundId);

            $grossAmount = (float)($data['total_amount'] ?? $round['ticket_cost_usd'] ?? 10.00);
            $currency = $data['currency'] ?? 'USD';

            $purchaseSessionId = $this->purchaseSessionModel->create([
                'player_id' => $playerId,
                'promotion_id' => $data['promotion_id'] ?? null,
                'gross_amount' => $grossAmount,
                'discount_amount' => (float)($data['discount_amount'] ?? 0),
                'currency' => $currency,
            ]);

            $roundTicketNumber = $this->getNextRoundTicketNumber($roundId);
            $ticketCode = $this->generateTicketCode($roundId, $roundTicketNumber);

            $normalizedPhone = $this->normalizePhone((string)($data['phone'] ?? ''));

            $stmt = $this->pdo->prepare('
                INSERT INTO tickets (
                    player_id,
                    round_id,
                    purchase_session_id,
                    promotion_id,
                    ticket_code,
                    round_ticket_number,
                    user_name,
                    phone,
                    total_amount,
                    currency,
                    country_code,
                    ip_address,
                    voucher_path,
                    status,
                    points,
                    paid_at,
                    created_at,
                    updated_at
                )
                VALUES (
                    :player_id,
                    :round_id,
                    :purchase_session_id,
                    :promotion_id,
                    :ticket_code,
                    :round_ticket_number,
                    :user_name,
                    :phone,
                    :total_amount,
                    :currency,
                    :country_code,
                    :ip_address,
                    :voucher_path,
                    "PENDING",
                    0,
                    NULL,
                    NOW(),
                    NOW()
                )
            ');

            $stmt->execute([
                ':player_id' => $playerId,
                ':round_id' => $roundId,
                ':purchase_session_id' => $purchaseSessionId,
                ':promotion_id' => $data['promotion_id'] ?? null,
                ':ticket_code' => $ticketCode,
                ':round_ticket_number' => $roundTicketNumber,
                ':user_name' => trim((string)($data['user_name'] ?? $data['full_name'] ?? $data['name'] ?? '')),
                ':phone' => $normalizedPhone,
                ':total_amount' => $grossAmount,
                ':currency' => $currency,
                ':country_code' => $data['country_code'] ?? 'US',
                ':ip_address' => $data['ip_address'] ?? $this->getClientIp(),
                ':voucher_path' => $data['voucher_path'] ?? null,
            ]);

            $ticketId = (int)$this->pdo->lastInsertId();

            $this->ticketItemModel->createMany($ticketId, $items);

            $this->purchaseSessionModel->markCompleted($purchaseSessionId);

            $this->pdo->commit();

            return $ticketId;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function getById(int $ticketId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                t.*,
                r.name AS round_name,
                r.custom_title,
                l.name AS league_name
            FROM tickets t
            INNER JOIN rounds r ON r.id = t.round_id
            INNER JOIN leagues l ON l.id = r.league_id
            WHERE t.id = :id
            LIMIT 1
        ');

        $stmt->execute([':id' => $ticketId]);

        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ticket ?: null;
    }

    public function getItemsByTicket(int $ticketId): array
    {
        $stmt = $this->pdo->prepare('
        SELECT
            ti.*,
            ti.selection AS pick,
            m.id AS match_id,
            m.kickoff_at,
            m.status AS match_status,
            m.home_score,
            m.away_score,
            m.result_outcome,
            ht.name AS home_team_name,
            at.name AS away_team_name,
            ht.logo_path AS home_team_logo,
            at.logo_path AS away_team_logo
        FROM ticket_items ti
        INNER JOIN matches m ON m.id = ti.match_id
        INNER JOIN teams ht ON ht.id = m.home_team_id
        INNER JOIN teams at ON at.id = m.away_team_id
        WHERE ti.ticket_id = :ticket_id
        ORDER BY m.kickoff_at ASC, m.id ASC
    ');

        $stmt->execute([
            ':ticket_id' => $ticketId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


    public function getTicketsByRound(int $roundId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                t.*,
                p.full_name,
                p.phone AS player_phone
            FROM tickets t
            INNER JOIN players p ON p.id = t.player_id
            WHERE t.round_id = :round_id
            ORDER BY t.created_at DESC
        ');

        $stmt->execute([':round_id' => $roundId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function markPaid(int $ticketId): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE tickets
            SET status = "PAID",
                paid_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ');

        return $stmt->execute([':id' => $ticketId]);
    }

    public function cancel(int $ticketId): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE tickets
            SET status = "CANCELLED",
                updated_at = NOW()
            WHERE id = :id
        ');

        return $stmt->execute([':id' => $ticketId]);
    }

    private function validateRoundIsOpen(int $roundId): void
    {
        $stmt = $this->pdo->prepare('
            SELECT id, status, open_at, close_at
            FROM rounds
            WHERE id = :id
            LIMIT 1
        ');

        $stmt->execute([':id' => $roundId]);
        $round = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$round) {
            throw new RuntimeException('La ronda no existe.');
        }

        if ($round['status'] !== 'OPEN') {
            throw new RuntimeException('La ronda no está abierta.');
        }

        $now = new \DateTimeImmutable();

        if (!empty($round['open_at']) && $now < new \DateTimeImmutable((string)$round['open_at'])) {
            throw new RuntimeException('La ronda aún no está disponible.');
        }

        if (!empty($round['close_at']) && $now > new \DateTimeImmutable((string)$round['close_at'])) {
            throw new RuntimeException('La ronda ya cerró.');
        }
    }

    private function getRound(int $roundId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM rounds
            WHERE id = :id
            LIMIT 1
        ');

        $stmt->execute([':id' => $roundId]);

        $round = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$round) {
            throw new RuntimeException('La ronda no existe.');
        }

        return $round;
    }

    private function getNextRoundTicketNumber(int $roundId): int
    {
        $stmt = $this->pdo->prepare('
            SELECT COALESCE(MAX(round_ticket_number), 0) + 1
            FROM tickets
            WHERE round_id = :round_id
            FOR UPDATE
        ');

        $stmt->execute([':round_id' => $roundId]);

        return (int)$stmt->fetchColumn();
    }

    private function generateTicketCode(int $roundId, int $number): string
    {
        return sprintf('QV-%04d-%05d', $roundId, $number);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    private function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function findByCode(string $ticketCode): ?array
    {
        $ticketCode = trim($ticketCode);

        if ($ticketCode === '') {
            return null;
        }

        $stmt = $this->pdo->prepare('
            SELECT
                t.*,
                r.name AS round_name,
                r.custom_title,
                r.round_number,
                r.status AS round_status,
                l.name AS league_name,
                l.slug AS league_slug,
                p.full_name AS player_name,
                p.phone AS player_phone,
                p.email AS player_email
            FROM tickets t
            INNER JOIN rounds r ON r.id = t.round_id
            INNER JOIN leagues l ON l.id = r.league_id
            LEFT JOIN players p ON p.id = t.player_id
            WHERE t.ticket_code = :ticket_code
            LIMIT 1
        ');

        $stmt->execute([
            ':ticket_code' => $ticketCode,
        ]);

        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ticket ?: null;
    }

 /**
 * Obtiene datos completos del verificador por código de ticket.
 *
 * @param string $ticketCode Código del ticket.
 * @return array{ticket:array<string,mixed>,items:array<int,array<string,mixed>>,rank:int|null}|null
 */
public function getVerifierDataByCode(string $ticketCode): ?array
{
    $ticket = $this->findByCode($ticketCode);

    if (!$ticket) {
        return null;
    }

    return $this->getVerifierDataByTicketId((int)$ticket['id']);
}

    /**
     * Busca tickets para el verificador público.
     *
     * Permite buscar por:
     * - código exacto de ticket
     * - teléfono normalizado
     * - nombre del jugador
     *
     * @param string $query Texto buscado.
     * @param string $type Tipo de búsqueda: auto, code, phone, name.
     * @return array<int,array<string,mixed>>
     */
    public function searchForVerifier(string $query, string $type = 'auto'): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $type = strtolower(trim($type));

        if (!in_array($type, ['auto', 'code', 'phone', 'name'], true)) {
            $type = 'auto';
        }

        $normalizedPhone = $this->normalizePhone($query);
        $likeQuery = '%' . $query . '%';
        $likePhone = '%' . $normalizedPhone . '%';

        $where = [];
        $params = [];

        if ($type === 'code') {
            $where[] = 't.ticket_code = :ticket_code';
            $params[':ticket_code'] = strtoupper($query);
        } elseif ($type === 'phone') {
            $where[] = 't.phone LIKE :phone';
            $params[':phone'] = $likePhone;
        } elseif ($type === 'name') {
            $where[] = '(t.user_name LIKE :user_name OR p.full_name LIKE :player_name)';
            $params[':user_name'] = $likeQuery;
            $params[':player_name'] = $likeQuery;
        } else {
            /*
         * Modo automático:
         * Busca por código, teléfono o nombre sin exigir que el usuario
         * conozca el tipo exacto de dato.
         */
            $where[] = '
            (
                t.ticket_code = :auto_ticket_code
                OR t.phone LIKE :auto_phone
                OR t.user_name LIKE :auto_user_name
                OR p.full_name LIKE :auto_player_name
            )
        ';

            $params[':auto_ticket_code'] = strtoupper($query);
            $params[':auto_phone'] = $likePhone;
            $params[':auto_user_name'] = $likeQuery;
            $params[':auto_player_name'] = $likeQuery;
        }

        $stmt = $this->pdo->prepare('
        SELECT
            t.id,
            t.ticket_code,
            t.round_ticket_number,
            t.user_name,
            t.phone,
            t.total_amount,
            t.currency,
            t.status,
            t.points,
            t.created_at,
            r.name AS round_name,
            r.round_number,
            r.status AS round_status,
            l.name AS league_name,
            p.full_name AS player_name
        FROM tickets t
        INNER JOIN rounds r
            ON r.id = t.round_id
        INNER JOIN leagues l
            ON l.id = r.league_id
        LEFT JOIN players p
            ON p.id = t.player_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY t.created_at DESC, t.id DESC
        LIMIT 12
    ');

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtiene datos completos del verificador por ID de ticket.
     *
     * @param int $ticketId ID del ticket.
     * @return array{ticket:array<string,mixed>,items:array<int,array<string,mixed>>,rank:int|null}|null
     */
    public function getVerifierDataByTicketId(int $ticketId): ?array
    {
        if ($ticketId <= 0) {
            return null;
        }

        $ticket = $this->getById($ticketId);

        if (!$ticket) {
            return null;
        }

        $items = $this->getItemsByTicket($ticketId);
        $rank = $this->calculateRoundRank((int)$ticket['round_id'], $ticketId);

        return [
            'ticket' => $ticket,
            'items' => $items,
            'rank' => $rank,
        ];
    }

    /**
     * Calcula posición del ticket dentro de su jornada.
     *
     * Regla simple:
     * - más puntos = mejor posición
     * - en empate, ticket más antiguo queda primero
     * - si sigue empate, ID menor queda primero
     *
     * @param int $roundId ID de jornada.
     * @param int $ticketId ID de ticket.
     * @return int|null
     */
    public function calculateRoundRank(int $roundId, int $ticketId): ?int
    {
        if ($roundId <= 0 || $ticketId <= 0) {
            return null;
        }

        $currentStmt = $this->pdo->prepare('
        SELECT id, points, created_at
        FROM tickets
        WHERE id = :ticket_id
          AND round_id = :round_id
        LIMIT 1
    ');

        $currentStmt->execute([
            ':ticket_id' => $ticketId,
            ':round_id' => $roundId,
        ]);

        $current = $currentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            return null;
        }

        $stmt = $this->pdo->prepare('
        SELECT COUNT(*) + 1
        FROM tickets t
        WHERE t.round_id = :rank_round_id
          AND (
                t.points > :current_points
                OR (
                    t.points = :same_points
                    AND t.created_at < :current_created_at
                )
                OR (
                    t.points = :same_points_id
                    AND t.created_at = :same_created_at
                    AND t.id < :current_id
                )
          )
    ');

        $stmt->execute([
            ':rank_round_id' => $roundId,
            ':current_points' => (int)$current['points'],
            ':same_points' => (int)$current['points'],
            ':current_created_at' => (string)$current['created_at'],
            ':same_points_id' => (int)$current['points'],
            ':same_created_at' => (string)$current['created_at'],
            ':current_id' => $ticketId,
        ]);

        return (int)$stmt->fetchColumn();
    }
}
