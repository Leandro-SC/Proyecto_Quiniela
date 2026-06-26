<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Request;
use App\Core\Response;
use App\Services\RankingService;
use App\Models\RoundModel;

class RankingAdminController extends BaseAdminController
{
    private RankingService $rankingService;
    private RoundModel $roundModel;

    private function boot(): void
    {
        if (!isset($this->rankingService)) {
            $this->rankingService = new RankingService();
        }
        if (!isset($this->roundModel)) {
            $this->roundModel = new RoundModel();
        }
    }

    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        // 1. Obtener ID de la Jornada (Soporta matchday_id o round_id)
        $matchdayId = 0;
        if (isset($_GET['matchday_id']) && is_numeric($_GET['matchday_id'])) {
            $matchdayId = (int)$_GET['matchday_id'];
        } elseif (isset($_GET['round_id']) && is_numeric($_GET['round_id'])) {
            $matchdayId = (int)$_GET['round_id'];
        }

        // 2. Filtros
        // Si no viene definido en la URL, por defecto es 'PAID'.
        // Si viene 'ALL', se respeta 'ALL'.
        $status = isset($_GET['status']) && $_GET['status'] !== '' 
                  ? (string)$_GET['status'] 
                  : 'PAID';
                  
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

        // 3. Selector de Jornadas
        $allRounds = $this->roundModel->getAllWithLeague();
        
        // Si no hay jornada seleccionada, tomar la última disponible
        if ($matchdayId === 0 && !empty($allRounds)) {
            $matchdayId = (int)$allRounds[0]['id'];
        }

        $tickets = [];
        $summary = null;
        $winners1 = [];
        $winners2 = [];

        if ($matchdayId > 0) {
            // A. Recalcular premios
            $summary = $this->rankingService->recomputeRound($matchdayId);
            
            if ($summary) {
                $winners1 = $this->rankingService->getRoundWinners($matchdayId, 1);
                $winners2 = $this->rankingService->getRoundWinners($matchdayId, 2);
            }

            // B. Obtener tickets (Si $status es 'ALL', el servicio traerá todos)
            $tickets = $this->rankingService->getRoundRanking($matchdayId, $status, $q);
        }

        // 4. Renderizar vista
        $this->render('admin/ranking/index', [
            'pageTitle'          => 'Admin · Ranking',
            'matchdays'          => $allRounds,
            'selectedMatchdayId' => $matchdayId,
            'statusFilter'       => $status, // Pasamos el estado actual a la vista
            'searchQuery'        => $q,
            'roundRanking'       => $tickets,
            'roundSummary'       => $summary,
            'firstWinners'       => $winners1,
            'secondWinners'      => $winners2
        ]);
    }
}