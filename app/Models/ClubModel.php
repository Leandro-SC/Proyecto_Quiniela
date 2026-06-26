<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ClubModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Obtener todos los clubes para el selector.
     */
    public function getAllWithCountry(): array
    {
        $sql = 'SELECT 
                    c.id, 
                    c.name, 
                    c.badge_path, 
                    co.name AS country_name 
                FROM clubs c
                LEFT JOIN countries co ON co.id = c.country_id
                ORDER BY c.name ASC';
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Sincronizar Club:
     * Busca un club por nombre. Si existe, actualiza su logo (si se envió uno nuevo).
     * Si no existe, lo crea en la base de datos.
     */
public function sync(string $name, ?string $badgePath, int $countryId = 0): int
    {
        $name = trim($name);
        if ($name === '') return 0;

        // 1. Buscar si ya existe
        $stmt = $this->pdo->prepare("SELECT id, badge_path, country_id FROM clubs WHERE name = :name LIMIT 1");
        $stmt->execute([':name' => $name]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $id = (int)$existing['id'];
            $updates = [];
            $params = [':id' => $id];

            // Actualizar logo si es nuevo
            if ($badgePath && $badgePath !== $existing['badge_path']) {
                $updates[] = "badge_path = :badge";
                $params[':badge'] = $badgePath;
            }

            // Actualizar país si se proporcionó uno válido y es diferente
            if ($countryId > 0 && $countryId !== (int)$existing['country_id']) {
                $updates[] = "country_id = :cid";
                $params[':cid'] = $countryId;
            }

            if (!empty($updates)) {
                $sql = "UPDATE clubs SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :id";
                $this->pdo->prepare($sql)->execute($params);
            }
            return $id;
        }

        // 2. Si no existe, CREARLO.
        if ($countryId <= 0) {
            $countryId = $this->getDefaultCountryId();
        }

        $ins = $this->pdo->prepare("INSERT INTO clubs (country_id, name, badge_path, created_at, updated_at) VALUES (:cid, :name, :badge, NOW(), NOW())");
        $ins->execute([
            ':cid'   => $countryId,
            ':name'  => $name,
            ':badge' => $badgePath
        ]);

        return (int)$this->pdo->lastInsertId();
    }
    

    /**
     * Obtiene un ID de país por defecto para evitar error de FK.
     */
   private function getDefaultCountryId(): int
    {
        $stmt = $this->pdo->query("SELECT id FROM countries WHERE name LIKE '%Mex%' LIMIT 1");
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;

        $stmt = $this->pdo->query("SELECT id FROM countries LIMIT 1");
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;

        $this->pdo->exec("INSERT INTO countries (name, iso_code, created_at) VALUES ('Internacional', 'WW', NOW())");
        return (int)$this->pdo->lastInsertId();
    }
}