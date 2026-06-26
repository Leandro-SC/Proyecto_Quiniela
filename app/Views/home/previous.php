<?php
declare(strict_types=1);

/**
 * Historial de quinielas + ranking (vista cliente).
 */

// Fallbacks defensivos
$league          = $league          ?? null;
$rounds          = $rounds          ?? [];
$selectedRoundId = $selectedRoundId ?? 0;
$selectedRound   = $selectedRound   ?? null;
$matches         = $matches         ?? [];
$rankingRows     = $rankingRows     ?? [];
$roundSummary    = $roundSummary    ?? [
    'total_tickets'      => 0,
    'total_collected'    => 0.0,
    'pool_total'         => 0.0,
    'first_prize_total'  => 0.0,
    'second_prize_total' => 0.0,
];

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function money_fmt_simple($amount): string {
    return '$' . number_format((float)$amount, 2, '.', ',');
}

// Map: match_id => outcome
$outcomesByMatchId = [];
foreach ($matches as $m) {
    $outcomesByMatchId[(int)$m['id']] = $m['result_outcome'] ?? null;
}

// Map: [ticket_id][match_id] = pick
$picksByTicketAndMatch = [];
foreach ($rankingRows as $row) {
    $tid = (int)$row['id'];
    $itemsJson = (string)($row['items'] ?? '[]');
    $items = json_decode($itemsJson, true);
    if (!is_array($items)) {
        continue;
    }
    foreach ($items as $item) {
        if (!is_array($item)) continue;
        $mid  = isset($item['match_id']) ? (int)$item['match_id'] : 0;
        $pick = isset($item['pick']) ? strtoupper((string)$item['pick']) : '';
        if ($mid > 0 && $pick !== '') {
            $picksByTicketAndMatch[$tid][$mid] = $pick;
        }
    }
}
?>
<section class="quiniela-previous py-3">
    <!-- Encabezado -->
    <div class="mb-3 p-3 rounded-3" style="background:#f0f0f0;">
        <div class="d-flex flex-column flex-md-row justify-content-between">
            <div>
                <h1 class="h5 mb-1 text-uppercase fw-bold">
                    <?= h($league['name'] ?? 'Quiniela Liga MX') ?>
                </h1>
                <p class="mb-0 small text-muted">
                    Historial de jornadas · Resultados oficiales · Ranking de participantes
                </p>
            </div>
            <div class="small text-md-end mt-2 mt-md-0">
                <div class="fw-semibold">Temporada 1</div>
                <?php if ($selectedRound !== null): ?>
                    <div class="text-muted">
                        Jornada <?= h($selectedRound['round_number'] ?? '') ?> ·
                        <?= h($selectedRound['name'] ?? '') ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted">
                        Selecciona una jornada disponible
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Barra azul con nombre de jornada y premios -->
    <div class="mb-3 p-3 rounded-3" style="background:#001b4d; color:#fff;">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2">
            <div>
                <div class="small text-uppercase text-warning mb-1">
                    Quiniela de la jornada
                </div>
                <div class="h5 mb-0 fw-bold">
                    <?= h($selectedRound['name'] ?? 'Selecciona una jornada') ?>
                </div>
                <?php if ($selectedRound !== null): ?>
                    <div class="small text-light">
                        Del <?= h($selectedRound['open_at'] ?? '') ?>
                        al <?= h($selectedRound['close_at'] ?? '') ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="small text-lg-end">
                <div>Boletos vendidos:
                    <strong><?= (int)$roundSummary['total_tickets'] ?></strong>
                </div>
                <div>Recaudado:
                    <strong><?= money_fmt_simple($roundSummary['total_collected']) ?></strong>
                </div>
                <div>Premio 1er lugar:
                    <strong><?= money_fmt_simple($roundSummary['first_prize_total']) ?></strong>
                </div>
                <div>Premio 2do lugar:
                    <strong><?= money_fmt_simple($roundSummary['second_prize_total']) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Si no hay rounds CLOSED para esta liga -->
    <?php if (empty($rounds)): ?>
        <div class="alert alert-info small">
            Aún no hay jornadas históricas cerradas para esta liga.
        </div>
        <?php return; ?>
    <?php endif; ?>

    <!-- Selector de jornada -->
    <form method="get" action="/quiniela/anterior" class="mb-3">
        <?php if (!empty($league['slug'] ?? '')): ?>
            <input type="hidden" name="league" value="<?= h($league['slug']) ?>">
        <?php endif; ?>
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6 col-lg-4">
                <label for="round_id" class="form-label small text-uppercase fw-semibold">
                    Selecciona la jornada
                </label>
                <select name="round_id" id="round_id" class="form-select form-select-sm">
                    <?php foreach ($rounds as $r): ?>
                        <?php
                            $rid  = (int)$r['id'];
                            $text = 'J' . ($r['round_number'] ?? '') . ' · ' . ($r['name'] ?? '');
                        ?>
                        <option value="<?= $rid ?>" <?= $rid === $selectedRoundId ? 'selected' : '' ?>>
                            <?= h($text) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 mt-3 mt-md-0">
                    Ver jornada
                </button>
            </div>
        </div>
    </form>

    <!-- Si hay jornada seleccionada, mostramos matriz -->
    <?php if ($selectedRound === null): ?>
        <div class="alert alert-secondary small">
            Selecciona una jornada en el filtro superior para ver resultados y ranking.
        </div>
        <?php return; ?>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:0.8rem;">
                    <thead>
                        <tr style="background:#e6e6e6;">
                            <th class="text-start align-middle" style="min-width:160px;">
                                Participante
                            </th>

                            <?php foreach ($matches as $m): ?>
                                <th class="text-center align-middle" style="min-width:90px; border-left:1px solid #ccc;">
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="fw-semibold" style="font-size:0.75rem;">
                                            <?= h($m['home_team_name'] ?? '') ?>
                                        </span>
                                        <span class="small text-muted">vs</span>
                                        <span class="fw-semibold" style="font-size:0.75rem;">
                                            <?= h($m['away_team_name'] ?? '') ?>
                                        </span>
                                        <?php if (!empty($m['result_score'])): ?>
                                            <span class="badge bg-dark mt-1" style="font-size:0.65rem;">
                                                <?= h($m['result_score']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </th>
                            <?php endforeach; ?>

                            <th class="text-center align-middle" style="min-width:60px; border-left:1px solid #ccc;">
                                PTS
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Fila de resultados oficiales -->
                        <tr style="background:#000066; color:#fff;">
                            <td class="fw-bold text-start">
                                Resultado oficial
                            </td>
                            <?php foreach ($matches as $m): ?>
                                <?php $outcome = $m['result_outcome'] ?? '-'; ?>
                                <td class="text-center fw-bold">
                                    <?= h($outcome ?: '-') ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="text-center fw-bold">–</td>
                        </tr>

                        <?php if (empty($rankingRows)): ?>
                            <tr>
                                <td colspan="<?= count($matches) + 2 ?>" class="text-center py-4 text-muted">
                                    No hay tickets pagados para esta jornada, no se puede mostrar ranking.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $rank      = 0;
                            $lastScore = null;
                            ?>
                            <?php foreach ($rankingRows as $row): ?>
                                <?php
                                    $tid    = (int)$row['id'];
                                    $points = (int)$row['points'];

                                    if ($lastScore === null || $points < $lastScore) {
                                        $rank++;
                                        $lastScore = $points;
                                    }

                                    // Colores del ranking
                                    $badgeClass = 'bg-light text-dark';
                                    if ($rank === 1) {
                                        $badgeClass = 'bg-warning text-dark';
                                    } elseif ($rank === 2) {
                                        $badgeClass = 'bg-info text-dark';
                                    }
                                ?>
                                <tr>
                                    <td class="text-start align-middle" style="background:#f7f7f7;">
                                        <div class="d-flex flex-column">
                                            <span class="fw-semibold">
                                                <?= h($row['user_name'] ?? '') ?>
                                            </span>
                                            <span class="text-muted" style="font-size:0.7rem;">
                                                <?= h($row['phone'] ?? '') ?>
                                            </span>
                                            <span class="text-muted" style="font-size:0.7rem;">
                                                Ticket: <?= h($row['ticket_code'] ?? '') ?>
                                            </span>
                                        </div>
                                    </td>

                                    <?php foreach ($matches as $m): ?>
                                        <?php
                                            $mid     = (int)$m['id'];
                                            $pick    = $picksByTicketAndMatch[$tid][$mid] ?? '';
                                            $outcome = $outcomesByMatchId[$mid] ?? null;

                                            $cellStyle = '';
                                            if ($pick !== '' && $outcome !== null) {
                                                if ($pick === $outcome) {
                                                    $cellStyle = 'background:#00cc66;color:#fff;';
                                                }
                                            }
                                        ?>
                                        <td class="text-center align-middle" style="<?= $cellStyle ?>">
                                            <?= h($pick ?: '-') ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <td class="text-center align-middle">
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= $points ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer small">
            <span class="me-3">
                <span class="badge" style="background:#00cc66;">&nbsp;&nbsp;</span>
                <span class="ms-1">Acierto (coincide con resultado oficial)</span>
            </span>
            <span class="me-3">
                <span class="badge bg-warning">&nbsp;&nbsp;</span>
                <span class="ms-1">Líderes del ranking</span>
            </span>
            <span class="me-3">
                <span class="badge bg-info">&nbsp;&nbsp;</span>
                <span class="ms-1">Segundo lugar</span>
            </span>
        </div>
    </div>
</section>
