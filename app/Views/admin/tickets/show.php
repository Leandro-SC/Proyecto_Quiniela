<?php
/**
 * @var array<string,mixed>             $ticket
 * @var array<int,array<string,mixed>> $items
 */
require __DIR__ . '/../partials/nav.php';

// Totales calculados
$totalPoints = 0;
?>
<section>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Ticket #<?= htmlspecialchars((string)$ticket['ticket_code']) ?></h1>
            <span class="text-muted small">ID Interno: <?= $ticket['id'] ?></span>
        </div>
        <a href="/admin/tickets" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>

    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-light border-bottom">
            <h5 class="card-title mb-0 h6 text-primary"><i class="bi bi-info-circle"></i> Información General del Ticket</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                
                <div class="col-md-4 border-end">
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Detalles de la Jornada</h6>
                    
                    <div class="mb-2">
                        <span class="d-block text-muted small">Jornada</span>
                        <span class="fw-bold text-dark">
                            <?= htmlspecialchars($ticket['league_name'] ?? '-') ?> &mdash; 
                            <?= htmlspecialchars($ticket['matchday_name'] ?? '-') ?>
                        </span>
                    </div>

                    <div class="mb-2">
                        <span class="d-block text-muted small">Estado del Ticket</span>
                        <?php 
                            $statusColor = match($ticket['status']) {
                                'PAID' => 'success',
                                'PENDING' => 'warning',
                                'CANCELLED', 'REJECTED' => 'danger',
                                default => 'secondary'
                            };
                        ?>
                        <span class="badge bg-<?= $statusColor ?> fs-6">
                            <?= htmlspecialchars($ticket['status']) ?>
                        </span>
                    </div>

                    <div class="mb-2">
                        <span class="d-block text-muted small">Fecha de Emisión</span>
                        <span class="fw-bold"><?= date('d/m/Y H:i:s', strtotime($ticket['created_at'])) ?></span>
                    </div>
                </div>

                <div class="col-md-4 border-end">
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Datos del Cliente</h6>
                    
                    <div class="mb-2">
                        <span class="d-block text-muted small">Nombre Registrado</span>
                        <span class="fw-bold fs-5"><?= htmlspecialchars($ticket['user_name']) ?></span>
                    </div>

                    <div class="mb-2">
                        <span class="d-block text-muted small">Teléfono</span>
                        <a href="tel:<?= htmlspecialchars($ticket['phone']) ?>" class="text-decoration-none">
                            <i class="bi bi-telephone"></i> <?= htmlspecialchars($ticket['phone']) ?>
                        </a>
                    </div>
                    
                    <div class="mb-2">
                        <span class="d-block text-muted small">ID de Usuario (Sistema)</span>
                        <code>User #<?= $ticket['user_id'] ?></code>
                    </div>
                </div>

                <div class="col-md-4">
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Auditoría y Pago</h6>
                    
                    <div class="row">
                        <div class="col-6 mb-2">
                            <span class="d-block text-muted small">Dirección IP</span>
                            <code class="text-dark fw-bold"><?= htmlspecialchars($ticket['ip_address'] ?? 'N/A') ?></code>
                        </div>
                        <div class="col-6 mb-2">
                            <span class="d-block text-muted small">País Origen</span>
                            <?php if(!empty($ticket['purchase_country'])): ?>
                                <img src="https://flagcdn.com/24x18/<?= strtolower($ticket['purchase_country']) ?>.png" 
                                     alt="<?= $ticket['purchase_country'] ?>" class="me-1 border">
                                <?= $ticket['purchase_country'] ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-2">
                        <span class="d-block text-muted small">Referencia de Pago (Sesión)</span>
                        <small class="font-monospace text-secondary"><?= htmlspecialchars($ticket['session_code'] ?? 'N/A') ?></small>
                    </div>

                    <div class="mt-2 pt-2 border-top">
                        <span class="d-block text-muted small">Monto Total</span>
                        <span class="fw-bold text-success fs-5">
                            <?= number_format((float)($ticket['net_amount'] ?? 0), 2) ?> 
                            <span class="fs-6 text-muted"><?= htmlspecialchars($ticket['payment_currency'] ?? 'MXN') ?></span>
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 h6"><i class="bi bi-list-check"></i> Detalle de Pronósticos</h5>
            <span class="badge bg-white text-primary">Aciertos Totales: <span id="total-points-display" class="fw-bold fs-6">0</span></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle text-center">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th class="py-3">Horario</th>
                        <th>Local</th>
                        <th>vs</th>
                        <th>Visita</th>
                        <th>Selección</th>
                        <th>Resultado Oficial</th>
                        <th>Puntos</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="7" class="py-5 text-muted">No hay partidos registrados para este ticket.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $it): 
                        $status = $it['match_status']; 
                        $officialResult = $it['result_outcome']; 
                        
                        // Fallback lógica de goles si no hay resultado oficial pero el partido acabó
                        if (!$officialResult && ($status === 'FINISHED' || $status === 'CLOSED')) {
                            if (is_numeric($it['home_score']) && is_numeric($it['away_score'])) {
                                if ($it['home_score'] > $it['away_score']) $officialResult = 'L';
                                elseif ($it['home_score'] < $it['away_score']) $officialResult = 'V';
                                else $officialResult = 'E';
                            }
                        }

                        $userPick = strtoupper($it['selection']); 
                        $isHit = false;
                        $points = 0;
                        $rowClass = '';

                        if ($status === 'CANCELLED') {
                            $rowClass = 'table-light text-muted'; 
                        } elseif ($officialResult) {
                            if ($userPick === $officialResult) {
                                $isHit = true;
                                $points = 1;
                                $rowClass = 'table-success bg-opacity-10'; // Verde muy suave
                                $totalPoints++;
                            }
                        }
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="small text-muted">
                            <?= date('d/m H:i', strtotime($it['kickoff_at'])) ?>
                            <?php if($status === 'LIVE'): ?><span class="badge bg-danger ms-1 animate-blink">LIVE</span><?php endif; ?>
                        </td>
                        <td class="text-end fw-bold text-nowrap">
                            <?= htmlspecialchars($it['local_team']) ?>
                            <?php if($it['local_logo']): ?>
                                <img src="<?= $it['local_logo'] ?>" height="24" class="ms-2" style="object-fit:contain;">
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted px-3">
                            <?php if (($status === 'FINISHED' || $status === 'LIVE') && isset($it['home_score'])): ?>
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    <?= $it['home_score'] ?> - <?= $it['away_score'] ?>
                                </span>
                            <?php else: ?>
                                vs
                            <?php endif; ?>
                        </td>
                        <td class="text-start fw-bold text-nowrap">
                            <?php if($it['visitor_logo']): ?>
                                <img src="<?= $it['visitor_logo'] ?>" height="24" class="me-2" style="object-fit:contain;">
                            <?php endif; ?>
                            <?= htmlspecialchars($it['visitor_team']) ?>
                        </td>
                        <td>
                            <span class="badge rounded-pill bg-info text-dark border px-3 py-2 fs-6">
                                <?= $userPick ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($officialResult): ?>
                                <strong class="<?= $isHit ? 'text-success' : 'text-danger' ?> fs-5">
                                    <?= $officialResult ?>
                                </strong>
                            <?php elseif ($status === 'CANCELLED'): ?>
                                <span class="badge bg-secondary">Cancelado</span>
                            <?php else: ?>
                                <span class="spinner-grow spinner-grow-sm text-muted" role="status"></span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold fs-5">
                            <?php if ($officialResult): ?>
                                <?= $points > 0 ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-muted opacity-25"></i>' ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($items)): ?>
        <div class="card-footer bg-light small text-muted">
            Nota: Los resultados se actualizan automáticamente según el reporte oficial de la liga.
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
    // Actualizar el contador total al cargar la página
    document.getElementById('total-points-display').innerText = '<?= $totalPoints ?>';
</script>

<style>
    .animate-blink { animation: blinker 1.5s linear infinite; }
    @keyframes blinker { 50% { opacity: 0; } }
</style>