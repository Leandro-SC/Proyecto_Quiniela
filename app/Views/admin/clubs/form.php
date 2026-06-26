<?php
declare(strict_types=1);
/** @var array<string,mixed>|null $club */
/** @var array<int,array<string,mixed>> $countries */
/** @var array<int,array<string,mixed>> $leagues */

require __DIR__ . '/../partials/nav.php';
?>
<h1 class="h4 mb-3">
    <?= $club ? 'Editar club' : 'Nuevo club' ?>
</h1>

<form method="post" enctype="multipart/form-data">
    <div class="mb-3">
        <label class="form-label">País</label>
        <select name="country_id" class="form-select" required>
            <option value="">Selecciona país</option>
            <?php foreach ($countries as $c): ?>
                <option value="<?= (int)$c['id'] ?>"
                    <?= isset($club['country_id']) && (int)$club['country_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

<div class="mb-3 position-relative" data-form="club-autocomplete">
    <label class="form-label">Nombre del club</label>
    <input type="text" 
           name="name" 
           class="form-control"
           data-club-input="single" 
           autocomplete="off"
           value="<?= htmlspecialchars((string)($club['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
           required>
    </div>

    <div class="mb-3">
        <label class="form-label">Nombre corto (opcional)</label>
        <input type="text" name="short_name" class="form-control"
               value="<?= htmlspecialchars((string)($club['short_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Liga (idLeague de TheSportsDB)</label>
        <select name="sportsdb_league_id" class="form-select">
            <option value="">Manual / varias ligas</option>
            <?php foreach ($leagues as $league): ?>
                <option value="<?= htmlspecialchars((string)$league['external_id'], ENT_QUOTES, 'UTF-8') ?>"
                    <?= isset($club['sportsdb_league_id']) && $club['sportsdb_league_id'] == $league['external_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$league['name'], ENT_QUOTES, 'UTF-8') ?>
                    (<?= htmlspecialchars((string)$league['external_id'], ENT_QUOTES, 'UTF-8') ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">idTeam en TheSportsDB (opcional)</label>
        <input type="text" name="sportsdb_team_id" class="form-control"
               value="<?= htmlspecialchars((string)($club['sportsdb_team_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Escudo</label>
        <input type="file" name="badge" class="form-control" accept=".png,.jpg,.jpeg,.gif">
        <?php if (!empty($club['badge_path'])): ?>
            <div class="mt-2">
                <img src="<?= htmlspecialchars((string)$club['badge_path'], ENT_QUOTES, 'UTF-8') ?>"
                     alt="Escudo actual"
                     style="width:40px;height:40px;object-fit:contain;border-radius:50%;">
            </div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="/admin/clubs" class="btn btn-secondary">Cancelar</a>
</form>
