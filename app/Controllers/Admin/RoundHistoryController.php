<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\RankingService;
use PDO;

/**
 * Historial de quinielas (vista tipo matriz) en ADMIN.
 */
class RoundHistoryController extends BaseAdminController
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->pdo = Database::getConnection();
    }

    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();

        // Usamos $_GET directamente
        $roundId = isset($_GET['round_id']) ? (int)$_GET['round_id'] : 0;
        $search  = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

        if ($roundId <= 0) {
            $roundId = (int)$this->pdo->query(
                'SELECT id FROM rounds ORDER BY id DESC LIMIT 1'
            )->fetchColumn();
        }

        if ($roundId <= 0) {
            $this->render('admin/history/empty', [
                'pageTitle' => 'Historial de quinielas',
            ]);
            return;
        }

        $roundStmt = $this->pdo->prepare(
            'SELECT r.*, l.name AS league_name
             FROM rounds r
             LEFT JOIN leagues l ON l.id = r.league_id
             WHERE r.id = :id'
        );
        $roundStmt->execute([':id' => $roundId]);
        $round = $roundStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($round === null) {
            $this->render('admin/history/empty', [
                'pageTitle' => 'Historial de quinielas',
            ]);
            return;
        }

        $matchesStmt = $this->pdo->prepare(
            'SELECT id, home_team_name, away_team_name,
                    home_team_logo, away_team_logo, result_outcome
             FROM matches
             WHERE round_id = :round_id
             ORDER BY id ASC'
        );
        $matchesStmt->execute([':round_id' => $roundId]);
        $matches = $matchesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $ticketsSql = 'SELECT t.*
                       FROM tickets t
                       WHERE t.matchday_id = :round_id
                         AND t.status = "PAID"';
        $params = [':round_id' => $roundId];

        if ($search !== '') {
            $ticketsSql .= ' AND (t.ticket_code LIKE :q OR t.user_name LIKE :q)';
            $params[':q'] = '%' . $search . '%';
        }

        $ticketsSql .= ' ORDER BY t.points DESC, t.id ASC';

        $ticketsStmt = $this->pdo->prepare($ticketsSql);
        $ticketsStmt->execute($params);
        $tickets = $ticketsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $rankingService = new RankingService();
        $summary        = $rankingService->recomputeRound($roundId);

        $roundsList = $this->pdo->query(
            'SELECT id, name FROM rounds ORDER BY id DESC'
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->render('admin/history/history', [
            'pageTitle' => 'Historial de quinielas',
            'round'     => $round,
            'rounds'    => $roundsList,
            'matches'   => $matches,
            'tickets'   => $tickets,
            'search'    => $search,
            'summary'   => $summary,
        ]);
    }
}
