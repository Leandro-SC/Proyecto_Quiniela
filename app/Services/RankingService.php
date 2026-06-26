<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class RankingService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Recalcula los puntos y premios de una jornada (Round).
     */
    public function recomputeRound(int $roundId): array
    {
       // 1. Obtener partidos
        $matches = $this->getMatchesByRound($roundId);
        
        if (empty($matches)) {
            return $this->emptySummary($roundId);
        }

        // Mapear resultados oficiales
        $resultsByMatch = [];
        foreach ($matches as $m) {
            // --- NUEVO: Ignorar partidos cancelados o postergados ---
            if (in_array($m['status'], ['CANCELLED', 'POSTPONED'])) {
                continue; 
            }
            // -------------------------------------------------------

            $outcome = $m['result_outcome'] ?? null;
            if ($outcome === null || $outcome === '') {
                continue;
            }
            $resultsByMatch[(int)$m['id']] = $outcome;
        }

        // 2. Obtener tickets pagados
        $tickets = $this->getPaidTicketsByRound($roundId);
        
        if (empty($tickets)) {
            return $this->emptySummary($roundId);
        }

        $maxPoints      = 0;
        $pointsById     = [];
        $totalCollected = 0.0;

        foreach ($tickets as $t) {
            $ticketId  = (int)$t['id'];
            $itemsJson = (string)$t['items'];
            $items     = json_decode($itemsJson, true);
            
            // Sumar al total recaudado
            $totalCollected += (float)$t['total_amount'];

            if (!is_array($items)) {
                $pointsById[$ticketId] = 0;
                $this->updateTicketPoints($ticketId, 0);
                continue;
            }

            $points = 0;
            foreach ($items as $item) {
                if (!is_array($item)) continue;
                
                $matchId = (int)($item['match_id'] ?? 0);
                $pick    = (string)($item['pick'] ?? '');

                if (isset($resultsByMatch[$matchId]) && $pick !== '') {
                    $outcome = $resultsByMatch[$matchId];
                    if ($pick === $outcome) {
                        $points += 1; 
                    }
                }
            }

            $pointsById[$ticketId] = $points;
            if ($points > $maxPoints) {
                $maxPoints = $points;
            }

            $this->updateTicketPoints($ticketId, $points);
        }

        // 3. Determinar ganadores
        $firstWinners  = [];
        $secondWinners = [];
        $secondPoints  = 0;

        if ($maxPoints > 0) {
            // Primer lugar (Empatados con maxPoints)
            foreach ($pointsById as $tid => $pts) {
                if ($pts === $maxPoints) {
                    $firstWinners[] = $tid;
                }
            }
            
            // Buscar cuál es el puntaje del segundo lugar
            foreach ($pointsById as $pts) {
                if ($pts < $maxPoints && $pts > $secondPoints) {
                    $secondPoints = $pts;
                }
            }
            
            // Segundo lugar (Empatados con secondPoints)
            if ($secondPoints > 0) {
                foreach ($pointsById as $tid => $pts) {
                    if ($pts === $secondPoints) {
                        $secondWinners[] = $tid;
                    }
                }
            }
        }

        // --- AQUÍ EL CAMBIO IMPORTANTE ---
        // 4. Calcular premios dinámicamente según la DB
        
        // Obtenemos la configuración de porcentajes de ESTA jornada específica
        $roundConfig = $this->getRoundConfig($roundId);
        
        // Convertimos porcentaje (ej. 45) a decimal (0.45)
        // Usamos valores por defecto (45, 30, 15) solo si la base de datos falla
        $poolPct   = (float)($roundConfig['prize_pool_percent'] ?? 45.0) / 100.0;
        $firstPct  = (float)($roundConfig['first_place_percent'] ?? 30.0) / 100.0;
        $secondPct = (float)($roundConfig['second_place_percent'] ?? 15.0) / 100.0;

        $totalPrizePool    = $totalCollected * $poolPct;
        $firstPrizeTotal   = $totalCollected * $firstPct;
        $secondPrizeTotal  = $totalCollected * $secondPct;

        // División entre ganadores
        $firstPrizeEach  = (!empty($firstWinners))  ? $firstPrizeTotal / count($firstWinners) : 0.0;
        $secondPrizeEach = (!empty($secondWinners)) ? $secondPrizeTotal / count($secondWinners) : 0.0;

        return [
            'matchday_id'        => $roundId,
            'total_collected'    => $totalCollected,
            'first_prize_total'  => $firstPrizeTotal,
            'second_prize_total' => $secondPrizeTotal,
            'first_prize_each'   => $firstPrizeEach,
            'second_prize_each'  => $secondPrizeEach,
            'first_winners'      => $firstWinners,
            'second_winners'     => $secondWinners,
        ];
    }

    // --- AGREGAR ESTE MÉTODO PRIVADO ---
    private function getRoundConfig(int $roundId): array
    {
        $stmt = $this->pdo->prepare("SELECT prize_pool_percent, first_place_percent, second_place_percent FROM rounds WHERE id = :id");
        $stmt->execute([':id' => $roundId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: [];
    }

    private function getMatchesByRound(int $roundId): array
    {
        $stmt = $this->pdo->prepare("SELECT id, result_outcome FROM matches WHERE round_id = :rid");
        $stmt->execute([':rid' => $roundId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getPaidTicketsByRound(int $roundId): array
    {
        $stmt = $this->pdo->prepare("SELECT id, items, total_amount FROM tickets WHERE matchday_id = :rid AND status = 'PAID'");
        $stmt->execute([':rid' => $roundId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function updateTicketPoints(int $ticketId, int $points): void
    {
        $stmt = $this->pdo->prepare("UPDATE tickets SET points = :pts, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':pts' => $points, ':id' => $ticketId]);
    }

    private function emptySummary(int $id): array
    {
        return [
            'matchday_id' => $id, 'total_collected' => 0.0, 'first_prize_total'=> 0.0, 'second_prize_total'=> 0.0, 'first_winners' => [], 'second_winners' => []
        ];
    }

    // --- Métodos Públicos para Controladores ---

    public function getRoundRanking(int $roundId, string $status = 'PAID', ?string $search = null): array
    {
        $where = ['t.matchday_id = :rid'];
        $params = [':rid' => $roundId];

        if ($status !== 'ALL') {
            $where[] = 't.status = :st';
            $params[':st'] = $status;
        }
        if ($search) {
            $where[] = '(t.ticket_code LIKE :q OR t.user_name LIKE :q)';
            $params[':q'] = "%$search%";
        }

        $sql = 'SELECT t.*, r.name as round_name, COALESCE(l.name, "Sin Liga") as league_name
                FROM tickets t
                LEFT JOIN rounds r ON r.id = t.matchday_id
                LEFT JOIN leagues l ON l.id = t.league_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY t.points DESC, t.created_at ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        $rank = 1;
        foreach($rows as &$r) { $r['rank'] = $rank++; }
        return $rows;
    }

    public function getRoundSummary(int $roundId): array
    {
        return $this->recomputeRound($roundId);
    }

    public function getRoundWinners(int $roundId, int $place): array
    {
        $summary = $this->recomputeRound($roundId);
        $ids = $place === 1 ? $summary['first_winners'] : $summary['second_winners'];
        if (empty($ids)) return [];
        $in = implode(',', array_map('intval', $ids));
        return $this->pdo->query("SELECT * FROM tickets WHERE id IN ($in)")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function onTicketStatusChanged(int $ticketId): void
    {
        $stmt = $this->pdo->prepare('SELECT matchday_id FROM tickets WHERE id = :id');
        $stmt->execute([':id' => $ticketId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) $this->recomputeRound((int)$row['matchday_id']);
    }
    
    public function getRoundSummaryCached(int $roundId): array
{
    $cacheFile = __DIR__ . '/../../storage/cache/ranking_' . $roundId . '.json';
    
    // Si el archivo existe y es reciente (menos de 60 segundos)
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 60)) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    // Si no, calculamos y guardamos
    $data = $this->recomputeRound($roundId);
    
    // Asegurarse que la carpeta exista
    if (!is_dir(dirname($cacheFile))) mkdir(dirname($cacheFile), 0777, true);
    
    file_put_contents($cacheFile, json_encode($data));
    
    return $data;
}
}