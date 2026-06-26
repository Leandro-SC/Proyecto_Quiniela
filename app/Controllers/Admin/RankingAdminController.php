<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Request;
use App\Core\Response;
use App\Models\RoundModel;
use App\Services\RankingService;
use Throwable;

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

        $roundId = isset($_GET['round_id']) && is_numeric($_GET['round_id'])
            ? (int)$_GET['round_id']
            : 0;

        $status = isset($_GET['status']) && $_GET['status'] !== ''
            ? strtoupper(trim((string)$_GET['status']))
            : 'PAID';

        if (!in_array($status, ['PAID', 'PENDING', 'CANCELLED', 'ALL'], true)) {
            $status = 'PAID';
        }

        $search = trim((string)($_GET['q'] ?? ''));

        $rounds = $this->roundModel->getAllWithLeague();

        if ($roundId <= 0 && $rounds !== []) {
            $roundId = (int)$rounds[0]['id'];
        }

        $ranking = [];
        $summary = null;
        $firstWinners = [];
        $secondWinners = [];

        if ($roundId > 0) {
            try {
                $summary = $this->rankingService->recomputeRound($roundId);

                $firstWinners = $this->rankingService->getRoundWinners($roundId, 1);
                $secondWinners = $this->rankingService->getRoundWinners($roundId, 2);

                $ranking = $this->rankingService->getRoundRanking(
                    $roundId,
                    $status,
                    $search !== '' ? $search : null
                );
            } catch (Throwable $e) {
                error_log('Error RankingAdminController@index: ' . $e->getMessage());
                $summary = null;
                $ranking = [];
                $firstWinners = [];
                $secondWinners = [];
            }
        }

        $this->render('admin/ranking/index', [
            'pageTitle' => 'Admin · Ranking',
            'matchdays' => $rounds,
            'rounds' => $rounds,
            'selectedMatchdayId' => $roundId,
            'selectedRoundId' => $roundId,
            'statusFilter' => $status,
            'searchQuery' => $search,
            'search' => $search,
            'roundRanking' => $ranking,
            'ranking' => $ranking,
            'roundSummary' => $summary,
            'summary' => $summary,
            'firstWinners' => $firstWinners,
            'secondWinners' => $secondWinners,
        ]);
    }
}