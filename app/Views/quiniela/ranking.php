<?php
$totalPrimero = $totalPrimero ?? 0;
$totalSegundo = $totalSegundo ?? 0;
$tickets = $tickets ?? [];
$matches = $matches ?? [];
$currentRound = $currentRound ?? null;
$estimatedPrizes = $estimatedPrizes ?? ['first' => 0, 'second' => 0];
$currencyCode = $currencyCode ?? 'USD';
?>

<?php

/** @var array|null $currentRound */
/** @var array $tickets */
/** @var array $matches */
/** @var string $updatedAt */
/** @var array $estimatedPrizes */
/** @var string $currencyCode */
/** @var array $activeLeagues */
/** @var string $leagueSlug */
/** @var array|null $selectedLeagueData */

$officialResults = [];
foreach ($matches as $m) {
    if (!empty($m['result_outcome'])) {
        $officialResults[$m['id']] = $m['result_outcome'];
    }
}

// --- LÓGICA DE COLORES ---
// Definimos una paleta de colores suaves de Bootstrap 5.3
// Se irán rotando cada vez que cambie la cantidad de puntos.
$colorPalette = [
    'bg-primary-subtle text-primary-emphasis',   // Azul suave
    'bg-success-subtle text-success-emphasis',   // Verde suave
    'bg-warning-subtle text-warning-emphasis',   // Amarillo suave
    'bg-danger-subtle text-danger-emphasis',     // Rojo suave
    'bg-info-subtle text-info-emphasis',         // Cian suave
    'bg-secondary-subtle text-secondary-emphasis', // Gris suave
    'bg-light border text-dark'                  // Blanco/Gris muy claro
];

$lastPoints = null;
$colorIndex = -1; // Iniciamos en -1 para que el primer grupo sea 0
?>

