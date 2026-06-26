<?php
declare(strict_types=1);
/** @var array<string,mixed>|null $country */
require __DIR__ . '/../partials/nav.php';
?>
<h1 class="h4 mb-3">
    <?= $country ? 'Editar país' : 'Nuevo país' ?>
</h1>

<form method="post" enctype="multipart/form-data">
    <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" class="form-control"
               value="<?= htmlspecialchars((string)($country['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Código ISO (2 letras)</label>
        <input type="text" name="iso_code" class="form-control"
               value="<?= htmlspecialchars((string)($country['iso_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="2" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Nombre en TheSportsDB (opcional)</label>
        <input type="text" name="sportsdb_country_name" class="form-control"
               value="<?= htmlspecialchars((string)($country['sportsdb_country_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Bandera (imagen pequeña)</label>
        <input type="file" name="flag" class="form-control" accept=".png,.jpg,.jpeg,.gif">
        <?php if (!empty($country['flag_path'])): ?>
            <div class="mt-2">
                <img src="<?= htmlspecialchars((string)$country['flag_path'], ENT_QUOTES, 'UTF-8') ?>"
                     alt="Bandera actual" style="width:40px;height:26px;object-fit:cover;">
            </div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="/admin/countries" class="btn btn-secondary">Cancelar</a>
</form>
