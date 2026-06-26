<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Request;
use App\Core\Response;
use App\Models\PromotionModel;
use App\Models\CountryModel; 
use Exception;

class PromotionAdminController extends BaseAdminController
{
   
private function uploadImage(array $file): ?string
    {
        // 1. Verificar si hay archivo y si no hubo error en la subida temporal
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null; // No se subió imagen nueva
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'promo_' . time() . '_' . rand(100,999) . '.' . $ext;

        // 2. DEFINIR RUTA ABSOLUTA (La solución segura)
        // $_SERVER['DOCUMENT_ROOT'] apunta a tu carpeta public_html automáticamente
        $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/';

        // 3. DIAGNÓSTICO DE CARPETA
        if (!is_dir($targetDir)) {
            // Intentar crearla
            if (!mkdir($targetDir, 0755, true)) {
                die("❌ ERROR CRÍTICO: No se pudo crear la carpeta.<br>Ruta intentada: <strong>" . $targetDir . "</strong><br>Verifique los permisos de 'public_html/assets'.");
            }
        }

        // 4. VERIFICAR ESCRITURA
        if (!is_writable($targetDir)) {
             die("❌ ERROR DE PERMISOS: La carpeta existe pero no se puede escribir en ella.<br>Ruta: <strong>" . $targetDir . "</strong><br>Solución: Cambie los permisos de la carpeta 'img' a 755 o 777 desde su administrador de archivos.");
        }

        // 5. MOVER ARCHIVO
        $targetFile = $targetDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return $filename; // ¡Éxito!
        } else {
            die("❌ ERROR AL MOVER: PHP no pudo mover el archivo temporal a la carpeta destino.<br>Origen: " . $file['tmp_name'] . "<br>Destino: " . $targetFile);
        }
    }
   
    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $model = new PromotionModel();
        $promotions = $model->getAll();

        $countries = [];
        if (class_exists('App\Models\CountryModel')) {
            $cModel = new CountryModel();
            if (method_exists($cModel, 'getAll')) {
                $countries = $cModel->getAll();
            }
        }

        $this->render('admin/promotions/index', [
            'pageTitle' => 'Promociones',
            'promotions' => $promotions,
            'countries' => $countries
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();
        
        try {
            $model = new PromotionModel();
            $image = $this->uploadImage($_FILES['image'] ?? []);

            $data = [
                'title'       => $_POST['title'] ?? 'Sin Título',
                'description' => $_POST['description'] ?? '',
                'image'       => $image,
                'country_id'  => (!empty($_POST['country_id']) && $_POST['country_id'] !== '0') ? $_POST['country_id'] : null,
                'start_date'  => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'end_date'    => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'cta_text'    => $_POST['cta_text'] ?? '',
                'cta_link'    => $_POST['cta_link'] ?? '',
                // CORRECCIÓN: Mapeamos el checkbox 'active' a la clave 'is_active'
                'is_active'   => isset($_POST['active']) ? 1 : 0
            ];

            $model->create($data);
            
        } catch (Exception $e) {
            error_log("Error guardando promo: " . $e->getMessage());
        }

        header('Location: /admin/promotions');
        exit;
    }

    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $model = new PromotionModel();
        $image = $this->uploadImage($_FILES['image'] ?? []);

        $data = [
            'id'          => $_POST['id'],
            'title'       => $_POST['title'],
            'description' => $_POST['description'],
            'image'       => $image,
            'country_id'  => (!empty($_POST['country_id']) && $_POST['country_id'] !== '0') ? $_POST['country_id'] : null,
            'start_date'  => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date'    => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'cta_text'    => $_POST['cta_text'],
            'cta_link'    => $_POST['cta_link'],
            // CORRECCIÓN: 'is_active'
            'is_active'   => isset($_POST['active']) ? 1 : 0
        ];

        $model->update($data);
        header('Location: /admin/promotions');
        exit;
    }

    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id > 0) {
            $model = new PromotionModel();
            $model->delete($id);
        }
        
        header('Location: /admin/promotions');
        exit;
    }
}