<?php
declare(strict_types=1);

/**
 * Vista Home: Quiniela Activa o Placeholder "Próximamente"
 * OPTIMIZADO: Lazy Loading, SEO Alt Text, Prevención de CLS.
 */

// 1. Preparación de variables
$ticketCostValue = isset($ticketCost) ? (float)$ticketCost : 10.0;
$currencyCode    = (string)($geoCurrencyCode ?? 'USD');
$currentSlug     = $selectedLeague ?? 'liga-mx'; 
$displayLeagues  = $activeLeagues ?? []; 

// --- LÓGICA PARA EL TÍTULO DINÁMICO ---
$leagueLabel = $selectedLeagueData['name'] ?? 'Quiniela';
$roundTitle = $currentRound['custom_title'] ?? ($currentRound['name'] ?? 'Jornada Actual');

if ($currentRound && $leagueLabel === 'Quiniela') {
    $leagueLabel = (string)($currentRound['league_name'] ?? 'Liga');
}

$matchdayText = 'Próximamente';
if ($currentRound) {
    $matchdayText = (string)($currentRound['name'] ?? 'Jornada Actual');
}

/** Helper Fechas */
$formatLongDate = function(?string $dateStr) {
    if (!$dateStr) return "Por definir";
    $ts = strtotime($dateStr);
    $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $months = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    
    $dayName = $days[date('w', $ts)];
    $dayNum = date('j', $ts);
    $monthName = $months[date('n', $ts)];
    $year = date('Y', $ts);
    $time = date('g:ia', $ts);
    
    return "{$dayName} {$dayNum} de {$monthName} del {$year} a las {$time}";
};

/** Helper Abreviaturas */
$getLeagueAbbr = function($name) {
    $name = strtoupper(trim($name ?? ''));
    if (strlen($name) <= 4) return $name;
    if (strpos($name, ' ') !== false) {
        $parts = explode(' ', $name);
        $abbr = '';
        foreach($parts as $p) if(isset($p[0])) $abbr .= $p[0];
        return substr($abbr, 0, 3);
    }
    return substr($name, 0, 3);
};

$displayOpenDate = $formatLongDate($currentRound['open_at'] ?? null);
$displayCloseDate = $formatLongDate($currentRound['close_at'] ?? null);
$deadlineIso = $deadlineIso ?? ''; 
?>

