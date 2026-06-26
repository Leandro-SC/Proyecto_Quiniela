<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

/**
 * Administración de países (tabla countries).
 */
class CountryAdminController extends BaseAdminController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $countries = $this->pdo->query(
            'SELECT * FROM countries ORDER BY name ASC'
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->render('admin/countries/index', [
            'pageTitle' => 'Países',
            'countries' => $countries,
        ]);
    }

    public function create(Request $request, Response $response): void
    {
        $this->requireAdmin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $name   = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
            $iso    = isset($_POST['iso_code']) ? trim((string)$_POST['iso_code']) : '';
            $sdname = isset($_POST['sportsdb_country_name']) ? trim((string)$_POST['sportsdb_country_name']) : '';

            if ($name === '' || $iso === '') {
                $_SESSION['flash_error'] = 'Nombre e ISO son obligatorios.';
                header('Location: /admin/countries/create');
                exit;
            }

            $flagPath = null;
            if (!empty($_FILES['flag']['tmp_name'])) {
                $flagPath = $this->storeFlagUpload($_FILES['flag']);
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO countries (name, iso_code, sportsdb_country_name, flag_path, created_at, updated_at)
                 VALUES (:name, :iso, :sdname, :flag, NOW(), NOW())'
            );
            $stmt->execute([
                ':name'   => $name,
                ':iso'    => $iso,
                ':sdname' => $sdname,
                ':flag'   => $flagPath,
            ]);

            $_SESSION['flash_success'] = 'País creado correctamente.';
            header('Location: /admin/countries');
            exit;
        }

        // CORRECCIÓN: Usamos 'form' en lugar de 'create'
        $this->render('admin/countries/form', [
            'pageTitle' => 'Crear país',
            'country'   => null 
        ]);
    }

    public function edit(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'País no especificado.';
            header('Location: /admin/countries');
            exit;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM countries WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $country = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$country) {
            $_SESSION['flash_error'] = 'País no encontrado.';
            header('Location: /admin/countries');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $name   = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
            $iso    = isset($_POST['iso_code']) ? trim((string)$_POST['iso_code']) : '';
            $sdname = isset($_POST['sportsdb_country_name']) ? trim((string)$_POST['sportsdb_country_name']) : '';

            if ($name === '' || $iso === '') {
                $_SESSION['flash_error'] = 'Nombre e ISO son obligatorios.';
                header('Location: /admin/countries/edit?id=' . $id);
                exit;
            }

            $flagPath = $country['flag_path'] ?? null;
            if (!empty($_FILES['flag']['tmp_name'])) {
                $flagPath = $this->storeFlagUpload($_FILES['flag']);
            }

            $stmt = $this->pdo->prepare(
                'UPDATE countries
                 SET name = :name,
                     iso_code = :iso,
                     sportsdb_country_name = :sdname,
                     flag_path = :flag,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':name'   => $name,
                ':iso'    => $iso,
                ':sdname' => $sdname,
                ':flag'   => $flagPath,
                ':id'     => $id,
            ]);

            $_SESSION['flash_success'] = 'País actualizado correctamente.';
            header('Location: /admin/countries');
            exit;
        }

        $this->render('admin/countries/form', [
            'pageTitle' => 'Editar país',
            'country'   => $country,
        ]);
    }

    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID inválido.';
            header('Location: /admin/countries');
            exit;
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM countries WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $_SESSION['flash_success'] = 'País eliminado correctamente.';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'No se puede eliminar el país (posiblemente tenga clubes asociados).';
        }

        header('Location: /admin/countries');
        exit;
    }

 private function storeFlagUpload(array $file): string
    {
        // CORRECCIÓN: Guardar en /assets/uploads/flags (SIN /public)
        $basePath = dirname(__DIR__, 3) . '/assets/uploads/flags';
        
        if (!is_dir($basePath)) {
            mkdir($basePath, 0775, true);
        }

        $ext = pathinfo($file['name'] ?? 'flag.png', PATHINFO_EXTENSION);
        $filename = 'flag_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $basePath . '/' . $filename;

        @move_uploaded_file($file['tmp_name'], $destPath);

        return '/assets/uploads/flags/' . $filename;
    }
}