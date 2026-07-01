<?php

declare(strict_types=1);

/**
 * Vista Home pública.
 * Rediseño preparado para public-modern.css.
 * Sin estilos internos pesados.
 * Sin CSS duplicado dentro de la vista.
 */

// =====================================================
// Helpers
// =====================================================

$e = static function (mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$currentRound = is_array($currentRound ?? null) ? $currentRound : [];
$selectedLeagueData = is_array($selectedLeagueData ?? null) ? $selectedLeagueData : [];
$activeLeagues = is_array($activeLeagues ?? null) ? $activeLeagues : [];

$ticketCostValue = isset($ticketCost) ? (float)$ticketCost : 10.0;
$currencyCode = (string)($geoCurrencyCode ?? 'USD');
$currentSlug = (string)($selectedLeague ?? 'liga-mx');
$displayLeagues = $activeLeagues;

$leagueLabel = (string)($selectedLeagueData['name'] ?? 'Quiniela');
$roundTitle = (string)($currentRound['custom_title'] ?? ($currentRound['name'] ?? 'Jornada Actual'));

if ($currentRound && $leagueLabel === 'Quiniela') {
    $leagueLabel = (string)($currentRound['league_name'] ?? 'Liga');
}

$matchdayText = 'Próximamente';

if ($currentRound) {
    $matchdayText = (string)($currentRound['name'] ?? 'Jornada Actual');
}

$formatLongDate = static function (?string $dateStr): string {
    if (!$dateStr) {
        return 'Por definir';
    }

    $ts = strtotime($dateStr);

    if ($ts === false) {
        return 'Por definir';
    }

    $days = [
        'domingo',
        'lunes',
        'martes',
        'miércoles',
        'jueves',
        'viernes',
        'sábado',
    ];

    $months = [
        '',
        'enero',
        'febrero',
        'marzo',
        'abril',
        'mayo',
        'junio',
        'julio',
        'agosto',
        'septiembre',
        'octubre',
        'noviembre',
        'diciembre',
    ];

    $dayName = $days[(int)date('w', $ts)];
    $dayNum = date('j', $ts);
    $monthName = $months[(int)date('n', $ts)];
    $year = date('Y', $ts);
    $time = date('g:ia', $ts);

    return "{$dayName} {$dayNum} de {$monthName} del {$year} a las {$time}";
};

$getLeagueAbbr = static function (mixed $name): string {
    $name = strtoupper(trim((string)$name));

    if ($name === '') {
        return 'LIGA';
    }

    if (strlen($name) <= 4) {
        return $name;
    }

    if (str_contains($name, ' ')) {
        $parts = explode(' ', $name);
        $abbr = '';

        foreach ($parts as $part) {
            if (isset($part[0])) {
                $abbr .= $part[0];
            }
        }

        return substr($abbr, 0, 3);
    }

    return substr($name, 0, 3);
};

$displayOpenDate = $formatLongDate($currentRound['open_at'] ?? null);
$displayCloseDate = $formatLongDate($currentRound['close_at'] ?? null);
$deadlineIso = (string)($deadlineIso ?? '');

$ticketCostLabel = (string)($ticketCostLabel ?? ('$' . number_format($ticketCostValue, 2) . ' ' . $currencyCode));

// =====================================================
// Fondo dinámico preparado para backend
// =====================================================

$publicSettings = $publicSettings ?? [];

$heroBackgroundDesktop = trim((string)($publicSettings['public_hero_bg_desktop'] ?? ''));
$heroBackgroundMobile = trim((string)($publicSettings['public_hero_bg_mobile'] ?? ''));
$heroOverlayOpacity = trim((string)($publicSettings['public_hero_overlay_opacity'] ?? '0.72'));

if ($heroBackgroundDesktop === '') {
    if (!empty($selectedLeagueData['image_background'])) {
        $heroBackgroundDesktop = (string)$selectedLeagueData['image_background'];
    } elseif (!empty($selectedLeagueData['image_banner'])) {
        $heroBackgroundDesktop = (string)$selectedLeagueData['image_banner'];
    } elseif (!empty($currentRound['image_background'])) {
        $heroBackgroundDesktop = (string)$currentRound['image_background'];
    }
}

if ($heroBackgroundMobile === '') {
    $heroBackgroundMobile = $heroBackgroundDesktop;
}

$heroOverlayOpacityFloat = (float)$heroOverlayOpacity;

if ($heroOverlayOpacityFloat < 0.35 || $heroOverlayOpacityFloat > 0.95) {
    $heroOverlayOpacityFloat = 0.72;
}

$heroStyleParts = [
    '--hero-overlay-opacity: ' . $heroOverlayOpacityFloat,
];

if ($heroBackgroundDesktop !== '') {
    $heroStyleParts[] = "--hero-bg-desktop: url('" . $e($heroBackgroundDesktop) . "')";
}

if ($heroBackgroundMobile !== '') {
    $heroStyleParts[] = "--hero-bg-mobile: url('" . $e($heroBackgroundMobile) . "')";
}

$heroStyle = implode('; ', $heroStyleParts) . ';';

$hasCurrentRound = !empty($currentRound) && is_array($currentRound);
$hasMatches = $hasCurrentRound && !empty($matches);
?>

<?php if (!empty($activePromo) && is_array($activePromo)): ?>
    <div class="modal fade" id="autoPromoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content promo-modal-content">
                <button
                    type="button"
                    class="btn-close promo-close-btn"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>

                <?php if (!empty($activePromo['image'])): ?>
                    <div class="position-relative">
                        <img
                            src="/assets/img/<?= $e($activePromo['image']) ?>"
                            class="w-100 d-block promo-modal-image"
                            alt="<?= $e($activePromo['title'] ?? 'Promoción') ?>"
                            loading="lazy">
                    </div>
                <?php endif; ?>

                <div class="modal-body text-center p-4 bg-white">
                    <h3 class="fw-bold text-primary mb-2">
                        <?= $e($activePromo['title'] ?? '') ?>
                    </h3>

                    <?php if (!empty($activePromo['description'])): ?>
                        <p class="text-muted mb-4">
                            <?= nl2br($e($activePromo['description'])) ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($activePromo['cta_text']) && !empty($activePromo['cta_link'])): ?>
                        <a
                            href="<?= $e($activePromo['cta_link']) ?>"
                            class="btn btn-primary btn-lg rounded-pill w-100 fw-bold shadow-sm">
                            <?= $e($activePromo['cta_text']) ?>
                            <i class="bi bi-arrow-right-short"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var promoModalEl = document.getElementById('autoPromoModal');

            if (promoModalEl && typeof bootstrap !== 'undefined') {
                var promoModal = new bootstrap.Modal(promoModalEl, {
                    keyboard: false
                });

                promoModal.show();
            }
        });
    </script>
