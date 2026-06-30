<?php

declare(strict_types=1);

use App\Core\Security;

/**
 * @var array<string,mixed>            $ticket
 * @var array<int,array<string,mixed>> $items
 */

require __DIR__ . '/../partials/nav.php';

$items = $items ?? [];
$totalPoints = 0;
$processedItems = [];

foreach ($items as $item) {
    $status = (string)($item['match_status'] ?? '');
    $officialResult = $item['result_outcome'] ?? null;

    if (!$officialResult && ($status === 'FINISHED' || $status === 'CLOSED')) {
        if (is_numeric($item['home_score'] ?? null) && is_numeric($item['away_score'] ?? null)) {
            if ((int)$item['home_score'] > (int)$item['away_score']) {
                $officialResult = 'L';
            } elseif ((int)$item['home_score'] < (int)$item['away_score']) {
                $officialResult = 'V';
            } else {
                $officialResult = 'E';
            }
        }
    }

    $userPick = strtoupper((string)($item['selection'] ?? ''));
    $isHit = false;
    $points = 0;

    if ($status !== 'CANCELLED' && $officialResult) {
        if ($userPick === $officialResult) {
            $isHit = true;
            $points = 1;
            $totalPoints++;
        }
    }

    $item['_official_result'] = $officialResult;
    $item['_user_pick'] = $userPick;
    $item['_is_hit'] = $isHit;
    $item['_points'] = $points;

    $processedItems[] = $item;
}

$status = (string)($ticket['status'] ?? '');
$statusClass = match ($status) {
    'PAID' => 'qv-status-paid',
    'PENDING' => 'qv-status-pending',
    'CANCELLED', 'REJECTED' => 'qv-status-danger',
    default => 'qv-status-muted',
};

$ticketCode = (string)($ticket['ticket_code'] ?? '');
?>

