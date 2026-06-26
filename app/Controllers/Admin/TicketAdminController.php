<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\RankingService;
use PDO;
use Exception;  // Importante para try-catch
use Throwable;  // Importante para capturar errores graves

class TicketAdminController extends BaseAdminController
{
    private PDO $pdo;
    private RankingService $rankingService;

    /**
     * Inicialización perezosa de dependencias.
     */
    private function boot(): void
    {
        if (!isset($this->pdo)) {
            $this->pdo = Database::getConnection();
        }
        if (!isset($this->rankingService)) {
            $this->rankingService = new RankingService();
        }
    }

    /**
     * Listado de tickets con filtros.
     */
    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        // Filtros
        $roundId = isset($_GET['round_id']) ? (int)$_GET['round_id'] : 0;
        $status  = isset($_GET['status'])   ? (string)$_GET['status']   : '';
        $from    = isset($_GET['from'])     ? trim((string)$_GET['from']) : '';
        $to      = isset($_GET['to'])       ? trim((string)$_GET['to'])   : '';
        $q       = isset($_GET['q'])        ? trim((string)$_GET['q'])    : '';

        $where  = [];
        $params = [];

        if ($roundId > 0) {
            $where[]             = 't.matchday_id = :round_id';
            $params[':round_id'] = $roundId;
        }

        if ($status !== '' && $status !== 'ALL') {
            $where[]           = 't.status = :status';
            $params[':status'] = $status;
        }

        if ($from !== '') {
            $where[]         = 't.created_at >= :from';
            $params[':from'] = $from . ' 00:00:00';
        }

        if ($to !== '') {
            $where[]       = 't.created_at <= :to';
            $params[':to'] = $to . ' 23:59:59';
        }

        if ($q !== '') {
            $where[]       = '(t.ticket_code LIKE :q1 OR t.user_name LIKE :q2 OR t.phone LIKE :q3)';
            $term          = '%' . $q . '%';
            $params[':q1'] = $term;
            $params[':q2'] = $term;
            $params[':q3'] = $term;
        }

        // Consulta Principal
        $sql = 'SELECT
                    t.*,
                    r.name   AS matchday_name,
                    r.round_number AS round_ticket_number,
                    l.name   AS league_name
                FROM tickets t
                LEFT JOIN rounds r ON r.id = t.matchday_id
                LEFT JOIN leagues l ON l.id = t.league_id';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY t.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Selector de Jornadas
        $sqlRounds = 'SELECT
                          r.id,
                          r.name,
                          r.round_number AS number,
                          l.name AS league_name
                      FROM rounds r
                      LEFT JOIN leagues l ON l.id = r.league_id
                      ORDER BY r.created_at DESC';

        $roundsStmt = $this->pdo->query($sqlRounds);
        $rounds = $roundsStmt ? $roundsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $filters = [
            'round_id' => $roundId,
            'status'   => $status,
            'from'     => $from,
            'to'       => $to,
            'q'        => $q,
        ];

        $this->render('admin/tickets/index', [
            'pageTitle' => 'Tickets',
            'tickets'   => $tickets,
            'rounds'    => $rounds,
            'filters'   => $filters,
        ]);
    }


