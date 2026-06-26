<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\RankingService;
use PDO;
use Throwable;

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

        try {
            $roundId = isset($_GET['round_id']) ? (int)$_GET['round_id'] : 0;
            $search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

            if ($roundId <= 0) {
                $roundId = (int)$this->pdo->query('
                    SELECT id
                    FROM rounds
                    ORDER BY id DESC
                    LIMIT 1
                ')->fetchColumn();
            }

            if ($roundId <= 0) {
                $this->render('admin/history/empty', [
                    'pageTitle' => 'Historial de quinielas',
                ]);
                return;
            }

            $round = $this->getRound($roundId);

            if (!$round) {
                $this->render('admin/history/empty', [
                    'pageTitle' => 'Historial de quinielas',
                ]);
                return;
            }

            $rankingService = new RankingService();
            $summary = $rankingService->recomputeRound($roundId);

            $matches = $this->getMatches($roundId);
            $tickets = $this->getTickets($roundId, $search);
            $tickets = $this->attachPicksToTickets($tickets);

            $roundsList = $this->getRoundsList();

            $this->render('admin/history/history', [
                'pageTitle' => 'Historial de quinielas',
                'round' => $round,
                'rounds' => $roundsList,
                'matches' => $matches,
                'tickets' => $tickets,
                'search' => $search,
                'summary' => $summary,
            ]);
        } catch (Throwable $e) {
            error_log('Error RoundHistoryController@index: ' . $e->getMessage());

            $this->render('admin/history/empty', [
                'pageTitle' => 'Historial de quinielas',
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getRound(int $roundId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                r.*,
                l.name AS league_name
            FROM rounds r
            LEFT JOIN leagues l ON l.id = r.league_id
            WHERE r.id = :id
            LIMIT 1
        ');

        $stmt->execute([
            ':id' => $roundId,
        ]);

        $round = $stmt->fetch(PDO::FETCH_ASSOC);

        return $round ?: null;
    }

    private function getMatches(int $roundId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                m.id,
                m.round_id,
                m.status,
                m.kickoff_at,
                m.home_score,
                m.away_score,
                m.result_outcome,
                ht.name AS home_team_name,
                at.name AS away_team_name,
                ht.logo_path AS home_team_logo,
                at.logo_path AS away_team_logo
            FROM matches m
            INNER JOIN teams ht ON ht.id = m.home_team_id
            INNER JOIN teams at ON at.id = m.away_team_id
            WHERE m.round_id = :round_id
              AND m.status NOT IN ("CANCELLED", "POSTPONED")
            ORDER BY m.kickoff_at ASC, m.id ASC
        ');

        $stmt->execute([
            ':round_id' => $roundId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getTickets(int $roundId, string $search): array
    {
        $sql = '
            SELECT
                t.id,
                t.ticket_code,
                t.user_name,
                t.phone,
                t.points,
                t.total_amount,
                t.currency,
                t.status,
                t.created_at
            FROM tickets t
            WHERE t.round_id = :round_id
              AND t.status = "PAID"
        ';

        $params = [
            ':round_id' => $roundId,
        ];

        if ($search !== '') {
            $sql .= ' AND (t.ticket_code LIKE :q OR t.user_name LIKE :q OR t.phone LIKE :q)';
            $params[':q'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY t.points DESC, t.created_at ASC, t.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function attachPicksToTickets(array $tickets): array
    {
        if ($tickets === []) {
            return [];
        }

        $ticketIds = array_map(
            static fn(array $ticket): int => (int)$ticket['id'],
            $tickets
        );

        $ticketIds = array_values(array_filter($ticketIds));

        if ($ticketIds === []) {
            return $tickets;
        }

        $placeholders = implode(',', array_fill(0, count($ticketIds), '?'));

        $stmt = $this->pdo->prepare("
            SELECT
                ticket_id,
                match_id,
                selection
            FROM ticket_items
            WHERE ticket_id IN ({$placeholders})
            ORDER BY id ASC
        ");

        $stmt->execute($ticketIds);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $picksByTicket = [];

        foreach ($items as $item) {
            $ticketId = (int)$item['ticket_id'];
            $matchId = (int)$item['match_id'];
            $selection = (string)$item['selection'];

            if (!isset($picksByTicket[$ticketId])) {
                $picksByTicket[$ticketId] = [];
            }

            $picksByTicket[$ticketId][$matchId] = $selection;
        }

        foreach ($tickets as &$ticket) {
            $ticketId = (int)$ticket['id'];
            $ticket['picks'] = $picksByTicket[$ticketId] ?? [];
        }

        unset($ticket);

        return $tickets;
    }

    private function getRoundsList(): array
    {
        $stmt = $this->pdo->query('
            SELECT
                r.id,
                r.name,
                l.name AS league_name
            FROM rounds r
            LEFT JOIN leagues l ON l.id = r.league_id
            ORDER BY r.id DESC
        ');

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }
}