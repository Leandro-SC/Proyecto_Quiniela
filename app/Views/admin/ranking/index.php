<?php

declare(strict_types=1);

use App\Core\Security;

/**
 * @var array<int,array<string,mixed>>  $matchdays
 * @var int                             $selectedMatchdayId
 * @var string                          $statusFilter
 * @var string                          $searchQuery
 * @var array<int,array<string,mixed>>  $roundRanking
 * @var array<string,mixed>|null        $roundSummary
 * @var array<int,array<string,mixed>>  $firstWinners
 * @var array<int,array<string,mixed>>  $secondWinners
 */

$pageTitle = $pageTitle ?? 'Ranking';
require __DIR__ . '/../partials/nav.php';

$matchdays = $matchdays ?? [];
$roundRanking = $roundRanking ?? [];
$firstWinners = $firstWinners ?? [];
$secondWinners = $secondWinners ?? [];

$prize1stEach = 0.0;
$prize2ndEach = 0.0;
$totalCollected = 0.0;
$totalPrize1st = 0.0;
$totalPrize2nd = 0.0;
$idsWinners1st = [];
$idsWinners2nd = [];

if (!empty($roundSummary)) {
    $prize1stEach = (float)($roundSummary['first_prize_each'] ?? 0);
    $prize2ndEach = (float)($roundSummary['second_prize_each'] ?? 0);
    $totalCollected = (float)($roundSummary['total_collected'] ?? 0);
    $totalPrize1st = (float)($roundSummary['first_prize_total'] ?? 0);
    $totalPrize2nd = (float)($roundSummary['second_prize_total'] ?? 0);
    $idsWinners1st = $roundSummary['first_winners'] ?? [];
    $idsWinners2nd = $roundSummary['second_winners'] ?? [];
}

$statusClass = static function (string $status): string {
    return match ($status) {
        'PAID' => 'qv-status-paid',
        'PENDING' => 'qv-status-pending',
        'REJECTED' => 'qv-status-danger',
        default => 'qv-status-muted',
    };
};
?>

