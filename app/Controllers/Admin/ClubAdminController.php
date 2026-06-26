<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

/**
 * Administración de clubes (tabla clubs).
 */
class ClubAdminController extends BaseAdminController
{
    private PDO $pdo;

    // CORRECCIÓN: El constructor no debe recibir argumentos ni llamar a parent::__construct
    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Listar todos los clubes
     */
    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $sql = 'SELECT c.*, co.name AS country_name 
                FROM clubs c
                LEFT JOIN countries co ON co.id = c.country_id
                ORDER BY c.name ASC';
        
        $clubs = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->render('admin/clubs/index', [
            'pageTitle' => 'Administrar Clubes',
            'clubs'     => $clubs
        ]);
    }

    /**
     * Crear un nuevo club
     */
    public function create(Request $request, Response $response): void
    {
        $this->requireAdmin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $data = [
                'country_id'         => (int)($_POST['country_id'] ?? 0),
                'name'               => trim((string)($_POST['name'] ?? '')),
                'short_name'         => trim((string)($_POST['short_name'] ?? '')),
                'sportsdb_team_id'   => trim((string)($_POST['sportsdb_team_id'] ?? '')),
                'sportsdb_league_id' => trim((string)($_POST['sportsdb_league_id'] ?? '')),
                'badge_path'         => null,
            ];

            if ($data['name'] === '' || $data['country_id'] <= 0) {
                $_SESSION['flash_error'] = 'Nombre y país son obligatorios.';
                header('Location: /admin/clubs/create');
                exit;
            }

            if (!empty($_FILES['badge']['tmp_name'])) {
                $data['badge_path'] = $this->storeBadgeUpload($_FILES['badge']);
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO clubs
                   (country_id, name, short_name, sportsdb_team_id, sportsdb_league_id, badge_path, created_at, updated_at)
                 VALUES
                   (:country_id, :name, :short_name, :team_id, :league_id, :badge, NOW(), NOW())'
            );
            $stmt->execute([
                ':country_id' => $data['country_id'],
                ':name'       => $data['name'],
                ':short_name' => $data['short_name'],
                ':team_id'    => $data['sportsdb_team_id'],
                ':league_id'  => $data['sportsdb_league_id'],
                ':badge'      => $data['badge_path'],
            ]);

            $_SESSION['flash_success'] = 'Club creado correctamente.';
            header('Location: /admin/clubs');
            exit;
        }

        $countries = $this->pdo->query('SELECT id, name FROM countries ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $leagues   = $this->pdo->query('SELECT name, external_id FROM leagues ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->render('admin/clubs/form', [
            'pageTitle' => 'Crear club',
            'countries' => $countries,
            'leagues'   => $leagues,
            'club'      => null 
        ]);
    }

    /**
     * Editar un club existente
     */
    public function edit(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID inválido.';
            header('Location: /admin/clubs');
            exit;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM clubs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $club = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$club) {
            $_SESSION['flash_error'] = 'Club no encontrado.';
            header('Location: /admin/clubs');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $data = [
                'country_id'         => (int)($_POST['country_id'] ?? 0),
                'name'               => trim((string)($_POST['name'] ?? '')),
                'short_name'         => trim((string)($_POST['short_name'] ?? '')),
                'sportsdb_team_id'   => trim((string)($_POST['sportsdb_team_id'] ?? '')),
                'sportsdb_league_id' => trim((string)($_POST['sportsdb_league_id'] ?? '')),
                'badge_path'         => $club['badge_path'] ?? null,
            ];

            if ($data['name'] === '' || $data['country_id'] <= 0) {
                $_SESSION['flash_error'] = 'Nombre y país son obligatorios.';
                header('Location: /admin/clubs/edit?id=' . $id);
                exit;
            }

            if (!empty($_FILES['badge']['tmp_name'])) {
                $data['badge_path'] = $this->storeBadgeUpload($_FILES['badge']);
            }

            $stmt = $this->pdo->prepare(
                'UPDATE clubs
                 SET country_id = :country_id,
                     name = :name,
                     short_name = :short_name,
                     sportsdb_team_id = :team_id,
                     sportsdb_league_id = :league_id,
                     badge_path = :badge,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':country_id' => $data['country_id'],
                ':name'       => $data['name'],
                ':short_name' => $data['short_name'],
                ':team_id'    => $data['sportsdb_team_id'],
                ':league_id'  => $data['sportsdb_league_id'],
                ':badge'      => $data['badge_path'],
                ':id'         => $id,
            ]);

            $_SESSION['flash_success'] = 'Club actualizado correctamente.';
            header('Location: /admin/clubs');
            exit;
        }

        $countries = $this->pdo->query('SELECT id, name FROM countries ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $leagues   = $this->pdo->query('SELECT name, external_id FROM leagues ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->render('admin/clubs/form', [
            'pageTitle' => 'Editar club',
            'club'      => $club,
            'countries' => $countries,
            'leagues'   => $leagues
        ]);
    }

    /**
     * Eliminar un club
     */
    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID inválido.';
            header('Location: /admin/clubs');
            exit;
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM clubs WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $_SESSION['flash_success'] = 'Club eliminado correctamente.';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'No se puede eliminar el club porque tiene partidos asociados.';
        }

        header('Location: /admin/clubs');
        exit;
    }

    /**
     * Búsqueda AJAX
     */
    public function searchAjax(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $term = $_GET['term'] ?? '';

        if (strlen($term) < 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'clubs' => []]);
            exit;
        }

        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.name, c.country_id, co.name AS country, c.badge_path AS badge
             FROM clubs c
             LEFT JOIN countries co ON co.id = c.country_id
             WHERE c.name LIKE :term OR co.name LIKE :term
             LIMIT 15'
        );
        
        $stmt->execute([':term' => '%' . $term . '%']);
        $clubs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'clubs' => $clubs]);
        exit;
    }
    
    

private function storeBadgeUpload(array $file): string
    {
        // CORRECCIÓN: Guardar en /assets/uploads/clubs (en la raíz, SIN /public)
        // Antes tenía: . '/public/assets/uploads/clubs';
        $basePath = dirname(__DIR__, 3) . '/assets/uploads/clubs';
        
        if (!is_dir($basePath)) {
            mkdir($basePath, 0775, true);
        }

        $ext = pathinfo($file['name'] ?? 'badge.png', PATHINFO_EXTENSION);
        $filename = 'club_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $basePath . '/' . $filename;

        @move_uploaded_file($file['tmp_name'], $destPath);

        // La ruta web sigue siendo la misma
        return '/assets/uploads/clubs/' . $filename;
    }
    
    
    
}