<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\RoundModel;
use App\Models\MatchModel;
use App\Models\LeagueModel;
use App\Models\PromotionModel;
use App\Services\RankingService;
use Throwable;

class QuinielaController extends BaseController
{
    /**
     * HOME: Jugar / Quiniela Actual + Promociones
     */
   public function index(Request $request, Response $response): void
    {
        try {
            $leagueModel = new LeagueModel();
            $roundModel  = new RoundModel();
            $matchModel  = new MatchModel();

            // 1. Obtener todas las Ligas Activas
            $activeLeagues = $leagueModel->getAllActive();

            // 2. DETECTAR LIGA SELECCIONADA
            $selectedLeagueData = null;
            $leagueSlug = isset($_GET['league']) && $_GET['league'] !== '' ? trim((string)$_GET['league']) : null;

            if ($leagueSlug && !empty($activeLeagues)) {
                foreach ($activeLeagues as $league) {
                    if ($league['slug'] === $leagueSlug) {
                        $selectedLeagueData = $league;
                        break;
                    }
                }
            }
            
            if (!$selectedLeagueData && $leagueSlug) {
                $selectedLeagueData = $leagueModel->findBySlug($leagueSlug);
                if ($selectedLeagueData && (int)$selectedLeagueData['is_active'] !== 1) {
                    $selectedLeagueData = null;
                }
            }

            if (!$selectedLeagueData && !empty($activeLeagues)) {
                $selectedLeagueData = $activeLeagues[0];
                $leagueSlug = $selectedLeagueData['slug'];
            }

            if (!$selectedLeagueData) {
                 $selectedLeagueData = [
                     'id' => 0, 'name' => 'Quiniela', 'slug' => 'default', 'image_background' => null, 'image_banner' => null
                 ];
            }

            // 3. DETECTAR JORNADA
            $availableRounds = [];
            $currentRound = null;

            if (!empty($selectedLeagueData['id'])) {
                $availableRounds = $roundModel->getOpenRoundsByLeague($leagueSlug);
                $reqRoundId = isset($_GET['round_id']) ? (int)$_GET['round_id'] : 0;

                if ($reqRoundId > 0) {
                    $reqRound = $roundModel->findById($reqRoundId);
                    if ($reqRound && (int)$reqRound['league_id'] === (int)$selectedLeagueData['id']) {
                        $currentRound = $reqRound;
                    }
                }
                if (!$currentRound) {
                    $currentRound = $roundModel->getCurrentRoundForLeagueSlug($leagueSlug);
                }
                if (!$currentRound && !empty($availableRounds)) {
                    $currentRound = $availableRounds[0];
                }
            }

            // 4. Cargar Partidos y Costos
            $matches = [];
            $ticketCost = 10.0;
            $geo = $_SESSION['geo'] ?? [];
            $geoCurrencyCode = $geo['currency_code'] ?? 'USD';

            if ($currentRound) {
                // OPTIMIZACIÓN: Partidos públicos ya filtrados
                $matches = $matchModel->getPublicMatchesByRound((int)$currentRound['id']);
                $ticketCost = ($geoCurrencyCode === 'MXN') ? (float)$currentRound['ticket_cost_mxn'] : (float)$currentRound['ticket_cost_usd'];
            }
            
            $ticketCostLabel = '$' . number_format($ticketCost, 2) . ' ' . $geoCurrencyCode;
            $whatsappPhone = $this->config['whatsapp']['phone'] ?? '';
            $deadlineIso = ($currentRound && !empty($currentRound['close_at'])) ? date('c', strtotime($currentRound['close_at'])) : '';

            // 5. Premios (CON CACHÉ SIMPLE)
            $estimatedPrizes = ['first' => 0.0, 'second' => 0.0];
            if ($currentRound && class_exists('App\Services\RankingService')) {
                // Usamos método privado para cachear y no matar el servidor
                $summary = $this->getRankingSummaryCached((int)$currentRound['id']);
                $estimatedPrizes['first'] = $summary['first_prize_total'] ?? 0.0;
                $estimatedPrizes['second'] = $summary['second_prize_total'] ?? 0.0;
            }

            $activePromo = null;
            if (class_exists('App\Models\PromotionModel')) {
                $promoModel = new PromotionModel();
                if (method_exists($promoModel, 'getActivePromo')) {
                    $activePromo = $promoModel->getActivePromo(null);
                }
            }

            // SEO: Meta Descripción Dinámica
            $metaDesc = "Participa en la Quiniela " . ($selectedLeagueData['name'] ?? 'Deportiva') . 
                        " Jornada " . ($currentRound['name'] ?? 'Actual') . ". " . 
                        "Predice resultados y gana premios en efectivo. Cierre: " . 
                        ($currentRound['close_at'] ?? 'Pronto');

            $this->render('home/index', [
                'pageTitle'          => ($selectedLeagueData['name'] ?? 'Quiniela') . ' - ' . ($currentRound['name'] ?? ''),
                'metaDescription'    => $metaDesc,
                'currentRound'       => $currentRound,
                'availableRounds'    => $availableRounds,
                'matches'            => $matches,
                'ticketCost'         => $ticketCost,
                'selectedLeague'     => $leagueSlug,
                'activeLeagues'      => $activeLeagues,
                'selectedLeagueData' => $selectedLeagueData,
                'geoCurrencyCode'    => $geoCurrencyCode,
                'ticketCostLabel'    => $ticketCostLabel,
                'whatsappPhone'      => $whatsappPhone,
                'deadlineIso'        => $deadlineIso,
                'estimatedPrizes'    => $estimatedPrizes,
                'activePromo'        => $activePromo
            ]);

        } catch (Throwable $e) {
            error_log("Error en QuinielaController: " . $e->getMessage());
            echo "Ocurrió un error inesperado. Por favor intenta más tarde."; 
            exit;
        }
    }

