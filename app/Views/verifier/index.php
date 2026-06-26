<?php
// Helper para escapar HTML
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$ticket = $ticket ?? null;
$matches = $matches ?? [];
$rank = $rank ?? '-';
$searchCode = $searchCode ?? '';
$error = $error ?? null;
?>

<div class="container py-5" style="max-width: 800px;">
    
    <div class="text-center mb-4">
        <h1 class="h3 fw-bold text-uppercase">Verificador de Quiniela</h1>
        <p class="text-muted">Ingresa tu código de ticket para ver tus resultados y posición.</p>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-4 bg-light">
            <form method="get" action="/verificador" class="row g-3 justify-content-center">
                <div class="col-12 col-md-8">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white"><i class="bi bi-ticket-perforated"></i></span>
                        <input type="text" name="ticket_code" class="form-control text-center fw-bold text-uppercase" 
                               placeholder="Ej: T2025..." value="<?= h($searchCode) ?>" required>
                        <button class="btn btn-primary px-4 fw-bold" type="submit">BUSCAR</button>
                    </div>
                </div>
            </form>
            <?php if ($error): ?>
                <div class="alert alert-danger mt-3 text-center mb-0">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($ticket): ?>
        <div class="card mb-4 border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-dark text-white py-3 text-center">
                <h2 class="h5 mb-0 text-uppercase">
                    <?= h($ticket['league_name']) ?> — <?= h($ticket['round_name']) ?>
                </h2>
            </div>
            <div class="card-body text-center bg-white">
                <div class="row">
                    <div class="col-6 border-end">
                        <div class="small text-muted text-uppercase">JUGADOR</div>
                        <div class="fw-bold fs-5"><?= h($ticket['user_name']) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted text-uppercase">CÓDIGO</div>
                        <div class="fw-bold fs-5 text-primary"><?= h($ticket['ticket_code']) ?></div>
                    </div>
                </div>
                <hr>
                <div class="row align-items-center">
                    <div class="col-6">
                         <div class="small text-muted text-uppercase">PUNTOS</div>
                         <div class="display-4 fw-bold text-dark"><?= (int)$ticket['points'] ?></div>
                    </div>
                    <div class="col-6">
                         <div class="small text-muted text-uppercase">POSICIÓN</div>
                         <?php $rankColor = ($rank == 1) ? 'text-warning' : 'text-primary'; ?>
                         <div class="display-4 fw-bold <?= $rankColor ?>">
                            <?= $rank ? '#' . $rank : '-' ?>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white text-center fw-bold text-uppercase">
                Detalle de Resultados
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 text-center align-middle" style="font-size: 0.9rem;">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th style="width: 40%">PARTIDO</th>
                                <th style="width: 20%">TU PRONÓSTICO</th>
                                <th style="width: 20%">RESULTADO</th>
                                <th style="width: 20%">ESTADO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $items = json_decode((string)$ticket['items'], true) ?: [];
                                // Mapear picks: match_id => pick
                                $picks = [];
                                foreach($items as $it) {
                                    $mid = (int)($it['match_id'] ?? 0);
                                    $pk = (string)($it['pick'] ?? $it['choice'] ?? '');
                                    if ($mid > 0) $picks[$mid] = $pk;
                                }
                            ?>

                            <?php foreach ($matches as $m): ?>
                                <?php 
                                    $mid = (int)$m['id'];
                                    $myPick = $picks[$mid] ?? '-';
                                    $official = $m['result_outcome'] ?? '-';
                                    
                                    $isHit = ($myPick !== '-' && $official !== '-' && $myPick === $official);
                                    $bgClass = $isHit ? 'bg-success text-white' : '';
                                    $icon = $isHit ? '✅' : '❌';
                                    if ($official === '-' || $official === null) {
                                        $icon = '⏳'; // Pendiente
                                        $bgClass = '';
                                    }
                                ?>
                                <tr>
                                    <td class="text-start ps-3">
                                        <div class="fw-bold"><?= h($m['home_team_name']) ?></div>
                                        <div class="small text-muted">vs</div>
                                        <div class="fw-bold"><?= h($m['away_team_name']) ?></div>
                                    </td>
                                    <td class="fw-bold fs-5 <?= $bgClass ?>">
                                        <?= h($myPick) ?>
                                    </td>
                                    <td class="fw-bold fs-5 text-muted">
                                        <?= h($official ?: 'Pend.') ?>
                                    </td>
                                    <td class="fs-5">
                                        <?= $icon ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light text-center small text-muted">
                * Los resultados se actualizan al finalizar cada partido.
            </div>
        </div>

    <?php endif; ?>
</div>