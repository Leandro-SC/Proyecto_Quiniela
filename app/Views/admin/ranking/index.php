<?php
/**
 * @var array<int,array<string,mixed>> $matchdays
 * @var int                             $selectedMatchdayId
 * @var string                          $statusFilter
 * @var string                          $searchQuery
 * @var array<int,array<string,mixed>>  $roundRanking
 * @var array<string,mixed>|null        $roundSummary
 * @var array                           $firstWinners
 * @var array                           $secondWinners
 */

$pageTitle = $pageTitle ?? 'Ranking';
require __DIR__ . '/../partials/nav.php';

// --- 1. Preparar Datos para la Vista ---
$prize1stEach = 0.0;
$prize2ndEach = 0.0;
$totalCollected = 0.0;
$totalPrize1st = 0.0;
$totalPrize2nd = 0.0;
$idsWinners1st = [];
$idsWinners2nd = [];

if (!empty($roundSummary)) {
    // Montos individuales
    $prize1stEach = (float)($roundSummary['first_prize_each'] ?? 0);
    $prize2ndEach = (float)($roundSummary['second_prize_each'] ?? 0);
    
    // Montos globales
    $totalCollected = (float)($roundSummary['total_collected'] ?? 0);
    $totalPrize1st  = (float)($roundSummary['first_prize_total'] ?? 0);
    $totalPrize2nd  = (float)($roundSummary['second_prize_total'] ?? 0);

    // IDs para resaltar en la tabla general (Array de IDs de tickets ganadores)
    $idsWinners1st = $roundSummary['first_winners'] ?? [];
    $idsWinners2nd = $roundSummary['second_winners'] ?? [];
}
?>