    /**
     * HISTORIAL: Quinielas Anteriores
     */
    public function previous(Request $request, Response $response): void
    {
        $roundModel = new RoundModel();
        
        $allRounds = $roundModel->getAllWithLeague();
        $matchdays = [];
        foreach ($allRounds as $r) {
            if (in_array($r['status'], ['CLOSED', 'FINISHED'])) {
                $r['label'] = mb_strtoupper(($r['league_name'] ?? 'LIGA') . ' - ' . ($r['name'] ?? 'JORNADA'));
                $matchdays[] = $r;
            }
        }

        $selectedRoundId = isset($_GET['matchday_id']) ? (int)$_GET['matchday_id'] : 0;
        
        if ($selectedRoundId <= 0 && !empty($matchdays)) {
            $selectedRoundId = (int)$matchdays[0]['id'];
        }

        $selectedRound = null;
        if ($selectedRoundId > 0) {
            foreach ($matchdays as $r) {
                if ((int)$r['id'] === $selectedRoundId) {
                    $selectedRound = $r; 
                    break;
                }
            }
        }

        $tickets = [];
        $matches = []; 
        $prizes  = ['first' => 0.0, 'second' => 0.0];
        
        $geo = $_SESSION['geo'] ?? [];
        $currencyCode = $geo['currency_code'] ?? 'USD';

        if ($selectedRoundId > 0 && class_exists(RankingService::class)) {
            $rankingService = new RankingService();
            // No usamos caché aquí porque el historial es estático y menos consultado, 
            // pero podríamos usarlo si la carga es alta.
            $summary = $rankingService->recomputeRound($selectedRoundId);
            $prizes['first']  = $summary['first_prize_total'] ?? 0.0;
            $prizes['second'] = $summary['second_prize_total'] ?? 0.0;
            $tickets = $rankingService->getRoundRanking($selectedRoundId, 'PAID');
            
            $matchModel = new MatchModel();
            $allMatches = $matchModel->getByRound($selectedRoundId);

            // FILTRAR: Quitamos Cancelados/Postergados
            $matches = array_filter($allMatches, function($m) {
                return !in_array($m['status'], ['CANCELLED', 'POSTPONED']);
            });
        }

        // SEO
        $metaDesc = "Consulta los resultados históricos de la Jornada " . ($selectedRound['name'] ?? '') . ". Revisa ganadores y marcadores.";

        $this->render('quiniela/previous', [
            'pageTitle'       => 'Historial - ' . ($selectedRound['label'] ?? 'Anteriores'),
            'metaDescription' => $metaDesc,
            'matchdays'       => $matchdays,          
            'selectedRoundId' => $selectedRoundId,
            'selectedRound'   => $selectedRound,
            'tickets'         => $tickets,            
            'matches'         => $matches,
            'prizes'          => $prizes,
            'currencyCode'    => $currencyCode
        ]);
    }

