<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Administración de testimonios.
 *
 * Tabla real utilizada:
 * - testimonials.photo_path
 * - testimonials.name
 * - testimonials.country
 * - testimonials.comment
 * - testimonials.rating
 * - testimonials.status
 * - testimonials.display_order
 */
class TestimonialAdminController extends BaseAdminController
{
    private PDO $pdo;

    /**
     * Inicializa dependencias del controlador.
     *
     * @return void
     */
    private function boot(): void
    {
        if (!isset($this->pdo)) {
            $this->pdo = Database::getConnection();
        }
    }

    /**
     * Lista testimonios con búsqueda simple.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $q = Security::cleanText($_GET['q'] ?? '', 100);

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(name LIKE :q OR country LIKE :q OR comment LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = '
            SELECT
                id,
                photo_path,
                name,
                country,
                comment,
                rating,
                status,
                display_order,
                created_at,
                updated_at
            FROM testimonials
        ';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY display_order ASC, id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->render('admin/testimonials/index', [
            'pageTitle' => 'Admin · Testimonios',
            'testimonials' => $testimonials,
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    /**
     * Muestra formulario de creación.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function create(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $this->render('admin/testimonials/form', [
            'pageTitle' => 'Crear testimonio',
            'testimonial' => null,
            'error' => null,
        ]);
    }

    /**
     * Guarda un nuevo testimonio.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();
        $this->boot();

        try {
            $data = $this->sanitizeData($_POST);
            $photoPath = $this->uploadPhoto('photo_path');

            if ($photoPath !== null) {
                $data['photo_path'] = $photoPath;
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO testimonials
                (
                    photo_path,
                    name,
                    country,
                    comment,
                    rating,
                    status,
                    display_order,
                    created_at,
                    updated_at
                )
                VALUES
                (
                    :photo_path,
                    :name,
                    :country,
                    :comment,
                    :rating,
                    :status,
                    :display_order,
                    NOW(),
                    NOW()
                )
            ');

            $stmt->execute([
                ':photo_path' => $data['photo_path'] ?? null,
                ':name' => $data['name'],
                ':country' => $data['country'],
                ':comment' => $data['comment'],
                ':rating' => $data['rating'],
                ':status' => $data['status'],
                ':display_order' => $data['display_order'],
            ]);

            header('Location: /admin/testimonials');
            exit;
        } catch (Throwable $e) {
            error_log('Error creando testimonio: ' . $e->getMessage());

            $this->render('admin/testimonials/form', [
                'pageTitle' => 'Crear testimonio',
                'testimonial' => $_POST,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Muestra formulario de edición.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
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

        if ($testimonial === null) {
            header('Location: /admin/testimonials');
            exit;
        }

        $this->render('admin/testimonials/form', [
            'pageTitle' => 'Editar testimonio',
            'testimonial' => $testimonial,
            'error' => null,
        ]);
    }

    /**
     * Actualiza un testimonio.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();
        $this->boot();

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/testimonials');
            exit;
        }

        try {
            $existing = $this->findById($id);

            if ($existing === null) {
                throw new RuntimeException('El testimonio no existe.');
            }

            $data = $this->sanitizeData($_POST);
            $photoPath = $this->uploadPhoto('photo_path');

            if ($photoPath === null) {
                $photoPath = (string)($existing['photo_path'] ?? '');
            }

            $stmt = $this->pdo->prepare('
                UPDATE testimonials
                SET
                    photo_path = :photo_path,
                    name = :name,
                    country = :country,
                    comment = :comment,
                    rating = :rating,
                    status = :status,
                    display_order = :display_order,
                    updated_at = NOW()
                WHERE id = :id
                LIMIT 1
            ');

            $stmt->execute([
                ':id' => $id,
                ':photo_path' => $photoPath !== '' ? $photoPath : null,
                ':name' => $data['name'],
                ':country' => $data['country'],
                ':comment' => $data['comment'],
                ':rating' => $data['rating'],
                ':status' => $data['status'],
                ':display_order' => $data['display_order'],
            ]);

            header('Location: /admin/testimonials');
            exit;
        } catch (Throwable $e) {
            error_log('Error actualizando testimonio: ' . $e->getMessage());

            $testimonial = $_POST;
            $testimonial['id'] = $id;

            $this->render('admin/testimonials/form', [
                'pageTitle' => 'Editar testimonio',
                'testimonial' => $testimonial,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Elimina un testimonio.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();
        $this->boot();

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            try {
                $stmt = $this->pdo->prepare('
                    DELETE FROM testimonials
                    WHERE id = :id
                    LIMIT 1
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

    /**
     * Busca un testimonio por ID.
     *
     * @param int $id
     * @return array<string,mixed>|null
     */
    private function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                id,
                photo_path,
                name,
                country,
                comment,
                rating,
                status,
                display_order,
                created_at,
                updated_at
            FROM testimonials
            WHERE id = :id
            LIMIT 1
        ');

        $stmt->execute([
            ':id' => $id,
        ]);

        $testimonial = $stmt->fetch(PDO::FETCH_ASSOC);

        return $testimonial !== false ? $testimonial : null;
    }

    /**
     * Sanitiza y valida datos de formulario.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private function sanitizeData(array $input): array
    {
        $name = Security::cleanText($input['name'] ?? '', 191);
        $country = Security::cleanText($input['country'] ?? '', 100);
        $comment = trim((string)($input['comment'] ?? ''));
        $rating = (int)($input['rating'] ?? 5);
        $status = strtoupper(Security::cleanText($input['status'] ?? 'ACTIVE', 20));
        $displayOrder = (int)($input['display_order'] ?? 0);

        if ($name === '') {
            throw new RuntimeException('El nombre es obligatorio.');
        }

        if ($comment === '') {
            throw new RuntimeException('El comentario es obligatorio.');
        }

        if (mb_strlen($comment) > 1200) {
            throw new RuntimeException('El comentario no debe superar los 1200 caracteres.');
        }

        if ($rating < 1) {
            $rating = 1;
        }

        if ($rating > 5) {
            $rating = 5;
        }

        if (!in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $status = 'ACTIVE';
        }

        if ($displayOrder < 0) {
            $displayOrder = 0;
        }

        return [
            'name' => $name,
            'country' => $country !== '' ? $country : null,
            'comment' => $comment,
            'rating' => $rating,
            'status' => $status,
            'display_order' => $displayOrder,
        ];
    }

    /**
     * Sube foto de testimonio de forma segura.
     *
     * Solo permite imágenes reales JPEG, PNG o WEBP.
     *
     * @param string $inputName Nombre del input file.
     * @return string|null Ruta pública de la imagen.
     */
    private function uploadPhoto(string $inputName): ?string
    {
        if (
            !isset($_FILES[$inputName]) ||
            !isset($_FILES[$inputName]['error']) ||
            $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }

        if ($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir la foto.');
        }

        $tmpName = (string)($_FILES[$inputName]['tmp_name'] ?? '');
        $size = (int)($_FILES[$inputName]['size'] ?? 0);

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Archivo de imagen inválido.');
        }

        if ($size <= 0 || $size > 2 * 1024 * 1024) {
            throw new RuntimeException('La foto no debe superar los 2 MB.');
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

        $uploadDir = dirname(__DIR__, 3) . '/assets/uploads/testimonials/';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new RuntimeException('No se pudo crear el directorio de testimonios.');
        }

        $extension = $allowedMimeTypes[$mimeType];
        $fileName = 'testimonial_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('No se pudo guardar la foto.');
        }

        return '/assets/uploads/testimonials/' . $fileName;
    }
}