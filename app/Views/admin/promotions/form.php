<?php
declare(strict_types=1);

/** @var array<string,mixed>|null $promotion */

$isEdit = $promotion !== null;
$action = $isEdit ? '/admin/promotions/update' : '/admin/promotions/store';

$id           = $promotion['id']           ?? null;
$name         = $promotion['name']         ?? '';
$countryCode  = $promotion['country_code'] ?? '';
$type         = $promotion['type']         ?? 'PERCENT';
$value        = $promotion['value']        ?? '';
$minAmount    = $promotion['min_amount']   ?? '';
$maxAmount    = $promotion['max_amount']   ?? '';
$startsAt     = $promotion['starts_at']    ?? '';
$endsAt       = $promotion['ends_at']      ?? '';
$isActive     = $promotion['is_active']    ?? 1;

// Normalizar datetime-local
$startValue = $startsAt !== '' ? str_replace(' ', 'T', substr((string)$startsAt, 0, 16)) : '';
$endValue   = $endsAt   !== '' ? str_replace(' ', 'T', substr((string)$endsAt, 0, 16))   : '';

require __DIR__ . '/../partials/nav.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">
        <?= $isEdit ? 'Editar promoción' : 'Crear promoción' ?>
    </h1>
    <a href="/admin/promotions" class="btn btn-sm btn-outline-secondary">
        Volver a promociones
    </a>
</div>

<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label small" for="name">Nombre de la promoción</label>
                    <input type="text" name="name" id="name"
                           class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') ?>"
                           required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small" for="country_code">País (MX, US, PE) o vacío = Global</label>
                    <input type="text" name="country_code" id="country_code"
                           class="form-control form-control-sm"
                           maxlength="2"
                           value="<?= htmlspecialchars((string)$countryCode, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small" for="type">Tipo de promoción</label>
                    <select name="type" id="type" class="form-select form-select-sm">
                        <option value="PERCENT" <?= $type === 'PERCENT' ? 'selected' : '' ?>>Porcentaje (%)</option>
                        <option value="FIXED"   <?= $type === 'FIXED' ? 'selected' : '' ?>>Monto fijo</option>
                        <option value="2X1"     <?= $type === '2X1' ? 'selected' : '' ?>>2x1</option>
                        <option value="3X2"     <?= $type === '3X2' ? 'selected' : '' ?>>3x2</option>
                    </select>
                </div>
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label small" for="value">Valor (para % o fijo)</label>
                    <input type="number" step="0.01" name="value" id="value"
                           class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small" for="min_amount">Monto mínimo</label>
                    <input type="number" step="0.01" name="min_amount" id="min_amount"
                           class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string)$minAmount, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small" for="max_amount">Monto máximo</label>
                    <input type="number" step="0.01" name="max_amount" id="max_amount"
                           class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string)$maxAmount, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <div class="form-check mt-4 pt-2">
                        <input class="form-check-input" type="checkbox" id="is_active"
                               name="is_active" <?= (int)$isActive === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="is_active">
                            Promoción activa
                        </label>
                    </div>
                </div>
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label small" for="starts_at">Inicio de vigencia</label>
                    <input type="datetime-local" name="starts_at" id="starts_at"
                           class="form-control form-control-sm"
                           value="<?= htmlspecialchars($startValue, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small" for="ends_at">Fin de vigencia</label>
                    <input type="datetime-local" name="ends_at" id="ends_at"
                           class="form-control form-control-sm"
                           value="<?= htmlspecialchars($endValue, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-sm btn-primary">
                Guardar promoción
            </button>
        </div>
    </div>
</form>
