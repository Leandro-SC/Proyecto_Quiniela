<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class PurchaseSessionModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function create(array $data): int
    {
        $grossAmount = (float)($data['gross_amount'] ?? 0);
        $discountAmount = (float)($data['discount_amount'] ?? 0);
        $netAmount = max(0, $grossAmount - $discountAmount);

        $stmt = $this->pdo->prepare('
            INSERT INTO purchase_sessions (
                player_id,
                promotion_id,
                session_token,
                gross_amount,
                discount_amount,
                net_amount,
                currency,
                status,
                expires_at,
                created_at,
                updated_at
            )
            VALUES (
                :player_id,
                :promotion_id,
                :session_token,
                :gross_amount,
                :discount_amount,
                :net_amount,
                :currency,
                "OPEN",
                DATE_ADD(NOW(), INTERVAL 30 MINUTE),
                NOW(),
                NOW()
            )
        ');

        $stmt->execute([
            ':player_id' => (int)$data['player_id'],
            ':promotion_id' => $data['promotion_id'] ?? null,
            ':session_token' => bin2hex(random_bytes(32)),
            ':gross_amount' => $grossAmount,
            ':discount_amount' => $discountAmount,
            ':net_amount' => $netAmount,
            ':currency' => $data['currency'] ?? 'USD',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function markCompleted(int $id): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE purchase_sessions
            SET status = "COMPLETED", updated_at = NOW()
            WHERE id = :id
        ');

        $stmt->execute([':id' => $id]);
    }
}