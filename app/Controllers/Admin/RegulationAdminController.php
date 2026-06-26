<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\RegulationModel;

class RegulationAdminController extends BaseAdminController
{
    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $model = new RegulationModel();
        $regulation = $model->getCurrent();

        $content = $regulation ? (string)($regulation['content'] ?? '') : '';

        $this->render('admin/regulations/index', [
            'pageTitle' => 'Gestión de Reglamento',
            'content' => $content,
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $content = (string)($_POST['content'] ?? '');

        $model = new RegulationModel();
        $model->save($content);

        header('Location: /admin/regulations?saved=1');
        exit;
    }
}