<section class="admin-mobile-page qv-admin-ranking-page">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Resultados y premios</span>
            <h1>Ranking y premios</h1>
            <p>
                Consulta posiciones, ganadores estimados, montos recaudados y distribución de premios por jornada.
            </p>
        </div>

        <a href="/admin/tickets" class="btn btn-outline-primary">
            <i class="bi bi-receipt me-1"></i>
            Ver tickets
        </a>
    </header>

    <form class="qv-admin-filter-panel" method="get" action="/admin/ranking">
        <div class="qv-admin-ranking-filter-grid">
            <div>
                <label for="f-jornada" class="form-label">Jornada</label>
                <select id="f-jornada" name="round_id" class="form-select" onchange="this.form.submit()">
                    <option value="0">Todas</option>

                    <?php foreach ($matchdays as $md): ?>
                        <?php
                        $mdId = (int)($md['id'] ?? 0);
                        $number = (int)($md['round_number'] ?? 0);
                        $name = !empty($md['name']) ? (string)$md['name'] : 'Jornada ' . $number;
                        $league = (string)($md['league_name'] ?? 'Liga');
                        $label = $league . ' · ' . $name;
                        ?>
                        <option value="<?= $mdId ?>" <?= $selectedMatchdayId === $mdId ? 'selected' : '' ?>>
                            <?= Security::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="f-estado" class="form-label">Estado</label>
                <select id="f-estado" name="status" class="form-select" onchange="this.form.submit()">
                    <option value="PAID" <?= $statusFilter === 'PAID' ? 'selected' : '' ?>>Solo pagados</option>
                    <option value="PENDING" <?= $statusFilter === 'PENDING' ? 'selected' : '' ?>>Pendientes</option>
                    <option value="ALL" <?= $statusFilter === 'ALL' ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>

            <div>
                <label for="f-q" class="form-label">Buscar</label>
                <div class="input-group">
                    <input
                        type="text"
                        id="f-q"
                        name="q"
                        class="form-control"
                        value="<?= Security::e($searchQuery ?? '') ?>"
                        placeholder="Ticket, nombre..."
                    >

                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                        Buscar
                    </button>
                </div>
            </div>
        </div>
    </form>

    <?php if ($roundSummary): ?>
        <section class="qv-admin-prize-grid" aria-label="Resumen de premios">
            <article class="qv-admin-prize-card qv-admin-prize-total">
                <div class="qv-admin-prize-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>

                <div>
                    <span>Bolsa total recaudada</span>
                    <strong>$<?= number_format($totalCollected, 2) ?></strong>
                    <small>Jornada #<?= (int)$selectedMatchdayId ?></small>
                </div>
            </article>

            <article class="qv-admin-prize-card qv-admin-prize-gold">
                <div class="qv-admin-prize-header">
                    <div>
                        <span>Primer lugar</span>
                        <strong>🥇 <?= count($idsWinners1st) ?> ganador(es)</strong>
                    </div>

                    <em>$<?= number_format($prize1stEach, 2) ?> c/u</em>
                </div>

                <?php if (empty($firstWinners)): ?>
                    <div class="qv-admin-mini-empty">Sin ganadores aún.</div>
                <?php else: ?>
                    <div class="qv-admin-winner-list">
                        <?php foreach ($firstWinners as $winner): ?>
                            <div class="qv-admin-winner-item">
                                <strong><?= Security::e((string)($winner['user_name'] ?? '')) ?></strong>
                                <span><?= Security::e((string)($winner['ticket_code'] ?? '')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <footer>Total a repartir: $<?= number_format($totalPrize1st, 2) ?></footer>
            </article>

            <article class="qv-admin-prize-card qv-admin-prize-silver">
                <div class="qv-admin-prize-header">
                    <div>
                        <span>Segundo lugar</span>
                        <strong>🥈 <?= count($idsWinners2nd) ?> ganador(es)</strong>
                    </div>

                    <em>$<?= number_format($prize2ndEach, 2) ?> c/u</em>
                </div>

                <?php if (empty($secondWinners)): ?>
                    <div class="qv-admin-mini-empty">Sin ganadores aún.</div>
                <?php else: ?>
                    <div class="qv-admin-winner-list">
                        <?php foreach ($secondWinners as $winner): ?>
                            <div class="qv-admin-winner-item">
                                <strong><?= Security::e((string)($winner['user_name'] ?? '')) ?></strong>
                                <span><?= Security::e((string)($winner['ticket_code'] ?? '')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <footer>Total a repartir: $<?= number_format($totalPrize2nd, 2) ?></footer>
            </article>
        </section>
    <?php endif; ?>

    <section class="qv-admin-panel qv-admin-ranking-panel">
        <div class="qv-admin-panel-head">
            <div>
                <span class="qv-admin-eyebrow">Tabla general</span>
                <h2>Ranking completo</h2>
            </div>

            <span class="qv-admin-ranking-count">
                <?= number_format(count($roundRanking)) ?> tickets
            </span>
        </div>

        <?php if (empty($roundRanking)): ?>
            <div class="qv-admin-empty-state">
                <i class="bi bi-trophy"></i>
                <strong>No hay tickets registrados con los filtros actuales.</strong>
                <span>Selecciona otra jornada o cambia el estado del filtro.</span>
            </div>
        <?php else: ?>
            <div class="qv-admin-ranking-list">
                <?php foreach ($roundRanking as $row): ?>
                    <?php
                    $ticketId = (int)($row['id'] ?? 0);
                    $rank = (int)($row['rank'] ?? 0);
                    $ticketCode = (string)($row['ticket_code'] ?? '');
                    $clientName = (string)($row['user_name'] ?? '');
                    $points = (int)($row['points'] ?? 0);
                    $amount = (float)($row['total_amount'] ?? 0);
                    $status = (string)($row['status'] ?? '');
                    $createdAt = (string)($row['created_at'] ?? '');

                    $medal = '';
                    $winnerClass = '';
                    $prizeToShow = 0.0;

                    if (in_array($ticketId, $idsWinners1st, true)) {
                        $medal = '🥇';
                        $winnerClass = 'is-gold';
                        $prizeToShow = $prize1stEach;
                    } elseif (in_array($ticketId, $idsWinners2nd, true)) {
                        $medal = '🥈';
                        $winnerClass = 'is-silver';
                        $prizeToShow = $prize2ndEach;
                    }
                    ?>

                    <article class="qv-admin-ranking-card <?= $winnerClass ?>">
                        <div class="qv-admin-ranking-position">
                            <span>#<?= $rank ?></span>
                            <?php if ($medal !== ''): ?>
                                <strong><?= $medal ?></strong>
                            <?php endif; ?>
                        </div>

                        <div class="qv-admin-ranking-ticket">
                            <a href="/admin/tickets/show?id=<?= $ticketId ?>">
                                <?= Security::e($ticketCode) ?>
                            </a>

                            <span>
                                <?= $createdAt !== '' ? date('d/m H:i', strtotime($createdAt)) : '-' ?>
                            </span>
                        </div>

                        <div class="qv-admin-ranking-client">
                            <span>Cliente</span>
                            <strong><?= Security::e($clientName) ?></strong>
                        </div>

                        <div class="qv-admin-ranking-points">
                            <span>Puntos</span>
                            <strong><?= $points ?></strong>
                        </div>

                        <div class="qv-admin-ranking-money">
                            <span>Monto</span>
                            <strong>$<?= number_format($amount, 2) ?></strong>
                        </div>

                        <div class="qv-admin-ranking-status">
                            <span class="qv-admin-status <?= $statusClass($status) ?>">
                                <?= Security::e($status) ?>
                            </span>
                        </div>

                        <div class="qv-admin-ranking-prize">
                            <span>Premio estimado</span>

                            <?php if ($prizeToShow > 0): ?>
                                <strong>$<?= number_format($prizeToShow, 2) ?></strong>
                            <?php else: ?>
                                <strong class="is-empty">-</strong>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>