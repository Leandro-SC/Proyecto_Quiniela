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
        $this->boot();

        try {
            $data = $this->sanitizeLeagueData($_POST);

            $logoPath = $this->uploadImage('logo_file')
                ?: trim((string)($_POST['logo_path'] ?? $_POST['image_logo'] ?? ''));

            $bannerPath = $this->uploadImage('banner_file')
                ?: trim((string)($_POST['banner_path'] ?? $_POST['image_banner'] ?? $_POST['image_background'] ?? ''));

            $data = $this->applyImageFields($data, $logoPath, $bannerPath);

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

            $logoPath = $this->uploadImage('logo_file')
                ?: trim((string)($_POST['logo_path'] ?? $_POST['image_logo'] ?? ''));

            $bannerPath = $this->uploadImage('banner_file')
                ?: trim((string)($_POST['banner_path'] ?? $_POST['image_banner'] ?? $_POST['image_background'] ?? ''));

            if ($logoPath === '') {
                $logoPath = (string)($existing['logo_path'] ?? $existing['image_logo'] ?? '');
            }

            if ($bannerPath === '') {
                $bannerPath = (string)($existing['banner_path'] ?? $existing['image_banner'] ?? $existing['image_background'] ?? '');
            }

            $data = $this->applyImageFields($data, $logoPath, $bannerPath);

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

    private function sanitizeLeagueData(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $slug = trim((string)($input['slug'] ?? ''));
        $countryId = (int)($input['country_id'] ?? 0);
        $description = trim((string)($input['description'] ?? ''));
        $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;
        $displayOrder = (int)($input['display_order'] ?? 0);

        if ($name === '') {
            throw new RuntimeException('El nombre de la liga es obligatorio.');
        }

        if ($slug === '') {
            $slug = $this->slugify($name);
        } else {
            $slug = $this->slugify($slug);
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

        if ($this->hasColumn('is_active')) {
            $data['is_active'] = $isActive === 1 ? 1 : 0;
        }

        if ($this->hasColumn('display_order')) {
            $data['display_order'] = $displayOrder;
        }

        return $data;
    }

    private function applyImageFields(array $data, string $logoPath, string $bannerPath): array
    {
        if ($logoPath !== '') {
            if ($this->hasColumn('logo_path')) {
                $data['logo_path'] = $logoPath;
            } elseif ($this->hasColumn('image_logo')) {
                $data['image_logo'] = $logoPath;
            }
        }

        if ($bannerPath !== '') {
            if ($this->hasColumn('banner_path')) {
                $data['banner_path'] = $bannerPath;
            } elseif ($this->hasColumn('image_banner')) {
                $data['image_banner'] = $bannerPath;
            } elseif ($this->hasColumn('image_background')) {
                $data['image_background'] = $bannerPath;
            }
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

    private function normalizeLeagueForView(array $league): array
    {
        $logo = (string)($league['logo_path'] ?? $league['image_logo'] ?? '');
        $banner = (string)($league['banner_path'] ?? $league['image_banner'] ?? $league['image_background'] ?? '');

        $league['logo_path'] = $logo;
        $league['image_logo'] = $logo;

        $league['banner_path'] = $banner;
        $league['image_banner'] = $banner;
        $league['image_background'] = $banner;

        $league['is_active'] = isset($league['is_active']) ? (int)$league['is_active'] : 1;
        $league['display_order'] = isset($league['display_order']) ? (int)$league['display_order'] : 0;

        return $league;
    }

    private function getCountries(): array
    {
        $stmt = $this->pdo->query('
            SELECT id, name
            FROM countries
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

    private function uploadImage(string $inputName): ?string
    {
        if (
            !isset($_FILES[$inputName]) ||
            !isset($_FILES[$inputName]['error']) ||
            $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK
        ) {
            return null;
        }

        $uploadDir = dirname(__DIR__, 3) . '/assets/uploads/leagues/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalName = basename((string)$_FILES[$inputName]['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)) {
            throw new RuntimeException('Formato de imagen no permitido.');
        }

        $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName) ?: 'league.' . $extension;
        $fileName = 'league_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . $cleanName;

        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file((string)$_FILES[$inputName]['tmp_name'], $targetPath)) {
            throw new RuntimeException('No se pudo subir la imagen.');
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