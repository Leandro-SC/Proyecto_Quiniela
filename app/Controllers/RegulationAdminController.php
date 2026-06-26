<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
// 👇 IMPORTANTE: Importar el modelo aquí también
use App\Models\RegulationModel;

class RegulationAdminController extends BaseAdminController
{
    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin(); // Protege la ruta
        
        $model = new RegulationModel();
        $regulation = $model->getCurrent();
        $content = $regulation ? $regulation['content'] : '';

        $this->render('admin/regulations/index', [
            'pageTitle' => 'Gestión de Reglamento',
            'content'   => $content
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();
        
        $model = new RegulationModel();
        $content = $_POST['content'] ?? '';
        
        $model->save($content);

        // Redirigir con éxito
        header('Location: /admin/regulations?saved=1');
        exit;
    }
}