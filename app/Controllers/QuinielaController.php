<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\RoundModel;
use App\Models\MatchModel;
use App\Models\LeagueModel;
use App\Models\PromotionModel;
use App\Models\TicketModel;
use App\Services\RankingService;
use Throwable;

class QuinielaController extends BaseController
{
    public function index(Request $request, Response $response): void
    {
        try {
            $leagueModel = new LeagueModel();
            $roundModel = new RoundModel();
            $matchModel = new MatchModel();

            $activeLeagues = $leagueModel->getAllActive();

            $selectedLeagueData = null;
            $leagueSlug = isset($_GET['league']) && $_GET['league'] !== ''
                ? trim((string)$_GET['league'])
                : null;

            if ($leagueSlug && $activeLeagues !== []) {
                foreach ($activeLeagues as $league) {
                    if (($league['slug'] ?? '') === $leagueSlug) {
                        $selectedLeagueData = $league;
                        break;
                    }
                }
            }

            if (!$selectedLeagueData && $leagueSlug && method_exists($leagueModel, 'findBySlug')) {
                $selectedLeagueData = $leagueModel->findBySlug($leagueSlug);

                if ($selectedLeagueData && (int)($selectedLeagueData['is_active'] ?? 0) !== 1) {
                    $selectedLeagueData = null;
                }
            }

            if (!$selectedLeagueData && $activeLeagues !== []) {
                $selectedLeagueData = $activeLeagues[0];
                $leagueSlug = (string)$selectedLeagueData['slug'];
            }

            if (!$selectedLeagueData) {
                $selectedLeagueData = [
                    'id' => 0,
                    'name' => 'Quiniela',
                    'slug' => 'default',
                    'image_background' => null,
                    'image_banner' => null,
                ];

                $leagueSlug = 'default';
            }

            $availableRounds = [];
            $currentRound = null;

            if (!empty($selectedLeagueData['id'])) {
                $availableRounds = $roundModel->getOpenRoundsByLeague((string)$leagueSlug);

                $requestedRoundId = isset($_GET['round_id']) ? (int)$_GET['round_id'] : 0;

                if ($requestedRoundId > 0) {
                    $requestedRound = $roundModel->findById($requestedRoundId);

                    if (
                        $requestedRound &&
                        (int)$requestedRound['league_id'] === (int)$selectedLeagueData['id']
                    ) {
                        $currentRound = $requestedRound;
                    }
                }

                if (!$currentRound) {
                    $currentRound = $roundModel->getCurrentRoundForLeagueSlug((string)$leagueSlug);
                }

                if (!$currentRound && $availableRounds !== []) {
                    $currentRound = $availableRounds[0];
                }
            }

            $matches = [];
            $ticketCost = 10.00;

            $geo = $_SESSION['geo'] ?? [];
            $geoCurrencyCode = $geo['currency_code'] ?? ($_SESSION['geo_currency'] ?? 'USD');
            $geoCountryName = $geo['country_name'] ?? ($_SESSION['geo_country_name'] ?? '');

            if ($currentRound) {
                $matches = $matchModel->getPublicMatchesByRound((int)$currentRound['id']);

                $ticketCost = $geoCurrencyCode === 'MXN'
                    ? (float)$currentRound['ticket_cost_mxn']
                    : (float)$currentRound['ticket_cost_usd'];
            }

            $ticketCostLabel = '$' . number_format($ticketCost, 2) . ' ' . $geoCurrencyCode;

            $whatsappPhone = $this->config['whatsapp']['phone'] ?? '';

            $deadlineIso = '';
            if ($currentRound && !empty($currentRound['close_at'])) {
                $deadlineIso = date('c', strtotime((string)$currentRound['close_at']));
            }

            $estimatedPrizes = [
                'first' => 0.0,
                'second' => 0.0,
            ];

            if ($currentRound && class_exists(RankingService::class)) {
                $summary = $this->getRankingSummaryCached((int)$currentRound['id']);

                $estimatedPrizes['first'] = (float)($summary['first_prize_total'] ?? 0.0);
                $estimatedPrizes['second'] = (float)($summary['second_prize_total'] ?? 0.0);
            }

            $activePromo = null;

            if (class_exists(PromotionModel::class)) {
                $promotionModel = new PromotionModel();
                $activePromo = $promotionModel->getActivePromo(null);
            }

            $metaDescription = 'Participa en la Quiniela ' .
                ($selectedLeagueData['name'] ?? 'Deportiva') .
                ' ' .
                ($currentRound['name'] ?? 'Actual') .
                '. Predice resultados y gana premios.';

            $this->render('home/index', [
                'pageTitle' => ($selectedLeagueData['name'] ?? 'Quiniela') . ' - ' . ($currentRound['name'] ?? ''),
                'metaDescription' => $metaDescription,
                'currentRound' => $currentRound,
                'availableRounds' => $availableRounds,
                'matches' => $matches,
                'ticketCost' => $ticketCost,
                'selectedLeague' => $leagueSlug,
                'activeLeagues' => $activeLeagues,
                'selectedLeagueData' => $selectedLeagueData,
                'geoCurrencyCode' => $geoCurrencyCode,
                'geoCountryName' => $geoCountryName,
                'ticketCostLabel' => $ticketCostLabel,
                'whatsappPhone' => $whatsappPhone,
                'deadlineIso' => $deadlineIso,
                'estimatedPrizes' => $estimatedPrizes,
                'activePromo' => $activePromo,
            ]);
        } catch (Throwable $e) {
            error_log('Error en QuinielaController@index: ' . $e->getMessage());

            echo 'Ocurrió un error inesperado. Por favor intenta más tarde.';
            exit;
        }
    }

