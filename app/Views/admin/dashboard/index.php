<?php

declare(strict_types=1);

use App\Core\Security;

/** @var array<string,mixed> $stats */
/** @var array<int,array<string,mixed>> $recentTickets */
/** @var array<int,array<string,mixed>> $rounds */
/** @var array<string,mixed> $filters */

$fromValue = Security::e($filters['from'] ?? '');
$toValue = Security::e($filters['to'] ?? '');

$totalTickets = (int)($stats['total_tickets'] ?? 0);
$paidTickets = (int)($stats['paid_tickets'] ?? 0);
$pendingTickets = max(0, $totalTickets - $paidTickets);
$paidRate = $totalTickets > 0 ? round(($paidTickets / $totalTickets) * 100) : 0;

require __DIR__ . '/../partials/nav.php';
?>

<div class="admin-mobile-page qv-admin-dashboard">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Resumen operativo</span>
            <h1>Panel administrador</h1>
            <p>
                Control rápido de tickets, jornadas, pagos y actividad reciente.
            </p>
        </div>

        <form class="qv-admin-filter-card" method="get" action="/admin">
            <div>
                <label for="from" class="form-label">Desde</label>
                <input
                    type="date"
                    name="from"
                    id="from"
                    class="form-control form-control-sm"
                    value="<?= $fromValue ?>"
                >
            </div>

            <div>
                <label for="to" class="form-label">Hasta</label>
                <input
                    type="date"
                    name="to"
                    id="to"
                    class="form-control form-control-sm"
                    value="<?= $toValue ?>"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-funnel-fill me-1"></i>
                Filtrar
            </button>
        </form>
    </header>

    <section class="qv-admin-quick-grid" aria-label="Acciones rápidas">
        <a href="/admin/rounds" class="qv-admin-action-card">
            <span class="qv-admin-action-icon">
                <i class="bi bi-calendar-plus"></i>
            </span>
            <span>
                <strong>Gestionar jornadas</strong>
                <small>Crear, editar y cerrar fechas</small>
            </span>
        </a>

        <a href="/admin/tickets" class="qv-admin-action-card">
            <span class="qv-admin-action-icon">
                <i class="bi bi-receipt"></i>
            </span>
            <span>
                <strong>Revisar tickets</strong>
                <small>Validar pagos y estados</small>
            </span>
        </a>

        <a href="/admin/promotions" class="qv-admin-action-card">
            <span class="qv-admin-action-icon">
                <i class="bi bi-megaphone-fill"></i>
            </span>
            <span>
                <strong>Promociones</strong>
                <small>Activar campañas visibles</small>
            </span>
        </a>

        <a href="/admin/settings" class="qv-admin-action-card">
            <span class="qv-admin-action-icon">
                <i class="bi bi-gear-fill"></i>
            </span>
            <span>
                <strong>Configuración</strong>
                <small>Precios, país y contacto</small>
            </span>
        </a>
    </section>

    <section class="qv-admin-kpi-grid" aria-label="Indicadores principales">
        <article class="qv-admin-kpi-card">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-ticket-perforated-fill"></i>
            </div>

            <div>
                <span>Tickets totales</span>
                <strong><?= number_format($totalTickets) ?></strong>
                <small>Dentro del rango seleccionado</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-success">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>

            <div>
                <span>Tickets pagados</span>
                <strong><?= number_format($paidTickets) ?></strong>
                <small><?= $paidRate ?>% de conversión a pago</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-warning">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-hourglass-split"></i>
            </div>

            <div>
                <span>Pendientes</span>
                <strong><?= number_format($pendingTickets) ?></strong>
                <small>Tickets por revisar</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-money">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-cash-stack"></i>
            </div>

            <div>
                <span>Recaudado</span>

                <?php if (empty($stats['amount_by_currency'])): ?>
                    <strong>0.00</strong>
                    <small>Sin montos registrados</small>
                <?php else: ?>
                    <div class="qv-admin-money-list">
                        <?php foreach ($stats['amount_by_currency'] as $currency => $amount): ?>
                            <div>
                                <strong>
                                    <?= Security::e($currency) ?> <?= number_format((float)$amount, 2) ?>
                                </strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small>Por moneda</small>
                <?php endif; ?>
            </div>
        </article>
    </section>

    <section class="qv-admin-panel">
        <div class="qv-admin-panel-head">
            <div>
                <span class="qv-admin-eyebrow">Actividad reciente</span>
                <h2>Últimos tickets</h2>
            </div>

            <a href="/admin/tickets" class="btn btn-outline-primary btn-sm">
                Ver todos
            </a>
        </div>

        <?php if (empty($recentTickets)): ?>
            <div class="qv-admin-empty-state">
                <i class="bi bi-inbox"></i>
                <strong>No hay tickets en el rango seleccionado.</strong>
                <span>Cuando se generen tickets, aparecerán aquí.</span>
            </div>
        <?php else: ?>
            <div class="qv-admin-ticket-list">
                <?php foreach ($recentTickets as $ticket): ?>
                    <?php
                    $ticketId = (int)($ticket['id'] ?? 0);
                    $ticketCode = (string)($ticket['ticket_code'] ?? '');
                    $roundName = (string)($ticket['round_name'] ?? '');
                    $roundNumber = (int)($ticket['round_number'] ?? 0);
                    $leagueName = (string)($ticket['league_name'] ?? '');
                    $userName = (string)($ticket['user_name'] ?? '');
                    $phone = (string)($ticket['phone'] ?? '');
                    $currency = (string)($ticket['currency'] ?? '');
                    $amount = (float)($ticket['total_amount'] ?? 0);
                    $status = (string)($ticket['status'] ?? '');
                    $createdAt = (string)($ticket['created_at'] ?? '');

                    $statusClass = match ($status) {
                        'PAID' => 'qv-status-paid',
                        'PENDING' => 'qv-status-pending',
                        default => 'qv-status-muted',
                    };
                    ?>

                    <article class="qv-admin-ticket-card">
                        <div class="qv-admin-ticket-main">
                            <a href="/admin/tickets/show?id=<?= $ticketId ?>" class="qv-admin-ticket-code">
                                <?= Security::e($ticketCode) ?>
                            </a>

                            <span>
                                #<?= (int)($ticket['round_ticket_number'] ?? 0) ?> en jornada
                            </span>
                        </div>

                        <div>
                            <strong><?= Security::e($userName) ?></strong>
                            <span><?= Security::e($phone) ?></span>
                        </div>

                        <div>
                            <strong><?= Security::e($roundName) ?></strong>
                            <span>
                                Jornada <?= $roundNumber ?> · <?= Security::e($leagueName) ?>
                            </span>
                        </div>

                        <div>
                            <strong><?= Security::e($currency) ?> <?= number_format($amount, 2) ?></strong>
                            <span><?= Security::e($createdAt) ?></span>
                        </div>

                        <div>
                            <span class="qv-admin-status <?= $statusClass ?>">
                                <?= Security::e($status) ?>
                            </span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>