<div class="ph-wrapper">

    <div>
        <h2 class="text-center mb-4">
            <?= !empty($currentRound['custom_title']) ? htmlspecialchars($currentRound['custom_title']) : htmlspecialchars($currentRound['name']) ?>
        </h2>
    </div>


    <?php if (!empty($activeLeagues) && count($activeLeagues) > 1): ?>
        <div class="d-flex justify-content-center gap-2 mb-3 px-2" style="overflow-x: auto; white-space: nowrap; padding-bottom: 5px;">
            <?php foreach ($activeLeagues as $lg):
                $isActive = ($lg['slug'] === $leagueSlug);
                $btnClass = $isActive ? 'btn-danger' : 'btn-outline-danger';
            ?>
                <a href="/ranking?league=<?= $lg['slug'] ?>" class="btn <?= $btnClass ?> btn-sm fw-bold rounded-pill px-3 shadow-sm">
                    <?= mb_strtoupper($lg['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="container mb-3">
        <form action="/ranking" method="get" class="row justify-content-center align-items-center g-2">
            <input type="hidden" name="league" value="<?= htmlspecialchars($leagueSlug) ?>">

            <div class="col-auto">
                <label class="fw-bold small">VER JORNADA:</label>
            </div>
            <div class="col-auto">
                <select name="round_id" class="form-select form-select-sm border-dark fw-bold text-uppercase shadow-sm"
                    onchange="this.form.submit()" style="min-width: 200px; background-color: #fff;">

                    <?php if (empty($availableRounds)): ?>
                        <?php if ($currentRound): ?>
                            <option value="<?= $currentRound['id'] ?>" selected>
                                <?= htmlspecialchars($currentRound['name']) ?>
                            </option>
                        <?php else: ?>
                            <option value="">NO HAY JORNADAS (<?= htmlspecialchars($leagueSlug) ?>)</option>
                        <?php endif; ?>
                    <?php else: ?>

                        <?php foreach ($availableRounds as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= ($currentRound && $currentRound['id'] == $r['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['name']) ?>
                                <?= !empty($r['status']) ? ' (' . $r['status'] . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </select>
            </div>
        </form>
    </div>


    <div class="row justify-content-center mb-4">
        <div class="col-auto">
            <div class="d-flex gap-3 align-items-center justify-content-center flex-wrap">

                <div class="position-relative bg-white text-dark border border-warning border-3 rounded-3 px-4 py-2 shadow-sm text-center"
                    style="min-width: 140px;">
                    <div class="position-absolute top-0 start-50 translate-middle badge bg-warning text-dark border border-light shadow-sm"
                        style="font-size: 0.7rem;">
                        🥇 1er LUGAR
                    </div>
                    <div class="fw-black fs-3 text-success mt-2">
                        $<?= number_format($estimatedPrizes['first'], 2) ?>
                    </div>
                    <small class="text-muted fw-bold" style="font-size: 0.7rem;"><?= $currencyCode ?></small>
                </div>

                <div class="position-relative bg-white text-dark border border-secondary border-3 rounded-3 px-4 py-2 shadow-sm text-center"
                    style="min-width: 140px;">
                    <div class="position-absolute top-0 start-50 translate-middle badge bg-secondary text-white border border-light shadow-sm"
                        style="font-size: 0.7rem;">
                        🥈 2do LUGAR
                    </div>
                    <div class="fw-black fs-3 text-primary mt-2">
                        $<?= number_format($estimatedPrizes['second'], 2) ?>
                    </div>
                    <small class="text-muted fw-bold" style="font-size: 0.7rem;"><?= $currencyCode ?></small>
                </div>

            </div>
            <div class="text-center text-muted mt-1" style="font-size: 10px;">
                * Premios acumulados estimados
            </div>

        </div>
    </div>


    <div class="row justify-content-center mb-2">
        <div class="col-12 text-center mb-2" style="font-size: 12px; font-weight: bold;">
            ACTUALIZADO: <?= $updatedAt ?> HRS | PARTICIPANTES: <span id="count-display"><?= count($tickets) ?></span>
            <a href="javascript:location.reload()" class="ms-2 text-danger text-decoration-underline">REFRESCAR</a>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-dark"><i class="bi bi-search"></i></span>
                <input type="text" id="ranking-search" class="form-control border-dark fw-bold text-uppercase"
                    placeholder="BUSCAR NOMBRE O TICKET..." autocomplete="off">
            </div>
        </div>
    </div>


    <div class="row mb-3">
        <div class="col-md-6 text-center">
            <div class="alert alert-warning">
             Total primeros lugares: <strong><?= (int)($totalPrimero ?? 0) ?></strong>
            </div>
        </div>
        <div class="col-md-6 text-center">
            <div class="alert alert-secondary">
               Total segundos lugares: <strong><?= (int)($totalSegundo ?? 0) ?></strong>
            </div>
        </div>
    </div>

    <div class="ph-table-container">
        <table class="ph-table" id="ranking-table">
            <thead>
                <tr class="ph-header-blue-row">
                    <th class="ph-col-name-header">PARTICIPANTE</th>
                    <?php foreach ($matches as $m):
                        $hasScore = isset($m['home_score']) && isset($m['away_score']) && $m['result_outcome'];
                    ?>
                        <th class="ph-col-match-header" title="<?= htmlspecialchars($m['home_team_name']) ?> vs <?= htmlspecialchars($m['away_team_name']) ?>">
                            <div class="d-flex flex-column align-items-center justify-content-center gap-1">
                                <?php if (!empty($m['home_team_logo'])): ?>
                                    <img src="<?= htmlspecialchars($m['home_team_logo']) ?>" class="ph-team-logo" alt="L">
                                <?php else: ?>
                                    <span>L</span>
                                <?php endif; ?>

                                <?php if ($hasScore): ?>
                                    <span class="ph-score-text">
                                        <?= $m['home_score'] ?> - <?= $m['away_score'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="ph-vs-text">vs</span>
                                <?php endif; ?>

                                <?php if (!empty($m['away_team_logo'])): ?>
                                    <img src="<?= htmlspecialchars($m['away_team_logo']) ?>" class="ph-team-logo" alt="V">
                                <?php else: ?>
                                    <span>V</span>
                                <?php endif; ?>
                            </div>
                        </th>
                    <?php endforeach; ?>
                    <th class="ph-pts-header">PTS</th>
                </tr>
                <tr class="ph-row-dark">
                    <th class="ph-col-name-header text-end text-white px-2">RESULTADOS &raquo;</th>
                    <?php foreach ($matches as $m): ?>
                        <th class="text-white"><?= $m['result_outcome'] ?? '-' ?></th>
                    <?php endforeach; ?>
                    <th class="ph-pts-header">-</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="<?= count($matches) + 2 ?>" class="p-4 text-center">No hay datos disponibles para esta jornada.</td>
                    </tr>
                <?php else: ?>
                    <?php $rank = 1;
                    foreach ($tickets as $t):
                        $picks = is_array($t['picks'] ?? null) ? $t['picks'] : [];

                        $currentPoints = (int)($t['points'] ?? 0);

                        if ($currentPoints !== $lastPoints) {
                            $colorIndex++;
                            $lastPoints = $currentPoints;
                        }

                        $pointsCellClass = $colorPalette[$colorIndex % count($colorPalette)];
                    ?>
                        <tr class="ranking-row">
                            <td class="ph-cell-name">
                                <span class="ph-rank-number"><?= $rank ?></span>
                                <span class="ph-user-name"><?= mb_strtoupper((string)($t['user_name'] ?? '')) ?></span>
<span class="d-none search-data"><?= mb_strtoupper((string)($t['user_name'] ?? '') . ' ' . (string)($t['ticket_code'] ?? '')) ?></span>
                            </td>
                            <?php foreach ($matches as $m):
                                $userPick = $picks[$m['id']] ?? '';
                                $official = $officialResults[$m['id']] ?? null;
                                $cellClass = 'ph-miss';
                                if ($userPick !== '' && $official !== null && $userPick === $official) {
                                    $cellClass = 'ph-hit';
                                }
                            ?>
                               <td class="ph-cell-pick <?= $cellClass ?>"><?= htmlspecialchars((string)($userPick ?: '-')) ?></td>
                            <?php endforeach; ?>

                            <td class="ph-pts <?= $pointsCellClass ?>"><?= (int)$t['points'] ?></td>
                        </tr>
                    <?php $rank++;
                    endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('ranking-search');
        const tableRows = document.querySelectorAll('.ranking-row');
        const countDisplay = document.getElementById('count-display');

        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                const term = e.target.value.toUpperCase();
                let visibleCount = 0;
                tableRows.forEach(row => {
                    const text = row.querySelector('.search-data').textContent;
                    if (text.includes(term)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                if (countDisplay) countDisplay.textContent = visibleCount;
            });
        }
    });
</script>

<style>
    .ph-wrapper {
        font-family: Arial, Helvetica, sans-serif;
        background-color: #f0f0f0;
        padding: 10px;
        min-height: 80vh;
    }

    /* El resto de estilos se mantienen igual */
    .ph-title-red {
        background-color: #cc0000;
        color: #fff;
        text-align: center;
        padding: 10px;
        font-weight: bold;
        font-size: 18px;
        margin-bottom: 10px;
        text-transform: uppercase;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .ph-bar-blue {
        background-color: #000080;
        color: #fff;
        text-align: center;
        padding: 8px;
        font-weight: bold;
        font-size: 14px;
        text-transform: uppercase;
        border-radius: 4px;
    }

    .ph-table-container {
        width: 100%;
        overflow-x: auto;
        background: #fff;
        border: 2px solid #000;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .ph-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
    }

    .ph-table th,
    .ph-table td {
        border: 1px solid #999;
        text-align: center;
        vertical-align: middle;
        padding: 6px 4px;
    }

    .ph-header-blue-row th {
        background-color: #000080;
        color: white;
        font-size: 12px;
    }

    .ph-row-dark th {
        background-color: #333;
        color: white;
        font-size: 12px;
    }

    .ph-col-name-header,
    .ph-cell-name {
        text-align: left;
        padding-left: 10px !important;
        min-width: 220px;
        font-size: 13px;
        font-weight: bold;
        white-space: nowrap;
    }

    .ph-col-match-header {
        min-width: 50px;
    }

    .ph-team-logo {
        width: 28px;
        height: 28px;
        object-fit: contain;
    }

    .ph-vs-text {
        font-size: 9px;
        color: #ffc107;
        font-weight: bold;
    }

    .ph-score-text {
        font-size: 11px;
        color: #fff;
        background: #000;
        padding: 1px 3px;
        border-radius: 3px;
        font-weight: bold;
    }

    .ph-cell-pick {
        font-weight: 900;
        font-size: 14px;
    }

    .ph-hit {
        background-color: #00cc00 !important;
        color: #000 !important;
    }

    .ph-miss {
        background-color: #fff !important;
        color: #000;
    }

    .ph-pts-header,
    .ph-pts {
        width: 60px;
        font-size: 15px;
        font-weight: 900;
    }

    .ph-rank-number {
        color: #000080;
        margin-right: 5px;
        font-size: 14px;
    }

    /* Nota: He eliminado las clases .ph-rank-1 y .ph-rank-2 porque ahora el color lo controla el array PHP */

    @media (max-width: 768px) {
        .ph-table-container {
            overflow-x: hidden;
            border: 1px solid #666;
        }

        .ph-table {
            table-layout: fixed;
            width: 100%;
        }

        .ph-table th,
        .ph-table td {
            padding: 2px 0px !important;
            height: 30px;
        }

        .ph-col-name-header,
        .ph-cell-name {
            width: 28% !important;
            max-width: 28% !important;
            min-width: 0 !important;
            font-size: 10px !important;
            padding-left: 4px !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ph-col-match-header,
        .ph-cell-pick {
            width: auto !important;
            font-size: 10px !important;
        }

        .ph-team-logo {
            width: 14px !important;
            height: 14px !important;
        }

        .ph-vs-text {
            display: none;
        }

        .ph-score-text {
            font-size: 9px;
            padding: 0 1px;
        }

        .ph-pts-header,
        .ph-pts {
            width: 9% !important;
            font-size: 11px !important;
        }

        .ph-rank-number {
            font-size: 9px;
            margin-right: 2px;
        }

        .ph-user-name {
            font-size: 9px;
        }
    }
</style>