    public function previous(Request $request, Response $response): void
    {
        $roundModel = new RoundModel();
        $matchModel = new MatchModel();
        $ticketModel = new TicketModel();

        $allRounds = $roundModel->getAllWithLeague();

        $matchdays = [];

        foreach ($allRounds as $round) {
            $status = strtoupper((string)($round['status'] ?? ''));

            if (in_array($status, ['CLOSED', 'FINISHED'], true)) {
                $round['label'] = mb_strtoupper(
                    ($round['league_name'] ?? 'LIGA') . ' - ' . ($round['name'] ?? 'JORNADA')
                );

                $matchdays[] = $round;
            }
        }

        $selectedRoundId = isset($_GET['round_id']) ? (int)$_GET['round_id'] : 0;

        if ($selectedRoundId <= 0) {
            $selectedRoundId = isset($_GET['round_id']) ? (int)$_GET['round_id'] : 0;
        }

        $search = trim((string)($_GET['q'] ?? ''));

        if ($selectedRoundId <= 0 && $matchdays !== []) {
            $selectedRoundId = (int)$matchdays[0]['id'];
        }

        $selectedRound = null;

        if ($selectedRoundId > 0) {
            foreach ($matchdays as $round) {
                if ((int)$round['id'] === $selectedRoundId) {
                    $selectedRound = $round;
                    break;
                }
            }
        }

        $tickets = [];
        $matches = [];
        $prizes = [
            'first' => 0.0,
            'second' => 0.0,
        ];

        $roundSummary = [
            'first_places' => 0,
            'second_places' => 0,
            'first_prize_total' => 0.0,
            'second_prize_total' => 0.0,
            'total_collected' => 0.0,
            'total_tickets' => 0,
        ];

        $geo = $_SESSION['geo'] ?? [];
        $currencyCode = $geo['currency_code'] ?? ($_SESSION['geo_currency'] ?? 'USD');

        if ($selectedRoundId > 0 && class_exists(RankingService::class)) {
            $rankingService = new RankingService();

            $summary = $rankingService->recomputeRound($selectedRoundId);

            $prizes['first'] = (float)($summary['first_prize_total'] ?? 0.0);
            $prizes['second'] = (float)($summary['second_prize_total'] ?? 0.0);

            $roundSummary['first_prize_total'] = $prizes['first'];
            $roundSummary['second_prize_total'] = $prizes['second'];
            $roundSummary['first_places'] = count($summary['first_winners'] ?? []);
            $roundSummary['second_places'] = count($summary['second_winners'] ?? []);
            $roundSummary['total_collected'] = (float)($summary['total_collected'] ?? 0.0);

            $tickets = $rankingService->getRoundRanking($selectedRoundId, 'PAID', $search !== '' ? $search : null);

            $matches = $matchModel->getByRound($selectedRoundId);

            $matches = array_values(array_filter($matches, function (array $match): bool {
                $status = strtoupper((string)($match['status'] ?? ''));

                return !in_array($status, ['CANCELLED', 'POSTPONED'], true);
            }));

            $tickets = $this->attachTicketPicksAndHits($tickets, $matches, $ticketModel);

            $roundSummary['total_tickets'] = count($tickets);
        }

        $metaDescription = 'Consulta los resultados históricos de la Jornada ' .
            ($selectedRound['name'] ?? '') .
            '. Revisa ganadores y marcadores.';

        $this->render('quiniela/previous', [
            'pageTitle' => 'Historial - ' . ($selectedRound['label'] ?? 'Anteriores'),
            'metaDescription' => $metaDescription,
            'matchdays' => $matchdays,
            'selectedRoundId' => $selectedRoundId,
            'selectedMatchdayId' => $selectedRoundId,
            'selectedRound' => $selectedRound,
            'tickets' => $tickets,
            'matches' => $matches,
            'prizes' => $prizes,
            'roundSummary' => $roundSummary,
            'searchQuery' => $search,
            'currencyCode' => $currencyCode,
            'league' => ['slug' => $selectedRound['league_slug'] ?? 'liga-mx'],
        ]);
    }

