<?php
/** @var array $matchdays */
/** @var int $selectedRoundId */
/** @var array|null $selectedRound */
/** @var array $tickets */
/** @var array $matches */
/** @var array $prizes */
/** @var string $currencyCode */

// Helper: Resultados oficiales
$officialResults = [];
foreach ($matches as $m) {
    if (!empty($m['result_outcome'])) {
        $officialResults[$m['id']] = $m['result_outcome'];
    }
}
?>

<div class="ph-wrapper">

    <div class="ph-title-red mb-3">
        RESULTADOS Y GANADORES ANTERIORES
    </div>

    <div class="container mb-4">
        <form action="/quiniela/anterior" method="get" class="row justify-content-center align-items-center g-2">
            <div class="col-auto">
                <label class="fw-bold text-dark text-uppercase small">Seleccionar Jornada:</label>
            </div>
            <div class="col-auto">
                <select name="matchday_id" class="form-select form-select-sm border-dark fw-bold text-uppercase shadow-sm" 
                        onchange="this.form.submit()" style="min-width: 250px; background-color: #fff;">
                    <?php if (empty($matchdays)): ?>
                        <option value="">NO HAY QUINIELAS CERRADAS</option>
                    <?php else: ?>
                        <?php foreach ($matchdays as $day): ?>
                            <option value="<?= $day['id'] ?>" <?= $selectedRoundId == $day['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($day['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($selectedRound): ?>

        <div class="ph-bar-blue mb-3">
            <?= htmlspecialchars($selectedRound['label']) ?>
        </div>

        <div class="row justify-content-center mb-4">
            <div class="col-auto">
                <div class="d-flex gap-3 align-items-center justify-content-center flex-wrap">
                    
                    <div class="position-relative bg-white text-dark border border-warning border-3 rounded-3 px-4 py-2 shadow-sm text-center" 
                         style="min-width: 140px;">
                        <div class="position-absolute top-0 start-50 translate-middle badge bg-warning text-dark border border-light shadow-sm"
                             style="font-size: 0.7rem;">
                            🥇 GANADOR
                        </div>
                        <div class="fw-black fs-3 text-success mt-2">
                            $<?= number_format($prizes['first'], 2) ?>
                        </div>
                        <small class="text-muted fw-bold" style="font-size: 0.7rem;"><?= $currencyCode ?></small>
                    </div>

                    <div class="position-relative bg-white text-dark border border-secondary border-3 rounded-3 px-4 py-2 shadow-sm text-center" 
                         style="min-width: 140px;">
                        <div class="position-absolute top-0 start-50 translate-middle badge bg-secondary text-white border border-light shadow-sm"
                             style="font-size: 0.7rem;">
                            🥈 SEGUNDO
                        </div>
                        <div class="fw-black fs-3 text-primary mt-2">
                            $<?= number_format($prizes['second'], 2) ?>
                        </div>
                        <small class="text-muted fw-bold" style="font-size: 0.7rem;"><?= $currencyCode ?></small>
                    </div>

                </div>
            </div>
        </div>

        <div class="row justify-content-center mb-2">
            <div class="col-12 text-center mb-2 fw-bold small">
                PARTICIPANTES: <span id="count-display"><?= count($tickets) ?></span>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-dark"><i class="bi bi-search"></i></span>
                    <input type="text" id="historial-search" class="form-control border-dark fw-bold text-uppercase" 
                           placeholder="BUSCAR TICKET O NOMBRE..." autocomplete="off">
                </div>
            </div>
        </div>

        <div class="ph-table-container">
            <table class="ph-table" id="history-table">
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
                        <tr><td colspan="<?= count($matches) + 2 ?>" class="p-4 text-center">No hay tickets en esta jornada.</td></tr>
                    <?php else: ?>
                        <?php $rank = 1; foreach ($tickets as $t): 
                            $items = json_decode((string)$t['items'], true);
                            $picks = [];
                            if (is_array($items)) foreach ($items as $it) $picks[$it['match_id']] = $it['pick'] ?? '-';
                            
                            $rankClass = 'ph-rank-std';
                            if ($rank === 1) $rankClass = 'ph-rank-1';
                            elseif ($rank === 2) $rankClass = 'ph-rank-2';
                            elseif ($rank === 3) $rankClass = 'ph-rank-2'; // 3ro también cyan o bronce
                            else $rankClass = 'bg-white';
                        ?>
                            <tr class="history-row">
                                <td class="ph-cell-name">
                                    <span class="ph-rank-number"><?= $rank ?></span>
                                    <span class="ph-user-name"><?= mb_strtoupper($t['user_name']) ?></span>
                                    <span class="d-none search-data"><?= mb_strtoupper($t['user_name'] . ' ' . $t['ticket_code']) ?></span>
                                </td>
                                <?php foreach ($matches as $m): 
                                    $userPick = $picks[$m['id']] ?? '';
                                    $official = $officialResults[$m['id']] ?? null;
                                    $cellClass = 'ph-miss';
                                    if ($userPick !== '' && $official !== null && $userPick === $official) {
                                        $cellClass = 'ph-hit';
                                    }
                                ?>
                                    <td class="ph-cell-pick <?= $cellClass ?>"><?= $userPick ?></td>
                                <?php endforeach; ?>
                                <td class="ph-pts <?= $rankClass ?>"><?= (int)$t['points'] ?></td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="alert alert-info text-center">
            Selecciona una jornada cerrada para ver los resultados históricos.
        </div>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('historial-search');
    const tableRows = document.querySelectorAll('.history-row');
    const countDisplay = document.getElementById('count-display');

    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const term = e.target.value.toUpperCase();
            let visibleCount = 0;
            tableRows.forEach(row => {
                const text = row.querySelector('.search-data').textContent;
                if (text.includes(term)) { row.style.display = ''; visibleCount++; } 
                else { row.style.display = 'none'; }
            });
            if(countDisplay) countDisplay.textContent = visibleCount;
        });
    }
});
</script>

