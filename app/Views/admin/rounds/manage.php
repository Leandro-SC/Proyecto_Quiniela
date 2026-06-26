<?php
declare(strict_types=1);

/** @var array<string,mixed> $round */
/** @var array<int,array<string,mixed>> $matches */
/** @var string|null $error */
require __DIR__ . '/../partials/nav.php';
?>
<div class="mb-3">
    <h1 class="h4">
        Admin · Partidos de
        <?= htmlspecialchars((string)$round['round_name'], ENT_QUOTES, 'UTF-8') ?>
        (<?= htmlspecialchars((string)$round['league_name'], ENT_QUOTES, 'UTF-8') ?>)
    </h1>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger small"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <strong>Agregar partido</strong>
    </div>
    <div class="card-body">
        <form method="post" action="/admin/matches/store" class="row g-3" novalidate>
            <input type="hidden" name="round_id" value="<?= (int)$round['id'] ?>">

            <div class="col-md-4">
                <label class="form-label" for="home_team">Equipo local</label>
                <input type="text" name="home_team" id="home_team" class="form-control"
                       value="<?= htmlspecialchars((string)($old['home_team'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="away_team">Equipo visitante</label>
                <input type="text" name="away_team" id="away_team" class="form-control"
                       value="<?= htmlspecialchars((string)($old['away_team'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="kickoff_at">Fecha/hora del partido</label>
                <input type="datetime-local" name="kickoff_at" id="kickoff_at" class="form-control"
                       value="<?= htmlspecialchars((string)($old['kickoff_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="col-12 d-flex justify-content-between mt-2">
                <a href="/admin/rounds" class="btn btn-outline-secondary btn-sm">Volver a jornadas</a>
                <button type="submit" class="btn btn-primary btn-sm">Agregar partido</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Partidos registrados</strong>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Local</th>
                        <th>Visitante</th>
                        <th>Kickoff</th>
                        <th>Estado</th>
                        <th>Resultado</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($matches)): ?>
                    <tr>
                        <td colspan="6" class="text-center small py-3">No hay partidos registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($matches as $m): ?>
                        <tr>
                            <td><?= (int)$m['id'] ?></td>
                            <td><?= htmlspecialchars((string)$m['home_team'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$m['away_team'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$m['kickoff_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$m['status'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($m['result'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