<?php endif; ?>

<section
    id="quiniela-root"
    class="ch-container"
    data-ticket-amount="<?= $e((string)$ticketCostValue) ?>"
    data-currency="<?= $e($currencyCode) ?>"
    data-league="<?= $e($currentSlug) ?>"
    data-matchday="<?= $e($matchdayText) ?>"
    data-round-id="<?= $hasCurrentRound ? (int)$currentRound['id'] : 0 ?>"
    data-whatsapp-phone="<?= $e((string)($whatsappPhone ?? '')) ?>">

    <div class="ch-hero text-center position-relative" <?= $heroStyle !== '' ? 'style="' . $heroStyle . '"' : '' ?>>
        <div class="ch-hero-overlay">
            <div class="hero-scene hero-scene--premium" aria-hidden="true">
                <div class="hero-scene__stadium-light hero-scene__stadium-light--left"></div>
                <div class="hero-scene__stadium-light hero-scene__stadium-light--right"></div>

                <div class="hero-scene__field">
                    <div class="hero-scene__field-line hero-scene__field-line--one"></div>
                    <div class="hero-scene__field-line hero-scene__field-line--two"></div>
                    <div class="hero-scene__field-circle"></div>
                </div>

                <div class="hero-scene__goal-wrap">
                    <div class="hero-scene__goal-shadow"></div>
                    <div class="hero-scene__goal"></div>
                    <div class="hero-scene__net-wave"></div>
                    <div class="hero-scene__goal-flash"></div>
                </div>

                <div class="hero-scene__ball-path">
                    <div class="hero-scene__ball-trail hero-scene__ball-trail--one"></div>
                    <div class="hero-scene__ball-trail hero-scene__ball-trail--two"></div>
                    <div class="hero-scene__ball"></div>
                </div>

                <div class="hero-scene__mascot"></div>

                <div class="hero-scene__particles">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <div class="hero-scene__goal-text">GOOOL</div>
            </div>

            <div class="container ch-hero-logo-wrap">
                <img
                    src="/assets/img/logo_quiniela.png"
                    class="hero-central-logo"
                    alt="Quiniela Villas Logo"
                    width="290"
                    height="290"
                    loading="eager">
            </div>

            <div class="container ch-league-selector-wrap">
                <div class="ch-league-scroll">
                    <?php if (!empty($displayLeagues)): ?>
                        <?php foreach ($displayLeagues as $league): ?>
                            <?php
                            $leagueSlug = (string)($league['slug'] ?? '');
                            $leagueName = (string)($league['name'] ?? '');
                            $isActive = $currentSlug === $leagueSlug;
                            ?>

                            <a
                                href="?league=<?= $e($leagueSlug) ?>"
                                class="btn btn-sm btn-league-custom <?= $isActive ? 'active' : '' ?>">
                                <?= $e(mb_strtoupper($leagueName)) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <a href="#" class="btn btn-sm btn-league active">
                            <?= $e(mb_strtoupper($leagueLabel)) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="container ch-hero-copy-wrap">
                <h1 class="text-uppercase">
                    <span>
                        <?= $e($leagueLabel) ?>
                    </span>
                </h1>

                <?php if ($hasCurrentRound): ?>
                    <h2>
                        <?= $e($roundTitle) ?>
                    </h2>
                <?php endif; ?>

                <?php if (!empty($availableRounds)): ?>
                    <form method="get" action="" class="ch-round-form">
                        <input type="hidden" name="league" value="<?= $e($currentSlug) ?>">

                        <div class="input-group">
                            <span class="input-group-text">
                                Cambiar jornada
                            </span>

                            <select
                                name="round_id"
                                class="form-select ch-round-select"
                                onchange="this.form.submit()">
                                <?php foreach ($availableRounds as $round): ?>
                                    <?php
                                    $roundId = (int)($round['id'] ?? 0);
                                    $roundName = (string)($round['name'] ?? ('Jornada ' . $roundId));
                                    ?>

                                    <option
                                        value="<?= $roundId ?>"
                                        <?= ($hasCurrentRound && (int)$currentRound['id'] === $roundId) ? 'selected' : '' ?>>
                                        <?= $e($roundName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($hasCurrentRound): ?>
                    <div class="ch-date-wrap">
                        <div class="date-info-container">
                            <div class="small fw-bold">
                                <span class="date-label date-label--start">Inicio:</span>
                                <?= $e($displayOpenDate) ?>
                            </div>

                            <div class="small fw-bold">
                                <span class="date-label date-label--end">Cierre:</span>
                                <?= $e($displayCloseDate) ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($hasCurrentRound): ?>
                <?php if (isset($estimatedPrizes) && (($estimatedPrizes['first'] ?? 0) > 0 || ($estimatedPrizes['second'] ?? 0) > 0)): ?>
                    <div class="container ch-prizes-wrap">
                        <div class="prize-grid">
                            <div class="prize-card prize-card--first">
                                <span class="prize-card__label">🥇 1er lugar</span>

                                <strong>
                                    $<?= number_format((float)($estimatedPrizes['first'] ?? 0), 2) ?>
                                </strong>
                            </div>

                            <div class="prize-card prize-card--second">
                                <span class="prize-card__label">🥈 2do lugar</span>

                                <strong>
                                    $<?= number_format((float)($estimatedPrizes['second'] ?? 0), 2) ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="ch-countdown-wrapper">
                    <div
                        class="countdown"
                        id="countdown"
                        data-deadline="<?= $e($deadlineIso) ?>">
                        <div class="ch-counter-box">
                            <span class="countdown-value" data-unit="days">00</span>
                            <small>Días</small>
                        </div>

                        <div class="ch-counter-box">
                            <span class="countdown-value" data-unit="hours">00</span>
                            <small>Horas</small>
                        </div>

                        <div class="ch-counter-box">
                            <span class="countdown-value" data-unit="minutes">00</span>
                            <small>Min</small>
                        </div>

                        <div class="ch-counter-box pulse-urgent">
                            <span class="countdown-value" data-unit="seconds">00</span>
                            <small>Seg</small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($hasMatches): ?>
        <div class="ch-table-container bg-white shadow-sm position-relative">
            <div class="ch-table-container bg-white shadow-sm position-relative">
                <div class="table-responsive ch-table-scroll-wrap">
                    <table class="table table-bordered mb-0 ch-table">
                        <thead class="ch-thead text-white text-center text-uppercase">
                            <tr>
                                <th class="ch-pick-head">L</th>
                                <th>Local</th>
                                <th class="ch-pick-head">E</th>
                                <th>Visita</th>
                                <th class="ch-pick-head">V</th>
                                <th class="col-info-header">Info</th>
                            </tr>
                        </thead>

                        <tbody id="matches-table-body" class="text-dark">
                            <?php foreach ($matches as $match): ?>
                                <?php
                                $matchStatus = (string)($match['status'] ?? 'SCHEDULED');
                                $isDisabled = $matchStatus !== 'SCHEDULED';

                                try {
                                    $kickoffDate = new DateTime((string)($match['kickoff_at'] ?? 'now'));
                                } catch (Throwable) {
                                    $kickoffDate = new DateTime();
                                }

                                $dateStr = $kickoffDate->format('d/m/Y');
                                $timeStr = $kickoffDate->format('H:i');
                                $leagueAbbr = $getLeagueAbbr($match['league_name'] ?? 'MX');

                                $homeTeamName = (string)($match['home_team_name'] ?? '');
                                $awayTeamName = (string)($match['away_team_name'] ?? '');
                                $homeLogo = (string)($match['home_team_logo'] ?? '');
                                $awayLogo = (string)($match['away_team_logo'] ?? '');
                                ?>

                                <tr data-match-id="<?= (int)($match['id'] ?? 0) ?>">
                                    <td class="text-center p-0 align-middle ch-pick-cell">
                                        <button
                                            type="button"
                                            class="btn btn-ch-pick btn-choice w-100 h-100 rounded-0"
                                            data-choice="L"
                                            aria-label="Gana local"
                                            <?= $isDisabled ? 'disabled' : '' ?>>
                                            L
                                        </button>
                                    </td>

                                    <td class="align-middle text-start ps-2 ps-md-3 fw-bold text-uppercase ch-team-cell ch-team-cell--home">
                                        <div class="d-flex align-items-center ch-team-box ch-team-box--home">
                                            <?php if ($homeLogo !== ''): ?>
                                                <img
                                                    src="<?= $e($homeLogo) ?>"
                                                    class="ch-team-logo me-2"
                                                    width="30"
                                                    height="30"
                                                    loading="lazy"
                                                    alt="<?= $e($homeTeamName) ?>"
                                                    onerror="this.style.display='none'">
                                            <?php endif; ?>

                                            <span><?= $e($homeTeamName) ?></span>
                                        </div>
                                    </td>

                                    <td class="text-center p-0 align-middle ch-pick-cell">
                                        <button
                                            type="button"
                                            class="btn btn-ch-pick btn-choice w-100 h-100 rounded-0"
                                            data-choice="E"
                                            aria-label="Empate"
                                            <?= $isDisabled ? 'disabled' : '' ?>>
                                            E
                                        </button>
                                    </td>

                                    <td class="align-middle text-end pe-2 pe-md-3 fw-bold text-uppercase ch-team-cell ch-team-cell--away">
                                        <div class="d-flex align-items-center justify-content-end ch-team-box ch-team-box--away">
                                            <span><?= $e($awayTeamName) ?></span>

                                            <?php if ($awayLogo !== ''): ?>
                                                <img
                                                    src="<?= $e($awayLogo) ?>"
                                                    class="ch-team-logo ms-2"
                                                    width="30"
                                                    height="30"
                                                    loading="lazy"
                                                    alt="<?= $e($awayTeamName) ?>"
                                                    onerror="this.style.display='none'">
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="text-center p-0 align-middle ch-pick-cell">
                                        <button
                                            type="button"
                                            class="btn btn-ch-pick btn-choice w-100 h-100 rounded-0"
                                            data-choice="V"
                                            aria-label="Gana visita"
                                            <?= $isDisabled ? 'disabled' : '' ?>>
                                            V
                                        </button>
                                    </td>

                                    <td class="align-middle text-center p-1 cell-match-info">
                                        <div class="cell-match-info-inner">
                                            <span class="match-date" title="Fecha del partido">
                                                <i class="bi bi-calendar-event"></i>
                                                <span><?= $e($dateStr) ?></span>
                                            </span>

                                            <span class="match-time" title="Hora del partido">
                                                <i class="bi bi-clock-history"></i>
                                                <span><?= $e($timeStr) ?></span>
                                            </span>

                                            <span class="badge bg-light text-secondary border match-league-badge" title="Liga">
                                                <?= $e($leagueAbbr) ?>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ch-info-bar d-flex flex-wrap text-white fw-bold text-uppercase shadow-sm">
                <div class="flex-fill bg-dark py-3 px-3 border-end border-light text-center text-md-start">
                    Costo:
                    <span class="text-warning ms-1" id="ticket-cost-label">
                        <?= $e($ticketCostLabel) ?>
                    </span>
                </div>

                <div class="flex-fill bg-danger py-3 px-3 text-center text-md-end">
                    Cierre:
                    <span class="ms-1 text-white">
                        Revisa fecha y hora arriba
                    </span>
                </div>
            </div>
        </div>
        </div>

        <div class="ch-form-area py-5 px-3">
            <div class="container ch-form-inner">
                <form id="ticket-form" novalidate>
                    <div class="row g-3 justify-content-center">
                        <div class="col-12 col-md-6">
                            <input
                                type="text"
                                id="player-name"
                                name="name"
                                class="form-control ch-input text-center text-uppercase"
                                placeholder="Ingresa tu nombre"
                                required>
                        </div>

                        <div class="col-12 col-md-6">
                            <input
                                type="tel"
                                id="player-phone"
                                name="phone"
                                class="form-control ch-input text-center"
                                placeholder="Número de celular"
                                required>
                        </div>
                    </div>

                    <div class="row mt-4 justify-content-center">
                        <div class="col-10 col-sm-8 col-md-6 d-grid">
                            <button
                                type="button"
                                id="btn-add-ticket"
                                class="btn btn-primary ch-btn-add py-3 text-uppercase fw-bold shadow-lg rounded-pill">
                                <span class="d-none d-sm-inline">+ Agregar quiniela</span>
                                <span class="d-inline d-sm-none">+ Agregar</span>
                            </button>
                        </div>

                        <div class="col-12 text-center mt-3">
                            <div class="help-text-transparent small">
                                * Agrega tus pronósticos antes de enviar
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button
                            type="button"
                            id="btn-send-whatsapp"
                            class="btn btn-success ch-btn-whats py-3 text-uppercase fw-bold shadow-lg rounded-pill"
                            disabled>
                            <i class="bi bi-whatsapp me-2"></i>
                            Enviar pedido por WhatsApp
                        </button>
                    </div>
                </form>

                <div class="mt-5 ch-ticket-summary-wrap">
                    <div class="card border-0 shadow-lg overflow-hidden ch-ticket-summary-card">
                        <div class="card-header bg-white border-bottom-0 text-center py-3 qv-summary-header">
                            <div class="qv-summary-header__title">
                                <h5 class="mb-0 fw-bold text-dark text-uppercase">
                                    Tus quinielas
                                </h5>

                                <small>
                                    Revisa el detalle antes de enviarlo
                                </small>
                            </div>

                            <button
                                type="button"
                                id="qv-toggle-summary"
                                class="qv-summary-toggle"
                                aria-expanded="true"
                                aria-controls="qv-summary-content">
                                <span data-summary-toggle-label>Ocultar</span>
                                <i class="bi bi-chevron-up" data-summary-toggle-icon></i>
                            </button>
                        </div>

                        <div id="qv-summary-content" class="card-body p-0 qv-summary-content is-open">
                            <div class="qv-summary-content__inner">
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0 text-center align-middle ch-summary-table">
                                        <thead class="table-dark small">
                                            <tr>
                                                <th>#</th>
                                                <th>Nombre</th>
                                                <th>Celular</th>
                                                <th>Pronósticos</th>
                                                <th class="text-end">Valor</th>
                                                <th class="text-center">Acción</th>
                                            </tr>
                                        </thead>

                                        <tbody id="tickets-summary-body"></tbody>
                                    </table>
                                </div>

                                <div id="empty-state-msg" class="text-center py-4 text-muted small bg-light ch-empty-state" style="display:none;">
                                    Aún no has agregado ninguna quiniela.
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-danger text-white d-flex justify-content-between align-items-center py-3 px-4">
                            <div class="fw-bold">
                                Total:
                                <span id="tickets-count-badge" class="badge bg-white text-danger ms-2 fs-6">0</span>
                            </div>

                            <div class="fw-bold fs-5">
                                <span id="tickets-total-amount">
                                    $0.00 <?= $e($currencyCode) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="ch-empty-round-section text-center">
            <div class="container py-5">
                <div class="mb-4">
                    <img
                        src="/assets/img/logo_quiniela.png"
                        alt="Quiniela Logo"
                        class="img-fluid ch-empty-logo"
                        loading="lazy">
                </div>

                <h2 class="display-6 fw-bold text-uppercase mb-3">
                    ¡Próximamente!
                </h2>

                <p class="lead mb-4 ch-empty-copy">
                    Aún no tenemos jornadas activas disponibles para
                    <strong><?= $e($leagueLabel) ?></strong>.
                    <br>
                    Estamos preparando los mejores partidos para ti.
                </p>

                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="/quiniela/anterior" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
                        <i class="bi bi-clock-history me-2"></i>
                        Ver historial
                    </a>

                    <?php if (count($displayLeagues) > 1): ?>
                        <button
                            class="btn btn-primary rounded-pill px-4 fw-bold"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#helpLeagues">
                            Cambiar de liga
                        </button>
                    <?php endif; ?>
                </div>

                <div class="collapse mt-4" id="helpLeagues">
                    <div class="card card-body border-0 bg-light">
                        <small class="text-muted fw-bold text-uppercase mb-2">
                            Prueba con estas ligas:
                        </small>

                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <?php foreach ($displayLeagues as $league): ?>
                                <?php
                                $leagueSlug = (string)($league['slug'] ?? '');
                                $leagueName = (string)($league['name'] ?? '');
                                ?>

                                <?php if ($leagueSlug !== $currentSlug): ?>
                                    <a href="?league=<?= $e($leagueSlug) ?>" class="btn btn-sm btn-outline-secondary">
                                        <?= $e(mb_strtoupper($leagueName)) ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var tbody = document.getElementById('tickets-summary-body');
        var emptyMsg = document.getElementById('empty-state-msg');

        if (!tbody || !emptyMsg) {
            return;
        }

        function updateEmptyState() {
            emptyMsg.style.display = tbody.children.length === 0 ? 'block' : 'none';
        }

        if (typeof window.MutationObserver === 'undefined') {
            return;
        }

        var observer = new window.MutationObserver(updateEmptyState);

        observer.observe(tbody, {
            childList: true
        });

        updateEmptyState();
    });
</script>