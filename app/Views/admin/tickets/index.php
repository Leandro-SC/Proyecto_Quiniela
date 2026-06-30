<?php

declare(strict_types=1);

use App\Core\Security;

/**
 * @var array<int,array<string,mixed>> $tickets
 * @var array<int,array<string,mixed>> $rounds
 * @var array<string,mixed>            $filters
 */

require __DIR__ . '/../partials/nav.php';

$fStatus = (string)($filters['status'] ?? '');
$filterRoundId = (int)($filters['round_id'] ?? 0);
$filterFrom = (string)($filters['from'] ?? '');
$filterTo = (string)($filters['to'] ?? '');
$filterQuery = (string)($filters['q'] ?? '');

$totalTickets = count($tickets ?? []);
$paidTickets = 0;
$pendingTickets = 0;
$rejectedTickets = 0;

foreach (($tickets ?? []) as $ticket) {
    $status = (string)($ticket['status'] ?? '');

    if ($status === 'PAID') {
        $paidTickets++;
    } elseif ($status === 'PENDING') {
        $pendingTickets++;
    } elseif ($status === 'REJECTED') {
        $rejectedTickets++;
    }
}

/**
 * Devuelve clase visual de estado.
 *
 * @param string $status
 * @return string
 */
$statusClass = static function (string $status): string {
    return match ($status) {
        'PAID' => 'qv-status-paid',
        'PENDING' => 'qv-status-pending',
        'REJECTED' => 'qv-status-danger',
        default => 'qv-status-muted',
    };
};
?>