    public function ranking(Request $request, Response $response): void
    {
        $roundModel = new RoundModel();
        $leagueModel = new LeagueModel();
        $matchModel = new MatchModel();
        $ticketModel = new TicketModel();

        $activeLeagues = $leagueModel->getAllActive();

        $selectedLeagueData = null;
        $leagueSlug = isset($_GET['league']) && $_GET['league'] !== ''
            ? trim((string)$_GET['league'])
            : null;

        if ($leagueSlug && method_exists($leagueModel, 'findBySlug')) {
            $selectedLeagueData = $leagueModel->findBySlug($leagueSlug);
        }

        if (!$selectedLeagueData && $activeLeagues !== []) {
            $selectedLeagueData = $activeLeagues[0];
            $leagueSlug = (string)$selectedLeagueData['slug'];
        }

        $availableRounds = [];

        if ($selectedLeagueData && $leagueSlug) {
            $availableRounds = $roundModel->getRankingRounds((string)$leagueSlug);
        }

        $currentRound = null;

        if (isset($_GET['round_id']) && (int)$_GET['round_id'] > 0) {
            $requestedRound = $roundModel->findById((int)$_GET['round_id']);

            if (
                $requestedRound &&
                (!isset($selectedLeagueData['id']) ||
                    (int)$requestedRound['league_id'] === (int)$selectedLeagueData['id'])
            ) {
                $currentRound = $requestedRound;
            }
        }

        if (!$currentRound && $availableRounds !== []) {
            foreach ($availableRounds as $round) {
                if (strtoupper((string)$round['status']) === 'CLOSED') {
                    $currentRound = $round;
                    break;
                }
            }

            if (!$currentRound) {
                $currentRound = $availableRounds[0];
            }
        }

        $tickets = [];
        $matches = [];
        $roundId = $currentRound ? (int)$currentRound['id'] : 0;
        $updatedAt = date('H:i');

        $estimatedPrizes = [
            'first' => 0.0,
            'second' => 0.0,
        ];

        $geo = $_SESSION['geo'] ?? [];
        $currencyCode = $geo['currency_code'] ?? ($_SESSION['geo_currency'] ?? 'USD');

        if ($roundId > 0 && class_exists(RankingService::class)) {
            $rankingService = new RankingService();

            $summary = $this->getRankingSummaryCached($roundId);

            $estimatedPrizes['first'] = (float)($summary['first_prize_total'] ?? 0.0);
            $estimatedPrizes['second'] = (float)($summary['second_prize_total'] ?? 0.0);

            $tickets = $rankingService->getRoundRanking($roundId, 'PAID');

            $matches = $matchModel->getByRound($roundId);

            $matches = array_values(array_filter($matches, function (array $match): bool {
                $status = strtoupper((string)($match['status'] ?? ''));

                return !in_array($status, ['CANCELLED', 'POSTPONED'], true);
            }));

            $tickets = $this->attachTicketPicksAndHits($tickets, $matches, $ticketModel);
        }

        $totalPrimero = 0;
        $totalSegundo = 0;

        if ($tickets !== []) {
            $maxPoints = (int)($tickets[0]['points'] ?? 0);
            $secondPoints = null;

            foreach ($tickets as $ticket) {
                $points = (int)$ticket['points'];

                if ($points === $maxPoints) {
                    $totalPrimero++;
                    continue;
                }

                if ($secondPoints === null) {
                    $secondPoints = $points;
                }

                if ($points === $secondPoints) {
                    $totalSegundo++;
                }
            }
        }

        $metaDescription = 'Ranking en vivo Jornada ' .
            ($currentRound['name'] ?? '') .
            '. Sigue los resultados minuto a minuto.';

        $this->render('quiniela/ranking', [
            'pageTitle' => 'Ranking - ' . ($currentRound['name'] ?? 'General'),
            'metaDescription' => $metaDescription,
            'currentRound' => $currentRound,
            'availableRounds' => $availableRounds,
            'tickets' => $tickets,
            'matches' => $matches,
            'updatedAt' => $updatedAt,
            'leagueSlug' => $leagueSlug,
            'activeLeagues' => $activeLeagues,
            'selectedLeagueData' => $selectedLeagueData,
            'estimatedPrizes' => $estimatedPrizes,
            'currencyCode' => $currencyCode,
            'totalPrimero' => $totalPrimero,
            'totalSegundo' => $totalSegundo,
        ]);
    }

