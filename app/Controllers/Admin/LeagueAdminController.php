<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;
use RuntimeException;
use Throwable;

class LeagueAdminController extends BaseAdminController
{
    private PDO $pdo;
    private array $leagueColumns = [];

    private function boot(): void
    {
        if (!isset($this->pdo)) {
            $this->pdo = Database::getConnection();
        }

        if ($this->leagueColumns === []) {
            $this->leagueColumns = $this->getTableColumns('leagues');
        }
    }

    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $countryId = (int)($_GET['country_id'] ?? 0);
        $q = trim((string)($_GET['q'] ?? ''));

        $where = [];
        $params = [];

        if ($countryId > 0 && $this->hasColumn('country_id')) {
            $where[] = 'l.country_id = :country_id';
            $params[':country_id'] = $countryId;
        }

        if ($q !== '') {
            $where[] = '(l.name LIKE :q OR l.slug LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $joinCountry = $this->hasColumn('country_id')
            ? 'LEFT JOIN countries c ON c.id = l.country_id'
            : '';

        $countrySelect = $this->hasColumn('country_id')
            ? 'c.name AS country_name'
            : 'NULL AS country_name';

        $sql = "
            SELECT
                l.*,
                {$countrySelect}
            FROM leagues l
            {$joinCountry}
        ";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= $this->hasColumn('display_order')
            ? ' ORDER BY l.display_order ASC, l.name ASC'
            : ' ORDER BY l.name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $leagues = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($leagues as &$league) {
            $league = $this->normalizeLeagueForView($league);
        }
        unset($league);

