<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\MatchModel;
use App\Services\RankingService;
use Throwable;

/**
 * Controlador legacy de partidos.
 *
 * Se mantiene únicamente por compatibilidad con rutas antiguas:
 * - /admin/matches/manage
 * - /admin/matches/store
 * - /admin/matches/update
 * - /admin/matches/delete
 *
 * El flujo oficial es:
 * - /admin/rounds/matches
 */
class MatchAdminController extends BaseAdminController
{
    /**
     * Redirige la pantalla legacy al flujo actual.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function manage(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $roundId = (int)($_GET['round_id'] ?? 0);

        if ($roundId > 0) {
            header('Location: /admin/rounds/matches?round_id=' . $roundId);
            exit;
        }

        header('Location: /admin/rounds');
        exit;
    }

    /**
     * Redirige creación legacy al formulario oficial.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();

        $roundId = (int)($_POST['round_id'] ?? 0);

        if ($roundId > 0) {
            header('Location: /admin/rounds/matches/create?round_id=' . $roundId);
            exit;
        }

        header('Location: /admin/rounds');
        exit;
    }

    /**
     * Redirige edición legacy al formulario oficial cuando exista ID.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();

        $roundId = (int)($_POST['round_id'] ?? 0);
        $matchId = (int)($_POST['id'] ?? 0);

        if ($roundId > 0 && $matchId > 0) {
            header('Location: /admin/rounds/matches/edit?round_id=' . $roundId . '&match_id=' . $matchId);
            exit;
        }

        if ($roundId > 0) {
            header('Location: /admin/rounds/matches?round_id=' . $roundId);
            exit;
        }

        header('Location: /admin/rounds');
        exit;
    }

    /**
     * Elimina partido desde rutas legacy y recalcula la jornada.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();

        $matchId = (int)($_POST['id'] ?? 0);
        $roundId = (int)($_POST['round_id'] ?? 0);

        if ($matchId > 0) {
            try {
                $matchModel = new MatchModel();
                $matchModel->delete($matchId);

                if ($roundId > 0) {
                    $rankingService = new RankingService();
                    $rankingService->recomputeRound($roundId);
                }
            } catch (Throwable $e) {
                error_log('Error eliminando partido legacy: ' . $e->getMessage());
            }
        }

        if ($roundId > 0) {
            header('Location: /admin/rounds/matches?round_id=' . $roundId);
            exit;
        }

        header('Location: /admin/rounds');
        exit;
    }
}