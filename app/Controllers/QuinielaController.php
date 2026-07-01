<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\LeagueModel;
use App\Models\MatchModel;
use App\Models\PromotionModel;
use App\Models\RoundModel;
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
                try {
                    $summary = $this->getRankingSummaryCached((int)$currentRound['id']);

                    $estimatedPrizes['first'] = (float)($summary['first_prize_total'] ?? 0.0);
                    $estimatedPrizes['second'] = (float)($summary['second_prize_total'] ?? 0.0);
                } catch (Throwable $e) {
                    error_log('Error calculando premios en home: ' . $e->getMessage());

                    $estimatedPrizes['first'] = 0.0;
                    $estimatedPrizes['second'] = 0.0;
                }
            }
            $activePromo = null;

            if (class_exists(PromotionModel::class)) {
                try {
                    $promotionModel = new PromotionModel();
                    $activePromo = $promotionModel->getActivePromo(null);
                } catch (Throwable $e) {
                    error_log('Error leyendo promoción activa en home: ' . $e->getMessage());
                    $activePromo = null;
                }
            }

            $metaDescription = 'Participa en la Quiniela ' .
                ($selectedLeagueData['name'] ?? 'Deportiva') .
                ' ' .
                ($currentRound['name'] ?? 'Actual') .
                '. Predice resultados y gana premios.';

            $publicSettings = $this->getPublicSettings();

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
                'publicSettings' => $publicSettings,
            ]);
        } catch (Throwable $e) {
            error_log('Error en QuinielaController@index: ' . $e->getMessage());

            echo 'Ocurrió un error inesperado. Por favor intenta más tarde.';
            exit;
        }
    }

    /**
     * Muestra histórico público de quinielas de forma guiada.
     *
     * Por defecto muestra cards de jornadas cerradas/finalizadas.
     * Solo muestra la tabla completa cuando el usuario selecciona una jornada.
     *
     * @param Request $request Petición HTTP.
     * @param Response $response Respuesta HTTP.
     * @return void
     */
    public function previous(Request $request, Response $response): void
    {
        $roundModel = new RoundModel();
        $matchModel = new MatchModel();
        $ticketModel = new TicketModel();

        $filters = $this->getPreviousHistoryFilters();

        $allRounds = $roundModel->getAllWithLeague();
        $historicalRounds = [];

        foreach ($allRounds as $round) {
            $status = strtoupper((string)($round['status'] ?? ''));

            /*
     * Ahora el archivo público puede mostrar:
     * - Jornadas abiertas
     * - Jornadas cerradas
     * - Jornadas finalizadas
     *
     * Esto permite al usuario revisar también quinielas activas desde los filtros.
     */
            if (!in_array($status, ['OPEN', 'CLOSED', 'FINISHED'], true)) {
                continue;
            }

            $round['label'] = mb_strtoupper(
                (string)($round['league_name'] ?? 'Liga') .
                    ' · ' .
                    (string)($round['name'] ?? 'Jornada')
            );

            $historicalRounds[] = $round;
        }

        /*
 * Ordenamos por fecha de creación de quiniela.
 * Esto hace que el histórico responda al tiempo en que se creó la jornada,
 * no al cierre ni a la fecha de actualización.
 */
        usort($historicalRounds, static function (array $a, array $b): int {
            $dateA = strtotime((string)($a['created_at'] ?? '')) ?: 0;
            $dateB = strtotime((string)($b['created_at'] ?? '')) ?: 0;

            return $dateB <=> $dateA;
        });

        $availableLeagues = $this->getAvailableHistoricalLeagues($historicalRounds);

        $filteredRounds = $this->filterHistoricalRounds($historicalRounds, $filters);

        /*
     * En modo reciente mostramos pocas jornadas para no saturar.
     * Si el usuario elige "Todas", mostramos más resultados.
     */
        $maxCards = $filters['period'] === 'all' ? 36 : 12;
        $filteredRounds = array_slice($filteredRounds, 0, $maxCards);

        $roundCards = [];

        foreach ($filteredRounds as $round) {
            $summary = $this->getHistoricalRoundCardSummary((int)$round['id']);

            $roundCards[] = array_merge($round, [
                'summary_total_tickets' => $summary['total_tickets'],
                'summary_total_matches' => $summary['total_matches'],
                'summary_total_collected' => $summary['total_collected'],
                'summary_winner_name' => $summary['winner_name'],
                'summary_winner_points' => $summary['winner_points'],
                'summary_currency' => $summary['currency'],
            ]);
        }

        $selectedRoundId = (int)($filters['round_id'] ?? 0);
        $selectedRound = null;

        if ($selectedRoundId > 0) {
            foreach ($historicalRounds as $round) {
                if ((int)$round['id'] === $selectedRoundId) {
                    $selectedRound = $round;
                    break;
                }
            }
        }

        $tickets = [];
        $matches = [];
        $winners = [];
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
            'total_matches' => 0,
            'finished_matches' => 0,
            'pending_matches' => 0,
        ];

        $geo = $_SESSION['geo'] ?? [];
        $currencyCode = $geo['currency_code'] ?? ($_SESSION['geo_currency'] ?? 'USD');

        if ($selectedRound && class_exists(RankingService::class)) {
            $roundId = (int)$selectedRound['id'];
            $rankingService = new RankingService();

            $summary = $rankingService->recomputeRound($roundId);

            $prizes['first'] = (float)($summary['first_prize_total'] ?? 0.0);
            $prizes['second'] = (float)($summary['second_prize_total'] ?? 0.0);

            $roundSummary['first_prize_total'] = $prizes['first'];
            $roundSummary['second_prize_total'] = $prizes['second'];
            $roundSummary['first_places'] = count($summary['first_winners'] ?? []);
            $roundSummary['second_places'] = count($summary['second_winners'] ?? []);
            $roundSummary['total_collected'] = (float)($summary['total_collected'] ?? 0.0);

            $tickets = $rankingService->getRoundRanking(
                $roundId,
                'PAID',
                $filters['q'] !== '' ? $filters['q'] : null
            );

            $matches = $matchModel->getByRound($roundId);

            $matches = array_values(array_filter($matches, function (array $match): bool {
                $status = strtoupper((string)($match['status'] ?? ''));

                return !in_array($status, ['CANCELLED', 'POSTPONED'], true);
            }));

            $finishedMatches = 0;

            foreach ($matches as $match) {
                if ((string)($match['result_outcome'] ?? '') !== '') {
                    $finishedMatches++;
                }
            }

            $tickets = $this->attachTicketPicksAndHits($tickets, $matches, $ticketModel);
            $winners = array_slice($tickets, 0, 3);

            $roundSummary['total_tickets'] = count($tickets);
            $roundSummary['total_matches'] = count($matches);
            $roundSummary['finished_matches'] = $finishedMatches;
            $roundSummary['pending_matches'] = max(0, count($matches) - $finishedMatches);
        }

        $metaDescription = 'Consulta jornadas anteriores, ganadores, premios y resultados de quinielas deportivas.';

        if ($selectedRound) {
            $metaDescription = 'Consulta resultados, ganadores y tabla histórica de ' .
                (string)($selectedRound['league_name'] ?? 'Liga') .
                ' · ' .
                (string)($selectedRound['name'] ?? 'Jornada') .
                '.';
        }

        $this->render('quiniela/previous', [
            'pageTitle' => 'Histórico de quinielas',
            'metaDescription' => $metaDescription,
            'filters' => $filters,
            'periodOptions' => $this->getPreviousHistoryPeriodOptions(),
            'statusOptions' => $this->getPreviousHistoryStatusOptions(),
            'availableLeagues' => $availableLeagues,
            'roundCards' => $roundCards,
            'selectedRound' => $selectedRound,
            'selectedRoundId' => $selectedRoundId,
            'tickets' => $tickets,
            'matches' => $matches,
            'winners' => $winners,
            'prizes' => $prizes,
            'roundSummary' => $roundSummary,
            'currencyCode' => $currencyCode,
        ]);
    }

    /**
     * Obtiene filtros del histórico público.
     *
     * Por defecto carga quinielas creadas durante el último mes.
     *
     * @return array{league:string,period:string,status:string,q:string,round_id:int}
     */
    private function getPreviousHistoryFilters(): array
    {
        $league = trim((string)($_GET['league'] ?? ''));
        $period = trim((string)($_GET['period'] ?? 'last_month'));
        $status = strtoupper(trim((string)($_GET['status'] ?? 'all')));
        $q = trim((string)($_GET['q'] ?? ''));
        $roundId = (int)($_GET['round_id'] ?? 0);

        $allowedPeriods = array_keys($this->getPreviousHistoryPeriodOptions());

        if (!in_array($period, $allowedPeriods, true)) {
            $period = 'last_month';
        }

        $allowedStatuses = array_keys($this->getPreviousHistoryStatusOptions());

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $q = preg_replace('/[\x00-\x1F\x7F]/u', '', $q) ?? '';
        $q = mb_substr($q, 0, 120);

        return [
            'league' => $league,
            'period' => $period,
            'status' => $status,
            'q' => $q,
            'round_id' => $roundId,
        ];
    }
    /**
     * Opciones simples de periodo para el cliente final.
     *
     * Los periodos se calculan usando rounds.created_at.
     *
     * @return array<string,string>
     */
    private function getPreviousHistoryPeriodOptions(): array
    {
        return [
            'last_month' => 'Último mes',
            'last_7_days' => 'Últimos 7 días',
            'current_month' => 'Este mes',
            'previous_month' => 'Mes anterior',
            'last_3_months' => 'Últimos 3 meses',
            'all' => 'Todas',
        ];
    }


    /**
     * Opciones de estado para filtrar jornadas.
     *
     * @return array<string,string>
     */
    private function getPreviousHistoryStatusOptions(): array
    {
        return [
            'all' => 'Todas',
            'OPEN' => 'Abiertas',
            'CLOSED' => 'Cerradas',
            'FINISHED' => 'Finalizadas',
        ];
    }

    /**
     * Obtiene ligas disponibles dentro del histórico.
     *
     * @param array<int,array<string,mixed>> $rounds Jornadas históricas.
     * @return array<int,array{slug:string,name:string}>
     */
    private function getAvailableHistoricalLeagues(array $rounds): array
    {
        $leagues = [];

        foreach ($rounds as $round) {
            $slug = (string)($round['league_slug'] ?? '');
            $name = (string)($round['league_name'] ?? 'Liga');

            if ($slug === '') {
                continue;
            }

            if (!isset($leagues[$slug])) {
                $leagues[$slug] = [
                    'slug' => $slug,
                    'name' => $name,
                ];
            }
        }

        return array_values($leagues);
    }

    /**
     * Filtra jornadas por liga, estado, periodo y búsqueda.
     *
     * @param array<int,array<string,mixed>> $rounds Jornadas.
     * @param array{league:string,period:string,status:string,q:string,round_id:int} $filters Filtros.
     * @return array<int,array<string,mixed>>
     */
    private function filterHistoricalRounds(array $rounds, array $filters): array
    {
        $searchRoundIds = [];

        if ($filters['q'] !== '') {
            $searchRoundIds = $this->getRoundIdsByHistoricalSearch($filters['q']);

            /*
         * Si el usuario busca algo y no hay tickets relacionados,
         * no devolvemos todas las jornadas.
         */
            if ($searchRoundIds === []) {
                return [];
            }
        }

        return array_values(array_filter($rounds, function (array $round) use ($filters, $searchRoundIds): bool {
            if ($filters['league'] !== '' && (string)($round['league_slug'] ?? '') !== $filters['league']) {
                return false;
            }

            if (($filters['status'] ?? 'all') !== 'all') {
                $roundStatus = strtoupper((string)($round['status'] ?? ''));

                if ($roundStatus !== $filters['status']) {
                    return false;
                }
            }

            if (!$this->roundMatchesPeriod($round, $filters['period'])) {
                return false;
            }

            if ($filters['q'] !== '' && !in_array((int)$round['id'], $searchRoundIds, true)) {
                return false;
            }

            return true;
        }));
    }

    /**
     * Verifica si una jornada pertenece al periodo seleccionado.
     *
     * El filtro usa rounds.created_at porque el usuario quiere buscar
     * por tiempo de creación de la quiniela, no por fecha de cierre.
     *
     * @param array<string,mixed> $round Jornada.
     * @param string $period Periodo.
     * @return bool
     */
    private function roundMatchesPeriod(array $round, string $period): bool
    {
        if ($period === 'all') {
            return true;
        }

        $createdAt = (string)($round['created_at'] ?? '');
        $timestamp = strtotime($createdAt);

        if (!$timestamp) {
            return false;
        }

        $createdDate = date('Y-m-d', $timestamp);
        $today = date('Y-m-d');

        if ($period === 'last_7_days') {
            $from = date('Y-m-d', strtotime('-7 days'));

            return $createdDate >= $from && $createdDate <= $today;
        }

        if ($period === 'last_month') {
            $from = date('Y-m-d', strtotime('-1 month'));

            return $createdDate >= $from && $createdDate <= $today;
        }

        if ($period === 'current_month') {
            return $createdDate >= date('Y-m-01') && $createdDate <= $today;
        }

        if ($period === 'previous_month') {
            $from = date('Y-m-01', strtotime('first day of previous month'));
            $to = date('Y-m-t', strtotime('last day of previous month'));

            return $createdDate >= $from && $createdDate <= $to;
        }

        if ($period === 'last_3_months') {
            $from = date('Y-m-d', strtotime('-3 months'));

            return $createdDate >= $from && $createdDate <= $today;
        }

        return true;
    }

    /**
     * Busca jornadas por ticket, nombre o teléfono.
     *
     * @param string $query Texto buscado.
     * @return array<int,int>
     */
    private function getRoundIdsByHistoricalSearch(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $phone = preg_replace('/\D+/', '', $query) ?? '';
        $likeQuery = '%' . $query . '%';
        $likePhone = '%' . $phone . '%';

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare('
            SELECT DISTINCT t.round_id
            FROM tickets t
            WHERE
                t.ticket_code LIKE :ticket_code
                OR t.user_name LIKE :user_name
                OR t.phone LIKE :phone
            ORDER BY t.round_id DESC
            LIMIT 100
        ');

            $stmt->execute([
                ':ticket_code' => $likeQuery,
                ':user_name' => $likeQuery,
                ':phone' => $likePhone,
            ]);

            $ids = [];

            foreach (($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $row) {
                $ids[] = (int)$row['round_id'];
            }

            return $ids;
        } catch (Throwable $e) {
            error_log('Error buscando jornadas históricas: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Calcula resumen liviano para una card de jornada.
     *
     * No recalcula ranking; solo lee datos existentes para que la lista cargue rápido.
     *
     * @param int $roundId ID de jornada.
     * @return array{total_tickets:int,total_matches:int,total_collected:float,winner_name:string,winner_points:int,currency:string}
     */
    private function getHistoricalRoundCardSummary(int $roundId): array
    {
        $default = [
            'total_tickets' => 0,
            'total_matches' => 0,
            'total_collected' => 0.0,
            'winner_name' => '',
            'winner_points' => 0,
            'currency' => 'USD',
        ];

        if ($roundId <= 0) {
            return $default;
        }

        try {
            $pdo = Database::getConnection();

            $ticketStmt = $pdo->prepare('
            SELECT
                COUNT(*) AS total_tickets,
                COALESCE(SUM(CASE WHEN status = "PAID" THEN total_amount ELSE 0 END), 0) AS total_collected,
                COALESCE(MAX(currency), "USD") AS currency
            FROM tickets
            WHERE round_id = :round_id
              AND status = "PAID"
        ');

            $ticketStmt->execute([
                ':round_id' => $roundId,
            ]);

            $ticketSummary = $ticketStmt->fetch(\PDO::FETCH_ASSOC) ?: [];

            $matchStmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM matches
            WHERE round_id = :round_id
              AND status NOT IN ("CANCELLED", "POSTPONED")
        ');

            $matchStmt->execute([
                ':round_id' => $roundId,
            ]);

            $winnerStmt = $pdo->prepare('
            SELECT user_name, points
            FROM tickets
            WHERE round_id = :round_id
              AND status = "PAID"
            ORDER BY points DESC, created_at ASC, id ASC
            LIMIT 1
        ');

            $winnerStmt->execute([
                ':round_id' => $roundId,
            ]);

            $winner = $winnerStmt->fetch(\PDO::FETCH_ASSOC) ?: [];

            return [
                'total_tickets' => (int)($ticketSummary['total_tickets'] ?? 0),
                'total_matches' => (int)$matchStmt->fetchColumn(),
                'total_collected' => (float)($ticketSummary['total_collected'] ?? 0.0),
                'winner_name' => (string)($winner['user_name'] ?? ''),
                'winner_points' => (int)($winner['points'] ?? 0),
                'currency' => (string)($ticketSummary['currency'] ?? 'USD'),
            ];
        } catch (Throwable $e) {
            error_log('Error calculando card histórica: ' . $e->getMessage());

            return $default;
        }
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

    $statusFilter = strtoupper(trim((string)($_GET['status'] ?? 'all')));

    $allowedStatuses = [
        'all',
        'OPEN',
        'CLOSED',
        'FINISHED',
    ];

    if (!in_array($statusFilter, $allowedStatuses, true)) {
        $statusFilter = 'all';
    }

    if ($statusFilter !== 'all') {
        $availableRounds = array_values(array_filter(
            $availableRounds,
            static fn (array $round): bool => strtoupper((string)($round['status'] ?? '')) === $statusFilter
        ));
    }

    $currentRound = null;

    if (isset($_GET['round_id']) && (int)$_GET['round_id'] > 0) {
        $requestedRound = $roundModel->findById((int)$_GET['round_id']);

        if (
            $requestedRound &&
            (!isset($selectedLeagueData['id']) ||
                (int)$requestedRound['league_id'] === (int)$selectedLeagueData['id'])
        ) {
            if (
                $statusFilter === 'all' ||
                strtoupper((string)($requestedRound['status'] ?? '')) === $statusFilter
            ) {
                $currentRound = $requestedRound;
            }
        }
    }

    if (!$currentRound && $availableRounds !== []) {
        /*
         * Prioridad:
         * 1. Abierta
         * 2. Cerrada
         * 3. Finalizada
         * 4. Primera disponible
         */
        foreach (['OPEN', 'CLOSED', 'FINISHED'] as $preferredStatus) {
            foreach ($availableRounds as $round) {
                if (strtoupper((string)($round['status'] ?? '')) === $preferredStatus) {
                    $currentRound = $round;
                    break 2;
                }
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

    $rankingStats = [
        'total_tickets' => 0,
        'total_matches' => 0,
        'finished_matches' => 0,
        'pending_matches' => 0,
        'total_first' => 0,
        'total_second' => 0,
        'max_points' => 0,
    ];

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

        $finishedMatches = 0;

        foreach ($matches as $match) {
            if ((string)($match['result_outcome'] ?? '') !== '') {
                $finishedMatches++;
            }
        }

        $tickets = $this->attachTicketPicksAndHits($tickets, $matches, $ticketModel);

        $rankingStats['total_tickets'] = count($tickets);
        $rankingStats['total_matches'] = count($matches);
        $rankingStats['finished_matches'] = $finishedMatches;
        $rankingStats['pending_matches'] = max(0, count($matches) - $finishedMatches);
    }

    $totalPrimero = 0;
    $totalSegundo = 0;

    if ($tickets !== []) {
        $maxPoints = (int)($tickets[0]['points'] ?? 0);
        $secondPoints = null;

        $rankingStats['max_points'] = $maxPoints;

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

    $rankingStats['total_first'] = $totalPrimero;
    $rankingStats['total_second'] = $totalSegundo;

    $topTickets = array_slice($tickets, 0, 3);

    $metaDescription = 'Ranking en vivo ' .
        ($currentRound['name'] ?? 'General') .
        '. Consulta puntos, ganadores y resultados de la quiniela.';

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
        'rankingStats' => $rankingStats,
        'topTickets' => $topTickets,
        'statusFilter' => $statusFilter,
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

    /**
     * Obtiene configuración pública visual.
     *
     * @return array<string,string>
     */
    private function getPublicSettings(): array
    {
        $defaults = [
            'public_hero_bg_desktop' => '',
            'public_hero_bg_mobile' => '',
            'public_hero_overlay_opacity' => '0.72',
        ];

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->query("
            SELECT setting_key, setting_value
            FROM settings
            WHERE setting_key IN (
                'public_hero_bg_desktop',
                'public_hero_bg_mobile',
                'public_hero_overlay_opacity'
            )
        ");

            $rows = $stmt ? ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];

            foreach ($rows as $row) {
                $key = (string)($row['setting_key'] ?? '');
                $value = (string)($row['setting_value'] ?? '');

                if ($key !== '' && array_key_exists($key, $defaults)) {
                    $defaults[$key] = $value;
                }
            }
        } catch (Throwable $e) {
            error_log('Error leyendo settings públicos: ' . $e->getMessage());
        }

        return $defaults;
    }
}
