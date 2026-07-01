<?php

declare(strict_types=1);

$currentRound = $currentRound ?? null;
$tickets = $tickets ?? [];
$matches = $matches ?? [];
$updatedAt = $updatedAt ?? date('H:i');
$estimatedPrizes = $estimatedPrizes ?? ['first' => 0, 'second' => 0];
$currencyCode = $currencyCode ?? 'USD';
$activeLeagues = $activeLeagues ?? [];
$leagueSlug = $leagueSlug ?? '';
$availableRounds = $availableRounds ?? [];
$topTickets = $topTickets ?? array_slice($tickets, 0, 3);
$statusFilter = $statusFilter ?? 'all';

$rankingStats = $rankingStats ?? [
    'total_tickets' => count($tickets),
    'total_matches' => count($matches),
    'finished_matches' => 0,
    'pending_matches' => 0,
    'total_first' => $totalPrimero ?? 0,
    'total_second' => $totalSegundo ?? 0,
    'max_points' => 0,
];

if (!function_exists('qvRankingH')) {
    function qvRankingH(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('qvRankingStatusLabel')) {
    function qvRankingStatusLabel(mixed $status): string
    {
        return match (strtoupper((string)$status)) {
            'OPEN' => 'Abierta',
            'CLOSED' => 'Cerrada',
            'FINISHED' => 'Finalizada',
            default => ucfirst(strtolower((string)$status)),
        };
    }
}

if (!function_exists('qvRankingStatusClass')) {
    function qvRankingStatusClass(mixed $status): string
    {
        return match (strtoupper((string)$status)) {
            'OPEN' => 'is-open',
            'CLOSED' => 'is-closed',
            'FINISHED' => 'is-finished',
            default => 'is-default',
        };
    }
}

$officialResults = [];

foreach ($matches as $match) {
    if (!empty($match['result_outcome'])) {
        $officialResults[(int)$match['id']] = $match['result_outcome'];
    }
}

$statusOptions = [
    'all' => 'Todas',
    'OPEN' => 'Abiertas',
    'CLOSED' => 'Cerradas',
    'FINISHED' => 'Finalizadas',
];

$roundStatus = strtoupper((string)($currentRound['status'] ?? ''));
$currentRoundName = $currentRound['custom_title'] ?? $currentRound['name'] ?? 'Ranking general';
?>

<section class="qv-ranking-page">
    <div class="container">

        <section class="qv-ranking-hero">
            <div>
                <span class="qv-ranking-eyebrow">
                    Ranking oficial
                </span>

                <h1>
                    <?= qvRankingH($currentRoundName) ?>
                </h1>

                <p>
                    Consulta posiciones, puntos, premios estimados y resultados actualizados de la jornada.
                </p>

                <div class="qv-ranking-hero-actions">
                    <a href="/verificador" class="btn btn-primary btn-lg">
                        <i class="bi bi-ticket-perforated me-1"></i>
                        Verificar mi ticket
                    </a>

                    <a href="/quinielas-anteriores" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-clock-history me-1"></i>
                        Ver histórico
                    </a>

                    <a href="javascript:location.reload()" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-arrow-clockwise me-1"></i>
                        Refrescar
                    </a>
                </div>
            </div>

            <div class="qv-ranking-prize-box">
                <div class="qv-ranking-status <?= qvRankingH(qvRankingStatusClass($roundStatus)) ?>">
                    <?= qvRankingStatusLabel($roundStatus) ?>
                </div>

                <div class="qv-ranking-prizes">
                    <article>
                        <span>🥇 1er lugar</span>
                        <strong>
                            <?= qvRankingH($currencyCode) ?>
                            <?= number_format((float)($estimatedPrizes['first'] ?? 0), 2) ?>
                        </strong>
                    </article>

                    <article>
                        <span>🥈 2do lugar</span>
                        <strong>
                            <?= qvRankingH($currencyCode) ?>
                            <?= number_format((float)($estimatedPrizes['second'] ?? 0), 2) ?>
                        </strong>
                    </article>
                </div>

                <small>
                    Premios estimados según tickets pagados.
                </small>
            </div>
        </section>

        <section class="qv-ranking-filters">
            <div class="qv-ranking-filter-title">
                <h2>
                    Cambiar ranking
                </h2>

                <p>
                    Filtra por liga, estado o jornada para encontrar rápidamente la quiniela.
                </p>
            </div>

            <?php if (!empty($activeLeagues) && count($activeLeagues) > 1): ?>
                <div class="qv-ranking-league-tabs">
                    <?php foreach ($activeLeagues as $league): ?>
                        <?php
                        $slug = (string)($league['slug'] ?? '');
                        $isActiveLeague = $slug === (string)$leagueSlug;
                        ?>

                        <a
                            href="/ranking?league=<?= qvRankingH($slug) ?>&status=<?= qvRankingH($statusFilter) ?>"
                            class="<?= $isActiveLeague ? 'is-active' : '' ?>"
                        >
                            <?= qvRankingH($league['name'] ?? 'Liga') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="/ranking" method="get" class="qv-ranking-filter-form">
                <input type="hidden" name="league" value="<?= qvRankingH($leagueSlug) ?>">

                <div>
                    <label for="status" class="form-label">
                        Estado
                    </label>

                    <select name="status" id="status" class="form-select form-select-lg">
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= qvRankingH($value) ?>" <?= (string)$statusFilter === (string)$value ? 'selected' : '' ?>>
                                <?= qvRankingH($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="round_id" class="form-label">
                        Jornada
                    </label>

                    <select name="round_id" id="round_id" class="form-select form-select-lg">
                        <?php if (empty($availableRounds)): ?>
                            <?php if ($currentRound): ?>
                                <option value="<?= (int)$currentRound['id'] ?>" selected>
                                    <?= qvRankingH($currentRound['name'] ?? 'Jornada') ?>
                                </option>
                            <?php else: ?>
                                <option value="">
                                    No hay jornadas disponibles
                                </option>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php foreach ($availableRounds as $round): ?>
                                <?php $roundId = (int)($round['id'] ?? 0); ?>

                                <option value="<?= $roundId ?>" <?= ($currentRound && (int)$currentRound['id'] === $roundId) ? 'selected' : '' ?>>
                                    <?= qvRankingH($round['name'] ?? 'Jornada') ?>
                                    <?= !empty($round['status']) ? ' · ' . qvRankingStatusLabel($round['status']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="qv-ranking-filter-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-funnel-fill me-1"></i>
                        Aplicar
                    </button>

                    <a href="/ranking" class="btn btn-outline-secondary btn-lg">
                        Limpiar
                    </a>
                </div>
            </form>
        </section>

        <section class="qv-ranking-kpis">
            <article>
                <span>Participantes</span>
                <strong><?= number_format((int)($rankingStats['total_tickets'] ?? count($tickets))) ?></strong>
                <small>Tickets pagados</small>
            </article>

            <article>
                <span>Partidos</span>
                <strong>
                    <?= (int)($rankingStats['finished_matches'] ?? 0) ?>/<?= (int)($rankingStats['total_matches'] ?? count($matches)) ?>
                </strong>
                <small><?= (int)($rankingStats['pending_matches'] ?? 0) ?> pendiente(s)</small>
            </article>

            <article>
                <span>Líderes</span>
                <strong><?= (int)($rankingStats['total_first'] ?? 0) ?></strong>
                <small>En primer lugar</small>
            </article>

            <article>
                <span>Segundos</span>
                <strong><?= (int)($rankingStats['total_second'] ?? 0) ?></strong>
                <small>En segundo lugar</small>
            </article>

            <article>
                <span>Actualizado</span>
                <strong><?= qvRankingH($updatedAt) ?></strong>
                <small>Hora local</small>
            </article>
        </section>

        <?php if ($topTickets !== []): ?>
            <section class="qv-ranking-podium">
                <?php foreach ($topTickets as $index => $ticket): ?>
                    <?php
                    $position = $index + 1;
                    $class = $position === 1 ? 'is-gold' : ($position === 2 ? 'is-silver' : 'is-bronze');
                    ?>

                    <article class="qv-ranking-podium-card <?= qvRankingH($class) ?>">
                        <div class="qv-ranking-podium-rank">
                            #<?= $position ?>
                        </div>

                        <div>
                            <h3>
                                <?= qvRankingH($ticket['user_name'] ?? 'Jugador') ?>
                            </h3>

                            <p>
                                Ticket:
                                <strong><?= qvRankingH($ticket['ticket_code'] ?? '') ?></strong>
                            </p>
                        </div>

                        <div class="qv-ranking-podium-points">
                            <?= (int)($ticket['points'] ?? 0) ?>
                            <span>pts</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="qv-ranking-table-tools">
            <div>
                <label for="ranking-search" class="form-label">
                    Buscar participante
                </label>

                <div class="input-group input-group-lg">
                    <span class="input-group-text">
                        <i class="bi bi-search"></i>
                    </span>

                    <input
                        type="text"
                        id="ranking-search"
                        class="form-control fw-bold text-uppercase"
                        placeholder="Nombre o código de ticket..."
                        autocomplete="off"
                    >
                </div>
            </div>

            <div class="qv-ranking-visible-count">
                Participantes visibles:
                <strong id="count-display"><?= count($tickets) ?></strong>
            </div>
        </section>

        <section class="qv-ranking-table-card">
            <div class="qv-ranking-table-head">
                <div>
                    <h2>
                        Tabla de posiciones
                    </h2>

                    <p>
                        Verde indica acierto. Blanco indica fallo o resultado pendiente.
                    </p>
                </div>

                <div>
                    <?= number_format(count($tickets)) ?> tickets
                </div>
            </div>

            <div class="ph-table-container">
                <table class="ph-table" id="ranking-table">
                    <thead>
                        <tr class="ph-header-blue-row">
                            <th class="ph-col-name-header">
                                PARTICIPANTE
                            </th>

                            <?php foreach ($matches as $match): ?>
                                <?php
                                $hasScore = isset($match['home_score']) &&
                                    isset($match['away_score']) &&
                                    (string)($match['result_outcome'] ?? '') !== '';
                                ?>

                                <th
                                    class="ph-col-match-header"
                                    title="<?= qvRankingH($match['home_team_name'] ?? '') ?> vs <?= qvRankingH($match['away_team_name'] ?? '') ?>"
                                >
                                    <div class="d-flex flex-column align-items-center justify-content-center gap-1">
                                        <?php if (!empty($match['home_team_logo'])): ?>
                                            <img
                                                src="<?= qvRankingH($match['home_team_logo']) ?>"
                                                class="ph-team-logo"
                                                alt="Local"
                                                loading="lazy"
                                            >
                                        <?php else: ?>
                                            <span>L</span>
                                        <?php endif; ?>

                                        <?php if ($hasScore): ?>
                                            <span class="ph-score-text">
                                                <?= (int)$match['home_score'] ?> - <?= (int)$match['away_score'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="ph-vs-text">
                                                vs
                                            </span>
                                        <?php endif; ?>

                                        <?php if (!empty($match['away_team_logo'])): ?>
                                            <img
                                                src="<?= qvRankingH($match['away_team_logo']) ?>"
                                                class="ph-team-logo"
                                                alt="Visita"
                                                loading="lazy"
                                            >
                                        <?php else: ?>
                                            <span>V</span>
                                        <?php endif; ?>
                                    </div>
                                </th>
                            <?php endforeach; ?>

                            <th class="ph-pts-header">
                                PTS
                            </th>
                        </tr>

                        <tr class="ph-row-dark">
                            <th class="ph-col-name-header text-end text-white px-2">
                                RESULTADOS »
                            </th>

                            <?php foreach ($matches as $match): ?>
                                <th class="text-white">
                                    <?= qvRankingH($match['result_outcome'] ?? '-') ?>
                                </th>
                            <?php endforeach; ?>

                            <th class="ph-pts-header">
                                -
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($tickets === []): ?>
                            <tr>
                                <td colspan="<?= count($matches) + 2 ?>" class="p-4 text-center">
                                    No hay datos disponibles para esta jornada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $index => $ticket): ?>
                                <?php
                                $rank = $index + 1;
                                $picks = is_array($ticket['picks'] ?? null) ? $ticket['picks'] : [];

                                $pointsClass = 'ph-rank-std';

                                if ($rank === 1) {
                                    $pointsClass = 'ph-rank-1';
                                } elseif ($rank === 2 || $rank === 3) {
                                    $pointsClass = 'ph-rank-2';
                                }
                                ?>

                                <tr class="ranking-row">
                                    <td class="ph-cell-name">
                                        <span class="ph-rank-number">
                                            <?= $rank ?>
                                        </span>

                                        <span class="ph-user-name">
                                            <?= qvRankingH(mb_strtoupper((string)($ticket['user_name'] ?? ''))) ?>
                                        </span>

                                        <span class="d-none search-data">
                                            <?= qvRankingH(mb_strtoupper(
                                                (string)($ticket['user_name'] ?? '') .
                                                ' ' .
                                                (string)($ticket['ticket_code'] ?? '')
                                            )) ?>
                                        </span>
                                    </td>

                                    <?php foreach ($matches as $match): ?>
                                        <?php
                                        $matchId = (int)($match['id'] ?? 0);
                                        $userPick = $picks[$matchId] ?? '';
                                        $official = $officialResults[$matchId] ?? null;
                                        $cellClass = 'ph-miss';

                                        if ($userPick !== '' && $official !== null && $userPick === $official) {
                                            $cellClass = 'ph-hit';
                                        }
                                        ?>

                                        <td class="ph-cell-pick <?= qvRankingH($cellClass) ?>">
                                            <?= qvRankingH((string)($userPick ?: '-')) ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <td class="ph-pts <?= qvRankingH($pointsClass) ?>">
                                        <?= (int)($ticket['points'] ?? 0) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('ranking-search');
        const tableRows = document.querySelectorAll('.ranking-row');
        const countDisplay = document.getElementById('count-display');

        if (!searchInput) {
            return;
        }

        searchInput.addEventListener('keyup', function (event) {
            const term = String(event.target.value || '').toUpperCase();
            let visibleCount = 0;

            tableRows.forEach(function (row) {
                const searchNode = row.querySelector('.search-data');
                const text = searchNode ? searchNode.textContent : '';

                if (text.includes(term)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countDisplay) {
                countDisplay.textContent = String(visibleCount);
            }
        });
    });
</script>

<style>
    .qv-ranking-page {
        min-height: 80vh;
        padding: 2rem 0 4rem;
        color: #0f172a;
        background:
            radial-gradient(circle at top left, rgba(245, 158, 11, 0.15), transparent 24rem),
            linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
    }

    .qv-ranking-hero {
        display: grid;
        gap: 1.2rem;
        align-items: stretch;
        margin-bottom: 1.2rem;
    }

    .qv-ranking-hero > div:first-child,
    .qv-ranking-prize-box,
    .qv-ranking-filters,
    .qv-ranking-kpis article,
    .qv-ranking-podium-card,
    .qv-ranking-table-tools,
    .qv-ranking-table-card {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 1.25rem;
        background: #ffffff;
        box-shadow: 0 18px 48px rgba(15, 23, 42, 0.1);
    }

    .qv-ranking-hero > div:first-child {
        padding: 1.4rem;
    }

    .qv-ranking-eyebrow {
        display: inline-flex;
        margin-bottom: 0.75rem;
        color: #f59e0b;
        font-size: 0.78rem;
        font-weight: 950;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .qv-ranking-hero h1 {
        margin: 0;
        color: #0f172a;
        font-size: clamp(2rem, 6vw, 4rem);
        font-weight: 950;
        line-height: 0.96;
        letter-spacing: -0.06em;
    }

    .qv-ranking-hero p {
        max-width: 760px;
        margin: 0.9rem 0 0;
        color: #64748b;
        line-height: 1.65;
    }

    .qv-ranking-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.7rem;
        margin-top: 1.2rem;
    }

    .qv-ranking-prize-box {
        padding: 1.2rem;
    }

    .qv-ranking-status {
        display: inline-flex;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 950;
        text-transform: uppercase;
    }

    .qv-ranking-status.is-open {
        color: #065f46;
        background: #d1fae5;
    }

    .qv-ranking-status.is-closed {
        color: #92400e;
        background: #fef3c7;
    }

    .qv-ranking-status.is-finished {
        color: #1e40af;
        background: #dbeafe;
    }

    .qv-ranking-prizes {
        display: grid;
        gap: 0.8rem;
        margin-top: 1rem;
    }

    .qv-ranking-prizes article {
        padding: 1rem;
        border-radius: 1rem;
        background: #f8fafc;
    }

    .qv-ranking-prizes span {
        display: block;
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 950;
        text-transform: uppercase;
    }

    .qv-ranking-prizes strong {
        display: block;
        margin-top: 0.35rem;
        color: #0f172a;
        font-size: 1.55rem;
        font-weight: 950;
        line-height: 1;
    }

    .qv-ranking-prize-box small {
        display: block;
        margin-top: 0.8rem;
        color: #64748b;
    }

    .qv-ranking-filters {
        margin-bottom: 1.2rem;
        padding: 1.15rem;
    }

    .qv-ranking-filter-title h2 {
        margin: 0;
        font-size: 1.45rem;
        font-weight: 950;
        letter-spacing: -0.04em;
    }

    .qv-ranking-filter-title p {
        margin: 0.35rem 0 0;
        color: #64748b;
    }

    .qv-ranking-league-tabs {
        display: flex;
        gap: 0.55rem;
        margin-top: 1rem;
        padding-bottom: 0.35rem;
        overflow-x: auto;
    }

    .qv-ranking-league-tabs a {
        flex: 0 0 auto;
        padding: 0.55rem 0.9rem;
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 999px;
        color: #0f172a;
        background: #f8fafc;
        font-weight: 900;
        text-decoration: none;
    }

    .qv-ranking-league-tabs a.is-active,
    .qv-ranking-league-tabs a:hover {
        color: #111827;
        border-color: rgba(245, 158, 11, 0.5);
        background: #fef3c7;
    }

    .qv-ranking-filter-form {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.8rem;
        margin-top: 1rem;
    }

    .qv-ranking-filter-form .form-label,
    .qv-ranking-table-tools .form-label {
        color: #64748b;
        font-size: 0.76rem;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .qv-ranking-filter-actions {
        display: grid;
        gap: 0.55rem;
        align-self: end;
    }

    .qv-ranking-kpis {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.85rem;
        margin-bottom: 1.2rem;
    }

    .qv-ranking-kpis article {
        padding: 1rem;
    }

    .qv-ranking-kpis span {
        display: block;
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 950;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .qv-ranking-kpis strong {
        display: block;
        margin-top: 0.25rem;
        color: #f59e0b;
        font-size: 1.45rem;
        font-weight: 950;
        line-height: 1;
    }

    .qv-ranking-kpis small {
        display: block;
        margin-top: 0.35rem;
        color: #64748b;
    }

    .qv-ranking-podium {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.85rem;
        margin-bottom: 1.2rem;
    }

    .qv-ranking-podium-card {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 0.85rem;
        align-items: center;
        padding: 1rem;
    }

    .qv-ranking-podium-card.is-gold {
        border-color: rgba(245, 158, 11, 0.65);
    }

    .qv-ranking-podium-rank {
        display: grid;
        place-items: center;
        width: 3rem;
        height: 3rem;
        border-radius: 1rem;
        color: #111827;
        background: #fbbf24;
        font-weight: 950;
    }

    .qv-ranking-podium-card h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 950;
    }

    .qv-ranking-podium-card p {
        margin: 0.25rem 0 0;
        color: #64748b;
        font-size: 0.85rem;
    }

    .qv-ranking-podium-points {
        color: #f59e0b;
        font-size: 1.55rem;
        font-weight: 950;
        line-height: 1;
        text-align: right;
    }

    .qv-ranking-podium-points span {
        display: block;
        color: #64748b;
        font-size: 0.72rem;
        text-transform: uppercase;
    }

    .qv-ranking-table-tools {
        display: grid;
        gap: 0.8rem;
        margin-bottom: 1rem;
        padding: 1rem;
    }

    .qv-ranking-visible-count {
        color: #64748b;
        font-weight: 800;
    }

    .qv-ranking-visible-count strong {
        color: #f59e0b;
    }

    .qv-ranking-table-card {
        overflow: hidden;
    }

    .qv-ranking-table-head {
        display: grid;
        gap: 0.7rem;
        padding: 1rem;
        border-bottom: 1px solid rgba(15, 23, 42, 0.1);
    }

    .qv-ranking-table-head h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 950;
    }

    .qv-ranking-table-head p {
        margin: 0.25rem 0 0;
        color: #64748b;
    }

    .qv-ranking-table-head > div:last-child {
        color: #f59e0b;
        font-weight: 950;
    }

    .ph-table-container {
        width: 100%;
        overflow-x: auto;
        background: #fff;
    }

    .ph-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
        color: #111827;
    }

    .ph-table th,
    .ph-table td {
        border: 1px solid #94a3b8;
        text-align: center;
        vertical-align: middle;
        padding: 6px 4px;
    }

    .ph-header-blue-row th {
        background-color: #0f172a;
        color: white;
        font-size: 12px;
    }

    .ph-row-dark th {
        background-color: #111827;
        color: white;
        font-size: 12px;
    }

    .ph-col-name-header,
    .ph-cell-name {
        text-align: left;
        padding-left: 10px !important;
        min-width: 220px;
        font-size: 13px;
        font-weight: 900;
        white-space: nowrap;
    }

    .ph-col-match-header {
        min-width: 54px;
    }

    .ph-team-logo {
        width: 28px;
        height: 28px;
        object-fit: contain;
    }

    .ph-vs-text {
        color: #f59e0b;
        font-size: 9px;
        font-weight: 900;
    }

    .ph-score-text {
        padding: 1px 4px;
        border-radius: 4px;
        color: #fff;
        background: #000;
        font-size: 11px;
        font-weight: 900;
    }

    .ph-cell-pick {
        font-weight: 950;
        font-size: 14px;
    }

    .ph-hit {
        background-color: #22c55e !important;
        color: #052e16 !important;
    }

    .ph-miss {
        background-color: #fff !important;
        color: #111827;
    }

    .ph-pts-header,
    .ph-pts {
        width: 62px;
        font-size: 15px;
        font-weight: 950;
    }

    .ph-rank-number {
        color: #1d4ed8;
        margin-right: 5px;
        font-size: 14px;
        font-weight: 950;
    }

    .ph-rank-1 {
        background-color: #fbbf24 !important;
        color: #111827 !important;
    }

    .ph-rank-2 {
        background-color: #67e8f9 !important;
        color: #111827 !important;
    }

    .ph-rank-std {
        background-color: #fef9c3 !important;
        color: #111827 !important;
    }

    @media (min-width: 768px) {
        .qv-ranking-hero {
            grid-template-columns: 1fr 360px;
        }

        .qv-ranking-prizes {
            grid-template-columns: 1fr;
        }

        .qv-ranking-filter-form {
            grid-template-columns: 1fr 1.4fr auto;
            align-items: end;
        }

        .qv-ranking-filter-actions {
            grid-template-columns: auto auto;
        }

        .qv-ranking-kpis {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .qv-ranking-podium {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .qv-ranking-table-tools {
            grid-template-columns: 1fr auto;
            align-items: end;
        }

        .qv-ranking-table-head {
            grid-template-columns: 1fr auto;
            align-items: center;
        }
    }

    @media (max-width: 768px) {
        .qv-ranking-page {
            padding-top: 1rem;
        }

        .qv-ranking-hero > div:first-child,
        .qv-ranking-prize-box,
        .qv-ranking-filters,
        .qv-ranking-table-tools {
            border-radius: 1rem;
        }

        .ph-table-container {
            overflow-x: auto;
        }

        .ph-table {
            min-width: 760px;
        }
    }
</style>