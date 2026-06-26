<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\TicketModel;
use Throwable;

class VerifierController extends BaseController
{
    public function index(Request $request, Response $response): void
    {
        $ticketCode = trim((string)($_GET['code'] ?? $_GET['ticket_code'] ?? ''));
        $ticket = null;
        $items = [];
        $error = null;

        if ($ticketCode !== '') {
            try {
                $ticketModel = new TicketModel();
                $data = $ticketModel->getVerifierDataByCode($ticketCode);

                if (!$data) {
                    $error = 'No se encontró ningún ticket con ese código.';
                } else {
                    $ticket = $data['ticket'];
                    $items = $data['items'];
                }
            } catch (Throwable $e) {
                error_log('Error VerifierController@index: ' . $e->getMessage());
                $error = 'No se pudo verificar el ticket en este momento.';
            }
        }

        $this->render('verifier/index', [
            'pageTitle' => 'Verificador de tickets',
            'ticketCode' => $ticketCode,
            'ticket' => $ticket,
            'items' => $items,
            'error' => $error,
        ]);
    }
}