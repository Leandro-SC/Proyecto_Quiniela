<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;
use Throwable;

class DashboardController extends BaseAdminController
{
    private PDO $pdo;

    private function boot(): void
    {
        if (!isset($this->pdo)) {
            $this->pdo = Database::getConnection();
        }
    }

    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        try {
            $stats = [
                'total_rounds' => $this->countTable('rounds'),
                'open_rounds' => $this->countWhere('rounds', 'status = "OPEN"'),
                'closed_rounds' => $this->countWhere('rounds', 'status IN ("CLOSED", "FINISHED")'),
                'total_matches' => $this->countTable('matches'),
                'total_tickets' => $this->countTable('tickets'),
                'paid_tickets' => $this->countWhere('tickets', 'status = "PAID"'),
                'pending_tickets' => $this->countWhere('tickets', 'status = "PENDING"'),
                'cancelled_tickets' => $this->countWhere('tickets', 'status = "CANCELLED"'),
                'total_players' => $this->countTable('players'),
                'total_teams' => $this->countTable('teams'),
                'total_leagues' => $this->countTable('leagues'),
            ];

            $money = $this->getMoneyStats();
            $recentTickets = $this->getRecentTickets();
            $activeRounds = $this->getActiveRounds();
            $recentMatches = $this->getRecentMatches();

            $this->render('admin/dashboard/index', [
                'pageTitle' => 'Admin · Dashboard',
                'stats' => $stats,
                'money' => $money,
                'recentTickets' => $recentTickets,
                'activeRounds' => $activeRounds,
                'recentMatches' => $recentMatches,
            ]);
        } catch (Throwable $e) {
            error_log('Error DashboardController@index: ' . $e->getMessage());

            $this->render('admin/dashboard/index', [
                'pageTitle' => 'Admin · Dashboard',
                'stats' => [],
                'money' => [
                    'total_collected' => 0,
                    'paid_amount' => 0,
                    'pending_amount' => 0,
                ],
                'recentTickets' => [],
                'activeRounds' => [],
                'recentMatches' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function countTable(string $table): int
    {
        $allowed = [
            'rounds',
            'matches',
            'tickets',
            'players',
            'teams',
            'leagues',
        ];

        if (!in_array($table, $allowed, true)) {
            return 0;
        }

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$table}");

        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    private function countWhere(string $table, string $where): int
    {
        $allowedTables = [
            'rounds',
            'tickets',
        ];

        if (!in_array($table, $allowedTables, true)) {
            return 0;
        }

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$table} WHERE {$where}");

        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    private function getMoneyStats(): array
    {
        $stmt = $this->pdo->query('
            SELECT
                COALESCE(SUM(CASE WHEN status = "PAID" THEN total_amount ELSE 0 END), 0) AS paid_amount,
                COALESCE(SUM(CASE WHEN status = "PENDING" THEN total_amount ELSE 0 END), 0) AS pending_amount,
                COALESCE(SUM(CASE WHEN status = "PAID" THEN total_amount ELSE 0 END), 0) AS total_collected
            FROM tickets
        ');

        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;

        return [
            'total_collected' => (float)($row['total_collected'] ?? 0),
            'paid_amount' => (float)($row['paid_amount'] ?? 0),
            'pending_amount' => (float)($row['pending_amount'] ?? 0),
        ];
    }

    private function getRecentTickets(): array
    {
        $stmt = $this->pdo->query('
            SELECT
                t.id,
                t.ticket_code,
                t.user_name,
                t.phone,
                t.total_amount,
                t.currency,
                t.status,
                t.points,
                t.created_at,
                r.name AS round_name,
                l.name AS league_name
            FROM tickets t
            INNER JOIN rounds r ON r.id = t.round_id
            INNER JOIN leagues l ON l.id = r.league_id
            ORDER BY t.created_at DESC, t.id DESC
            LIMIT 10
        ');

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    private function getActiveRounds(): array
    {
        $stmt = $this->pdo->query('
            SELECT
                r.id,
                r.name,
                r.round_number,
                r.status,
                r.open_at,
                r.close_at,
                r.ticket_cost_mxn,
                r.ticket_cost_usd,
                l.name AS league_name,
                COUNT(DISTINCT m.id) AS total_matches,
                COUNT(DISTINCT t.id) AS total_tickets,
                COALESCE(SUM(CASE WHEN t.status = "PAID" THEN t.total_amount ELSE 0 END), 0) AS total_collected
            FROM rounds r
            INNER JOIN leagues l ON l.id = r.league_id
            LEFT JOIN matches m ON m.round_id = r.id
            LEFT JOIN tickets t ON t.round_id = r.id
            WHERE r.status = "OPEN"
            GROUP BY
                r.id,
                r.name,
                r.round_number,
                r.status,
                r.open_at,
                r.close_at,
                r.ticket_cost_mxn,
                r.ticket_cost_usd,
                l.name
            ORDER BY r.close_at ASC
            LIMIT 10
        ');

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    private function getRecentMatches(): array
    {
        $stmt = $this->pdo->query('
            SELECT
                m.id,
                m.round_id,
                m.kickoff_at,
                m.status,
                m.home_score,
                m.away_score,
                m.result_outcome,
                ht.name AS home_team_name,
                at.name AS away_team_name,
                r.name AS round_name,
                l.name AS league_name
            FROM matches m
            INNER JOIN teams ht ON ht.id = m.home_team_id
            INNER JOIN teams at ON at.id = m.away_team_id
            INNER JOIN rounds r ON r.id = m.round_id
            INNER JOIN leagues l ON l.id = r.league_id
            ORDER BY m.kickoff_at DESC, m.id DESC
            LIMIT 10
        ');

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }
}