/**
     * Detalle de un ticket.
     */
    public function show(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Ticket no especificado.';
            header('Location: /admin/tickets');
            exit;
        }

        // 1. Datos del Ticket + Datos de Compra (JOIN con purchase_sessions)
        $sql = 'SELECT
                    t.*,
                    r.name    AS matchday_name,
                    r.round_number AS matchday_number,
                    l.name    AS league_name,
                    
                    -- Datos extendidos de la sesión de compra
                    ps.ip_address,
                    ps.country_code AS purchase_country,
                    ps.session_code,
                    ps.net_amount,
                    ps.currency AS payment_currency,
                    ps.status AS payment_status
                    
                FROM tickets t
                LEFT JOIN rounds r ON r.id = t.matchday_id
                LEFT JOIN leagues l ON l.id = t.league_id
                LEFT JOIN purchase_sessions ps ON ps.id = t.purchase_session_id -- <--- NUEVO JOIN
                WHERE t.id = :id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            $_SESSION['flash_error'] = 'Ticket no encontrado.';
            header('Location: /admin/tickets');
            exit;
        }

        // 2. Items del Ticket (Mantenemos la lógica corregida anteriormente)
        $itemsSql = 'SELECT
                        ti.selection,
                        m.id AS match_id,
                        m.home_score,
                        m.away_score,
                        m.status AS match_status,
                        m.result_outcome,
                        m.kickoff_at,
                        COALESCE(c_local.name, m.home_team_name) AS local_team,
                        COALESCE(c_visitor.name, m.away_team_name) AS visitor_team,
                        COALESCE(c_local.badge_path, m.home_team_logo) AS local_logo,
                        COALESCE(c_visitor.badge_path, m.away_team_logo) AS visitor_logo
                     FROM ticket_items ti
                     INNER JOIN matches m ON m.id = ti.match_id
                     LEFT JOIN clubs c_local ON m.home_club_id = c_local.id
                     LEFT JOIN clubs c_visitor ON m.away_club_id = c_visitor.id
                     WHERE ti.ticket_id = :tid
                     ORDER BY m.kickoff_at ASC';

        $itemsStmt = $this->pdo->prepare($itemsSql);
        $itemsStmt->execute([':tid' => $id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->render('admin/tickets/show', [
            'pageTitle' => 'Detalle ticket',
            'ticket'    => $ticket,
            'items'     => $items,
        ]);
    }
    

    /**
     * Actualizar estado.
     */
    public function updateStatus(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (strtoupper($method) !== 'POST') {
            header('Location: /admin/tickets');
            exit;
        }

        $ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
        $status   = isset($_POST['status'])    ? (string)$_POST['status']    : '';

        if ($ticketId <= 0 || !in_array($status, ['PENDING', 'PAID', 'REJECTED'], true)) {
            $_SESSION['flash_error'] = 'Datos inválidos.';
            header('Location: /admin/tickets');
            exit;
        }

        // Verificar actual
        $stmt = $this->pdo->prepare('SELECT status FROM tickets WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $ticketId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            $_SESSION['flash_error'] = 'Ticket no encontrado.';
            header('Location: /admin/tickets');
            exit;
        }

        if ((string)$current['status'] === $status) {
            $_SESSION['flash_success'] = 'El estado ya era ' . $status;
            header('Location: /admin/tickets');
            exit;
        }

        // Actualizar
        $upd = $this->pdo->prepare(
            'UPDATE tickets SET status = :status, updated_at = NOW() WHERE id = :id'
        );
        $upd->execute([':status' => $status, ':id' => $ticketId]);

        // Recalcular Ranking (Si el estado cambia, los puntos podrían contar o descontarse)
        if ($this->rankingService) {
            $this->rankingService->onTicketStatusChanged($ticketId);
        }

        $_SESSION['flash_success'] = 'Estado actualizado correctamente.';
        header('Location: /admin/tickets');
        exit;
    }
    
    /**
     * Eliminar Ticket (CORREGIDO)
     */
    public function delete(Request $request, Response $response): void
    {
        try {
            $this->requireAdmin();
            $this->boot(); // <--- ESTO FALTABA (Inicializa $this->pdo)
            
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            
            if ($ticketId > 0) {
                // 1. Eliminar items primero (por seguridad de FK)
                $delItems = $this->pdo->prepare("DELETE FROM ticket_items WHERE ticket_id = :id");
                $delItems->execute([':id' => $ticketId]);

                // 2. Eliminar ticket principal
                $delTicket = $this->pdo->prepare("DELETE FROM tickets WHERE id = :id");
                $delTicket->execute([':id' => $ticketId]);
                
                $_SESSION['flash_success'] = 'Ticket eliminado permanentemente.';
            }
        } catch (Throwable $e) {
            // Log del error para depuración
            error_log("Error eliminando ticket: " . $e->getMessage());
            $_SESSION['flash_error'] = 'No se pudo eliminar el ticket.';
        }
        
        header('Location: /admin/tickets');
        exit;
    }
}
