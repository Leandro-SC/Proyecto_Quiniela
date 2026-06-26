<?php

declare(strict_types=1);

/** @var array<string,mixed> $round */
/** @var array<int,array<string,mixed>> $rounds */
/** @var array<int,array<string,mixed>> $matches */
/** @var array<int,array<string,mixed>> $tickets */
/** @var array<string,mixed> $summary */

?>

<?php
$round = $round ?? null;
$rounds = $rounds ?? [];
$matches = $matches ?? [];
$tickets = $tickets ?? [];
$summary = $summary ?? [];
$search = $search ?? ($searchQuery ?? '');
?>

<div class="mb-3 text-center">
    <h1 class="h4 mb-1 text-uppercase">
        <?= htmlspecialchars((string)$round['name'], ENT_QUOTES, 'UTF-8') ?>
    </h1>
    <div class="small">
        <?= htmlspecialchars((string)($round['league_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="small text-danger fw-bold mt-1">
        Total recaudado:
        $<?= number_format((float)$summary['total_collected'], 2) ?>
        · 1° premio total:
        $<?= number_format((float)$summary['first_prize_total'], 2) ?>
        · 2° premio total:
        $<?= number_format((float)$summary['second_prize_total'], 2) ?>
    </div>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-md-4">
        <label class="form-label small mb-1">Jornada</label>
        <select name="round_id" class="form-select form-select-sm">
            <?php foreach ($rounds as $r): ?>
                <option value="<?= (int)$r['id'] ?>"
                    <?= (int)$r['id'] === (int)$round['id'] ? 'selected' : '' ?>>
                    <?= (int)$r['id'] ?> - <?= htmlspecialchars((string)$r['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label small mb-1">Búsqueda (código o nombre)</label>
        <input type="text"
            name="q"
            class="form-control form-control-sm"
            value="<?= htmlspecialchars((string)$search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar quiniela...">
    </div>
    <div class="col-12 col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-sm btn-primary w-100">Aplicar</button>
    </div>
</form>

<?php if (empty($matches) || empty($tickets)): ?>
    <div class="alert alert-info">
        Aún no hay partidos con resultado y tickets pagados para esta jornada.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm align-middle" style="min-width: 900px;">
            <thead>
                <tr class="table-primary text-center align-middle">
                    <th rowspan="3" style="min-width: 150px;">ID / NOMBRE</th>
                    <?php foreach ($matches as $m): ?>
                        <th colspan="1" style="min-width: 80px;">
                            <div class="d-flex flex-column align-items-center justify-content-center">
                                <div class="small fw-semibold">
                                    <?= htmlspecialchars((string)$m['home_team_name'], ENT_QUOTES, 'UTF-8') ?>
                                    vs
                                    <?= htmlspecialchars((string)$m['away_team_name'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </th>
                    <?php endforeach; ?>
                    <th rowspan="3" class="table-warning">PTS</th>
                </tr>
                <tr class="table-primary text-center align-middle">
                    <?php foreach ($matches as $m): ?>
                        <th>
                            <?php if (!empty($m['home_team_logo']) || !empty($m['away_team_logo'])): ?>
                                <div class="d-flex justify-content-center gap-1">
                                    <?php if (!empty($m['home_team_logo'])): ?>
                                        <img src="<?= htmlspecialchars((string)$m['home_team_logo'], ENT_QUOTES, 'UTF-8') ?>"
                                            alt="Local"
                                            style="width:20px;height:20px;object-fit:contain;border-radius:50%;">
                                    <?php endif; ?>
                                    <?php if (!empty($m['away_team_logo'])): ?>
                                        <img src="<?= htmlspecialchars((string)$m['away_team_logo'], ENT_QUOTES, 'UTF-8') ?>"
                                            alt="Visita"
                                            style="width:20px;height:20px;object-fit:contain;border-radius:50%;">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="small text-muted">
                                Resultado:
                                <?= htmlspecialchars((string)($m['result_outcome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                    <?php
                    $byMatch = is_array($t['picks'] ?? null) ? $t['picks'] : [];
                    ?>
                    <tr>
                        <td class="small">
                            <strong><?= htmlspecialchars((string)$t['ticket_code'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <br>
                            <?= htmlspecialchars((string)($t['user_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <?php foreach ($matches as $m): ?>
                            <?php
                            $mid     = (int)$m['id'];
                            $pick    = $byMatch[$mid] ?? '';
                            $result  = (string)($m['result_outcome'] ?? '');
                            $class   = '';
                            if ($pick !== '' && $result !== '') {
                                if ($pick === $result) {
                                    $class = 'bg-success text-white';
                                } elseif ($pick !== 'E' && $result !== 'E' && $pick !== $result) {
                                    $class = 'bg-info text-white';
                                } else {
                                    $class = 'bg-white';
                                }
                            }
                            ?>
                            <td class="text-center <?= $class ?>">
                                <strong><?= htmlspecialchars($pick, ENT_QUOTES, 'UTF-8') ?></strong>
                            </td>
                        <?php endforeach; ?>
                        <td class="text-center table-warning fw-bold">
                            <?= (int)($t['points'] ?? 0) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>