<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

class PlayerModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findByPhone(string $phone): ?array
    {
        $phone = $this->normalizePhone($phone);

        $stmt = $this->pdo->prepare('
            SELECT *
            FROM players
            WHERE phone = :phone
            LIMIT 1
        ');

        $stmt->execute([':phone' => $phone]);

        $player = $stmt->fetch(PDO::FETCH_ASSOC);

        return $player ?: null;
    }

    public function findOrCreate(array $data): int
    {
        $phone = $this->normalizePhone((string)($data['phone'] ?? ''));
        $fullName = trim((string)($data['full_name'] ?? $data['name'] ?? ''));

        if ($phone === '') {
            throw new RuntimeException('El teléfono del jugador es obligatorio.');
        }

        if ($fullName === '') {
            throw new RuntimeException('El nombre del jugador es obligatorio.');
        }

        $existing = $this->findByPhone($phone);

        if ($existing) {
            $stmt = $this->pdo->prepare('
                UPDATE players
                SET
                    full_name = :full_name,
                    country_code = :country_code,
                    email = :email,
                    last_ticket_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id
            ');

            $stmt->execute([
                ':id' => (int)$existing['id'],
                ':full_name' => $fullName,
                ':country_code' => $data['country_code'] ?? $existing['country_code'] ?? 'US',
                ':email' => $data['email'] ?? $existing['email'] ?? null,
            ]);

            return (int)$existing['id'];
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO players (
                full_name,
                phone,
                country_code,
                email,
                is_blocked,
                notes,
                last_ticket_at,
                created_at,
                updated_at
            )
            VALUES (
                :full_name,
                :phone,
                :country_code,
                :email,
                0,
                NULL,
                NOW(),
                NOW(),
                NOW()
            )
        ');

        $stmt->execute([
            ':full_name' => $fullName,
            ':phone' => $phone,
            ':country_code' => $data['country_code'] ?? 'US',
            ':email' => $data['email'] ?? null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}