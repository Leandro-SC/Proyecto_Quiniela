<?php

declare(strict_types=1);

/**
 * Histórico público guiado.
 *
 * @var array<string,mixed> $filters
 * @var array<string,string> $periodOptions
 * @var array<int,array<string,mixed>> $availableLeagues
 * @var array<int,array<string,mixed>> $roundCards
 * @var array<string,mixed>|null $selectedRound
 * @var array<int,array<string,mixed>> $tickets
 * @var array<int,array<string,mixed>> $matches
 * @var array<int,array<string,mixed>> $winners
 * @var array<string,mixed> $roundSummary
 * @var array<string,float> $prizes
 * @var string $currencyCode
 */

$filters = $filters ?? [
    'league' => '',
    'period' => 'recent',
    'q' => '',
    'round_id' => 0,
];

$periodOptions = $periodOptions ?? [];
$statusOptions = $statusOptions ?? [
    'all' => 'Todas',
    'OPEN' => 'Abiertas',
    'CLOSED' => 'Cerradas',
    'FINISHED' => 'Finalizadas',
];
$availableLeagues = $availableLeagues ?? [];
$roundCards = $roundCards ?? [];
$selectedRound = $selectedRound ?? null;
$tickets = $tickets ?? [];
$matches = $matches ?? [];
$winners = $winners ?? [];
$roundSummary = $roundSummary ?? [];
$prizes = $prizes ?? ['first' => 0.0, 'second' => 0.0];
$currencyCode = $currencyCode ?? 'USD';