<style>
    /* Color Dorado intenso para 1er Lugar */
    .winner-gold {
        background-color: #fff3cd !important; /* Fondo amarillo claro */
        border-left: 5px solid #ffc107 !important; /* Borde dorado a la izquierda */
    }
    /* Color Plata para 2do Lugar */
    .winner-silver {
        background-color: #e2e3e5 !important; /* Fondo gris claro */
        border-left: 5px solid #6c757d !important; /* Borde gris oscuro a la izquierda */
    }
    /* Para que el texto resalte un poco más en las filas ganadoras */
    .winner-gold td, .winner-silver td {
        font-weight: 500;
        color: #212529;
    }
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Admin · Ranking y Premios</h1>
    </div>

    <form class="card mb-4 shadow-sm" method="get" action="/admin/ranking">
        <div class="card-body row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label for="f-jornada" class="form-label fw-bold">Jornada</label>
                <select id="f-jornada" name="matchday_id" class="form-select" onchange="this.form.submit()">
                    <option value="0">Todas</option>
                    <?php foreach (($matchdays ?? []) as $md): ?>
                        <?php
                        $mdId   = (int)$md['id'];
                        $number = (int)($md['round_number'] ?? 0);
                        $name   = $md['name'] ? (string)$md['name'] : 'Jornada ' . $number;
                        $league = (string)($md['league_name'] ?? 'Liga');
                        $label  = $league . ' · ' . $name;
                        // Marcar seleccionada
                        $selected = ($selectedMatchdayId === $mdId) ? 'selected' : '';
                        ?>
                        <option value="<?= $mdId ?>" <?= $selected ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label for="f-estado" class="form-label">Estado</label>
                <select id="f-estado" name="status" class="form-select" onchange="this.form.submit()">
                    <option value="PAID"    <?= $statusFilter === 'PAID'    ? 'selected' : '' ?>>Solo Pagados</option>
                    <option value="PENDING" <?= $statusFilter === 'PENDING' ? 'selected' : '' ?>>Pendientes</option>
                    <option value="ALL"     <?= $statusFilter === 'ALL'     ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>

            <div class="col-12 col-md-3">
                <label for="f-q" class="form-label">Buscar</label>
                <div class="input-group">
                    <input type="text" id="f-q" name="q" class="form-control" 
                           value="<?= htmlspecialchars($searchQuery ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Ticket, nombre...">
                    <button class="btn btn-primary" type="submit">Actualizar</button>
                </div>
            </div>
        </div>
    </form>

    <?php if ($roundSummary): ?>
    <div class="row g-3 mb-4">
        
        <div class="col-12 col-md-4">
            <div class="card h-100 border-primary shadow-sm">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <small class="text-uppercase text-muted fw-bold mb-1">Bolsa Total Recaudada</small>
                    <div class="fs-2 fw-black text-primary">
                        $<?= number_format($totalCollected, 2) ?>
                    </div>
                    <small class="text-muted">Jornada #<?= $selectedMatchdayId ?></small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card h-100 border-warning shadow-sm">
                <div class="card-header fw-bold bg-warning text-dark d-flex justify-content-between align-items-center">
                    <span>🥇 1er Lugar (<?= count($idsWinners1st) ?> ganadores)</span>
                    <span class="badge bg-white text-dark border border-dark">
                        $<?= number_format($prize1stEach, 2) ?> c/u
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($firstWinners)): ?>
                        <div class="p-4 text-center text-muted small">
                            Sin ganadores aún.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush" style="max-height: 200px; overflow-y:auto;">
                            <?php foreach ($firstWinners as $w): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-warning bg-opacity-10">
                                    <span class="fw-bold"><?= htmlspecialchars((string)$w['user_name']) ?></span>
                                    <span class="font-monospace small"><?= htmlspecialchars((string)$w['ticket_code']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white small text-muted text-center py-1">
                    Total a repartir: $<?= number_format($totalPrize1st, 2) ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card h-100 border-secondary shadow-sm">
                <div class="card-header fw-bold bg-secondary text-white d-flex justify-content-between align-items-center">
                    <span>🥈 2do Lugar (<?= count($idsWinners2nd) ?> ganadores)</span>
                    <span class="badge bg-white text-dark border">
                        $<?= number_format($prize2ndEach, 2) ?> c/u
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($secondWinners)): ?>
                        <div class="p-4 text-center text-muted small">
                            Sin ganadores aún.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush" style="max-height: 200px; overflow-y:auto;">
                            <?php foreach ($secondWinners as $w): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                                    <span class="fw-bold"><?= htmlspecialchars((string)$w['user_name']) ?></span>
                                    <span class="font-monospace small"><?= htmlspecialchars((string)$w['ticket_code']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white small text-muted text-center py-1">
                    Total a repartir: $<?= number_format($totalPrize2nd, 2) ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold py-2">
            <i class="bi bi-list-ol me-2"></i>Ranking Completo
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-center small text-uppercase">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th class="text-start">Ticket</th>
                            <th class="text-start">Cliente</th>
                            <th>Puntos</th>
                            <th class="text-end">Monto</th>
                            <th>Estado</th>
                            <th class="text-end fw-bold">Premio Est.</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($roundRanking ?? [])): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No hay tickets registrados con los filtros actuales.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($roundRanking as $row): 
                            $ticketId   = (int)$row['id'];
                            $rank       = (int)($row['rank'] ?? 0);
                            $ticketCode = (string)($row['ticket_code'] ?? '');
                            $clientName = (string)($row['user_name'] ?? '');
                            $points     = (int)($row['points'] ?? 0);
                            $amount     = (float)($row['total_amount'] ?? 0);
                            $status     = (string)($row['status'] ?? '');
                            $createdAt  = (string)($row['created_at'] ?? '');

                            // --- LÓGICA REFORZADA DE PINTADO ---
                            $rowClass = ''; 
                            $medal = '';
                            $prizeToShow = 0.0;

                            // 1. Verificamos si es 1er Lugar (Puede haber varios)
                            if (in_array($ticketId, $idsWinners1st, true)) {
                                $rowClass = 'winner-gold'; // Clase CSS personalizada
                                $medal = '🥇';
                                $prizeToShow = $prize1stEach;
                            } 
                            // 2. Verificamos si es 2do Lugar (Puede haber varios)
                            elseif (in_array($ticketId, $idsWinners2nd, true)) {
                                $rowClass = 'winner-silver'; // Clase CSS personalizada
                                $medal = '🥈';
                                $prizeToShow = $prize2ndEach;
                            }

                            // Badge Estado
                            $badgeClass = match($status) {
                                'PAID' => 'bg-success',
                                'PENDING' => 'bg-warning text-dark',
                                'REJECTED' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="text-center fw-bold text-muted"><?= $rank ?></td>
                            
                            <td class="text-start">
                                <span class="font-monospace fw-bold text-primary">
                                    <?= htmlspecialchars($ticketCode) ?>
                                </span>
                                <div class="small text-muted" style="font-size: 0.75rem;">
                                    <?= date('d/m H:i', strtotime($createdAt)) ?>
                                </div>
                            </td>
                            
                            <td class="text-start fw-bold">
                                <?= htmlspecialchars($clientName) ?>
                                <?php if($medal): ?>
                                    <span class="ms-2 fs-5"><?= $medal ?></span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-center fs-5 fw-black">
                                <?= $points ?>
                            </td>
                            
                            <td class="text-end text-muted small">
                                $<?= number_format($amount, 2) ?>
                            </td>
                            
                            <td class="text-center">
                                <span class="badge <?= $badgeClass ?> rounded-pill" style="font-size: 0.7em;">
                                    <?= $status ?>
                                </span>
                            </td>
                            
                            <td class="text-end fw-bold">
                                <?php if ($prizeToShow > 0): ?>
                                    <span class="text-success">$<?= number_format($prizeToShow, 2) ?></span>
                                <?php else: ?>
                                    <span class="text-muted opacity-25">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>