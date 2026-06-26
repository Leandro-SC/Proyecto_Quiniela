<?php
declare(strict_types=1);

/** @var array<string,mixed> $stats */
/** @var array<int,array<string,mixed>> $recentTickets */
/** @var array<int,array<string,mixed>> $rounds */
/** @var array<string,mixed> $filters */

$fromValue = htmlspecialchars((string)($filters['from'] ?? ''), ENT_QUOTES, 'UTF-8');
$toValue   = htmlspecialchars((string)($filters['to'] ?? ''), ENT_QUOTES, 'UTF-8');

require __DIR__ . '/../partials/nav.php';
?>
<div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <div>
        <h1 class="h4 mb-1">Panel administrador</h1>
        <p class="text-muted small mb-0">
            Resumen operativo de quinielas y tickets.
        </p>
    </div>
    <form class="row g-2 mt-3 mt-md-0" method="get" action="/admin">
        <div class="col-auto">
            <input type="date" name="from" class="form-control form-control-sm"
                   value="<?= $fromValue ?>" placeholder="Desde">
        </div>
        <div class="col-auto">
            <input type="date" name="to" class="form-control form-control-sm"
                   value="<?= $toValue ?>" placeholder="Hasta">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">
                Filtrar
            </button>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase mb-1">
                    Tickets totales (rango)
                </div>
                <div class="h4 mb-0">
                    <?= (int)$stats['total_tickets'] ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase mb-1">
                    Tickets pagados
                </div>
                <div class="h4 mb-0">
                    <?= (int)$stats['paid_tickets'] ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase mb-1">
                    Recaudado por moneda
                </div>
                <?php if (empty($stats['amount_by_currency'])): ?>
                    <div class="small text-muted">Sin montos registrados.</div>
                <?php else: ?>
                    <?php foreach ($stats['amount_by_currency'] as $currency => $amount): ?>
                        <div class="d-flex justify-content-between small">
                            <span><?= htmlspecialchars((string)$currency, ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= number_format((float)$amount, 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <strong class="small text-uppercase">Últimos tickets</strong>
        </div>
        <a href="/admin/tickets" class="btn btn-sm btn-outline-primary">
            Ver todos
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ticket</th>
                        <th>Jornada</th>
                        <th>Liga</th>
                        <th>Cliente</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($recentTickets)): ?>
                    <tr>
                        <td colspan="7" class="text-center small py-3">
                            No hay tickets en el rango seleccionado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentTickets as $t): ?>
                        <tr>
                            <td>
                                <a href="/admin/tickets/show?id=<?= (int)$t['id'] ?>"
                                   class="text-decoration-none">
                                    <?= htmlspecialchars((string)$t['ticket_code'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <div class="small text-muted">
                                    #<?= (int)$t['round_ticket_number'] ?> en jornada
                                </div>
                            </td>
                            <td>
                                <?= htmlspecialchars((string)$t['round_name'], ENT_QUOTES, 'UTF-8') ?>
                                <div class="small text-muted">
                                    Jornada <?= (int)$t['round_number'] ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars((string)$t['league_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars((string)$t['user_name'], ENT_QUOTES, 'UTF-8') ?>
                                <div class="small text-muted">
                                    <?= htmlspecialchars((string)$t['phone'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </td>
                            <td>
                                <?= htmlspecialchars((string)$t['currency'], ENT_QUOTES, 'UTF-8') ?>
                                <?= number_format((float)$t['total_amount'], 2) ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $t['status'] === 'PAID' ? 'success' : ($t['status'] === 'PENDING' ? 'warning' : 'secondary') ?>">
                                    <?= htmlspecialchars((string)$t['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= htmlspecialchars((string)$t['created_at'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
