<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\RoundModel;
use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Admin · Jornadas (quinielas).
 */
class RoundAdminController extends BaseAdminController
{
    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $roundModel = new RoundModel();
        $rounds     = $roundModel->getAllWithLeague();

        // Cargar ligas para el formulario modal de creación rápida
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT id, name FROM leagues WHERE is_active = 1 ORDER BY name ASC');
        $leagues = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->render('admin/rounds/index', [
            'pageTitle' => 'Admin · Jornadas',
            'rounds'    => $rounds,
            'leagues'   => $leagues,
        ]);
    }

    public function create(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT id, name FROM leagues WHERE is_active = 1 ORDER BY name ASC');
        $leagues = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->render('admin/rounds/form', [
            'pageTitle' => 'Crear jornada',
            'leagues'   => $leagues,
            'round'     => null,
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();

        try {
            $data = $this->sanitizeRoundData($_POST);

            $roundModel = new RoundModel();
            $roundId    = $roundModel->create($data);

            header('Location: /admin/rounds');
            exit;
        } catch (Throwable $e) {
            // En caso de error simple, volvemos al listado
            header('Location: /admin/rounds');
            exit;
        }
    }

    public function edit(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: /admin/rounds');
            exit;
        }

        $roundModel = new RoundModel();
        $round      = $roundModel->findById($id);
        if ($round === null) {
            header('Location: /admin/rounds');
            exit;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT id, name FROM leagues WHERE is_active = 1 ORDER BY name ASC');
        $leagues = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->render('admin/rounds/form', [
            'pageTitle' => 'Editar jornada',
            'leagues'   => $leagues,
            'round'     => $round,
        ]);
    }

    /**
     * Sanitizar y normalizar datos de jornada.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private function sanitizeRoundData(array $input): array
    {
        $leagueId    = isset($input['league_id']) ? (int)$input['league_id'] : 0;
        $name        = trim((string)($input['name'] ?? ''));
        $customTitle = trim((string)($input['custom_title'] ?? ''));
        $roundNumber = isset($input['round_number']) ? (int)$input['round_number'] : 1;
        $status      = (string)($input['status'] ?? 'OPEN');
        $openAt      = (string)($input['open_at'] ?? '');
        $closeAt     = (string)($input['close_at'] ?? '');
        $mxn         = (float)($input['ticket_cost_mxn'] ?? 0);
        $usd         = (float)($input['ticket_cost_usd'] ?? 0);
        $pool        = (float)($input['prize_pool_percent'] ?? 45.0);
        $first       = (float)($input['first_place_percent'] ?? 30.0);
        $second      = (float)($input['second_place_percent'] ?? 15.0);

        if ($leagueId <= 0 || $name === '' || $openAt === '' || $closeAt === '') {
            throw new RuntimeException('Datos de jornada incompletos.');
        }

        if (!in_array($status, ['OPEN', 'CLOSED'], true)) {
            $status = 'OPEN';
        }

        if ($pool <= 0 || $pool > 100) {
            $pool = 45.0;
        }

        if ($first < 0 || $second < 0 || ($first + $second) > $pool) {
         
            $first  = 30.0;
            $second = 15.0;
        }

        return [
            'league_id'           => $leagueId,
            'name'                => $name,
            'custom_title'        => $customTitle,
            'round_number'        => $roundNumber,
            'status'              => $status,
            'open_at'             => $openAt,
            'close_at'            => $closeAt,
            'ticket_cost_mxn'     => $mxn,
            'ticket_cost_usd'     => $usd,
            'prize_pool_percent'  => $pool,
            'first_place_percent' => $first,
            'second_place_percent'=> $second,
        ];
    }
    
 public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $model = new RoundModel();
        
        $data = [
            'id' => $_POST['id'],
            'league_id' => $_POST['league_id'],
            'name' => $_POST['name'],
            'round_number' => $_POST['round_number'],
            'open_at' => $_POST['open_at'],
            'close_at' => $_POST['close_at'],
            'status' => $_POST['status'],
            'ticket_cost_mxn' => $_POST['ticket_cost_mxn'] ?? 0,
            'ticket_cost_usd' => $_POST['ticket_cost_usd'] ?? 0,
            
            // --- AGREGAR ESTAS LÍNEAS QUE FALTABAN ---
            'prize_pool_percent'   => $_POST['prize_pool_percent'] ?? 45,
            'first_place_percent'  => $_POST['first_place_percent'] ?? 30,
            'second_place_percent' => $_POST['second_place_percent'] ?? 15,
            
            'custom_title' => trim($_POST['custom_title'] ?? '')
        ];

        $model->update($data);
        header('Location: /admin/rounds');
        exit;
    }

    // NUEVA FUNCIÓN DELETE
    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        
        if($id > 0){
            $model = new RoundModel();
            $model->delete($id);
        }
        header('Location: /admin/rounds');
        exit;
    }
}