<section class="admin-mobile-page qv-admin-ticket-page">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Control operativo</span>
            <h1>Tickets</h1>
            <p>
                Revisa tickets generados, valida pagos, cambia estados y consulta el detalle de cada quiniela.
            </p>
        </div>

        <a href="/admin/ranking" class="btn btn-outline-primary">
            <i class="bi bi-trophy-fill me-1"></i>
            Ver ranking
        </a>
    </header>

    <section class="qv-admin-kpi-grid qv-admin-ticket-kpis" aria-label="Resumen de tickets">
        <article class="qv-admin-kpi-card">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-ticket-perforated-fill"></i>
            </div>

            <div>
                <span>Total</span>
                <strong><?= number_format($totalTickets) ?></strong>
                <small>Tickets filtrados</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-success">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>

            <div>
                <span>Pagados</span>
                <strong><?= number_format($paidTickets) ?></strong>
                <small>Confirmados</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-warning">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-hourglass-split"></i>
            </div>

            <div>
                <span>Pendientes</span>
                <strong><?= number_format($pendingTickets) ?></strong>
                <small>Por revisar</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-danger">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-x-circle-fill"></i>
            </div>

            <div>
                <span>Rechazados</span>
                <strong><?= number_format($rejectedTickets) ?></strong>
                <small>No válidos</small>
            </div>
        </article>
    </section>

    <form class="qv-admin-filter-panel" method="get" action="/admin/tickets">
        <div class="qv-admin-filter-grid">
            <div>
                <label for="filterRound" class="form-label">Jornada</label>
                <select name="round_id" id="filterRound" class="form-select">
                    <option value="0">Todas</option>

                    <?php foreach ($rounds as $round): ?>
                        <?php
                        $id = (int)$round['id'];
                        $name = (string)($round['name'] ?: ('Jornada ' . (string)($round['number'] ?? '')));
                        $label = $name . ' · ' . (string)($round['league_name'] ?? '');
                        ?>
                        <option value="<?= $id ?>" <?= $filterRoundId === $id ? 'selected' : '' ?>>
                            <?= Security::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="filterStatus" class="form-label">Estado</label>
                <select name="status" id="filterStatus" class="form-select">
                    <option value="ALL" <?= $fStatus === 'ALL' || $fStatus === '' ? 'selected' : '' ?>>Todos</option>
                    <option value="PENDING" <?= $fStatus === 'PENDING' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="PAID" <?= $fStatus === 'PAID' ? 'selected' : '' ?>>Pagado</option>
                    <option value="REJECTED" <?= $fStatus === 'REJECTED' ? 'selected' : '' ?>>Rechazado</option>
                </select>
            </div>

            <div>
                <label for="filterFrom" class="form-label">Desde</label>
                <input
                    type="date"
                    name="from"
                    id="filterFrom"
                    class="form-control"
                    value="<?= Security::e($filterFrom) ?>"
                >
            </div>

            <div>
                <label for="filterTo" class="form-label">Hasta</label>
                <input
                    type="date"
                    name="to"
                    id="filterTo"
                    class="form-control"
                    value="<?= Security::e($filterTo) ?>"
                >
            </div>

            <div class="qv-admin-filter-search">
                <label for="filterQuery" class="form-label">Buscar</label>
                <div class="input-group">
                    <input
                        type="text"
                        name="q"
                        id="filterQuery"
                        class="form-control"
                        placeholder="Ticket, nombre o teléfono"
                        value="<?= Security::e($filterQuery) ?>"
                    >
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                        Buscar
                    </button>
                </div>
            </div>
        </div>
    </form>

    <?php if (empty($tickets)): ?>
        <section class="qv-admin-empty-state">
            <i class="bi bi-inbox"></i>
            <strong>No se encontraron tickets.</strong>
            <span>Modifica los filtros o espera nuevos registros.</span>
        </section>
    <?php else: ?>
        <section class="qv-admin-ticket-grid">
            <?php foreach ($tickets as $ticket): ?>
                <?php
                $id = (int)($ticket['id'] ?? 0);
                $code = (string)($ticket['ticket_code'] ?? '');
                $createdAt = (string)($ticket['created_at'] ?? '');
                $league = (string)($ticket['league_name'] ?? '');
                $roundName = (string)($ticket['matchday_name'] ?? '');
                $client = (string)($ticket['user_name'] ?? '');
                $phone = (string)($ticket['phone'] ?? '');
                $points = (int)($ticket['points'] ?? 0);
                $amount = (float)($ticket['total_amount'] ?? 0);
                $currency = (string)($ticket['currency'] ?? '');
                $status = (string)($ticket['status'] ?? '');
                $roundTicketNumber = (int)($ticket['round_ticket_number'] ?? 0);
                ?>

                <article class="qv-admin-ticket-modern-card">
                    <div class="qv-admin-ticket-modern-head">
                        <div>
                            <a href="/admin/tickets/show?id=<?= $id ?>" class="qv-admin-ticket-modern-code">
                                <?= Security::e($code) ?>
                            </a>
                            <span>#<?= $roundTicketNumber ?> en jornada</span>
                        </div>

                        <span class="qv-admin-status <?= $statusClass($status) ?>">
                            <?= Security::e($status) ?>
                        </span>
                    </div>

                    <div class="qv-admin-ticket-modern-body">
                        <div class="qv-admin-ticket-modern-info">
                            <span>Cliente</span>
                            <strong><?= Security::e($client) ?></strong>
                            <small><?= Security::e($phone) ?></small>
                        </div>

                        <div class="qv-admin-ticket-modern-info">
                            <span>Jornada</span>
                            <strong><?= Security::e($league) ?></strong>
                            <small><?= Security::e($roundName) ?></small>
                        </div>

                        <div class="qv-admin-ticket-modern-info">
                            <span>Fecha</span>
                            <strong><?= $createdAt !== '' ? date('d/m/y H:i', strtotime($createdAt)) : '-' ?></strong>
                            <small>Registro del ticket</small>
                        </div>

                        <div class="qv-admin-ticket-modern-info">
                            <span>Puntos</span>
                            <strong class="qv-admin-ticket-points"><?= $points ?></strong>
                            <small>Resultado actual</small>
                        </div>

                        <div class="qv-admin-ticket-modern-info">
                            <span>Monto</span>
                            <strong><?= Security::e($currency) ?> <?= number_format($amount, 2) ?></strong>
                            <small>Total registrado</small>
                        </div>
                    </div>

                    <div class="qv-admin-ticket-modern-actions">
                        <form method="post" action="/admin/tickets/update-status" class="qv-admin-ticket-status-form">
                            <?= Security::csrfInput() ?>

                            <input type="hidden" name="ticket_id" value="<?= $id ?>">

                            <select name="status" class="form-select form-select-sm qv-admin-select-compact">
                                <option value="PENDING" <?= $status === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                                <option value="PAID" <?= $status === 'PAID' ? 'selected' : '' ?>>PAID</option>
                                <option value="REJECTED" <?= $status === 'REJECTED' ? 'selected' : '' ?>>REJECTED</option>
                            </select>

                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar estado">
                                <i class="bi bi-check-lg"></i>
                                Guardar
                            </button>
                        </form>

                        <div class="qv-admin-ticket-button-group">
                            <a href="/admin/tickets/show?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                                Ver
                            </a>

                            <button type="button" onclick="confirmDeleteTicket(<?= $id ?>)" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                                Eliminar
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</section>

<script>
    function confirmDeleteTicket(id) {
        window.qvConfirmDelete(
            '¿Eliminar ticket?',
            'El ticket y sus pronósticos asociados serán eliminados.',
            function () {
                window.enviarFormularioAdmin('/admin/tickets/delete', {
                    ticket_id: id
                });
            }
        );
    }
</script>