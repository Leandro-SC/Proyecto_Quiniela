<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class PromotionModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function getAll(): array
    {
        $sql = "
            SELECT
                p.*,
                p.image_path AS image,
                p.starts_at AS start_date,
                p.ends_at AS end_date,
                p.cta_url AS cta_link
            FROM promotions p
            ORDER BY p.display_order ASC, p.id DESC
        ";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): bool
    {
        $sql = "
            INSERT INTO promotions (
                title,
                description,
                image_path,
                discount_type,
                discount_value,
                cta_text,
                cta_url,
                starts_at,
                ends_at,
                is_active,
                display_order,
                created_at,
                updated_at
            )
            VALUES (
                :title,
                :description,
                :image_path,
                :discount_type,
                :discount_value,
                :cta_text,
                :cta_url,
                :starts_at,
                :ends_at,
                :is_active,
                :display_order,
                NOW(),
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':title' => trim((string)$data['title']),
            ':description' => $data['description'] ?? null,
            ':image_path' => $data['image'] ?? $data['image_path'] ?? null,
            ':discount_type' => $data['discount_type'] ?? 'NONE',
            ':discount_value' => (float)($data['discount_value'] ?? 0),
            ':cta_text' => $data['cta_text'] ?? null,
            ':cta_url' => $data['cta_link'] ?? $data['cta_url'] ?? null,
            ':starts_at' => $data['start_date'] ?? $data['starts_at'] ?? null,
            ':ends_at' => $data['end_date'] ?? $data['ends_at'] ?? null,
            ':is_active' => (int)($data['is_active'] ?? 1),
            ':display_order' => (int)($data['display_order'] ?? 0),
        ]);
    }

    public function update(array $data): bool
    {
        $fields = "
            title = :title,
            description = :description,
            discount_type = :discount_type,
            discount_value = :discount_value,
            cta_text = :cta_text,
            cta_url = :cta_url,
            starts_at = :starts_at,
            ends_at = :ends_at,
            is_active = :is_active,
            display_order = :display_order,
            updated_at = NOW()
        ";

        $params = [
            ':title' => trim((string)$data['title']),
            ':description' => $data['description'] ?? null,
            ':discount_type' => $data['discount_type'] ?? 'NONE',
            ':discount_value' => (float)($data['discount_value'] ?? 0),
            ':cta_text' => $data['cta_text'] ?? null,
            ':cta_url' => $data['cta_link'] ?? $data['cta_url'] ?? null,
            ':starts_at' => $data['start_date'] ?? $data['starts_at'] ?? null,
            ':ends_at' => $data['end_date'] ?? $data['ends_at'] ?? null,
            ':is_active' => (int)($data['is_active'] ?? 1),
            ':display_order' => (int)($data['display_order'] ?? 0),
            ':id' => (int)$data['id'],
        ];

        if (!empty($data['image']) || !empty($data['image_path'])) {
            $fields .= ", image_path = :image_path";
            $params[':image_path'] = $data['image'] ?? $data['image_path'];
        }

        $sql = "UPDATE promotions SET {$fields} WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM promotions
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
        ]);
    }

    public function getActivePromo(?int $countryId = null): ?array
    {
        $sql = "
            SELECT
                p.*,
                p.image_path AS image,
                p.starts_at AS start_date,
                p.ends_at AS end_date,
                p.cta_url AS cta_link
            FROM promotions p
            WHERE p.is_active = 1
              AND (p.starts_at IS NULL OR p.starts_at <= NOW())
              AND (p.ends_at IS NULL OR p.ends_at >= NOW())
            ORDER BY p.display_order ASC, p.id DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}