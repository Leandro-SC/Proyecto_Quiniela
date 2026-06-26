<?php
declare(strict_types=1);

/** @var array<int,array<string,mixed>> $leagues */
/** @var array<string,mixed>|null $old */
/** @var string|null $error */
$old = $old ?? [];
require __DIR__ . '/../partials/nav.php';
?>
<div class="mb-3">
    <h1 class="h4">Admin · Nueva jornada</h1>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger small"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="/admin/rounds/store" novalidate>
            <div class="mb-3">
                <label for="league_id" class="form-label">Liga</label>
                <select name="league_id" id="league_id" class="form-select" required>
                    <option value="">Selecciona una liga</option>
                    <?php foreach ($leagues as $league): ?>
                        <option value="<?= (int)$league['id'] ?>"
                            <?= ((int)($old['league_id'] ?? 0) === (int)$league['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$league['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Nombre de la jornada</label>
                <input type="text" name="name" id="name" class="form-control"
                       value="<?= htmlspecialchars((string)($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="mb-3">
                <label for="round_number" class="form-label">Número de jornada</label>
                <input type="number" name="round_number" id="round_number" class="form-control"
                       value="<?= htmlspecialchars((string)($old['round_number'] ?? '1'), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Estado</label>
                <select name="status" id="status" class="form-select">
                    <option value="OPEN" <?= (($old['status'] ?? 'OPEN') === 'OPEN') ? 'selected' : '' ?>>Abierta</option>
                    <option value="CLOSED" <?= (($old['status'] ?? '') === 'CLOSED') ? 'selected' : '' ?>>Cerrada</option>
                </select>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="open_at" class="form-label">Fecha/hora de apertura</label>
                    <input type="datetime-local" name="open_at" id="open_at" class="form-control"
                           value="<?= htmlspecialchars((string)($old['open_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="close_at" class="form-label">Fecha/hora de cierre</label>
                    <input type="datetime-local" name="close_at" id="close_at" class="form-control"
                           value="<?= htmlspecialchars((string)($old['close_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label for="ticket_cost_mxn" class="form-label">Costo por ticket (MXN)</label>
                    <input type="number" step="0.01" name="ticket_cost_mxn" id="ticket_cost_mxn" class="form-control"
                           value="<?= htmlspecialchars((string)($old['ticket_cost_mxn'] ?? '200'), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="ticket_cost_usd" class="form-label">Costo por ticket (USD)</label>
                    <input type="number" step="0.01" name="ticket_cost_usd" id="ticket_cost_usd" class="form-control"
                           value="<?= htmlspecialchars((string)($old['ticket_cost_usd'] ?? '10'), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>

            <div class="mt-3 d-flex justify-content-between">
                <a href="/admin/rounds" class="btn btn-outline-secondary btn-sm">Volver</a>
                <button type="submit" class="btn btn-primary btn-sm">Guardar jornada</button>
            </div>
        </form>
    </div>
</div>