if (!function_exists('qvHistoryH')) {
    /**
     * Escapa salida HTML.
     *
     * @param mixed $value Valor.
     * @return string
     */
    function qvHistoryH(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('qvHistoryDate')) {
    /**
     * Formatea fecha.
     *
     * @param mixed $date Fecha.
     * @return string
     */
    function qvHistoryDate(mixed $date): string
    {
        $timestamp = strtotime((string)$date);

        if (!$timestamp) {
            return '-';
        }

        return date('d/m/Y', $timestamp);
    }
}

if (!function_exists('qvHistoryStatusLabel')) {
    /**
     * Traduce estado.
     *
     * @param mixed $status Estado.
     * @return string
     */
    function qvHistoryStatusLabel(mixed $status): string
    {
        return match (strtoupper((string)$status)) {
            'OPEN' => 'Abierta',
            'CLOSED' => 'Cerrada',
            'FINISHED' => 'Finalizada',
            default => ucfirst(strtolower((string)$status)),
        };
    }
}

if (!function_exists('qvHistoryPickLabel')) {
    /**
     * Traduce pronóstico.
     *
     * @param mixed $pick Pronóstico.
     * @return string
     */
    function qvHistoryPickLabel(mixed $pick): string
    {
        return match (strtoupper((string)$pick)) {
            'L' => 'Local',
            'E' => 'Empate',
            'V' => 'Visita',
            default => '-',
        };
    }
}

if (!function_exists('qvHistoryBuildUrl')) {
    /**
     * Construye URL de histórico conservando filtros.
     *
     * @param array<string,mixed> $filters Filtros.
     * @param array<string,mixed> $extra Parámetros extra.
     * @return string
     */
    function qvHistoryBuildUrl(array $filters, array $extra = []): string
    {
        $params = [];

        if (($filters['league'] ?? '') !== '') {
            $params['league'] = (string)$filters['league'];
        }

        if (($filters['period'] ?? '') !== '') {
            $params['period'] = (string)$filters['period'];
        }

        if (($filters['status'] ?? '') !== '') {
            $params['status'] = (string)$filters['status'];
        }

        if (($filters['q'] ?? '') !== '') {
            $params['q'] = (string)$filters['q'];
        }

        foreach ($extra as $key => $value) {
            if ($value === null || $value === '') {
                unset($params[$key]);
                continue;
            }

            $params[$key] = $value;
        }

        return '/quinielas-anteriores' . ($params !== [] ? '?' . http_build_query($params) : '');
    }
}

$officialResults = [];

foreach ($matches as $match) {
    $officialResults[(int)$match['id']] = $match['result_outcome'] ?? null;
}

$totalTickets = (int)($roundSummary['total_tickets'] ?? count($tickets));
$totalMatches = (int)($roundSummary['total_matches'] ?? count($matches));
$finishedMatches = (int)($roundSummary['finished_matches'] ?? 0);
$pendingMatches = (int)($roundSummary['pending_matches'] ?? 0);
$totalCollected = (float)($roundSummary['total_collected'] ?? 0.0);
$hasSelectedRound = is_array($selectedRound);
?>

<section class="qv-history-guide-hero">
    <div class="container">
        <div class="qv-history-guide-hero__content">
            <span class="qv-public-eyebrow">
                Archivo oficial
            </span>

            <h1>
                Histórico de quinielas
            </h1>

            <p>
                Encuentra jornadas anteriores, revisa ganadores y consulta resultados
                sin perderte entre todas las quinielas.
            </p>

            <div class="qv-history-guide-hero__actions">
                <a href="/" class="btn btn-primary btn-lg">
                    <i class="bi bi-trophy-fill me-1"></i>
                    Jugar quiniela
                </a>

                <a href="/verificador" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-ticket-perforated me-1"></i>
                    Verificar ticket
                </a>
            </div>
        </div>
    </div>
</section>

<section class="qv-history-guide-page" id="qvHistoryPage" data-theme="light">
    <div class="container">


        <section class="qv-history-guide-finder">
            <div class="qv-history-guide-finder__title">
                <div>
                    <span class="qv-public-eyebrow">
                        Encuentra tu quiniela
                    </span>

                    <h2>
                        Busca por liga, periodo o ticket
                    </h2>

                    <p>
                        Por defecto mostramos solo las quinielas creadas durante el último mes.
                        Puedes cambiar el periodo cuando quieras.
                    </p>
                </div>

                <button
                    type="button"
                    class="qv-history-theme-switch"
                    id="qvHistoryThemeToggle"
                    aria-label="Cambiar modo visual"
                    aria-pressed="false">
                    <span class="qv-history-theme-switch__track">
                        <span class="qv-history-theme-switch__thumb">
                            <i class="bi bi-sun-fill"></i>
                        </span>
                    </span>

                    <span class="qv-history-theme-switch__label">
                        Modo claro
                    </span>
                </button>
            </div>

            <form method="get" action="/quinielas-anteriores" class="qv-history-guide-form">

                <div class="qv-history-filter-field">
                    <label for="status" class="form-label">
                        Estado
                    </label>

                    <select name="status" id="status" class="form-select form-select-lg">
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= qvHistoryH($value) ?>" <?= (string)($filters['status'] ?? 'all') === (string)$value ? 'selected' : '' ?>>
                                <?= qvHistoryH($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="qv-history-filter-field">
                    <label for="period" class="form-label">
                        Periodo de creación
                    </label>

                    <select name="period" id="period" class="form-select form-select-lg">
                        <?php foreach ($periodOptions as $value => $label): ?>
                            <option value="<?= qvHistoryH($value) ?>" <?= (string)$filters['period'] === (string)$value ? 'selected' : '' ?>>
                                <?= qvHistoryH($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="qv-history-filter-field qv-history-filter-field--search">
                    <label for="q" class="form-label">
                        Buscar ticket o jugador
                    </label>

                    <div class="input-group input-group-lg">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>

                        <input
                            type="search"
                            name="q"
                            id="q"
                            class="form-control"
                            value="<?= qvHistoryH($filters['q'] ?? '') ?>"
                            placeholder="Nombre, teléfono o código"
                            autocomplete="off">
                    </div>
                </div>

                <div class="qv-history-guide-form__actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-search me-1"></i>
                        Filtrar
                    </button>

                    <a href="/quinielas-anteriores" class="btn btn-outline-secondary btn-lg">
                        Limpiar
                    </a>
                </div>
            </form>

            <div class="qv-history-active-filter-note">
                <i class="bi bi-info-circle"></i>

                <span>
                    Mostrando:
                    <strong><?= qvHistoryH($periodOptions[$filters['period']] ?? 'Último mes') ?></strong>
                    · Estado:
                    <strong><?= qvHistoryH($statusOptions[$filters['status'] ?? 'all'] ?? 'Todas') ?></strong>

                    <?php if (($filters['league'] ?? '') !== ''): ?>
                        · Liga filtrada
                    <?php endif; ?>

                    <?php if (($filters['q'] ?? '') !== ''): ?>
                        · Búsqueda:
                        <strong><?= qvHistoryH($filters['q']) ?></strong>
                    <?php endif; ?>
                </span>
            </div>
        </section>


        <section class="qv-history-guide-results">
            <div class="qv-history-guide-section-title">
                <div>
                    <h2>
                        Jornadas encontradas
                    </h2>

                    <p>
                        Selecciona una quiniela para ver ganadores, premios y tabla completa.
                    </p>
                </div>

                <span>
                    <?= number_format(count($roundCards)) ?> resultado(s)
                </span>
            </div>

            <?php if ($roundCards === []): ?>
                <article class="qv-history-guide-empty">
                    <div>
                        <i class="bi bi-calendar-x"></i>
                    </div>

                    <h3>
                        No encontramos quinielas con esos filtros
                    </h3>

                    <p>
                        Prueba cambiando la liga, el periodo o limpiando la búsqueda.
                    </p>

                    <a href="/quinielas-anteriores" class="btn btn-primary">
                        Limpiar filtros
                    </a>
                </article>
            <?php else: ?>
                <div class="qv-history-round-grid">
                    <?php foreach ($roundCards as $round): ?>
                        <?php
                        $roundId = (int)($round['id'] ?? 0);
                        $isActive = $hasSelectedRound && $roundId === (int)($selectedRound['id'] ?? 0);
                        $detailUrl = qvHistoryBuildUrl($filters, ['round_id' => $roundId]);
                        $winnerName = (string)($round['summary_winner_name'] ?? '');
                        $currency = (string)($round['summary_currency'] ?? $currencyCode);
                        ?>

                        <article class="qv-history-round-item <?= $isActive ? 'is-active' : '' ?>">
                            <div class="qv-history-round-item__top">
                                <span>
                                    <?= qvHistoryH($round['league_name'] ?? 'Liga') ?>
                                </span>

                                <strong>
                                    <?= qvHistoryStatusLabel($round['status'] ?? '') ?>
                                </strong>
                            </div>

                            <h3>
                                <?= qvHistoryH($round['name'] ?? 'Jornada') ?>
                            </h3>

                            <div class="qv-history-round-item__date">
                                <i class="bi bi-calendar3"></i>
                                <?= qvHistoryDate($round['close_at'] ?? $round['updated_at'] ?? '') ?>
                            </div>

                            <div class="qv-history-round-item__stats">
                                <div>
                                    <strong><?= number_format((int)($round['summary_total_tickets'] ?? 0)) ?></strong>
                                    <span>tickets</span>
                                </div>

                                <div>
                                    <strong><?= number_format((int)($round['summary_total_matches'] ?? 0)) ?></strong>
                                    <span>partidos</span>
                                </div>

                                <div>
                                    <strong><?= qvHistoryH($currency) ?> <?= number_format((float)($round['summary_total_collected'] ?? 0), 0) ?></strong>
                                    <span>recaudado</span>
                                </div>
                            </div>

                            <div class="qv-history-round-item__winner">
                                <?php if ($winnerName !== ''): ?>
                                    <span>Ganador destacado</span>
                                    <strong>
                                        <?= qvHistoryH($winnerName) ?>
                                        ·
                                        <?= (int)($round['summary_winner_points'] ?? 0) ?> pts
                                    </strong>
                                <?php else: ?>
                                    <span>Ganador destacado</span>
                                    <strong>Por definir</strong>
                                <?php endif; ?>
                            </div>

                            <a
                                href="<?= qvHistoryH($detailUrl) ?>"
                                class="btn btn-primary w-100">
                                <?= $isActive ? 'Viendo detalle' : 'Ver detalle' ?>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!$hasSelectedRound): ?>
            <section class="qv-history-guide-hint">
                <div>
                    <i class="bi bi-hand-index-thumb"></i>
                </div>

                <h2>
                    Elige una jornada para ver el detalle
                </h2>

                <p>
                    Así evitamos mostrar demasiada información de golpe. Primero selecciona una quiniela anterior y luego verás la tabla completa.
                </p>
            </section>
        <?php else: ?>
            <section class="qv-history-detail">
                <div class="qv-history-detail__head">
                    <div>
                        <span class="qv-public-eyebrow">
                            Detalle de jornada
                        </span>

                        <h2>
                            <?= qvHistoryH($selectedRound['league_name'] ?? 'Liga') ?>
                            ·
                            <?= qvHistoryH($selectedRound['name'] ?? 'Jornada') ?>
                        </h2>

                        <p>
                            Ganadores, premios, resultados oficiales y tabla completa de participantes.
                        </p>
                    </div>

                    <a href="/quinielas-anteriores" class="btn btn-outline-light">
                        Volver al listado
                    </a>
                </div>

                <div class="qv-history-detail-kpis">
                    <article>
                        <span>Participantes</span>
                        <strong><?= number_format($totalTickets) ?></strong>
                        <small>tickets pagados</small>
                    </article>

                    <article>
                        <span>Recaudado</span>
                        <strong><?= qvHistoryH($currencyCode) ?> <?= number_format($totalCollected, 2) ?></strong>
                        <small>total de la jornada</small>
                    </article>

                    <article>
                        <span>Primer lugar</span>
                        <strong><?= qvHistoryH($currencyCode) ?> <?= number_format((float)($prizes['first'] ?? 0), 2) ?></strong>
                        <small><?= (int)($roundSummary['first_places'] ?? 0) ?> ganador(es)</small>
                    </article>

                    <article>
                        <span>Segundo lugar</span>
                        <strong><?= qvHistoryH($currencyCode) ?> <?= number_format((float)($prizes['second'] ?? 0), 2) ?></strong>
                        <small><?= (int)($roundSummary['second_places'] ?? 0) ?> ganador(es)</small>
                    </article>

                    <article>
                        <span>Partidos</span>
                        <strong><?= $finishedMatches ?>/<?= $totalMatches ?></strong>
                        <small><?= $pendingMatches ?> pendiente(s)</small>
                    </article>
                </div>

                <?php if ($winners !== []): ?>
                    <div class="qv-history-winners">
                        <?php foreach ($winners as $index => $winner): ?>
                            <?php
                            $position = $index + 1;
                            $winnerClass = $position === 1 ? 'is-gold' : ($position === 2 ? 'is-silver' : 'is-bronze');
                            ?>

                            <article class="qv-history-winner-card <?= qvHistoryH($winnerClass) ?>">
                                <div class="qv-history-winner-rank">
                                    #<?= $position ?>
                                </div>

                                <div>
                                    <h3>
                                        <?= qvHistoryH($winner['user_name'] ?? 'Jugador') ?>
                                    </h3>

                                    <p>
                                        Ticket:
                                        <strong><?= qvHistoryH($winner['ticket_code'] ?? '') ?></strong>
                                    </p>
                                </div>

                                <div class="qv-history-winner-points">
                                    <?= (int)($winner['points'] ?? 0) ?>
                                    <span>pts</span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="qv-history-table-tools">
                    <div>
                        <label for="historial-search" class="form-label">
                            Buscar dentro de esta jornada
                        </label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>

                            <input
                                type="text"
                                id="historial-search"
                                class="form-control fw-bold text-uppercase"
                                placeholder="Buscar ticket o nombre..."
                                autocomplete="off">
                        </div>
                    </div>

                    <small>
                        Participantes visibles:
                        <strong id="count-display"><?= count($tickets) ?></strong>
                    </small>
                </div>

                <div class="qv-history-table-card">
                    <div class="qv-history-table-head">
                        <div>
                            <h3>
                                Tabla completa
                            </h3>

                            <p>
                                Verde indica acierto. Blanco indica fallo o resultado pendiente.
                            </p>
                        </div>

                        <div>
                            <?= number_format(count($tickets)) ?> tickets
                        </div>
                    </div>

                    <div class="ph-table-container">
                        <table class="ph-table" id="history-table">
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
                                            title="<?= qvHistoryH($match['home_team_name'] ?? '') ?> vs <?= qvHistoryH($match['away_team_name'] ?? '') ?>">
                                            <div class="d-flex flex-column align-items-center justify-content-center gap-1">
                                                <?php if (!empty($match['home_team_logo'])): ?>
                                                    <img
                                                        src="<?= qvHistoryH($match['home_team_logo']) ?>"
                                                        class="ph-team-logo"
                                                        alt="Local"
                                                        loading="lazy">
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
                                                        src="<?= qvHistoryH($match['away_team_logo']) ?>"
                                                        class="ph-team-logo"
                                                        alt="Visita"
                                                        loading="lazy">
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
                                            <?= qvHistoryH($match['result_outcome'] ?? '-') ?>
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
                                            No hay tickets en esta jornada.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tickets as $index => $ticket): ?>
                                        <?php
                                        $rank = $index + 1;
                                        $picks = is_array($ticket['picks'] ?? null) ? $ticket['picks'] : [];

                                        $rankClass = 'ph-rank-std';

                                        if ($rank === 1) {
                                            $rankClass = 'ph-rank-1';
                                        } elseif ($rank === 2 || $rank === 3) {
                                            $rankClass = 'ph-rank-2';
                                        }
                                        ?>

                                        <tr class="history-row">
                                            <td class="ph-cell-name">
                                                <span class="ph-rank-number">
                                                    <?= $rank ?>
                                                </span>

                                                <span class="ph-user-name">
                                                    <?= qvHistoryH(mb_strtoupper((string)($ticket['user_name'] ?? ''))) ?>
                                                </span>

                                                <span class="d-none search-data">
                                                    <?= qvHistoryH(mb_strtoupper(
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

                                                <td
                                                    class="ph-cell-pick <?= qvHistoryH($cellClass) ?>"
                                                    title="<?= qvHistoryH(qvHistoryPickLabel($userPick)) ?>">
                                                    <?= qvHistoryH((string)($userPick ?: '-')) ?>
                                                </td>
                                            <?php endforeach; ?>

                                            <td class="ph-pts <?= qvHistoryH($rankClass) ?>">
                                                <?= (int)($ticket['points'] ?? 0) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const historyPage = document.getElementById('qvHistoryPage');
        const themeToggle = document.getElementById('qvHistoryThemeToggle');

        if (historyPage && themeToggle) {
            const label = themeToggle.querySelector('.qv-history-theme-switch__label');
            const icon = themeToggle.querySelector('.qv-history-theme-switch__thumb i');

            const applyTheme = function(theme) {
                const isDark = theme === 'dark';

                historyPage.setAttribute('data-theme', theme);
                themeToggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');

                if (label) {
                    label.textContent = isDark ? 'Modo oscuro' : 'Modo claro';
                }

                if (icon) {
                    icon.className = isDark ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
                }
            };

            const storedTheme = localStorage.getItem('qv_history_theme') || 'light';

            applyTheme(storedTheme);

            themeToggle.addEventListener('click', function() {
                const currentTheme = historyPage.getAttribute('data-theme') || 'light';
                const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

                localStorage.setItem('qv_history_theme', nextTheme);
                applyTheme(nextTheme);
            });
        }

        const searchInput = document.getElementById('historial-search');
        const tableRows = document.querySelectorAll('.history-row');
        const countDisplay = document.getElementById('count-display');

        if (searchInput) {
            searchInput.addEventListener('keyup', function(event) {
                const term = String(event.target.value || '').toUpperCase();
                let visibleCount = 0;

                tableRows.forEach(function(row) {
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
        }
    });
</script>

<style>
    .qv-history-guide-hero {
        position: relative;
        overflow: hidden;
        padding: 4.5rem 0 3rem;
        color: #fff;
        background:
            radial-gradient(circle at 14% 12%, rgba(255, 193, 7, 0.22), transparent 24rem),
            radial-gradient(circle at 82% 8%, rgba(37, 99, 235, 0.18), transparent 22rem),
            linear-gradient(180deg, #050914 0%, #080d18 100%);
    }

    .qv-history-guide-hero::after {
        content: "";
        position: absolute;
        inset: auto 0 -1px 0;
        height: 5rem;
        background: linear-gradient(180deg, transparent, #080d18);
    }

    .qv-history-guide-hero__content {
        position: relative;
        z-index: 1;
        max-width: 880px;
        margin: 0 auto;
        text-align: center;
    }

    .qv-public-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        margin-bottom: 0.8rem;
        color: #ffc107;
        font-size: 0.76rem;
        font-weight: 900;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .qv-history-guide-hero h1 {
        margin: 0;
        font-size: clamp(2rem, 6vw, 4rem);
        font-weight: 950;
        line-height: 0.98;
        letter-spacing: -0.06em;
    }

    .qv-history-guide-hero p {
        max-width: 720px;
        margin: 1rem auto 0;
        color: rgba(226, 232, 240, 0.78);
        font-size: clamp(1rem, 2vw, 1.12rem);
        line-height: 1.7;
    }

    .qv-history-guide-hero__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.85rem;
        margin-top: 1.8rem;
    }

    .qv-history-guide-page {
        --qv-history-bg: #f4f7fb;
        --qv-history-surface: #ffffff;
        --qv-history-surface-soft: #f8fafc;
        --qv-history-border: rgba(15, 23, 42, 0.1);
        --qv-history-text: #0f172a;
        --qv-history-muted: #64748b;
        --qv-history-accent: #f59e0b;
        --qv-history-shadow: 0 18px 48px rgba(15, 23, 42, 0.1);

        padding: 2rem 0 4rem;
        color: var(--qv-history-text);
        background:
            radial-gradient(circle at top, rgba(245, 158, 11, 0.13), transparent 24rem),
            var(--qv-history-bg);
    }

    .qv-history-guide-page[data-theme="dark"] {
        --qv-history-bg: #080d18;
        --qv-history-surface: #121821;
        --qv-history-surface-soft: rgba(15, 23, 42, 0.88);
        --qv-history-border: rgba(148, 163, 184, 0.18);
        --qv-history-text: #ffffff;
        --qv-history-muted: #94a3b8;
        --qv-history-accent: #ffc107;
        --qv-history-shadow: 0 22px 70px rgba(0, 0, 0, 0.34);

        background:
            radial-gradient(circle at top, rgba(255, 193, 7, 0.08), transparent 25rem),
            var(--qv-history-bg);
    }

    .qv-history-guide-finder {
        margin-bottom: 1.5rem;
        padding: 1.2rem;
        border: 1px solid var(--qv-history-border);
        border-radius: 1.3rem;
        background: var(--qv-history-surface);
        box-shadow: var(--qv-history-shadow);
    }

    .qv-history-guide-finder__title {
        display: grid;
        gap: 0.85rem;
        margin-bottom: 1rem;
    }

    .qv-history-guide-finder__title h2,
    .qv-history-guide-section-title h2,
    .qv-history-detail__head h2 {
        margin: 0;
        color: var(--qv-history-text);
        font-size: clamp(1.45rem, 4vw, 2.35rem);
        font-weight: 950;
        letter-spacing: -0.045em;
    }

    .qv-history-guide-finder__title p,
    .qv-history-guide-section-title p,
    .qv-history-detail__head p {
        margin: 0.35rem 0 0;
        color: var(--qv-history-muted);
        line-height: 1.65;
    }

    .qv-history-guide-form {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.85rem;
    }

    .qv-history-filter-field {
        padding: 0.85rem;
        border: 1px solid var(--qv-history-border);
        border-radius: 1rem;
        background: var(--qv-history-surface-soft);
    }

    .qv-history-guide-form .form-label,
    .qv-history-table-tools .form-label {
        color: var(--qv-history-muted);
        font-size: 0.76rem;
        font-weight: 850;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .qv-history-guide-form .form-control,
    .qv-history-guide-form .form-select,
    .qv-history-guide-form .input-group-text {
        border-color: var(--qv-history-border);
    }

    .qv-history-guide-page[data-theme="dark"] .qv-history-guide-form .form-control,
    .qv-history-guide-page[data-theme="dark"] .qv-history-guide-form .form-select,
    .qv-history-guide-page[data-theme="dark"] .qv-history-guide-form .input-group-text {
        color: #fff;
        background-color: #0f172a;
        border-color: var(--qv-history-border);
    }

    .qv-history-guide-page[data-theme="dark"] .qv-history-guide-form .form-control::placeholder {
        color: #94a3b8;
    }

    .qv-history-guide-form__actions {
        display: grid;
        gap: 0.55rem;
        align-self: end;
    }

    .qv-history-active-filter-note {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        margin-top: 1rem;
        padding: 0.85rem 1rem;
        border: 1px solid rgba(245, 158, 11, 0.26);
        border-radius: 1rem;
        color: var(--qv-history-text);
        background: rgba(245, 158, 11, 0.1);
        font-size: 0.92rem;
    }

    .qv-history-active-filter-note i {
        color: var(--qv-history-accent);
        margin-top: 0.15rem;
    }

    .qv-history-active-filter-note strong {
        color: var(--qv-history-accent);
    }

    .qv-history-guide-section-title {
        display: grid;
        gap: 0.75rem;
        align-items: end;
        margin-bottom: 1rem;
    }

    .qv-history-guide-section-title>span {
        display: inline-flex;
        justify-self: start;
        padding: 0.55rem 0.85rem;
        border: 1px solid rgba(245, 158, 11, 0.45);
        border-radius: 999px;
        color: var(--qv-history-accent);
        background: rgba(245, 158, 11, 0.1);
        font-weight: 900;
    }

    .qv-history-round-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .qv-history-round-item {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        padding: 1rem;
        border: 1px solid var(--qv-history-border);
        border-radius: 1.15rem;
        background: var(--qv-history-surface);
        box-shadow: var(--qv-history-shadow);
        transition:
            transform 180ms ease,
            border-color 180ms ease,
            box-shadow 180ms ease;
    }

    .qv-history-round-item:hover,
    .qv-history-round-item.is-active {
        transform: translateY(-3px);
        border-color: rgba(245, 158, 11, 0.5);
    }

    .qv-history-round-item__top {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        align-items: center;
    }

    .qv-history-round-item__top span {
        color: var(--qv-history-accent);
        font-size: 0.78rem;
        font-weight: 950;
        text-transform: uppercase;
    }

    .qv-history-round-item__top strong {
        padding: 0.35rem 0.6rem;
        border-radius: 999px;
        color: var(--qv-history-text);
        background: rgba(148, 163, 184, 0.14);
        font-size: 0.72rem;
        text-transform: uppercase;
    }

    .qv-history-round-item h3 {
        margin: 0;
        color: var(--qv-history-text);
        font-size: 1.35rem;
        font-weight: 950;
        letter-spacing: -0.035em;
    }

    .qv-history-round-item__date {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        color: var(--qv-history-muted);
        font-size: 0.9rem;
        font-weight: 700;
    }

    .qv-history-round-item__stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.55rem;
    }

    .qv-history-round-item__stats div {
        padding: 0.65rem;
        border-radius: 0.85rem;
        background: rgba(148, 163, 184, 0.12);
    }

    .qv-history-round-item__stats strong {
        display: block;
        color: var(--qv-history-text);
        font-size: 0.95rem;
        font-weight: 950;
        line-height: 1;
    }

    .qv-history-round-item__stats span {
        display: block;
        margin-top: 0.3rem;
        color: var(--qv-history-muted);
        font-size: 0.7rem;
        font-weight: 850;
        text-transform: uppercase;
    }

    .qv-history-round-item__winner {
        padding: 0.85rem;
        border-left: 3px solid var(--qv-history-accent);
        border-radius: 0.75rem;
        background: rgba(245, 158, 11, 0.08);
    }

    .qv-history-round-item__winner span {
        display: block;
        color: var(--qv-history-muted);
        font-size: 0.75rem;
        font-weight: 850;
        text-transform: uppercase;
    }

    .qv-history-round-item__winner strong {
        display: block;
        margin-top: 0.25rem;
        color: var(--qv-history-text);
        font-weight: 950;
    }

    .qv-history-guide-empty,
    .qv-history-guide-hint {
        max-width: 760px;
        margin: 1.5rem auto;
        padding: 2rem;
        border: 1px solid var(--qv-history-border);
        border-radius: 1.2rem;
        text-align: center;
        color: var(--qv-history-text);
        background: var(--qv-history-surface);
        box-shadow: var(--qv-history-shadow);
    }

    .qv-history-guide-empty div,
    .qv-history-guide-hint div {
        display: inline-grid;
        place-items: center;
        width: 4rem;
        height: 4rem;
        margin-bottom: 1rem;
        border-radius: 1rem;
        color: #111827;
        background: var(--qv-history-accent);
        font-size: 1.6rem;
    }

    .qv-history-guide-empty h3,
    .qv-history-guide-hint h2 {
        color: var(--qv-history-text);
        font-weight: 950;
    }

    .qv-history-guide-empty p,
    .qv-history-guide-hint p {
        color: var(--qv-history-muted);
    }

    .qv-history-detail {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--qv-history-border);
    }

    .qv-history-detail__head {
        display: grid;
        gap: 1rem;
        align-items: center;
        margin-bottom: 1rem;
    }

    .qv-history-detail-kpis {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.85rem;
        margin-bottom: 1.3rem;
    }

    .qv-history-detail-kpis article,
    .qv-history-table-tools,
    .qv-history-table-card {
        border: 1px solid var(--qv-history-border);
        background: var(--qv-history-surface);
        box-shadow: var(--qv-history-shadow);
    }

    .qv-history-detail-kpis article {
        padding: 1rem;
        border-radius: 1.1rem;
    }

    .qv-history-detail-kpis span {
        display: block;
        color: var(--qv-history-muted);
        font-size: 0.78rem;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .qv-history-detail-kpis strong {
        display: block;
        margin-top: 0.3rem;
        color: var(--qv-history-accent);
        font-size: 1.28rem;
        font-weight: 950;
        line-height: 1;
    }

    .qv-history-detail-kpis small {
        display: block;
        margin-top: 0.35rem;
        color: var(--qv-history-muted);
    }

    .qv-history-winners {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.85rem;
        margin-bottom: 1.3rem;
    }

    .qv-history-winner-card {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 0.9rem;
        align-items: center;
        padding: 1rem;
        border-radius: 1rem;
        border: 1px solid var(--qv-history-border);
        background: var(--qv-history-surface);
        box-shadow: var(--qv-history-shadow);
    }

    .qv-history-winner-card.is-gold {
        border-color: rgba(245, 158, 11, 0.6);
    }

    .qv-history-winner-rank {
        display: grid;
        place-items: center;
        width: 3rem;
        height: 3rem;
        border-radius: 1rem;
        color: #111827;
        background: var(--qv-history-accent);
        font-weight: 950;
    }

    .qv-history-winner-card h3 {
        margin: 0;
        color: var(--qv-history-text);
        font-size: 1rem;
        font-weight: 950;
    }

    .qv-history-winner-card p {
        margin: 0.25rem 0 0;
        color: var(--qv-history-muted);
        font-size: 0.85rem;
    }

    .qv-history-winner-points {
        color: var(--qv-history-accent);
        font-size: 1.45rem;
        font-weight: 950;
        line-height: 1;
        text-align: right;
    }

    .qv-history-winner-points span {
        display: block;
        color: var(--qv-history-muted);
        font-size: 0.72rem;
        text-transform: uppercase;
    }

    .qv-history-table-tools {
        display: grid;
        gap: 0.75rem;
        margin-bottom: 1rem;
        padding: 1rem;
        border-radius: 1rem;
    }

    .qv-history-table-tools small {
        color: var(--qv-history-muted);
    }

    .qv-history-table-card {
        overflow: hidden;
        border-radius: 1rem;
    }

    .qv-history-table-head {
        display: grid;
        gap: 0.75rem;
        padding: 1rem;
        border-bottom: 1px solid var(--qv-history-border);
    }

    .qv-history-table-head h3 {
        margin: 0;
        color: var(--qv-history-text);
        font-size: 1.15rem;
        font-weight: 950;
    }

    .qv-history-table-head p {
        margin: 0.25rem 0 0;
        color: var(--qv-history-muted);
        font-size: 0.9rem;
    }

    .qv-history-table-head>div:last-child {
        align-self: center;
        color: var(--qv-history-accent);
        font-weight: 950;
    }

    @media (min-width: 768px) {

        .qv-history-guide-finder__title,
        .qv-history-guide-section-title,
        .qv-history-detail__head {
            grid-template-columns: 1fr auto;
        }

        .qv-history-theme-switch {
            width: auto;
            justify-self: end;
        }

        .qv-history-guide-form {
            grid-template-columns: 0.9fr 1fr 0.9fr 1.4fr auto;
            align-items: end;
        }

        .qv-history-guide-form__actions {
            grid-template-columns: auto auto;
        }

        .qv-history-round-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .qv-history-detail-kpis {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .qv-history-winners {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .qv-history-table-tools {
            grid-template-columns: 1fr auto;
            align-items: end;
        }

        .qv-history-table-head {
            grid-template-columns: 1fr auto;
            align-items: center;
        }
    }

    @media (min-width: 1100px) {
        .qv-history-round-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
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
        color: #fff;
        background: #0f172a;
    }

    .ph-row-dark th {
        color: #fff;
        background: #111827;
    }

    .ph-col-name-header,
    .ph-cell-name {
        min-width: 220px;
        text-align: left;
        padding-left: 10px !important;
        font-size: 13px;
        font-weight: 900;
        white-space: nowrap;
    }

    .ph-col-match-header {
        min-width: 56px;
    }

    .ph-team-logo {
        width: 28px;
        height: 28px;
        object-fit: contain;
    }

    .ph-vs-text {
        color: #ffc107;
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
        background-color: #ffc107 !important;
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

        .qv-history-guide-finder__title,
        .qv-history-guide-section-title,
        .qv-history-detail__head {
            grid-template-columns: 1fr auto;
        }

        .qv-history-guide-form {
            grid-template-columns: 0.9fr 1fr 1.35fr auto;
        }

        .qv-history-guide-form__actions {
            grid-template-columns: auto auto;
        }

        .qv-history-round-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .qv-history-detail-kpis {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .qv-history-winners {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .qv-history-table-tools {
            grid-template-columns: 1fr auto;
            align-items: end;
        }

        .qv-history-table-head {
            grid-template-columns: 1fr auto;
            align-items: center;
        }
    }

    @media (min-width: 1100px) {
        .qv-history-round-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .ph-table-container {
            overflow-x: auto;
        }

        .ph-table {
            min-width: 760px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .qv-history-round-item {
            transition: none;
        }

        .qv-history-round-item:hover {
            transform: none;
        }
    }

    .qv-history-theme-switch {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.65rem;
        width: 100%;
        min-height: 48px;
        padding: 0.55rem 0.85rem;
        border: 1px solid var(--qv-history-border);
        border-radius: 999px;
        color: var(--qv-history-text);
        background: var(--qv-history-surface-soft);
        font-weight: 900;
    }

    .qv-history-theme-switch__track {
        position: relative;
        display: inline-flex;
        align-items: center;
        width: 3.1rem;
        height: 1.65rem;
        padding: 0.16rem;
        border-radius: 999px;
        background: #fbbf24;
        transition: background 180ms ease;
    }

    .qv-history-theme-switch__thumb {
        display: grid;
        place-items: center;
        width: 1.32rem;
        height: 1.32rem;
        border-radius: 999px;
        color: #f59e0b;
        background: #fff;
        font-size: 0.78rem;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.24);
        transform: translateX(0);
        transition: transform 180ms ease, color 180ms ease;
    }

    .qv-history-theme-switch[aria-pressed="true"] .qv-history-theme-switch__track {
        background: #334155;
    }

    .qv-history-theme-switch[aria-pressed="true"] .qv-history-theme-switch__thumb {
        color: #334155;
        transform: translateX(1.42rem);
    }

    .qv-history-theme-switch__label {
        min-width: 5.8rem;
        text-align: left;
        font-size: 0.9rem;
    }
</style>