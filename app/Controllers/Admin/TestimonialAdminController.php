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

class TestimonialAdminController extends BaseAdminController
{
    private PDO $pdo;
    private array $columns = [];

    private function boot(): void
    {
        if (!isset($this->pdo)) {
            $this->pdo = Database::getConnection();
        }

        if ($this->columns === []) {
            $this->columns = $this->getTableColumns('testimonials');
        }
    }

    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $q = trim((string)($_GET['q'] ?? ''));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(name LIKE :q OR content LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = '
            SELECT *
            FROM testimonials
        ';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if ($this->hasColumn('display_order')) {
            $sql .= ' ORDER BY display_order ASC, id DESC';
        } else {
            $sql .= ' ORDER BY id DESC';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($testimonials as &$testimonial) {
            $testimonial = $this->normalizeForView($testimonial);
        }
        unset($testimonial);

        $this->render('admin/testimonials/index', [
            'pageTitle' => 'Admin · Testimonios',
            'testimonials' => $testimonials,
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    public function create(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $this->render('admin/testimonials/form', [
            'pageTitle' => 'Crear testimonio',
            'testimonial' => null,
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        try {
            $data = $this->sanitizeData($_POST);

            $imagePath = $this->uploadImage('image_file')
                ?: $this->uploadImage('photo_file')
                ?: trim((string)($_POST['image_path'] ?? $_POST['photo'] ?? $_POST['avatar'] ?? ''));

            if ($imagePath !== '') {
                if ($this->hasColumn('image_path')) {
                    $data['image_path'] = $imagePath;
                } elseif ($this->hasColumn('photo')) {
                    $data['photo'] = $imagePath;
                } elseif ($this->hasColumn('avatar')) {
                    $data['avatar'] = $imagePath;
                }
            }

            $this->insertTestimonial($data);

            header('Location: /admin/testimonials');
            exit;
        } catch (Throwable $e) {
            error_log('Error creando testimonio: ' . $e->getMessage());

            $this->render('admin/testimonials/form', [
                'pageTitle' => 'Crear testimonio',
                'testimonial' => $this->normalizeForView($_POST),
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
            header('Location: /admin/testimonials');
            exit;
        }

        $testimonial = $this->findById($id);

        if (!$testimonial) {
            header('Location: /admin/testimonials');
            exit;
        }

        $this->render('admin/testimonials/form', [
            'pageTitle' => 'Editar testimonio',
            'testimonial' => $testimonial,
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/testimonials');
            exit;
        }

        try {
            $existing = $this->findById($id);

            if (!$existing) {
                throw new RuntimeException('El testimonio no existe.');
            }

            $data = $this->sanitizeData($_POST);

            $imagePath = $this->uploadImage('image_file')
                ?: $this->uploadImage('photo_file')
                ?: trim((string)($_POST['image_path'] ?? $_POST['photo'] ?? $_POST['avatar'] ?? ''));

            if ($imagePath === '') {
                $imagePath = (string)($existing['image_path'] ?? $existing['photo'] ?? $existing['avatar'] ?? '');
            }

            if ($imagePath !== '') {
                if ($this->hasColumn('image_path')) {
                    $data['image_path'] = $imagePath;
                } elseif ($this->hasColumn('photo')) {
                    $data['photo'] = $imagePath;
                } elseif ($this->hasColumn('avatar')) {
                    $data['avatar'] = $imagePath;
                }
            }

            $this->updateTestimonial($id, $data);

            header('Location: /admin/testimonials');
            exit;
        } catch (Throwable $e) {
            error_log('Error actualizando testimonio: ' . $e->getMessage());

            $testimonial = $this->normalizeForView($_POST);
            $testimonial['id'] = $id;

            $this->render('admin/testimonials/form', [
                'pageTitle' => 'Editar testimonio',
                'testimonial' => $testimonial,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            try {
                $stmt = $this->pdo->prepare('
                    DELETE FROM testimonials
                    WHERE id = :id
                ');

                $stmt->execute([
                    ':id' => $id,
                ]);
            } catch (Throwable $e) {
                error_log('Error eliminando testimonio: ' . $e->getMessage());
            }
        }

        header('Location: /admin/testimonials');
        exit;
    }

    private function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM testimonials
            WHERE id = :id
            LIMIT 1
        ');

        $stmt->execute([
            ':id' => $id,
        ]);

        $testimonial = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$testimonial) {
            return null;
        }

        return $this->normalizeForView($testimonial);
    }

    private function sanitizeData(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $role = trim((string)($input['role'] ?? $input['position'] ?? ''));
        $content = trim((string)($input['content'] ?? $input['message'] ?? $input['testimonial'] ?? ''));
        $rating = (int)($input['rating'] ?? 5);
        $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;
        $displayOrder = (int)($input['display_order'] ?? 0);

        if ($name === '') {
            throw new RuntimeException('El nombre es obligatorio.');
        }

        if ($content === '') {
            throw new RuntimeException('El contenido del testimonio es obligatorio.');
        }

        if ($rating < 1) {
            $rating = 1;
        }

        if ($rating > 5) {
            $rating = 5;
        }

        $data = [];

        if ($this->hasColumn('name')) {
            $data['name'] = $name;
        }

        if ($this->hasColumn('role')) {
            $data['role'] = $role !== '' ? $role : null;
        } elseif ($this->hasColumn('position')) {
            $data['position'] = $role !== '' ? $role : null;
        }

        if ($this->hasColumn('content')) {
            $data['content'] = $content;
        } elseif ($this->hasColumn('message')) {
            $data['message'] = $content;
        } elseif ($this->hasColumn('testimonial')) {
            $data['testimonial'] = $content;
        }

        if ($this->hasColumn('rating')) {
            $data['rating'] = $rating;
        }

        if ($this->hasColumn('is_active')) {
            $data['is_active'] = $isActive === 1 ? 1 : 0;
        }

        if ($this->hasColumn('display_order')) {
            $data['display_order'] = $displayOrder;
        }

        return $data;
    }

    private function insertTestimonial(array $data): void
    {
        if ($this->hasColumn('created_at')) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        if ($this->hasColumn('updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $data = $this->filterDataByColumns($data);

        $columns = array_keys($data);

        if ($columns === []) {
            throw new RuntimeException('No hay datos válidos para guardar.');
        }

        $placeholders = array_map(fn(string $column): string => ':' . $column, $columns);

        $sql = '
            INSERT INTO testimonials (' . implode(', ', $columns) . ')
            VALUES (' . implode(', ', $placeholders) . ')
        ';

        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }

        $stmt->execute();
    }

    private function updateTestimonial(int $id, array $data): void
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
            UPDATE testimonials
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

    private function normalizeForView(array $testimonial): array
    {
        $name = (string)($testimonial['name'] ?? '');
        $role = (string)($testimonial['role'] ?? $testimonial['position'] ?? '');
        $content = (string)($testimonial['content'] ?? $testimonial['message'] ?? $testimonial['testimonial'] ?? '');
        $image = (string)($testimonial['image_path'] ?? $testimonial['photo'] ?? $testimonial['avatar'] ?? '');

        $testimonial['name'] = $name;
        $testimonial['role'] = $role;
        $testimonial['position'] = $role;

        $testimonial['content'] = $content;
        $testimonial['message'] = $content;
        $testimonial['testimonial'] = $content;

        $testimonial['image_path'] = $image;
        $testimonial['photo'] = $image;
        $testimonial['avatar'] = $image;

        $testimonial['rating'] = isset($testimonial['rating']) ? (int)$testimonial['rating'] : 5;
        $testimonial['is_active'] = isset($testimonial['is_active']) ? (int)$testimonial['is_active'] : 1;
        $testimonial['display_order'] = isset($testimonial['display_order']) ? (int)$testimonial['display_order'] : 0;

        return $testimonial;
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
        return in_array($column, $this->columns, true);
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

        $uploadDir = dirname(__DIR__, 3) . '/assets/uploads/testimonials/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalName = basename((string)$_FILES[$inputName]['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)) {
            throw new RuntimeException('Formato de imagen no permitido.');
        }

        $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName) ?: 'testimonial.' . $extension;
        $fileName = 'testimonial_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . $cleanName;

        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file((string)$_FILES[$inputName]['tmp_name'], $targetPath)) {
            throw new RuntimeException('No se pudo subir la imagen.');
        }

        return '/assets/uploads/testimonials/' . $fileName;
    }
}