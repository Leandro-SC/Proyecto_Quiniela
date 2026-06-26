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
     * Obtener todos los equipos con país y liga.
     *
     * Mantiene alias "badge_path" para compatibilidad con vistas viejas.
     */
    public function getAllWithCountry(): array
    {
        $sql = '
            SELECT
                t.id,
                t.country_id,
                t.league_id,
                t.name,
                t.short_name,
                t.slug,
                t.logo_path,
                t.logo_path AS badge_path,
                t.is_active,
                c.name AS country_name,
                l.name AS league_name
            FROM teams t
            LEFT JOIN countries c ON c.id = t.country_id
            LEFT JOIN leagues l ON l.id = t.league_id
            WHERE t.is_active = 1
            ORDER BY t.name ASC
        ';

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtener equipos por liga.
     *
     * Útil para formularios de partidos.
     */
    public function getByLeague(int $leagueId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                t.id,
                t.country_id,
                t.league_id,
                t.name,
                t.short_name,
                t.slug,
                t.logo_path,
                t.logo_path AS badge_path,
                c.name AS country_name,
                l.name AS league_name
            FROM teams t
            LEFT JOIN countries c ON c.id = t.country_id
            LEFT JOIN leagues l ON l.id = t.league_id
            WHERE t.is_active = 1
              AND (t.league_id = :league_id OR t.league_id IS NULL)
            ORDER BY t.name ASC
        ');

        $stmt->execute([
            ':league_id' => $leagueId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar equipo por ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                t.*,
                t.logo_path AS badge_path,
                c.name AS country_name,
                l.name AS league_name
            FROM teams t
            LEFT JOIN countries c ON c.id = t.country_id
            LEFT JOIN leagues l ON l.id = t.league_id
            WHERE t.id = :id
            LIMIT 1
        ');

        $stmt->execute([
            ':id' => $id,
        ]);

        $team = $stmt->fetch(PDO::FETCH_ASSOC);

        return $team ?: null;
    }

    /**
     * Sincronizar equipo.
     *
     * Busca por nombre y liga. Si existe, actualiza logo/país.
     * Si no existe, lo crea.
     *
     * Mantiene el nombre sync() porque el código viejo ya lo puede estar usando.
     */
    public function sync(string $name, ?string $badgePath, int $countryId = 0, int $leagueId = 0): int
    {
        $name = trim($name);

        if ($name === '') {
            return 0;
        }

        if ($countryId <= 0) {
            $countryId = $this->getDefaultCountryId();
        }

        $existing = $this->findExistingTeam($name, $leagueId);

        if ($existing) {
            $id = (int)$existing['id'];

            $updates = [];
            $params = [
                ':id' => $id,
            ];

            if ($badgePath !== null && $badgePath !== '' && $badgePath !== (string)($existing['logo_path'] ?? '')) {
                $updates[] = 'logo_path = :logo_path';
                $params[':logo_path'] = $badgePath;
            }

            if ($countryId > 0 && $countryId !== (int)($existing['country_id'] ?? 0)) {
                $updates[] = 'country_id = :country_id';
                $params[':country_id'] = $countryId;
            }

            if ($leagueId > 0 && $leagueId !== (int)($existing['league_id'] ?? 0)) {
                $updates[] = 'league_id = :league_id';
                $params[':league_id'] = $leagueId;
            }

            if ($updates !== []) {
                $updates[] = 'updated_at = NOW()';

                $sql = '
                    UPDATE teams
                    SET ' . implode(', ', $updates) . '
                    WHERE id = :id
                ';

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }

            return $id;
        }

        $slug = $this->makeUniqueSlug($this->slugify($name));

        $stmt = $this->pdo->prepare('
            INSERT INTO teams (
                country_id,
                league_id,
                name,
                short_name,
                slug,
                logo_path,
                is_active,
                created_at,
                updated_at
            )
            VALUES (
                :country_id,
                :league_id,
                :name,
                :short_name,
                :slug,
                :logo_path,
                1,
                NOW(),
                NOW()
            )
        ');

        $stmt->execute([
            ':country_id' => $countryId > 0 ? $countryId : null,
            ':league_id' => $leagueId > 0 ? $leagueId : null,
            ':name' => $name,
            ':short_name' => $name,
            ':slug' => $slug,
            ':logo_path' => $badgePath ?: null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Crear equipo explícitamente.
     */
    public function create(array $data): int
    {
        $name = trim((string)($data['name'] ?? ''));

        if ($name === '') {
            return 0;
        }

        $countryId = (int)($data['country_id'] ?? 0);
        $leagueId = (int)($data['league_id'] ?? 0);
        $shortName = trim((string)($data['short_name'] ?? ''));

        if ($shortName === '') {
            $shortName = $name;
        }

        if ($countryId <= 0) {
            $countryId = $this->getDefaultCountryId();
        }

        $logoPath = trim((string)($data['logo_path'] ?? $data['badge_path'] ?? ''));
        $slug = trim((string)($data['slug'] ?? ''));

        if ($slug === '') {
            $slug = $this->makeUniqueSlug($this->slugify($name));
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO teams (
                country_id,
                league_id,
                name,
                short_name,
                slug,
                logo_path,
                is_active,
                created_at,
                updated_at
            )
            VALUES (
                :country_id,
                :league_id,
                :name,
                :short_name,
                :slug,
                :logo_path,
                :is_active,
                NOW(),
                NOW()
            )
        ');

        $stmt->execute([
            ':country_id' => $countryId > 0 ? $countryId : null,
            ':league_id' => $leagueId > 0 ? $leagueId : null,
            ':name' => $name,
            ':short_name' => $shortName,
            ':slug' => $slug,
            ':logo_path' => $logoPath !== '' ? $logoPath : null,
            ':is_active' => (int)($data['is_active'] ?? 1),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualizar equipo.
     */
    public function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $name = trim((string)($data['name'] ?? ''));

        if ($name === '') {
            return false;
        }

        $shortName = trim((string)($data['short_name'] ?? ''));

        if ($shortName === '') {
            $shortName = $name;
        }

        $logoPath = trim((string)($data['logo_path'] ?? $data['badge_path'] ?? ''));

        $stmt = $this->pdo->prepare('
            UPDATE teams
            SET
                country_id = :country_id,
                league_id = :league_id,
                name = :name,
                short_name = :short_name,
                slug = :slug,
                logo_path = :logo_path,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ');

        return $stmt->execute([
            ':id' => $id,
            ':country_id' => (int)($data['country_id'] ?? 0) > 0 ? (int)$data['country_id'] : null,
            ':league_id' => (int)($data['league_id'] ?? 0) > 0 ? (int)$data['league_id'] : null,
            ':name' => $name,
            ':short_name' => $shortName,
            ':slug' => trim((string)($data['slug'] ?? '')) !== ''
                ? $this->slugify((string)$data['slug'])
                : $this->slugify($name),
            ':logo_path' => $logoPath !== '' ? $logoPath : null,
            ':is_active' => (int)($data['is_active'] ?? 1),
        ]);
    }

    /**
     * Eliminar equipo.
     *
     * Si tiene partidos relacionados, se desactiva.
     */
    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->pdo->prepare('
            SELECT COUNT(*)
            FROM matches
            WHERE home_team_id = :id
               OR away_team_id = :id
        ');

        $stmt->execute([
            ':id' => $id,
        ]);

        $isUsed = (int)$stmt->fetchColumn() > 0;

        if ($isUsed) {
            $update = $this->pdo->prepare('
                UPDATE teams
                SET is_active = 0,
                    updated_at = NOW()
                WHERE id = :id
            ');

            return $update->execute([
                ':id' => $id,
            ]);
        }

        $delete = $this->pdo->prepare('
            DELETE FROM teams
            WHERE id = :id
        ');

        return $delete->execute([
            ':id' => $id,
        ]);
    }

    private function findExistingTeam(string $name, int $leagueId = 0): ?array
    {
        if ($leagueId > 0) {
            $stmt = $this->pdo->prepare('
                SELECT *
                FROM teams
                WHERE league_id = :league_id
                  AND LOWER(TRIM(name)) = LOWER(TRIM(:name))
                LIMIT 1
            ');

            $stmt->execute([
                ':league_id' => $leagueId,
                ':name' => $name,
            ]);
        } else {
            $stmt = $this->pdo->prepare('
                SELECT *
                FROM teams
                WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name))
                LIMIT 1
            ');

            $stmt->execute([
                ':name' => $name,
            ]);
        }

        $team = $stmt->fetch(PDO::FETCH_ASSOC);

        return $team ?: null;
    }

    private function getDefaultCountryId(): int
    {
        $stmt = $this->pdo->query("
            SELECT id
            FROM countries
            WHERE iso_code IN ('MX', 'MEX')
               OR name LIKE '%Mex%'
            ORDER BY id ASC
            LIMIT 1
        ");

        $id = $stmt ? $stmt->fetchColumn() : false;

        if ($id) {
            return (int)$id;
        }

        $stmt = $this->pdo->query('
            SELECT id
            FROM countries
            ORDER BY id ASC
            LIMIT 1
        ');

        $id = $stmt ? $stmt->fetchColumn() : false;

        if ($id) {
            return (int)$id;
        }

        $insert = $this->pdo->prepare('
            INSERT INTO countries (
                name,
                iso_code,
                phone_code,
                flag_emoji,
                is_active,
                created_at,
                updated_at
            )
            VALUES (
                "Internacional",
                "WW",
                NULL,
                NULL,
                1,
                NOW(),
                NOW()
            )
        ');

        $insert->execute();

        return (int)$this->pdo->lastInsertId();
    }

    private function makeUniqueSlug(string $baseSlug): string
    {
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'equipo';
        $slug = $baseSlug;
        $counter = 2;

        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*)
            FROM teams
            WHERE slug = :slug
        ');

        $stmt->execute([
            ':slug' => $slug,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function slugify(string $text): string
    {
        $text = trim($text);
        $text = strtolower($text);

        $replacements = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
            'ü' => 'u',
        ];

        $text = strtr($text, $replacements);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');

        return $text !== '' ? $text : 'equipo';
    }
}