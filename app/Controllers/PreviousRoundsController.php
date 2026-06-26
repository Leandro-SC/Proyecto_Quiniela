<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\RankingService;
use PDO;

/**
 * Página pública "Quinielas anteriores".
 */
class PreviousRoundsController extends BaseController
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->pdo = Database::getConnection();
    }

    public function index(Request $request, Response $response): void
    {
        // IMPORTANTE: Usamos directamente $_GET para evitar métodos inexistentes en Request.
        $roundId = isset($_GET['round_id']) ? (int)$_GET['round_id'] : 0;
        $search  = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

        if ($roundId <= 0 && $search !== '') {
            // Buscar jornada por nombre
            $stmt = $this->pdo->prepare(
                'SELECT id FROM rounds WHERE name LIKE :name ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute([':name' => '%' . $search . '%']);
            $roundId = (int)($stmt->fetchColumn() ?: 0);
        }

        if ($roundId <= 0) {
            // Última jornada cerrada por defecto
            $roundId = (int)$this->pdo->query(
                "SELECT id FROM rounds WHERE status = 'CLOSED' ORDER BY id DESC LIMIT 1"
            )->fetchColumn();
        }

        if ($roundId <= 0) {
            $this->render('home/previous-empty', [
                'pageTitle' => 'Quinielas anteriores',
            ]);
            return;
        }

        // Datos de la jornada
        $roundStmt = $this->pdo->prepare(
            'SELECT r.*, l.name AS league_name
             FROM rounds r
             LEFT JOIN leagues l ON l.id = r.league_id
             WHERE r.id = :id'
        );
        $roundStmt->execute([':id' => $roundId]);
        $round = $roundStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($round === null) {
            $this->render('home/previous-empty', [
                'pageTitle' => 'Quinielas anteriores',
            ]);
            return;
        }

        // Todas las jornadas (selector)
        $rounds = $this->pdo->query(
            "SELECT id, name
             FROM rounds
             WHERE status IN ('OPEN','CLOSED')
             ORDER BY id DESC"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Partidos de la jornada
        $matchesStmt = $this->pdo->prepare(
            'SELECT id,
                    home_team_name,
                    away_team_name,
                    home_team_logo,
                    away_team_logo,
                    result_outcome,
                    result_score
             FROM matches
             WHERE round_id = :round_id
             ORDER BY id ASC'
        );
        $matchesStmt->execute([':round_id' => $roundId]);
        $matches = $matchesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Tickets pagados
        $ticketsStmt = $this->pdo->prepare(
            'SELECT id,
                    ticket_code,
                    round_ticket_number,
                    user_name,
                    items,
                    points
             FROM tickets
             WHERE matchday_id = :round_id
               AND status = "PAID"
             ORDER BY points DESC, id ASC'
        );
        $ticketsStmt->execute([':round_id' => $roundId]);
        $tickets = $ticketsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Resumen de premios
        $rankingService = new RankingService();
        $summary        = $rankingService->recomputeRound($roundId);

        $this->render('home/previous', [
            'pageTitle' => 'Quinielas anteriores',
            'round'     => $round,
            'rounds'    => $rounds,
            'matches'   => $matches,
            'tickets'   => $tickets,
            'search'    => $search,
            'summary'   => $summary,
        ]);
    }
}
