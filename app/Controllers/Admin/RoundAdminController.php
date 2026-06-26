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

class RoundAdminController extends BaseAdminController
{
    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $roundModel = new RoundModel();
        $rounds = $roundModel->getAllWithLeague();

        $leagues = $this->getActiveLeagues();

        $this->render('admin/rounds/index', [
            'pageTitle' => 'Admin · Jornadas',
            'rounds' => $rounds,
            'leagues' => $leagues,
        ]);
    }

    public function create(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $this->render('admin/rounds/form', [
            'pageTitle' => 'Crear jornada',
            'leagues' => $this->getActiveLeagues(),
            'round' => null,
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();

        try {
            $data = $this->sanitizeRoundData($_POST);

            $roundModel = new RoundModel();
            $roundModel->create($data);

            header('Location: /admin/rounds');
            exit;
        } catch (Throwable $e) {
            error_log('Error creando jornada: ' . $e->getMessage());

            header('Location: /admin/rounds');
            exit;
        }
    }

    public function edit(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/rounds');
            exit;
        }

        $roundModel = new RoundModel();
        $round = $roundModel->findById($id);

        if ($round === null) {
            header('Location: /admin/rounds');
            exit;
        }

        $this->render('admin/rounds/form', [
            'pageTitle' => 'Editar jornada',
            'leagues' => $this->getActiveLeagues(),
            'round' => $round,
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();

        try {
            $data = $this->sanitizeRoundData($_POST);
            $data['id'] = (int)($_POST['id'] ?? 0);

            if ($data['id'] <= 0) {
                throw new RuntimeException('ID de jornada inválido.');
            }

            $roundModel = new RoundModel();
            $roundModel->update($data);

            header('Location: /admin/rounds');
            exit;
        } catch (Throwable $e) {
            error_log('Error actualizando jornada: ' . $e->getMessage());

            header('Location: /admin/rounds');
            exit;
        }
    }

    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $roundModel = new RoundModel();
            $roundModel->delete($id);
        }

        header('Location: /admin/rounds');
        exit;
    }

    private function getActiveLeagues(): array
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->query('
            SELECT id, name
            FROM leagues
            WHERE is_active = 1
            ORDER BY name ASC
        ');

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function sanitizeRoundData(array $input): array
    {
        $leagueId = (int)($input['league_id'] ?? 0);
        $name = trim((string)($input['name'] ?? ''));
        $customTitle = trim((string)($input['custom_title'] ?? ''));
        $roundNumber = (int)($input['round_number'] ?? 1);
        $status = strtoupper(trim((string)($input['status'] ?? 'OPEN')));
        $openAt = trim((string)($input['open_at'] ?? ''));
        $closeAt = trim((string)($input['close_at'] ?? ''));

        $mxn = (float)($input['ticket_cost_mxn'] ?? 200.00);
        $usd = (float)($input['ticket_cost_usd'] ?? 10.00);

        $pool = (float)($input['prize_pool_percent'] ?? 45.00);
        $first = (float)($input['first_place_percent'] ?? 30.00);
        $second = (float)($input['second_place_percent'] ?? 15.00);

        if ($leagueId <= 0 || $name === '' || $openAt === '' || $closeAt === '') {
            throw new RuntimeException('Datos de jornada incompletos.');
        }

        if (!in_array($status, ['DRAFT', 'OPEN', 'CLOSED', 'FINISHED', 'CANCELLED'], true)) {
            $status = 'OPEN';
        }

        if ($roundNumber <= 0) {
            $roundNumber = 1;
        }

        if ($mxn <= 0) {
            $mxn = 200.00;
        }

        if ($usd <= 0) {
            $usd = 10.00;
        }

        if ($pool <= 0 || $pool > 100) {
            $pool = 45.00;
        }

        if ($first < 0 || $second < 0 || ($first + $second) > $pool) {
            $first = 30.00;
            $second = 15.00;
        }

        return [
            'league_id' => $leagueId,
            'name' => $name,
            'custom_title' => $customTitle !== '' ? $customTitle : null,
            'round_number' => $roundNumber,
            'status' => $status,
            'open_at' => $openAt,
            'close_at' => $closeAt,
            'ticket_cost_mxn' => $mxn,
            'ticket_cost_usd' => $usd,
            'prize_pool_percent' => $pool,
            'first_place_percent' => $first,
            'second_place_percent' => $second,
        ];
    }
}