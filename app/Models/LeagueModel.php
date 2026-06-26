<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class LeagueModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function getAll(): array
    {
        $sql = 'SELECT * FROM leagues ORDER BY id DESC';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAllActive(): array
    {
        $sql = 'SELECT * FROM leagues WHERE is_active = 1 ORDER BY name ASC';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = 'SELECT * FROM leagues WHERE slug = :slug LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM leagues WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): bool
    {
        // CORRECCIÓN: Se cambió 'country_id' por 'country_code' para coincidir con la BD
        $sql = "INSERT INTO leagues (name, slug, country_code, is_active, external_id, image_background, image_banner) 
                VALUES (:name, :slug, :cc, :active, :ext, :img_bg, :img_bn)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':name'   => $data['name'],
            ':slug'   => $data['slug'],
            ':cc'     => $data['country_code'] ?? 'MX', // Valor por defecto seguro
            ':active' => $data['is_active'] ?? 1,
            ':ext'    => $data['external_id'] ?? null,
            ':img_bg' => $data['image_background'] ?? null,
            ':img_bn' => $data['image_banner'] ?? null
        ]);
    }

    public function update(array $data): bool
    {
        // CORRECCIÓN: Se agrega actualización de country_code si viene en el array, si no, se mantiene
        $fields = "name = :name, slug = :slug, is_active = :active, external_id = :ext";
        $params = [
            ':name'   => $data['name'],
            ':slug'   => $data['slug'],
            ':active' => $data['is_active'],
            ':ext'    => $data['external_id'],
            ':id'     => $data['id']
        ];

        // Si se envía country_code, lo actualizamos
        if (!empty($data['country_code'])) {
            $fields .= ", country_code = :cc";
            $params[':cc'] = $data['country_code'];
        }

        if (!empty($data['image_background'])) {
            $fields .= ", image_background = :img_bg";
            $params[':img_bg'] = $data['image_background'];
        }
        if (!empty($data['image_banner'])) {
            $fields .= ", image_banner = :img_bn";
            $params[':img_bn'] = $data['image_banner'];
        }

        $sql = "UPDATE leagues SET $fields WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM leagues WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}