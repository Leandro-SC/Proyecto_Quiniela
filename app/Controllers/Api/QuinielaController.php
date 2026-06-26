<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\RoundModel;
use App\Models\MatchModel;
use App\Models\TicketModel;
use App\Models\PromotionModel;
use App\Services\RankingService;

class QuinielaController extends BaseController
{
    public function index(Request $request, Response $response): void
    {
        $defaultLeague = 'liga-mx';
        $leagueSlug = $_GET['league'] ?? $defaultLeague;

        $roundModel = new RoundModel();
        $matchModel = new MatchModel();
        $promoModel = new PromotionModel();

        $currentRound = $roundModel->getCurrentRoundForLeagueSlug($leagueSlug);
        $matches = $currentRound ? $matchModel->getPublicMatchesByRound((int)$currentRound['id']) : [];

        $ticketCost = $currentRound ? (float)$currentRound['ticket_cost_usd'] : 10.0;
        $activePromo = $promoModel->getActivePromo(null);

        $this->render('home/index', [
            'pageTitle' => 'Quiniela',
            'currentRound' => $currentRound,
            'matches' => $matches,
            'ticketCost' => $ticketCost,
            'activePromo' => $activePromo,
            'selectedLeague' => $leagueSlug,
            'geoCurrencyCode' => 'USD',
            'geoCountryName' => '',
            'ticketCostLabel' => '$' . number_format($ticketCost, 2) . ' USD',
            'whatsappPhone' => '',
        ]);
    }

    public function previous(Request $request, Response $response): void
    {
        $roundModel = new RoundModel();
        $matchModel = new MatchModel();
        $ticketModel = new TicketModel();

        $allRounds = $roundModel->getAllWithLeague();

        $matchdays = [];

        foreach ($allRounds as $round) {
            $status = strtoupper((string)$round['status']);

            if ($status === 'CLOSED' || $status === 'FINISHED') {
                $round['label'] = ($round['league_name'] ?? 'Liga') . ' - ' . ($round['name'] ?? 'Jornada');
                $matchdays[] = $round;
            }
        }

        $selectedRoundId = isset($_GET['matchday_id']) ? (int)$_GET['matchday_id'] : 0;

        if ($selectedRoundId === 0) {
            $selectedRoundId = isset($_GET['round_id']) ? (int)$_GET['round_id'] : 0;
        }

        $search = trim((string)($_GET['q'] ?? ''));

        if ($selectedRoundId <= 0 && $matchdays !== []) {
            $selectedRoundId = (int)$matchdays[0]['id'];
        }

        $selectedRound = null;
        $matches = [];
        $tickets = [];

        $roundSummary = [
            'first_places' => 0,
            'second_places' => 0,
            'first_prize_total' => 0,
            'second_prize_total' => 0,
            'total_collected' => 0,
            'total_tickets' => 0,
        ];

        if ($selectedRoundId > 0) {
            foreach ($matchdays as $round) {
                if ((int)$round['id'] === $selectedRoundId) {
                    $selectedRound = $round;
                    break;
                }
            }

            $matches = $matchModel->getByRound($selectedRoundId);

            if (class_exists(RankingService::class)) {
                $rankingService = new RankingService();
                $summaryData = $rankingService->recomputeRound($selectedRoundId);

                $roundSummary['first_prize_total'] = $summaryData['first_prize_total'] ?? 0;
                $roundSummary['second_prize_total'] = $summaryData['second_prize_total'] ?? 0;
                $roundSummary['first_places'] = count($summaryData['first_winners'] ?? []);
                $roundSummary['second_places'] = count($summaryData['second_winners'] ?? []);
                $roundSummary['total_collected'] = $summaryData['total_collected'] ?? 0;
            }

            $rawTickets = $ticketModel->getTicketsByRound($selectedRoundId);
            $roundSummary['total_tickets'] = count($rawTickets);

            foreach ($rawTickets as $ticket) {
                if ($search !== '') {
                    $haystack = strtolower(
                        (string)$ticket['ticket_code'] . ' ' .
                        (string)$ticket['user_name'] . ' ' .
                        (string)$ticket['phone']
                    );

                    if (!str_contains($haystack, strtolower($search))) {
                        continue;
                    }
                }

                $ticketItems = $ticketModel->getItemsByTicket((int)$ticket['id']);

                $picks = [];

                foreach ($ticketItems as $item) {
                    $matchId = (int)$item['match_id'];
                    $picks[$matchId] = (string)($item['selection'] ?? $item['pick'] ?? '');
                }

                $hits = [];

                foreach ($matches as $match) {
                    $matchId = (int)$match['id'];
                    $result = (string)($match['result_outcome'] ?? '');
                    $userPick = (string)($picks[$matchId] ?? '');

                    $hits[$matchId] = $result !== '' && $userPick === $result;
                }

                $tickets[] = [
                    'ticket_code' => $ticket['ticket_code'],
                    'user_name' => $ticket['user_name'],
                    'phone' => $ticket['phone'],
                    'points' => (int)$ticket['points'],
                    'picks' => $picks,
                    'hits' => $hits,
                ];
            }
        }

        $this->render('quiniela/previous', [
            'pageTitle' => 'Quinielas Anteriores',
            'matchdays' => $matchdays,
            'tickets' => $tickets,
            'selectedMatchdayId' => $selectedRoundId,
            'selectedRound' => $selectedRound,
            'matches' => $matches,
            'roundSummary' => $roundSummary,
            'searchQuery' => $search,
            'league' => ['slug' => 'liga-mx'],
        ]);
    }
}