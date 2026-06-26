<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\TicketModel;
use App\Models\MatchModel;
use App\Services\RankingService;

class VerifierController extends BaseController
{
    public function index(Request $request, Response $response): void
    {
        $code = isset($_GET['ticket_code']) ? trim((string)$_GET['ticket_code']) : '';
        $ticket = null;
        $matches = [];
        $rank = null;
        $error = null;

        if ($code !== '') {
            $ticketModel = new TicketModel();
            $ticket = $ticketModel->findByCode($code);

            if ($ticket) {
                $roundId = (int)$ticket['matchday_id'];
                
                // 1. Obtener Partidos
                $matchModel = new MatchModel();
                if (method_exists($matchModel, 'getByRound')) {
                    $matches = $matchModel->getByRound($roundId);
                }

                // 2. Calcular Posición en el Ranking
                $rankingService = new RankingService();
                $ranking = $rankingService->getRoundRanking($roundId);
                
                // Buscar el ticket en el ranking
                foreach ($ranking as $r) {
                    if ((int)$r['id'] === (int)$ticket['id']) {
                        $rank = $r['rank']; // 'rank' lo genera el servicio getRoundRanking
                        break;
                    }
                }
            } else {
                $error = 'No encontramos ningún ticket con el código: ' . htmlspecialchars($code);
            }
        }

        $this->render('verifier/index', [
            'pageTitle'  => 'Verificador de Quiniela',
            'ticket'     => $ticket,
            'matches'    => $matches,
            'rank'       => $rank,
            'searchCode' => $code,
            'error'      => $error
        ]);
    }
}