<style>
    /* Logo central en el Hero */
    .hero-central-logo {
        max-height: 290px;
        width: auto;
        margin-bottom: 1.5rem;
        filter: drop-shadow(0 4px 10px rgba(0,0,0,0.5));
    }
    /* Estilo para la manito marca de agua */
    .step-hand-watermark {
        position: absolute;
        opacity: 0.25;
        pointer-events: none;
        z-index: 1;
        width: 180px;
        filter: grayscale(1);
    }
    .date-info-container {
        background-color: rgba(0, 0, 0, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        display: inline-block;
        padding: 12px 20px;
        border-radius: 12px;
    }
    .promo-modal-content { border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
    .promo-close-btn { position: absolute; top: 15px; right: 15px; z-index: 1056; background-color: white; opacity: 0.9; border-radius: 50%; padding: 0.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.2s; }
    .promo-close-btn:hover { transform: scale(1.1); opacity: 1; }

    /* Celda de Info (Escritorio) */
    .cell-match-info {
        min-width: 90px;
        background-color: #f8f9fa;
        border-left: 1px solid #dee2e6;
        font-size: 0.75rem;
        vertical-align: middle;
    }
    .match-date { font-weight: 700; color: #495057; display: block; }
    .match-time { color: #6c757d; font-size: 0.7rem; display: block; }
    .match-league-badge {
        font-size: 0.6rem !important;
        padding: 2px 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #666;
        background: #fff;
    }
    .col-info-header {
        width: 90px;
        background-color: #001f3f;
        border-left: 1px solid rgba(255,255,255,0.1);
    }
    .help-text-transparent {
        color: rgba(255, 255, 255, 0.6) !important;
        font-size: 0.85rem;
        font-weight: 300;
        letter-spacing: 0.5px;
    }

    /* ADAPTACIÓN MÓVIL */
    @media (max-width: 768px) {
        .ch-table thead { display: none; }
        .ch-table tbody tr {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            flex-wrap: nowrap;
            padding: 8px 4px;
            margin-bottom: 6px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .ch-table td:nth-child(1), .ch-table td:nth-child(3), .ch-table td:nth-child(5) {
            flex: 0 0 40px; 
            width: 40px;
            padding: 0;
            border: none;
            display: flex;
            justify-content: center;
        }
        .btn-choice { width: 38px !important; height: 38px !important; font-size: 0.9rem !important; }
        .ch-table td:nth-child(2), .ch-table td:nth-child(4) {
            flex: 1 1 0;
            min-width: 0;
            padding: 0 2px;
            border: none;
            display: flex;
            align-items: center;
        }
        .align-middle span {
            font-size: 9px;
            line-height: 1.1;
            white-space: normal;
            max-height: 22px;
            overflow: hidden;
        }
        .ch-table td:last-child.cell-match-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            flex: 0 0 55px; 
            width: 55px;
            margin-left: 4px;
            border: none;
            border-left: 1px dashed #ccc;
            background: transparent;
            padding: 0;
        }
        .cell-match-info .match-date { font-size: 9px; margin-bottom: 1px; }
        .cell-match-info .match-time { font-size: 9px; font-weight: bold; color: #000; }
        .cell-match-info .match-league-badge { font-size: 8px !important; padding: 1px 2px; margin-top: 2px !important; }
    }
    
    
    /*adiconal prueba*/
    /* ADAPTACIÓN MÓVIL (REEMPLAZA TODO TU BLOQUE @media) */
@media (max-width: 768px) {

  .ch-table thead { display: none; }

  .ch-table tbody tr{
    display: flex;
    flex-direction: row;
    align-items: center;          /* ayuda a centrar todo vertical */
    justify-content: space-between;
    gap: 6px;
    flex-wrap: nowrap;
    padding: 8px 6px;
    margin: 0 8px 8px;
    background: #fff;
    border-radius: 10px;
    border: 1px solid #e0e0e0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  }

  /* ✅ L/E/V: centrado vertical y más angosto para dar espacio a equipos */
  .ch-table td:nth-child(1),
  .ch-table td:nth-child(3),
  .ch-table td:nth-child(5){
    flex: 0 0 32px;
    width: 32px;
    padding: 0;
    border: none;
    display: flex;
    justify-content: center;
    align-items: center;          /* ✅ centra verticalmente el botón */
  }

  .btn-choice{
    width: 32px !important;
    height: 32px !important;
    font-size: 0.95rem !important;
    font-weight: 900 !important;
    border-radius: 8px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;
    line-height: 1 !important;
  }

  /* ✅ Equipos: más ancho real */
  .ch-table td:nth-child(2),
  .ch-table td:nth-child(4){
    flex: 1 1 0;
    min-width: 0;
    padding: 0 2px;
    border: none;
    display: flex;
    align-items: center;
  }

  /* ✅ Convertimos el contenedor actual (d-flex...) en un DIV con logo arriba y nombre abajo */
  .ch-table td:nth-child(2) .d-flex,
  .ch-table td:nth-child(4) .d-flex{
    display: flex !important;
    flex-direction: column !important;   /* logo arriba, texto abajo */
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    min-width: 0 !important;
    gap: 3px !important;
  }

  /* El visitante estaba justify-content-end: en móvil lo centramos igual */
  .ch-table td:nth-child(4) .d-flex{
    justify-content: center !important;
  }

  /* Logo compacto para liberar ancho */
  .ch-team-logo{
    width: 22px !important;
    height: 22px !important;
    margin: 0 !important;               /* quita me-2/ms-2 */
    flex: 0 0 auto !important;
    object-fit: contain !important;
  }

  /* ✅ Nombre notorio: 2 líneas, sin desaparecer */
  .ch-table td:nth-child(2) span,
  .ch-table td:nth-child(4) span{
    font-size: 12.5px !important;
    font-weight: 900 !important;
    line-height: 1.05 !important;
    color: #0b0f14 !important;

    display: -webkit-box !important;
    -webkit-box-orient: vertical !important;
    -webkit-line-clamp: 2 !important;
    overflow: hidden !important;

    white-space: normal !important;
    overflow-wrap: anywhere !important;
    word-break: break-word !important;

    text-align: center !important;       /* porque ahora es vertical */
    max-height: none !important;
  }

  /* ✅ Info (fecha/hora) más angosta */
  .ch-table td:last-child.cell-match-info{
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    flex: 0 0 50px;       /* antes 55+ */
    width: 50px;
    margin-left: 0;
    border: none;
    border-left: 1px dashed #ccc;
    background: transparent;
    padding: 0 0 0 6px;
  }

  .cell-match-info .match-date{ font-size: 9px; font-weight: 900; margin-bottom: 1px; }
  .cell-match-info .match-time{ font-size: 9px; font-weight: 900; color: #000; }
  .cell-match-info .match-league-badge{ font-size: 8px !important; padding: 1px 3px; margin-top: 2px !important; }
}

    /* =====================================================
   ELIMINAR SCROLL HORIZONTAL EN MÓVIL (DEFINITIVO)
   Mantiene tabla, no corta contenido
===================================================== */
@media (max-width: 768px){

  /* 1️⃣ BLOQUEO GLOBAL DEL SCROLL */
  html, body{
    max-width: 100%;
    overflow-x: hidden !important;
  }

  #quiniela-root,
  .ch-container,
  .ch-table-container,
  .table-responsive{
    max-width: 100% !important;
    overflow-x: hidden !important;
  }

  /* 2️⃣ LA TABLA NUNCA PUEDE SALIRSE */
  .ch-table{
    width: 100% !important;
    max-width: 100% !important;
    table-layout: fixed !important;
  }

  /* 3️⃣ FILAS FLEX SIN DESBORDE */
  .ch-table tbody tr{
    margin-left: 0 !important;
    margin-right: 0 !important;
    width: 100% !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
  }

  /* 4️⃣ TODAS LAS CELDAS PUEDEN ENCOGERSE */
  .ch-table td{
    min-width: 0 !important;
    box-sizing: border-box !important;
  }

  /* 5️⃣ L / E / V COMPACTOS Y CENTRADOS */
  .ch-table td:nth-child(1),
  .ch-table td:nth-child(3),
  .ch-table td:nth-child(5){
    flex: 0 0 32px !important;
    width: 32px !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
  }

  .btn-choice{
    width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    line-height: 1 !important;
  }

  /* 6️⃣ INFO (FECHA/HORA) ULTRA COMPACTA */
  .ch-table td.cell-match-info{
    flex: 0 0 38px !important;
    width: 38px !important;
    min-width: 38px !important;
    padding: 0 0 0 4px !important;
  }

  .ch-table td.cell-match-info > div{
    min-width: 0 !important;
    width: 100% !important;
  }

  /* 7️⃣ EQUIPOS GANAN EL RESTO DEL ANCHO */
  .ch-table td:nth-child(2),
  .ch-table td:nth-child(4){
    flex: 1 1 auto !important;
    min-width: 0 !important;
    padding: 0 2px !important;
  }

  /* Logo arriba / nombre abajo SIN DESBORDE */
  .ch-table td:nth-child(2) .d-flex,
  .ch-table td:nth-child(4) .d-flex{
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    min-width: 0 !important;
  }

  .ch-team-logo{
    width: 22px !important;
    height: 22px !important;
    margin: 0 !important;
    flex: 0 0 auto !important;
  }

  /* 8️⃣ NOMBRES VISIBLES, SIN SCROLL */
  .ch-table td:nth-child(2) span,
  .ch-table td:nth-child(4) span{
    font-size: 12.5px !important;
    font-weight: 900 !important;
    line-height: 1.05 !important;

    display: -webkit-box !important;
    -webkit-box-orient: vertical !important;
    -webkit-line-clamp: 2 !important;

    overflow: hidden !important;
    white-space: normal !important;
    overflow-wrap: anywhere !important;
    word-break: break-word !important;

    text-align: center !important;
  }

  /* 9️⃣ FECHA/HORA COMPACTA Y LEGIBLE */
  .cell-match-info .match-date,
  .cell-match-info .match-time{
    font-size: 8px !important;
    line-height: 1 !important;
    margin: 0 !important;
    font-weight: 800 !important;
  }

  .cell-match-info .match-league-badge{
    font-size: 7px !important;
    padding: 1px 2px !important;
    margin-top: 1px !important;
  }
}

    
</style>

<?php if (!empty($activePromo) && is_array($activePromo)): ?>
    <div class="modal fade" id="autoPromoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content promo-modal-content">
                <button type="button" class="btn-close promo-close-btn" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                <?php if (!empty($activePromo['image'])): ?>
                    <div class="position-relative">
                        <img src="/assets/img/<?= htmlspecialchars($activePromo['image']) ?>" 
                             class="w-100 d-block" 
                             style="max-height: 350px; object-fit: cover;" 
                             alt="<?= htmlspecialchars($activePromo['title']) ?>" 
                             loading="lazy">
                    </div>
                <?php endif; ?>
                <div class="modal-body text-center p-4 bg-white">
                    <h3 class="fw-bold text-primary mb-2"><?= htmlspecialchars($activePromo['title']) ?></h3>
                    <?php if (!empty($activePromo['description'])): ?>
                        <p class="text-muted mb-4"><?= nl2br(htmlspecialchars($activePromo['description'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($activePromo['cta_text']) && !empty($activePromo['cta_link'])): ?>
                        <a href="<?= htmlspecialchars($activePromo['cta_link']) ?>" class="btn btn-primary btn-lg rounded-pill w-100 fw-bold shadow-sm">
                            <?= htmlspecialchars($activePromo['cta_text']) ?> <i class="bi bi-arrow-right-short"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var promoModalEl = document.getElementById('autoPromoModal');
            if (promoModalEl) { var myModal = new bootstrap.Modal(promoModalEl, { keyboard: false }); myModal.show(); }
        });
    </script>
<?php endif; ?>

<section id="quiniela-root"
         data-ticket-amount="<?= htmlspecialchars((string)$ticketCostValue, ENT_QUOTES, 'UTF-8') ?>"
         data-currency="<?= htmlspecialchars($currencyCode, ENT_QUOTES, 'UTF-8') ?>"
         data-league="<?= htmlspecialchars($currentSlug, ENT_QUOTES, 'UTF-8') ?>"
         data-matchday="<?= htmlspecialchars($matchdayText, ENT_QUOTES, 'UTF-8') ?>"
         data-round-id="<?= $currentRound ? (int)$currentRound['id'] : 0 ?>"
         data-whatsapp-phone="<?= htmlspecialchars((string)($whatsappPhone ?? ''), ENT_QUOTES, 'UTF-8') ?>"
         class="ch-container">

    <div class="ch-hero text-center text-white position-relative">
        <div class="ch-hero-overlay py-4 d-flex flex-column justify-content-center">
            
            <div class="container">
                <img src="/assets/img/logo_quiniela.png" class="hero-central-logo" alt="Quiniela Villas Logo" width="290" height="290" loading="eager">
            </div>

            <div class="container mb-3">
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <?php if (!empty($displayLeagues)): ?>
                        <?php foreach ($displayLeagues as $league): ?>
                            <?php $isActive = ($currentSlug === $league['slug']); ?>
                            <a href="?league=<?= htmlspecialchars($league['slug']) ?>" 
                               class="btn btn-sm btn-league-custom <?= $isActive ? 'active' : '' ?> shadow-sm"> 
                                <?= mb_strtoupper($league['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <a href="#" class="btn btn-sm btn-league active"><?= mb_strtoupper($leagueLabel) ?></a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="container position-relative z-2">
                <h1 class="h3 fw-bold text-uppercase mb-1 text-shadow">
                    <span class="text-warning"><?= htmlspecialchars($leagueLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </h1>

                <?php if ($currentRound): ?>
                    <h2 class="h4 fw-bold text-white mb-3 text-shadow">
                        <?= htmlspecialchars($roundTitle, ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                <?php endif; ?>

                <?php if (!empty($availableRounds)): ?>
                    <form method="get" action="" class="d-inline-block">
                        <input type="hidden" name="league" value="<?= htmlspecialchars($currentSlug) ?>">
                        
                        <div class="input-group input-group-sm justify-content-center">
                            <span class="input-group-text bg-dark text-warning border-secondary fw-bold">CAMBIAR JORNADA:</span>
                            <select name="round_id" class="form-select form-select-sm bg-dark text-white border-secondary fw-bold text-uppercase w-auto" 
                                    onchange="this.form.submit()" style="max-width: 250px;">
                                <?php foreach ($availableRounds as $r): ?>
                                    <option value="<?= (int)$r['id'] ?>" 
                                        <?= ($currentRound && (int)$currentRound['id'] === (int)$r['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($currentRound): ?>
                    <div class="mt-3">
                        <div class="date-info-container">
                            <div class="small fw-bold">
                                <span class="text-info text-uppercase">Inicio:</span> <?= $displayOpenDate ?>
                            </div>
                            <div class="small fw-bold mt-1">
                                <span class="text-danger text-uppercase">Cierre:</span> <?= $displayCloseDate ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($currentRound): ?>
                
                <?php if (isset($estimatedPrizes) && ($estimatedPrizes['first'] > 0 || $estimatedPrizes['second'] > 0)): ?>
                <div class="row justify-content-center mt-3 mb-2">
                    <div class="col-auto">
                        <div class="d-flex gap-3 align-items-center justify-content-center flex-wrap scale-sm">
                            <div class="position-relative bg-white text-dark border border-warning border-2 rounded-3 px-3 py-1 shadow" style="min-width: 120px;">
                                <div class="position-absolute top-0 start-50 translate-middle badge bg-warning text-dark border border-light shadow-sm" style="font-size: 0.6rem;">🥇 1er LUGAR</div>
                                <div class="fw-black fs-5 text-success mt-1">$<?= number_format($estimatedPrizes['first'], 2) ?></div>
                            </div>
                            <div class="position-relative bg-white text-dark border border-secondary border-2 rounded-3 px-3 py-1 shadow" style="min-width: 120px;">
                                <div class="position-absolute top-0 start-50 translate-middle badge bg-secondary text-white border border-light shadow-sm" style="font-size: 0.6rem;">🥈 2do LUGAR</div>
                                <div class="fw-black fs-5 text-primary mt-1">$<?= number_format($estimatedPrizes['second'], 2) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="ch-countdown-wrapper py-3 my-3">
                    <div class="countdown d-inline-flex gap-2" id="countdown" data-deadline="<?= htmlspecialchars($deadlineIso, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="ch-counter-box"><span class="h2 fw-bold d-block mb-0 countdown-value" data-unit="days">00</span><small>DÍAS</small></div>
                        <div class="ch-counter-box"><span class="h2 fw-bold d-block mb-0 countdown-value" data-unit="hours">00</span><small>HORAS</small></div>
                        <div class="ch-counter-box"><span class="h2 fw-bold d-block mb-0 countdown-value" data-unit="minutes">00</span><small>MIN</small></div>
                        <div class="ch-counter-box pulse-urgent"><span class="h2 fw-bold d-block mb-0 countdown-value" data-unit="seconds">00</span><small>SEG</small></div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php if ($currentRound && !empty($matches)): ?>
        
        <div class="ch-table-container bg-white shadow-sm position-relative">
            <img src="/assets/img/manito_pasos.png" class="step-hand-watermark d-none d-md-block" style="top: 20px; left: 20px; transform: rotate(-10deg);" alt="Pasos para jugar">

            <div class="table-responsive" style="position: relative; z-index: 2;">
                <table class="table table-bordered mb-0 ch-table">
                    <thead class="ch-thead text-white text-center text-uppercase">
                        <tr>
                            <th style="width: 50px;">L</th>
                            <th>Local</th> 
                            <th style="width: 50px;">E</th>
                            <th>Visita</th> 
                            <th style="width: 50px;">V</th>
                            <th class="col-info-header">Info</th> 
                        </tr>
                    </thead>
                    <tbody id="matches-table-body" class="text-dark">
                        <?php foreach ($matches as $m): ?>
                            <?php 
                                $isDisabled = ($m['status'] ?? 'SCHEDULED') !== 'SCHEDULED'; 
                                $kickoffDate = new DateTime($m['kickoff_at'] ?? 'now');
                                $dateStr = $kickoffDate->format('d/m/Y'); 
                                $timeStr = $kickoffDate->format('H:i');
                                $leagueAbbr = $getLeagueAbbr($m['league_name'] ?? 'MX');
                            ?>
                            <tr data-match-id="<?= (int)$m['id'] ?>">
                                <td class="text-center p-0 align-middle">
                                    <button type="button" class="btn btn-ch-pick btn-choice w-100 h-100 rounded-0" data-choice="L" aria-label="Gana Local" <?= $isDisabled ? 'disabled' : '' ?>>L</button>
                                </td>
                                <td class="align-middle text-start ps-2 ps-md-3 fw-bold text-uppercase">
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($m['home_team_logo'])): ?>
                                            <img src="<?= htmlspecialchars((string)$m['home_team_logo'], ENT_QUOTES, 'UTF-8') ?>" 
                                                 class="ch-team-logo me-2" 
                                                 width="30" height="30"
                                                 loading="lazy"
                                                 alt="<?= htmlspecialchars((string)$m['home_team_name'], ENT_QUOTES, 'UTF-8') ?>" 
                                                 onerror="this.style.display='none'">
                                        <?php endif; ?>
                                        <span><?= htmlspecialchars((string)$m['home_team_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td class="text-center p-0 align-middle">
                                    <button type="button" class="btn btn-ch-pick btn-choice w-100 h-100 rounded-0" data-choice="E" aria-label="Empate" <?= $isDisabled ? 'disabled' : '' ?>>E</button>
                                </td>
                                <td class="align-middle text-end pe-2 pe-md-3 fw-bold text-uppercase">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <span><?= htmlspecialchars((string)$m['away_team_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if (!empty($m['away_team_logo'])): ?>
                                            <img src="<?= htmlspecialchars((string)$m['away_team_logo'], ENT_QUOTES, 'UTF-8') ?>" 
                                                 class="ch-team-logo ms-2" 
                                                 width="30" height="30"
                                                 loading="lazy"
                                                 alt="<?= htmlspecialchars((string)$m['away_team_name'], ENT_QUOTES, 'UTF-8') ?>" 
                                                 onerror="this.style.display='none'">
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center p-0 align-middle">
                                    <button type="button" class="btn btn-ch-pick btn-choice w-100 h-100 rounded-0" data-choice="V" aria-label="Gana Visita" <?= $isDisabled ? 'disabled' : '' ?>>V</button>
                                </td>
                                
                                <td class="align-middle text-center p-1 cell-match-info">
                                    <div class="d-flex flex-column align-items-center justify-content-center" style="line-height: 1.1;">
                                        <span class="match-date"><?= $dateStr ?></span>
                                        <span class="match-time"><?= $timeStr ?></span>
                                        <span class="badge bg-light text-secondary border mt-1 match-league-badge"><?= $leagueAbbr ?></span>
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
                COSTO: <span class="text-warning ms-1" id="ticket-cost-label"><?= $ticketCostLabel ?></span>
            </div>
            <div class="flex-fill bg-danger py-3 px-3 text-center text-md-end">
                CIERRE: <span class="ms-1 text-white">VER DETALLE ARRIBA</span>
            </div>
        </div>

        <div class="ch-form-area py-5 px-3" style="background-color: #001f3f;">
            <div class="container" style="max-width: 800px;">
                <form id="ticket-form" novalidate>
                    <div class="row g-3 justify-content-center">
                        <div class="col-12 col-md-6">
                            <input type="text" id="player-name" name="name" class="form-control ch-input text-center text-uppercase" placeholder="INGRESA TU NOMBRE" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <input type="tel" id="player-phone" name="phone" class="form-control ch-input text-center" placeholder="NÚMERO DE CELULAR" required>
                        </div>
                    </div>

             <div class="row mt-4 justify-content-center">
    <div class="col-10 col-sm-8 col-md-6 d-grid">
        <button type="button" id="btn-add-ticket" class="btn btn-primary ch-btn-add py-3 text-uppercase fw-bold shadow-lg rounded-pill">
            <span class="d-none d-sm-inline">+ AGREGAR QUINIELA</span>
            <span class="d-inline d-sm-none">+ AGREGAR</span>
        </button>
    </div>
    
    <div class="col-12 text-center mt-3"> 
        <div class="help-text-transparent small">* Agrega tus pronósticos antes de enviar</div>
    </div>
</div>
                    
                    <div class="d-grid mt-4">
                        <button type="button" id="btn-send-whatsapp" class="btn btn-success ch-btn-whats py-3 text-uppercase fw-bold shadow-lg rounded-pill" disabled>
                            <i class="bi bi-whatsapp me-2"></i> ENVIAR PEDIDO POR WHATSAPP
                        </button>
                    </div>
                </form>

                <div class="mt-5">
                    <div class="card border-0 shadow-lg overflow-hidden">
                        <div class="card-header bg-white border-bottom-0 text-center py-3">
                            <h5 class="mb-0 fw-bold text-dark text-uppercase">Tus Quinielas</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0 text-center align-middle">
                                    <thead class="table-dark small">
                                        <tr>
                                            <th>#</th>
                                            <th>Nombre</th>
                                            <th>Celular</th>
                                            <th>Pronósticos</th>
                                            <th class="text-end">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tickets-summary-body"></tbody>
                                </table>
                            </div>
                            <div id="empty-state-msg" class="text-center py-4 text-muted small bg-light" style="display:none;">
                                Aún no has agregado ninguna quiniela.
                            </div>
                        </div>
                        <div class="card-footer bg-danger text-white d-flex justify-content-between align-items-center py-3 px-4">
                            <div class="fw-bold">TOTAL: <span id="tickets-count-badge" class="badge bg-white text-danger ms-2 fs-6">0</span></div>
                            <div class="fw-bold fs-5"><span id="tickets-total-amount">$0.00 <?= $currencyCode ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        
        <div class="bg-white py-5 text-center">
            <div class="container py-5">
                
                <div class="mb-4">
                    <img src="/assets/img/logo_quiniela.png" alt="Quiniela Logo" class="img-fluid" style="max-height: 120px; filter: grayscale(100%); opacity: 0.6;">
                </div>

                <h2 class="display-6 fw-bold text-uppercase text-secondary mb-3">
                    ¡Próximamente!
                </h2>
                
                <p class="lead text-muted mb-4" style="max-width: 600px; margin: 0 auto;">
                    Aún no tenemos jornadas activas disponibles para <strong><?= htmlspecialchars($leagueLabel) ?></strong>.
                    <br>Estamos preparando los mejores partidos para ti.
                </p>

                <div class="d-flex justify-content-center gap-3">
                    <a href="/quiniela/anterior" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
                        <i class="bi bi-clock-history me-2"></i> Ver Historial
                    </a>
                    <?php if(count($displayLeagues) > 1): ?>
                        <button class="btn btn-primary rounded-pill px-4 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#helpLeagues">
                            Cambiar de Liga
                        </button>
                    <?php endif; ?>
                </div>

                <div class="collapse mt-4" id="helpLeagues">
                    <div class="card card-body border-0 bg-light">
                        <small class="text-muted fw-bold text-uppercase mb-2">Prueba con estas ligas:</small>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <?php foreach ($displayLeagues as $league): ?>
                                <?php if($league['slug'] !== $currentSlug): ?>
                                    <a href="?league=<?= htmlspecialchars($league['slug']) ?>" class="btn btn-sm btn-outline-secondary">
                                        <?= mb_strtoupper($league['name']) ?>
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
    const tbody = document.getElementById('tickets-summary-body');
    if (tbody) {
        const emptyMsg = document.getElementById('empty-state-msg');
        const observer = new MutationObserver(function() {
            emptyMsg.style.display = (tbody.children.length === 0) ? 'block' : 'none';
        });
        observer.observe(tbody, {childList: true});
        if(tbody.children.length === 0) emptyMsg.style.display = 'block';
    }
});
</script>