<section class="admin-mobile-page qv-admin-ticket-detail-page">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Detalle de ticket</span>
            <h1>Ticket <?= Security::e($ticketCode) ?></h1>
            <p>
                Consulta datos del cliente, auditoría, pago y pronósticos asociados.
            </p>
        </div>

        <a href="/admin/tickets" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Volver al listado
        </a>
    </header>

    <section class="qv-admin-ticket-detail-hero">
        <div class="qv-admin-ticket-detail-code">
            <span>Código de ticket</span>
            <strong><?= Security::e($ticketCode) ?></strong>
            <small>ID interno: <?= (int)($ticket['id'] ?? 0) ?></small>
        </div>

        <div class="qv-admin-ticket-detail-score">
            <span>Aciertos totales</span>
            <strong><?= $totalPoints ?></strong>
            <small><?= number_format(count($processedItems)) ?> pronósticos</small>
        </div>

        <div class="qv-admin-ticket-detail-status">
            <span>Estado</span>
            <strong class="qv-admin-status <?= $statusClass ?>">
                <?= Security::e($status) ?>
            </strong>
        </div>
    </section>

    <section class="qv-admin-ticket-info-grid">
        <article class="qv-admin-info-card">
            <div class="qv-admin-info-card-icon">
                <i class="bi bi-calendar-event"></i>
            </div>

            <div>
                <span>Jornada</span>
                <strong>
                    <?= Security::e((string)($ticket['league_name'] ?? '-')) ?>
                    ·
                    <?= Security::e((string)($ticket['matchday_name'] ?? '-')) ?>
                </strong>

                <small>
                    Emitido:
                    <?= !empty($ticket['created_at']) ? date('d/m/Y H:i:s', strtotime((string)$ticket['created_at'])) : '-' ?>
                </small>
            </div>
        </article>

        <article class="qv-admin-info-card">
            <div class="qv-admin-info-card-icon">
                <i class="bi bi-person-fill"></i>
            </div>

            <div>
                <span>Cliente</span>
                <strong><?= Security::e((string)($ticket['user_name'] ?? '-')) ?></strong>

                <?php if (!empty($ticket['phone'])): ?>
                    <small>
                        <a href="tel:<?= Security::e((string)$ticket['phone']) ?>">
                            <i class="bi bi-telephone"></i>
                            <?= Security::e((string)$ticket['phone']) ?>
                        </a>
                    </small>
                <?php else: ?>
                    <small>Sin teléfono</small>
                <?php endif; ?>

              <small>Jugador #<?= (int)($ticket['user_id'] ?? $ticket['player_id'] ?? 0) ?></small>
            </div>
        </article>

        <article class="qv-admin-info-card">
            <div class="qv-admin-info-card-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <div>
                <span>Auditoría</span>
               <?= Security::e((string)($ticket['purchase_ip_address'] ?? $ticket['ip_address'] ?? 'N/A')) ?>

                <small>
                    País:
                    <?= Security::e((string)($ticket['purchase_country'] ?? '-')) ?>
                </small>

                <small>
                    Ref:
                 <?= Security::e((string)($ticket['session_code'] ?? $ticket['session_token'] ?? 'N/A')) ?>
                </small>
            </div>
        </article>

        <article class="qv-admin-info-card">
            <div class="qv-admin-info-card-icon">
                <i class="bi bi-cash-stack"></i>
            </div>

            <div>
                <span>Monto total</span>
                <strong>
                    <?= number_format((float)($ticket['net_amount'] ?? 0), 2) ?>
                    <?= Security::e((string)($ticket['payment_currency'] ?? 'MXN')) ?>
                </strong>

                <small>Total registrado para el ticket.</small>
            </div>
        </article>
    </section>

    <section class="qv-admin-panel qv-admin-ticket-picks-panel">
        <div class="qv-admin-panel-head">
            <div>
                <span class="qv-admin-eyebrow">Pronósticos</span>
                <h2>Detalle de selecciones</h2>
            </div>

            <span class="qv-admin-status qv-status-soft">
                <?= $totalPoints ?> acierto(s)
            </span>
        </div>

        <?php if (empty($processedItems)): ?>
            <div class="qv-admin-empty-state">
                <i class="bi bi-list-check"></i>
                <strong>No hay partidos registrados para este ticket.</strong>
                <span>Cuando existan pronósticos aparecerán aquí.</span>
            </div>
        <?php else: ?>
            <div class="qv-admin-pick-list">
                <?php foreach ($processedItems as $item): ?>
                    <?php
                    $itemStatus = (string)($item['match_status'] ?? '');
                    $officialResult = $item['_official_result'];
                    $userPick = (string)$item['_user_pick'];
                    $isHit = (bool)$item['_is_hit'];
                    $points = (int)$item['_points'];

                    $homeLogo = (string)($item['local_logo'] ?? '');
                    $awayLogo = (string)($item['visitor_logo'] ?? '');

                    $pickClass = $isHit ? 'is-hit' : ($officialResult ? 'is-miss' : 'is-pending');
                    ?>

                    <article class="qv-admin-pick-card <?= $pickClass ?>">
                        <div class="qv-admin-pick-time">
                            <span>
                                <?= !empty($item['kickoff_at']) ? date('d/m H:i', strtotime((string)$item['kickoff_at'])) : '-' ?>
                            </span>

                            <?php if ($itemStatus === 'LIVE'): ?>
                                <strong class="qv-admin-live-badge">LIVE</strong>
                            <?php endif; ?>
                        </div>

                        <div class="qv-admin-pick-teams">
                            <div class="qv-admin-pick-team qv-admin-pick-team-home">
                                <strong><?= Security::e((string)($item['local_team'] ?? '')) ?></strong>

                                <?php if ($homeLogo !== ''): ?>
                                    <img src="<?= Security::e($homeLogo) ?>" alt="" class="qv-admin-ticket-team-logo">
                                <?php endif; ?>
                            </div>

                            <div class="qv-admin-pick-score">
                                <?php if (($itemStatus === 'FINISHED' || $itemStatus === 'LIVE') && isset($item['home_score'])): ?>
                                    <strong>
                                        <?= Security::e((string)$item['home_score']) ?>
                                        -
                                        <?= Security::e((string)$item['away_score']) ?>
                                    </strong>
                                <?php else: ?>
                                    <span>vs</span>
                                <?php endif; ?>
                            </div>

                            <div class="qv-admin-pick-team qv-admin-pick-team-away">
                                <?php if ($awayLogo !== ''): ?>
                                    <img src="<?= Security::e($awayLogo) ?>" alt="" class="qv-admin-ticket-team-logo">
                                <?php endif; ?>

                                <strong><?= Security::e((string)($item['visitor_team'] ?? '')) ?></strong>
                            </div>
                        </div>

                        <div class="qv-admin-pick-result">
                            <div>
                                <span>Selección</span>
                                <strong><?= Security::e($userPick) ?></strong>
                            </div>

                            <div>
                                <span>Oficial</span>

                                <?php if ($officialResult): ?>
                                    <strong class="<?= $isHit ? 'text-success' : 'text-danger' ?>">
                                        <?= Security::e((string)$officialResult) ?>
                                    </strong>
                                <?php elseif ($itemStatus === 'CANCELLED'): ?>
                                    <strong>Cancelado</strong>
                                <?php else: ?>
                                    <strong>-</strong>
                                <?php endif; ?>
                            </div>

                            <div>
                                <span>Punto</span>

                                <?php if ($officialResult): ?>
                                    <?php if ($points > 0): ?>
                                        <strong class="text-success">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </strong>
                                    <?php else: ?>
                                        <strong class="text-muted">
                                            <i class="bi bi-x-circle"></i>
                                        </strong>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <strong>-</strong>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="qv-admin-panel-note">
                Los resultados se actualizan según el reporte oficial de la liga.
            </div>
        <?php endif; ?>
    </section>
</section>