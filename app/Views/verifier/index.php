<?php
if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

$ticket = $ticket ?? null;
$items = $items ?? [];
$ticketCode = $ticketCode ?? ($searchCode ?? '');
$error = $error ?? null;
$rank = $rank ?? null;

function pickLabel(string $pick): string
{
    return match (strtoupper($pick)) {
        'L' => 'Local',
        'E' => 'Empate',
        'V' => 'Visita',
        default => '-',
    };
}
?>

<div class="container py-5" style="max-width: 900px;">

    <div class="text-center mb-4">
        <h1 class="h3 fw-bold text-uppercase">Verificador de Quiniela</h1>
        <p class="text-muted">Ingresa tu código de ticket para ver tus resultados.</p>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-4 bg-light">
            <form method="get" action="/verificador" class="row g-3 justify-content-center">
                <div class="col-12 col-md-9">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-ticket-perforated"></i>
                        </span>

                        <input
                            type="text"
                            name="ticket_code"
                            class="form-control text-center fw-bold text-uppercase"
                            placeholder="Ej: QV-0001-00001"
                            value="<?= h($ticketCode) ?>"
                            required
                        >

                        <button class="btn btn-primary px-4 fw-bold" type="submit">
                            BUSCAR
                        </button>
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
                    <?= h($ticket['league_name'] ?? 'Liga') ?>
                    —
                    <?= h($ticket['round_name'] ?? 'Jornada') ?>
                </h2>
            </div>

            <div class="card-body text-center bg-white">
                <div class="row">
                    <div class="col-6 border-end">
                        <div class="small text-muted text-uppercase">Jugador</div>
                        <div class="fw-bold fs-5">
                            <?= h($ticket['user_name'] ?? $ticket['player_name'] ?? '') ?>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="small text-muted text-uppercase">Código</div>
                        <div class="fw-bold fs-5 text-primary">
                            <?= h($ticket['ticket_code'] ?? '') ?>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row align-items-center">
                    <div class="col-4">
                        <div class="small text-muted text-uppercase">Estado</div>
                        <div class="fw-bold fs-5">
                            <?= h($ticket['status'] ?? '-') ?>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="small text-muted text-uppercase">Puntos</div>
                        <div class="display-5 fw-bold text-dark">
                            <?= (int)($ticket['points'] ?? 0) ?>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="small text-muted text-uppercase">Posición</div>
                        <div class="display-5 fw-bold text-primary">
                            <?= $rank ? '#' . h($rank) : '-' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white text-center fw-bold text-uppercase">
                Detalle de resultados
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 text-center align-middle" style="font-size: 0.9rem;">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th style="width: 36%">Partido</th>
                                <th style="width: 18%">Tu pronóstico</th>
                                <th style="width: 18%">Resultado</th>
                                <th style="width: 14%">Marcador</th>
                                <th style="width: 14%">Estado</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($items === []): ?>
                                <tr>
                                    <td colspan="5" class="py-4 text-muted">
                                        Este ticket no tiene pronósticos registrados.
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($items as $item): ?>
                                <?php
                                    $myPick = strtoupper((string)($item['selection'] ?? $item['pick'] ?? ''));
                                    $official = strtoupper((string)($item['result_outcome'] ?? ''));

                                    $matchStatus = strtoupper((string)($item['match_status'] ?? ''));

                                    $hasOfficialResult = $official !== '';
                                    $isHit = $hasOfficialResult && $myPick === $official;

                                    $pickClass = '';
                                    $icon = '⏳';

                                    if ($hasOfficialResult) {
                                        $pickClass = $isHit ? 'bg-success text-white' : 'bg-danger text-white';
                                        $icon = $isHit ? '✅' : '❌';
                                    }

                                    $homeScore = $item['home_score'] ?? null;
                                    $awayScore = $item['away_score'] ?? null;

                                    $scoreLabel = '-';

                                    if ($homeScore !== null && $homeScore !== '' && $awayScore !== null && $awayScore !== '') {
                                        $scoreLabel = (int)$homeScore . ' - ' . (int)$awayScore;
                                    }
                                ?>

                                <tr>
                                    <td class="text-start ps-3">
                                        <div class="fw-bold">
                                            <?= h($item['home_team_name'] ?? '') ?>
                                        </div>

                                        <div class="small text-muted">vs</div>

                                        <div class="fw-bold">
                                            <?= h($item['away_team_name'] ?? '') ?>
                                        </div>
                                    </td>

                                    <td class="fw-bold <?= $pickClass ?>">
                                        <?= h(pickLabel($myPick)) ?>
                                        <div class="small">
                                            <?= h($myPick ?: '-') ?>
                                        </div>
                                    </td>

                                    <td class="fw-bold text-muted">
                                        <?= h($hasOfficialResult ? pickLabel($official) : 'Pendiente') ?>
                                        <div class="small">
                                            <?= h($official ?: '-') ?>
                                        </div>
                                    </td>

                                    <td class="fw-bold">
                                        <?= h($scoreLabel) ?>
                                    </td>

                                    <td class="fs-5">
                                        <div><?= $icon ?></div>
                                        <div class="small text-muted">
                                            <?= h($matchStatus ?: 'PENDIENTE') ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-light text-center small text-muted">
                * Los resultados se actualizan cuando el administrador registra los marcadores.
            </div>
        </div>
    <?php endif; ?>
</div>