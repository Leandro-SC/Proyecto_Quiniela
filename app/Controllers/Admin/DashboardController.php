<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\TicketModel;
use App\Models\RoundModel;

/**
 * Dashboard principal del módulo administrador.
 * Muestra resumen de tickets y recaudación.
 */
class DashboardController extends BaseAdminController
{
    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $from = isset($_GET['from']) ? (string)$_GET['from'] : null;
        $to   = isset($_GET['to'])   ? (string)$_GET['to']   : null;

        $ticketModel = new TicketModel();
        $roundModel  = new RoundModel();

        $stats         = $ticketModel->getDashboardStats($from, $to);
        $recentTickets = $ticketModel->getRecentTickets(10, $from, $to);
        $rounds        = $roundModel->getAllWithLeague();

        $this->render('admin/dashboard/index', [
            'pageTitle'     => 'Panel administrador · Villa Quiniela',
            'stats'         => $stats,
            'recentTickets' => $recentTickets,
            'rounds'        => $rounds,
            'filters'       => [
                'from' => $from,
                'to'   => $to,
            ],
        ]);
    }
}