    /**
     * RANKING EN VIVO
     */
    public function ranking(Request $request, Response $response): void
    {
        $roundModel  = new RoundModel();
        $leagueModel = new LeagueModel();
        
        // 1. Detección de Liga
        $activeLeagues = $leagueModel->getAllActive();
        $selectedLeagueData = null;
        $leagueSlug = isset($_GET['league']) ? trim((string)$_GET['league']) : null;

        if ($leagueSlug && method_exists($leagueModel, 'findBySlug')) {
            $selectedLeagueData = $leagueModel->findBySlug($leagueSlug);
        }
        if (!$selectedLeagueData && !empty($activeLeagues)) {
            $selectedLeagueData = $activeLeagues[0];
            $leagueSlug = $selectedLeagueData['slug'];
        }
        
        // 2. Detección de Jornadas disponibles
        $availableRounds = [];
        if ($selectedLeagueData && $leagueSlug) {
            $availableRounds = $roundModel->getRankingRounds($leagueSlug);
        }
        
        // 3. Selección de Jornada actual
        $currentRound = null;
        if (isset($_GET['round_id']) && (int)$_GET['round_id'] > 0) {
            $reqRound = $roundModel->findById((int)$_GET['round_id']);
            if ($reqRound && (!isset($selectedLeagueData['id']) || (int)$reqRound['league_id'] === (int)$selectedLeagueData['id'])) {
                $currentRound = $reqRound;
            }
        }

        // Selección automática (Priorizar CLOSED sobre OPEN)
        if (!$currentRound && !empty($availableRounds)) {
            foreach ($availableRounds as $r) {
                if ($r['status'] === 'CLOSED') { $currentRound = $r; break; }
            }
            if (!$currentRound) $currentRound = $availableRounds[0];
        }
        
        // 4. Carga de Datos y Cálculos
        $tickets = []; 
        $matches = []; 
        $roundId = $currentRound ? (int)$currentRound['id'] : 0;
        $updatedAt = date('H:i');
        $estimatedPrizes = ['first' => 0.0, 'second' => 0.0];
        $geo = $_SESSION['geo'] ?? []; 
        $currencyCode = $geo['currency_code'] ?? 'USD';

        if ($roundId > 0 && class_exists(RankingService::class)) {
            $rankingService = new RankingService();
            
            // Usamos Caché para el resumen de premios
            $summary = $this->getRankingSummaryCached($roundId);
            $estimatedPrizes['first']  = $summary['first_prize_total'] ?? 0.0;
            $estimatedPrizes['second'] = $summary['second_prize_total'] ?? 0.0;
            
            // Obtenemos el ranking real (Tickets)
            // NOTA: Podríamos cachear esto también si hay muchos usuarios
            $tickets = $rankingService->getRoundRanking($roundId, 'PAID');
            
            $matchModel = new MatchModel();
            $allMatches = $matchModel->getByRound($roundId);

            // FILTRAR VISUALMENTE
            $matches = array_filter($allMatches, function($m) {
                return !in_array($m['status'], ['CANCELLED', 'POSTPONED']);
            });
        }

        // 5. LÓGICA DE CONTADORES (1ero y 2do lugar)
        $totalPrimero = 0;
        $totalSegundo = 0;

        if (!empty($tickets)) {
            $maxPuntos = (int)($tickets[0]['points'] ?? 0); 
            $segundosPuntos = null;

            foreach ($tickets as $t) {
                $puntosActuales = (int)$t['points'];

                if ($puntosActuales === $maxPuntos) {
                    $totalPrimero++;
                } else {
                    if ($segundosPuntos === null) {
                        $segundosPuntos = $puntosActuales;
                    }
                    if ($puntosActuales === $segundosPuntos) {
                        $totalSegundo++;
                    }
                }
            }
        }

        // SEO
        $metaDesc = "Ranking en vivo Jornada " . ($currentRound['name'] ?? '') . ". Sigue los resultados minuto a minuto.";

        $this->render('quiniela/ranking', [
            'pageTitle'          => 'Ranking - ' . ($currentRound['name'] ?? 'General'),
            'metaDescription'    => $metaDesc,
            'currentRound'       => $currentRound,
            'availableRounds'    => $availableRounds,
            'tickets'            => $tickets,
            'matches'            => $matches,
            'updatedAt'          => $updatedAt,
            'leagueSlug'         => $leagueSlug,
            'activeLeagues'      => $activeLeagues,
            'selectedLeagueData' => $selectedLeagueData,
            'estimatedPrizes'    => $estimatedPrizes,
            'currencyCode'       => $currencyCode,
            'totalPrimero'       => $totalPrimero, 
            'totalSegundo'       => $totalSegundo 
        ]);
    }

    /**
     * Helper privado para cachear el cálculo de premios y resumen.
     * Evita recalcular puntos en cada visita al Home.
     * Duración caché: 60 segundos.
     */
    private function getRankingSummaryCached(int $roundId): array
    {
        // Aseguramos que RankingService exista
        if (!class_exists(RankingService::class)) return [];

        // Definimos archivo de caché temporal
        $cacheFile = sys_get_temp_dir() . '/quiniela_rank_sum_' . $roundId . '.json';
        
        // Si existe y es fresco (< 60 seg), lo usamos
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 60)) {
            $content = file_get_contents($cacheFile);
            if ($content) {
                $data = json_decode($content, true);
                if (is_array($data)) return $data;
            }
        }

        // Si no, calculamos
        $service = new RankingService();
        $data = $service->recomputeRound($roundId);

        // Guardamos
        file_put_contents($cacheFile, json_encode($data));

        return $data;
    }
}