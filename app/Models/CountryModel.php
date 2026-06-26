<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modelo para países (countries).
 */
class CountryModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Listar todos los países.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getAll(): array
    {
        $sql = 'SELECT * FROM countries ORDER BY name ASC';
        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar un país por ID.
     *
     * @return array<string,mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM countries WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Crear un país.
     */
    public function create(string $name, string $isoCode, ?string $flagPath, ?string $sportsdbName): int
    {
        $sql = 'INSERT INTO countries (name, iso_code, flag_path, sportsdb_country_name, created_at, updated_at)
                VALUES (:name, :iso, :flag, :sdname, NOW(), NOW())';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name'   => $name,
            ':iso'    => strtoupper($isoCode),
            ':flag'   => $flagPath,
            ':sdname' => $sportsdbName,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualizar un país.
     */
    public function update(
        int $id,
        string $name,
        string $isoCode,
        ?string $flagPath,
        ?string $sportsdbName
    ): void {
        $sql = 'UPDATE countries
                SET name = :name,
                    iso_code = :iso,
                    flag_path = :flag,
                    sportsdb_country_name = :sdname,
                    updated_at = NOW()
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id'     => $id,
            ':name'   => $name,
            ':iso'    => strtoupper($isoCode),
            ':flag'   => $flagPath,
            ':sdname' => $sportsdbName,
        ]);
    }

    /**
     * Obtener o crear un país a partir del nombre.
     * Útil para imports desde TheSportsDB.
     */
    public function getOrCreateByName(string $name): int
    {
        $name = trim($name);
        if ($name === '') {
            // Valor seguro por defecto: MX
            $name = 'México';
            $isoCode = 'MX';
        } else {
            $isoCode = strtoupper(substr($name, 0, 2));
        }

        // Primero buscamos por sportsdb_country_name o name
        $sql = 'SELECT id FROM countries
                WHERE sportsdb_country_name = :name
                   OR name = :name
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return (int)$row['id'];
        }

        // Crear registro mínimo
        return $this->create($name, $isoCode, null, $name);
    }
}