        $this->render('admin/leagues/index', [
            'pageTitle' => 'Admin · Ligas',
            'leagues' => $leagues,
            'countries' => $this->getCountries(),
            'filters' => [
                'country_id' => $countryId,
                'q' => $q,
            ],
        ]);
    }

    public function create(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $this->render('admin/leagues/form', [
            'pageTitle' => 'Crear liga',
            'league' => null,
            'countries' => $this->getCountries(),
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();
        $this->boot();
        try {
            $data = $this->sanitizeLeagueData($_POST);
            $backgroundPath = $this->uploadImage('image_background_file')
                ?: trim((string)($_POST['image_background'] ?? ''));

            $bannerPath = $this->uploadImage('image_banner_file')
                ?: trim((string)($_POST['image_banner'] ?? ''));

            $data = $this->applyImageFields($data, $backgroundPath, $bannerPath);

            $this->insertLeague($data);

            header('Location: /admin/leagues');
            exit;
        } catch (Throwable $e) {
            error_log('Error creando liga: ' . $e->getMessage());

            $this->render('admin/leagues/form', [
                'pageTitle' => 'Crear liga',
                'league' => $this->normalizeLeagueForView($_POST),
                'countries' => $this->getCountries(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/leagues');
            exit;
        }

        $league = $this->findLeagueById($id);

        if (!$league) {
            header('Location: /admin/leagues');
            exit;
        }

        $this->render('admin/leagues/form', [
            'pageTitle' => 'Editar liga',
            'league' => $league,
            'countries' => $this->getCountries(),
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();
        $this->boot();

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/leagues');
            exit;
        }

        try {
            $existing = $this->findLeagueById($id);

            if (!$existing) {
                throw new RuntimeException('La liga no existe.');
            }

            $data = $this->sanitizeLeagueData($_POST);

            $backgroundPath = $this->uploadImage('image_background_file')
                ?: trim((string)($_POST['image_background'] ?? ''));

            $bannerPath = $this->uploadImage('image_banner_file')
                ?: trim((string)($_POST['image_banner'] ?? ''));

            if ($backgroundPath === '') {
                $backgroundPath = (string)($existing['image_background'] ?? '');
            }

            if ($bannerPath === '') {
                $bannerPath = (string)($existing['image_banner'] ?? '');
            }

            $data = $this->applyImageFields($data, $backgroundPath, $bannerPath);

            $this->updateLeague($id, $data);

            header('Location: /admin/leagues');
            exit;
        } catch (Throwable $e) {
            error_log('Error actualizando liga: ' . $e->getMessage());

            $league = $this->normalizeLeagueForView($_POST);
            $league['id'] = $id;

            $this->render('admin/leagues/form', [
                'pageTitle' => 'Editar liga',
                'league' => $league,
                'countries' => $this->getCountries(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();
        $this->boot();

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/leagues');
            exit;
        }

        try {
            $usedStmt = $this->pdo->prepare('
                SELECT
                    (
                        SELECT COUNT(*) FROM rounds WHERE league_id = :id
                    ) +
                    (
                        SELECT COUNT(*) FROM teams WHERE league_id = :id
                    ) +
                    (
                        SELECT COUNT(*) FROM matches WHERE league_id = :id
                    ) AS total_used
            ');

            $usedStmt->execute([
                ':id' => $id,
            ]);

            $isUsed = (int)$usedStmt->fetchColumn() > 0;

            if ($isUsed && $this->hasColumn('is_active')) {
                $stmt = $this->pdo->prepare('
                    UPDATE leagues
                    SET is_active = 0,
                        updated_at = NOW()
                    WHERE id = :id
                ');

                $stmt->execute([
                    ':id' => $id,
                ]);
            } elseif (!$isUsed) {
                $stmt = $this->pdo->prepare('
                    DELETE FROM leagues
                    WHERE id = :id
                ');

                $stmt->execute([
                    ':id' => $id,
                ]);
            }
        } catch (Throwable $e) {
            error_log('Error eliminando liga: ' . $e->getMessage());
        }

        header('Location: /admin/leagues');
        exit;
    }

    private function findLeagueById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM leagues
            WHERE id = :id
            LIMIT 1
        ');

        $stmt->execute([
            ':id' => $id,
        ]);

        $league = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$league) {
            return null;
        }

        return $this->normalizeLeagueForView($league);
    }

    /**
     * Sanitiza datos de liga según columnas reales disponibles.
     *
     * @param array<string,mixed> $input Datos recibidos por POST.
     * @return array<string,mixed>
     */
    private function sanitizeLeagueData(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $slug = trim((string)($input['slug'] ?? ''));
        $countryId = (int)($input['country_id'] ?? 0);
        $description = trim((string)($input['description'] ?? ''));
        $externalId = trim((string)($input['external_id'] ?? ''));
        $externalLeagueId = trim((string)($input['external_league_id'] ?? ''));
        $color = trim((string)($input['color'] ?? '#6c757d'));
        $isActive = isset($input['is_active']) ? 1 : 0;
        $displayOrder = (int)($input['display_order'] ?? 0);

        if ($name === '') {
            throw new RuntimeException('El nombre de la liga es obligatorio.');
        }

        if ($slug === '') {
            $slug = $this->slugify($name);
        } else {
            $slug = $this->slugify($slug);
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#6c757d';
        }

        if ($displayOrder < 0) {
            $displayOrder = 0;
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
        ];

        if ($this->hasColumn('country_id')) {
            $data['country_id'] = $countryId > 0 ? $countryId : null;
        }

        if ($this->hasColumn('description')) {
            $data['description'] = $description !== '' ? $description : null;
        }

        if ($this->hasColumn('external_id')) {
            $data['external_id'] = $externalId !== '' ? $externalId : null;
        }

        if ($this->hasColumn('external_league_id')) {
            $data['external_league_id'] = $externalLeagueId !== '' ? $externalLeagueId : null;
        }

        if ($this->hasColumn('color')) {
            $data['color'] = $color;
        }

        if ($this->hasColumn('is_active')) {
            $data['is_active'] = $isActive;
        }

        if ($this->hasColumn('display_order')) {
            $data['display_order'] = $displayOrder;
        }

        return $data;
    }

    /**
     * Aplica imágenes de liga según columnas reales.
     *
     * La tabla actual usa:
     * - image_background
     * - image_banner
     *
     * @param array<string,mixed> $data Datos base.
     * @param string $backgroundPath Ruta pública del fondo.
     * @param string $bannerPath Ruta pública del banner.
     * @return array<string,mixed>
     */
    private function applyImageFields(array $data, string $backgroundPath, string $bannerPath): array
    {
        if ($backgroundPath !== '' && $this->hasColumn('image_background')) {
            $data['image_background'] = $backgroundPath;
        }

        if ($bannerPath !== '' && $this->hasColumn('image_banner')) {
            $data['image_banner'] = $bannerPath;
        }

        return $data;
    }


    private function insertLeague(array $data): void
    {
        if ($this->hasColumn('created_at')) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        if ($this->hasColumn('updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $data = $this->filterDataByColumns($data);

        $columns = array_keys($data);
        $placeholders = array_map(fn(string $column): string => ':' . $column, $columns);

        $sql = '
            INSERT INTO leagues (' . implode(', ', $columns) . ')
            VALUES (' . implode(', ', $placeholders) . ')
        ';

        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }

        $stmt->execute();
    }

    private function updateLeague(int $id, array $data): void
    {
        if ($this->hasColumn('updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $data = $this->filterDataByColumns($data);

        $sets = [];

        foreach (array_keys($data) as $column) {
            $sets[] = $column . ' = :' . $column;
        }

        if ($sets === []) {
            return;
        }

        $sql = '
            UPDATE leagues
            SET ' . implode(', ', $sets) . '
            WHERE id = :id
        ';

        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

/**
 * Normaliza datos de liga para vistas.
 *
 * También corrige imágenes antiguas guardadas solo como nombre de archivo.
 *
 * @param array<string,mixed> $league
 * @return array<string,mixed>
 */
private function normalizeLeagueForView(array $league): array
{
    $league['image_background'] = $this->normalizeImagePath((string)($league['image_background'] ?? ''));
    $league['image_banner'] = $this->normalizeImagePath((string)($league['image_banner'] ?? ''));
    $league['external_id'] = (string)($league['external_id'] ?? '');
    $league['external_league_id'] = (string)($league['external_league_id'] ?? '');
    $league['color'] = (string)($league['color'] ?? '#6c757d');
    $league['is_active'] = isset($league['is_active']) ? (int)$league['is_active'] : 1;
    $league['display_order'] = isset($league['display_order']) ? (int)$league['display_order'] : 0;

    return $league;
}

/**
 * Normaliza ruta de imagen de liga.
 *
 * @param string $path
 * @return string
 */
private function normalizeImagePath(string $path): string
{
    $path = trim($path);

    if ($path === '') {
        return '';
    }

    if (str_starts_with($path, '/')) {
        return $path;
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    return '/assets/uploads/leagues/' . ltrim($path, '/');
}

    private function getCountries(): array
    {
        $stmt = $this->pdo->query('
    SELECT id, name, iso_code
    FROM countries
    WHERE is_active = 1
    ORDER BY name ASC
');

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getTableColumns(string $table): array
    {
        $stmt = $this->pdo->query('SHOW COLUMNS FROM ' . $table);

        $columns = [];

        foreach (($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []) as $column) {
            $columns[] = $column['Field'];
        }

        return $columns;
    }

    private function hasColumn(string $column): bool
    {
        return in_array($column, $this->leagueColumns, true);
    }

    private function filterDataByColumns(array $data): array
    {
        return array_filter(
            $data,
            fn(string $column): bool => $this->hasColumn($column),
            ARRAY_FILTER_USE_KEY
        );
    }

  /**
 * Sube imágenes de liga de forma segura.
 *
 * @param string $inputName Nombre del input file.
 * @return string|null Ruta pública de la imagen.
 */
private function uploadImage(string $inputName): ?string
{
    if (
        !isset($_FILES[$inputName]) ||
        !isset($_FILES[$inputName]['error']) ||
        $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if ($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la imagen.');
    }

    $tmpName = (string)($_FILES[$inputName]['tmp_name'] ?? '');
    $size = (int)($_FILES[$inputName]['size'] ?? 0);

    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Archivo de imagen inválido.');
    }

    if ($size <= 0 || $size > 3 * 1024 * 1024) {
        throw new RuntimeException('La imagen no debe superar los 3 MB.');
    }

    $mimeType = mime_content_type($tmpName);

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new RuntimeException('Formato no permitido. Usa JPG, PNG o WEBP.');
    }

    $uploadDir = dirname(__DIR__, 3) . '/assets/uploads/leagues/';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new RuntimeException('No se pudo crear el directorio de ligas.');
    }

    $extension = $allowedMimeTypes[$mimeType];
    $fileName = 'league_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }

    return '/assets/uploads/leagues/' . $fileName;
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

        return $text !== '' ? $text : 'liga-' . time();
    }
}
