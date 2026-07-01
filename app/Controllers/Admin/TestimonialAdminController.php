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
 * Tabla utilizada:
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
     * @param Request $request Petición HTTP.
     * @param Response $response Respuesta HTTP.
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
            /*
             * No reutilizamos el mismo placeholder varias veces porque
             * algunos drivers PDO fallan con placeholders duplicados.
             */
            $where[] = '
                (
                    name LIKE :q_name
                    OR country LIKE :q_country
                    OR comment LIKE :q_comment
                )
            ';

            $searchValue = '%' . $q . '%';

            $params[':q_name'] = $searchValue;
            $params[':q_country'] = $searchValue;
            $params[':q_comment'] = $searchValue;
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
     * @param Request $request Petición HTTP.
     * @param Response $response Respuesta HTTP.
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
     * @param Request $request Petición HTTP.
     * @param Response $response Respuesta HTTP.
     * @return void
     */
    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();
        $this->boot();

        try {
            $data = $this->sanitizeData($_POST);
            $photoPath = $this->uploadPhoto('photo_file');

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

            $_SESSION['flash_success'] = 'Testimonio creado correctamente.';

            header('Location: /admin/testimonials');
            exit;
        } catch (Throwable $e) {
            error_log('Error creando testimonio: ' . $e->getMessage());

            $testimonial = $_POST;
            $testimonial['status'] = isset($_POST['status']) ? 'ACTIVE' : 'INACTIVE';

            $this->render('admin/testimonials/form', [
                'pageTitle' => 'Crear testimonio',
                'testimonial' => $testimonial,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Muestra formulario de edición.
     *
     * @param Request $request Petición HTTP.
     * @param Response $response Respuesta HTTP.
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
            $_SESSION['flash_error'] = 'El testimonio no existe.';

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
     * @param Request $request Petición HTTP.
     * @param Response $response Respuesta HTTP.
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
            $photoPath = $this->uploadPhoto('photo_file');

            /*
             * Si no se sube una foto nueva, conservamos la imagen anterior.
             * Esto evita perder la foto al editar solo texto o estado.
             */
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

            $_SESSION['flash_success'] = 'Testimonio actualizado correctamente.';

            header('Location: /admin/testimonials');
            exit;
        } catch (Throwable $e) {
            error_log('Error actualizando testimonio: ' . $e->getMessage());

            $testimonial = $_POST;
            $testimonial['id'] = $id;
            $testimonial['status'] = isset($_POST['status']) ? 'ACTIVE' : 'INACTIVE';

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
     * @param Request $request Petición HTTP.
     * @param Response $response Respuesta HTTP.
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

                $_SESSION['flash_success'] = 'Testimonio eliminado correctamente.';
            } catch (Throwable $e) {
                error_log('Error eliminando testimonio: ' . $e->getMessage());
                $_SESSION['flash_error'] = 'No se pudo eliminar el testimonio.';
            }
        }

        header('Location: /admin/testimonials');
        exit;
    }

    /**
     * Busca un testimonio por ID.
     *
     * @param int $id ID del testimonio.
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
     * @param array<string,mixed> $input Datos recibidos por POST.
     * @return array<string,mixed>
     */
    private function sanitizeData(array $input): array
    {
        $name = Security::cleanText($input['name'] ?? '', 191);
        $country = Security::cleanText($input['country'] ?? '', 100);
        $comment = trim((string)($input['comment'] ?? ''));
        $rating = (int)($input['rating'] ?? 5);

        /*
         * La tabla real usa enum ACTIVE / INACTIVE.
         * El checkbox solo llega por POST cuando está marcado.
         */
        $status = isset($input['status']) ? 'ACTIVE' : 'INACTIVE';

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
     * Solo permite imágenes reales JPEG, PNG, WEBP o AVIF.
     *
     * @param string $inputName Nombre del input file.
     * @return string|null Ruta pública de la imagen.
     */
    private function uploadPhoto(string $inputName): ?string
    {
        if (
            !isset($_FILES[$inputName]) ||
            !is_array($_FILES[$inputName]) ||
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

        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            throw new RuntimeException('La foto no debe superar los 5 MB.');
        }

        $mimeType = $this->detectMimeType($tmpName);

        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
        ];

        if (!isset($allowedMimeTypes[$mimeType])) {
            throw new RuntimeException('Formato no permitido. Usa JPG, PNG, WEBP o AVIF.');
        }

        $uploadDir = dirname(__DIR__, 3) . '/assets/uploads/testimonials';

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                throw new RuntimeException('No se pudo crear el directorio de testimonios.');
            }
        }

        if (!is_writable($uploadDir)) {
            throw new RuntimeException('La carpeta assets/uploads/testimonials no tiene permisos de escritura.');
        }

        $extension = $allowedMimeTypes[$mimeType];
        $fileName = 'testimonial-' . date('Ymd-His') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('No se pudo guardar la foto.');
        }

        return '/assets/uploads/testimonials/' . $fileName;
    }

    /**
     * Detecta MIME real de un archivo.
     *
     * @param string $tmpName Archivo temporal.
     * @return string
     */
    private function detectMimeType(string $tmpName): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo) {
                $mimeType = (string)finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                return $mimeType;
            }
        }

        if (function_exists('mime_content_type')) {
            return (string)mime_content_type($tmpName);
        }

        return '';
    }
}