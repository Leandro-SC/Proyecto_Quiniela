<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Request;
use App\Core\Response;
use App\Models\LeagueModel;
use App\Models\CountryModel; // Importante: Importar el modelo de países

class LeagueAdminController extends BaseAdminController
{
    private function uploadLeagueImage(array $file, string $prefix): ?string
    {
        if (isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $prefix . '_' . time() . '_' . rand(100,999) . '.' . $ext;
            $path = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/leagues/';
            
            if (!is_dir($path)) mkdir($path, 0755, true);
            
            if (move_uploaded_file($file['tmp_name'], $path . $filename)) {
                return $filename;
            }
        }
        return null;
    }

    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $model = new LeagueModel();
        $countryModel = new CountryModel(); // Instanciar modelo de países

        $leagues = $model->getAll();
        $countries = $countryModel->getAll(); // Obtener lista para el select
        
        $this->render('admin/leagues/index', [
            'pageTitle' => 'Gestión de Ligas',
            'leagues' => $leagues,
            'countries' => $countries // Pasar países a la vista
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $model = new LeagueModel();
        $countryModel = new CountryModel();
        
        $name = trim($_POST['name']);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        // Obtener el código de país basado en el ID enviado
        $countryId = (int)($_POST['country_id'] ?? 0);
        $country = $countryModel->find($countryId);
        $countryCode = $country ? $country['iso_code'] : 'MX'; // Default MX si falla

        // Subir imágenes
        $imgBg = $this->uploadLeagueImage($_FILES['image_background'] ?? [], 'bg');
        $imgBn = $this->uploadLeagueImage($_FILES['image_banner'] ?? [], 'banner');
        
        $data = [
            'name' => $name,
            'slug' => $slug,
            'country_code' => $countryCode, // Usar country_code
            'color' => $_POST['color'] ?? '#6c757d',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'external_id' => !empty($_POST['external_id']) ? $_POST['external_id'] : null,
            'image_background' => $imgBg,
            'image_banner' => $imgBn
        ];

        $model->create($data);
        header('Location: /admin/leagues');
        exit;
    }

    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $model = new LeagueModel();
        $countryModel = new CountryModel();
        
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        // Obtener código de país para actualizar
        $countryId = (int)($_POST['country_id'] ?? 0);
        $countryCode = null;
        if($countryId > 0) {
            $country = $countryModel->find($countryId);
            if($country) $countryCode = $country['iso_code'];
        }

        $imgBg = $this->uploadLeagueImage($_FILES['image_background'] ?? [], 'bg');
        $imgBn = $this->uploadLeagueImage($_FILES['image_banner'] ?? [], 'banner');
        
        $data = [
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'country_code' => $countryCode,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'external_id' => !empty($_POST['external_id']) ? $_POST['external_id'] : null,
            'image_background' => $imgBg,
            'image_banner' => $imgBn
        ];

        $model->update($data);
        header('Location: /admin/leagues');
        exit;
    }

    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        
        if($id > 0){
            $model = new LeagueModel();
            $model->delete($id);
        }
        header('Location: /admin/leagues');
        exit;
    }
}