<style>
    .ph-wrapper { font-family: Arial, Helvetica, sans-serif; background-color: #f0f0f0; padding: 10px; min-height: 80vh; }
    .ph-table-container { width: 100%; overflow-x: auto; background: #fff; border: 2px solid #000; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .ph-table { width: 100%; border-collapse: collapse; table-layout: auto; }
    .ph-table th, .ph-table td { border: 1px solid #999; text-align: center; vertical-align: middle; padding: 6px 4px; }
    
    .ph-col-name-header, .ph-cell-name { text-align: left; padding-left: 10px !important; min-width: 220px; font-size: 13px; font-weight: bold; white-space: nowrap; }
    .ph-col-match-header { min-width: 50px; }
    .ph-team-logo { width: 28px; height: 28px; object-fit: contain; }
    
    .ph-vs-text { font-size: 9px; color: #ffc107; font-weight: bold; }
    .ph-score-text { font-size: 11px; color: #fff; background: #000; padding: 1px 3px; border-radius: 3px; font-weight: bold; }
    
    .ph-cell-pick { font-weight: 900; font-size: 14px; }
    .ph-hit { background-color: #00cc00 !important; color: #000 !important; }
    .ph-miss { background-color: #fff !important; color: #000; }
    
    .ph-pts-header, .ph-pts { width: 60px; font-size: 15px; font-weight: 900; }
    .ph-rank-number { color: #000080; margin-right: 5px; font-size: 14px; }
    
    .ph-rank-1 { background-color: #ffff00 !important; }
    .ph-rank-2 { background-color: #00ffff !important; }
    .ph-rank-std { background-color: #ffff99 !important; }

    @media (max-width: 768px) {
        .ph-table-container { overflow-x: hidden; border: 1px solid #666; }
        .ph-table { table-layout: fixed; width: 100%; }
        .ph-table th, .ph-table td { padding: 2px 0px !important; height: 30px; }
        .ph-col-name-header, .ph-cell-name { width: 28% !important; max-width: 28% !important; min-width: 0 !important; font-size: 10px !important; padding-left: 4px !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ph-col-match-header, .ph-cell-pick { width: auto !important; font-size: 10px !important; }
        .ph-team-logo { width: 14px !important; height: 14px !important; }
        .ph-vs-text { display: none; }
        .ph-score-text { font-size: 9px; padding: 0 1px; }
        .ph-pts-header, .ph-pts { width: 9% !important; font-size: 11px !important; }
        .ph-rank-number { font-size: 9px; margin-right: 2px; }
        .ph-user-name { font-size: 9px; }
    }
</style>