<?php

declare(strict_types=1);

use App\Core\Security;

/**
 * @var array<string,mixed>|null $club
 * @var array<int,array<string,mixed>> $countries
 * @var array<int,array<string,mixed>> $leagues
 * @var string|null $error
 */

require __DIR__ . '/../partials/nav.php';

$isEdit = is_array($club) && !empty($club['id']);
$action = $isEdit ? '/admin/clubs/update' : '/admin/clubs/store';

$id = (int)($club['id'] ?? 0);
$name = (string)($club['name'] ?? '');
$shortName = (string)($club['short_name'] ?? '');
$slug = (string)($club['slug'] ?? '');
$countryId = (int)($club['country_id'] ?? 0);
$leagueId = (int)($club['league_id'] ?? 0);
$logoPath = (string)($club['logo_path'] ?? $club['badge_path'] ?? '');
$isActive = (int)($club['is_active'] ?? 1) === 1;
?>

<section class="admin-mobile-page qv-admin-club-form-page">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Equipos</span>
            <h1><?= $isEdit ? 'Editar club' : 'Nuevo club' ?></h1>
            <p>
                Define el país, liga, nombre y escudo del club para usarlo en partidos.
            </p>
        </div>

        <a href="/admin/clubs" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Volver
        </a>
    </header>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= Security::e($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= Security::e($action) ?>" enctype="multipart/form-data" class="qv-admin-edit-shell">
        <?= Security::csrfInput() ?>

        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= $id ?>">
        <?php endif; ?>

        <input type="hidden" name="logo_path" value="<?= Security::e($logoPath) ?>">
        <input type="hidden" name="badge_path" value="<?= Security::e($logoPath) ?>">

        <section class="qv-admin-edit-main">
            <article class="qv-admin-form-card">
                <div class="qv-admin-form-card-head">
                    <i class="bi bi-shield-shaded"></i>
                    <div>
                        <strong>Información del club</strong>
                        <span>Datos principales del equipo.</span>
                    </div>
                </div>

                <div class="qv-admin-form-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="country_id" class="form-label">País</label>
                            <select name="country_id" id="country_id" class="form-select" required>
                                <option value="">Selecciona país</option>

                                <?php foreach ($countries as $country): ?>
                                    <?php $currentCountryId = (int)($country['id'] ?? 0); ?>
                                    <option value="<?= $currentCountryId ?>" <?= $countryId === $currentCountryId ? 'selected' : '' ?>>
                                        <?= Security::e((string)($country['name'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="league_id" class="form-label">Liga</label>
                            <select name="league_id" id="league_id" class="form-select">
                                <option value="0">Sin liga específica</option>

                                <?php foreach ($leagues as $league): ?>
                                    <?php $currentLeagueId = (int)($league['id'] ?? 0); ?>
                                    <option value="<?= $currentLeagueId ?>" <?= $leagueId === $currentLeagueId ? 'selected' : '' ?>>
                                        <?= Security::e((string)($league['name'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="name" class="form-label">Nombre del club</label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control form-control-lg"
                                value="<?= Security::e($name) ?>"
                                autocomplete="off"
                                required
                            >
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="short_name" class="form-label">Nombre corto</label>
                            <input
                                type="text"
                                name="short_name"
                                id="short_name"
                                class="form-control"
                                value="<?= Security::e($shortName) ?>"
                                placeholder="Ej. MX, AME, TIG"
                            >
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="slug" class="form-label">Slug</label>
                            <input
                                type="text"
                                name="slug"
                                id="slug"
                                class="form-control"
                                value="<?= Security::e($slug) ?>"
                                placeholder="Se genera automáticamente si lo dejas vacío"
                            >
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch p-3 rounded border bg-light">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="is_active"
                                    id="is_active"
                                    value="1"
                                    <?= $isActive ? 'checked' : '' ?>
                                >

                                <label class="form-check-label fw-bold" for="is_active">
                                    Club activo
                                </label>

                                <div class="small text-muted">
                                    Los clubes activos pueden seleccionarse al crear partidos.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <aside class="qv-admin-edit-side">
            <article class="qv-admin-form-card">
                <div class="qv-admin-form-card-head">
                    <i class="bi bi-image-fill"></i>
                    <div>
                        <strong>Escudo</strong>
                        <span>Imagen del club.</span>
                    </div>
                </div>

                <div class="qv-admin-form-card-body">
                    <div class="qv-admin-logo-preview-box qv-admin-club-logo-preview">
                        <?php if ($logoPath !== ''): ?>
                            <img src="<?= Security::e($logoPath) ?>" alt="Escudo actual" class="qv-admin-logo-preview-img">
                        <?php else: ?>
                            <span class="qv-admin-logo-preview-empty">
                                <i class="bi bi-image"></i>
                                Sin escudo
                            </span>
                        <?php endif; ?>
                    </div>

                    <label for="badge_file" class="form-label mt-3">Subir nuevo escudo</label>
                    <input
                        type="file"
                        name="badge_file"
                        id="badge_file"
                        class="form-control"
                        accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
                    >

                    <div class="form-text">
                        Formatos recomendados: PNG o WEBP con fondo transparente.
                    </div>
                </div>
            </article>

            <div class="qv-admin-sticky-actions qv-admin-edit-actions">
                <a href="/admin/clubs" class="btn btn-outline-secondary">
                    Cancelar
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Guardar club
                </button>
            </div>
        </aside>
    </form>
</section>