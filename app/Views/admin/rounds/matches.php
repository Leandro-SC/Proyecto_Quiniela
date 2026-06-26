<?php
declare(strict_types=1);

/** @var array<string,mixed> $round */
/** @var array<int,array<string,mixed>> $matches */
/** @var string|null $flash */

// Menú admin (si ya lo tienes)
require __DIR__ . '/../partials/nav.php';

$roundId = (int)$round['id'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1">
            Partidos de la jornada:
            <?= htmlspecialchars((string)$round['name'], ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <p class="text-muted small mb-0">
            Liga:
            <?= htmlspecialchars((string)$round['league_name'], ENT_QUOTES, 'UTF-8') ?>
            · Jornada <?= (int)$round['round_number'] ?>
        </p>
    </div>
    <a href="/admin/rounds" class="btn btn-sm btn-outline-secondary">
        Volver a Jornadas
    </a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-info py-2 small">
        <?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <strong>Agregar partido manualmente</strong>
    </div>
    <div class="card-body">
        <form method="post" action="/admin/rounds/matches/store" class="row g-3">
            <input type="hidden" name="round_id" value="<?= $roundId ?>">

            <div class="col-12 col-md-4">
                <label class="form-label small" for="home_team_name">Equipo local</label>
                <input type="text" name="home_team_name" id="home_team_name"
                       class="form-control form-control-sm"
                       required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small" for="away_team_name">Equipo visitante</label>
                <input type="text" name="away_team_name" id="away_team_name"
                       class="form-control form-control-sm"
                       required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small" for="kickoff_at">Fecha/hora partido</label>
                <input type="datetime-local" name="kickoff_at" id="kickoff_at"
                       class="form-control form-control-sm">
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label small" for="home_team_logo">Logo local (URL o ruta)</label>
                <input type="text" name="home_team_logo" id="home_team_logo"
                       class="form-control form-control-sm"
                       placeholder="/assets/img/benfica.png">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small" for="away_team_logo">Logo visitante (URL o ruta)</label>
                <input type="text" name="away_team_logo" id="away_team_logo"
                       class="form-control form-control-sm"
                       placeholder="/assets/img/napoli.png">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small" for="status">Estado</label>
                <select name="status" id="status"
                        class="form-select form-select-sm">
                    <option value="SCHEDULED">Programado</option>
                    <option value="LIVE">En vivo</option>
                    <option value="FINISHED">Finalizado</option>
                    <option value="CANCELLED">Cancelado</option>
                </select>
            </div>

            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-primary">
                    Agregar partido
                </button>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h5 mb-0">Partidos configurados</h2>
    <form method="post" action="/admin/rounds/matches/import-api" class="d-inline">
        <input type="hidden" name="round_id" value="<?= $roundId ?>">
        <button type="submit" class="btn btn-sm btn-outline-primary"
                onclick="return confirm('Se intentará importar partidos desde la API configurada. ¿Continuar?');">
            Importar desde API (liga/jornada)
        </button>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th style="width: 40px;">L</th>
                        <th>Local</th>
                        <th style="width: 60px;" class="text-center">Empate</th>
                        <th>Visitante</th>
                        <th style="width: 40px;">V</th>
                        <th>Fecha/hora</th>
                        <th style="width: 120px;">Estado</th>
                        <th style="width: 120px;"></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($matches)): ?>
                    <tr>
                        <td colspan="9" class="text-center small py-3">
                            Aún no hay partidos cargados para esta jornada.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = 0; ?>
                    <?php foreach ($matches as $m): ?>
                        <?php $i++; ?>
                        <tr>
                            <td class="text-muted small"><?= $i ?></td>
                            <td class="text-center">L</td>
                            <td>
                                <form method="post" action="/admin/rounds/matches/update" class="row g-1 align-items-center">
                                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                    <input type="hidden" name="round_id" value="<?= $roundId ?>">

                                    <div class="col-auto">
                                        <?php if (!empty($m['home_team_logo'])): ?>
                                            <img src="<?= htmlspecialchars((string)$m['home_team_logo'], ENT_QUOTES, 'UTF-8') ?>"
                                                 alt="Logo local"
                                                 style="height:24px;width:24px;object-fit:contain;border-radius:50%;">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col">
                                        <input type="text" name="home_team_name"
                                               class="form-control form-control-sm"
                                               value="<?= htmlspecialchars((string)$m['home_team_name'], ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                            </td>
                            <td class="text-center fw-semibold">E</td>
                            <td>
                                    <div class="row g-1 align-items-center">
                                        <div class="col">
                                            <input type="text" name="away_team_name"
                                                   class="form-control form-control-sm"
                                                   value="<?= htmlspecialchars((string)$m['away_team_name'], ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-auto">
                                            <?php if (!empty($m['away_team_logo'])): ?>
                                                <img src="<?= htmlspecialchars((string)$m['away_team_logo'], ENT_QUOTES, 'UTF-8') ?>"
                                                     alt="Logo visitante"
                                                     style="height:24px;width:24px;object-fit:contain;border-radius:50%;">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                            </td>
                            <td class="text-center">V</td>
                            <td style="min-width: 160px;">
                                <?php
                                $kickoff = $m['kickoff_at'] ?? '';
                                $kickValue = $kickoff !== ''
                                    ? str_replace(' ', 'T', substr((string)$kickoff, 0, 16))
                                    : '';
                                ?>
                                <input type="datetime-local" name="kickoff_at"
                                       class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($kickValue, ENT_QUOTES, 'UTF-8') ?>">
                            </td>
                            <td>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="SCHEDULED" <?= $m['status'] === 'SCHEDULED' ? 'selected' : '' ?>>Programado</option>
                                    <option value="LIVE"      <?= $m['status'] === 'LIVE'      ? 'selected' : '' ?>>En vivo</option>
                                    <option value="FINISHED"  <?= $m['status'] === 'FINISHED'  ? 'selected' : '' ?>>Finalizado</option>
                                    <option value="CANCELLED" <?= $m['status'] === 'CANCELLED' ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="submit" class="btn btn-primary">
                                        Guardar
                                    </button>
                                </form>
                                    <form method="post" action="/admin/rounds/matches/delete"
                                          onsubmit="return confirm('¿Eliminar este partido?');">
                                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                        <input type="hidden" name="round_id" value="<?= $roundId ?>">
                                        <button type="submit" class="btn btn-outline-danger">
                                            X
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