    private function attachTicketPicksAndHits(array $tickets, array $matches, TicketModel $ticketModel): array
    {
        foreach ($tickets as &$ticket) {
            $ticketId = (int)($ticket['id'] ?? 0);

            $picks = [];
            $hits = [];

            if ($ticketId > 0) {
                $items = $ticketModel->getItemsByTicket($ticketId);

                foreach ($items as $item) {
                    $matchId = (int)$item['match_id'];
                    $picks[$matchId] = (string)($item['selection'] ?? $item['pick'] ?? '');
                }
            }

            foreach ($matches as $match) {
                $matchId = (int)$match['id'];
                $result = (string)($match['result_outcome'] ?? '');
                $userPick = (string)($picks[$matchId] ?? '');

                $hits[$matchId] = $result !== '' && $userPick === $result;
            }

            $ticket['picks'] = $picks;
            $ticket['hits'] = $hits;
        }

        unset($ticket);

        return $tickets;
    }

    private function getRankingSummaryCached(int $roundId): array
    {
        if (!class_exists(RankingService::class)) {
            return [];
        }

        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $cacheFile = $cacheDir . '/ranking_summary_' . $roundId . '.json';

        if (is_file($cacheFile) && (time() - filemtime($cacheFile) < 60)) {
            $content = file_get_contents($cacheFile);

            if ($content !== false) {
                $data = json_decode($content, true);

                if (is_array($data)) {
                    return $data;
                }
            }
        }

        $rankingService = new RankingService();
        $data = $rankingService->recomputeRound($roundId);

        file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE));

        return $data;
    }
}