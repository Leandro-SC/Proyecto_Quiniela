<?php

declare(strict_types=1);

use App\Core\Security;

/** @var array<string,mixed>|null $league */
/** @var array<int,array<string,mixed>> $countries */
/** @var string|null $error */

require __DIR__ . '/../partials/nav.php';

$isEdit = is_array($league) && !empty($league['id']);
$action = $isEdit ? '/admin/leagues/update' : '/admin/leagues/store';

$id = (int)($league['id'] ?? 0);
$name = (string)($league['name'] ?? '');
$slug = (string)($league['slug'] ?? '');
$countryId = (int)($league['country_id'] ?? 0);
$description = (string)($league['description'] ?? '');
$externalId = (string)($league['external_id'] ?? '');
$externalLeagueId = (string)($league['external_league_id'] ?? '');
$color = (string)($league['color'] ?? '#6c757d');
$imageBackground = (string)($league['image_background'] ?? '');
$imageBanner = (string)($league['image_banner'] ?? '');
$isActive = (int)($league['is_active'] ?? 1) === 1;
$displayOrder = (int)($league['display_order'] ?? 0);
?>

<div class="admin-mobile-page">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">
                <?= $isEdit ? 'Editar liga' : 'Crear liga' ?>
            </h1>
            <p class="text-muted small mb-0">
                Gestiona la información visible de la liga.
            </p>
        </div>

        <a href="/admin/leagues" class="btn btn-outline-secondary btn-sm">
            Volver
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= Security::e($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= Security::e($action) ?>" enctype="multipart/form-data" class="card border-0 shadow-sm">
        <?= Security::csrfInput() ?>

        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= $id ?>">
        <?php endif; ?>

        <input type="hidden" name="image_background" value="<?= Security::e($imageBackground) ?>">
        <input type="hidden" name="image_banner" value="<?= Security::e($imageBanner) ?>">

        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-8">
                    <label for="name" class="form-label fw-semibold">Nombre</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?= Security::e($name) ?>" required>
                </div>

                <div class="col-12 col-md-4">
                    <label for="slug" class="form-label fw-semibold">Slug</label>
                    <input type="text" name="slug" id="slug" class="form-control" value="<?= Security::e($slug) ?>" placeholder="auto">
                </div>

                <div class="col-12 col-md-6">
                    <label for="country_id" class="form-label fw-semibold">País</label>
                    <select name="country_id" id="country_id" class="form-select">
                        <option value="">Sin país</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= (int)$country['id'] ?>" <?= $countryId === (int)$country['id'] ? 'selected' : '' ?>>
                                <?= Security::e($country['name']) ?>
                                <?php if (!empty($country['iso_code'])): ?>
                                    (<?= Security::e($country['iso_code']) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label for="color" class="form-label fw-semibold">Color</label>
                    <input type="color" name="color" id="color" class="form-control form-control-color" value="<?= Security::e($color) ?>">
                </div>

                <div class="col-6 col-md-3">
                    <label for="display_order" class="form-label fw-semibold">Orden</label>
                    <input type="number" name="display_order" id="display_order" class="form-control" value="<?= $displayOrder ?>" min="0">
                </div>

                <div class="col-12 col-md-6">
                    <label for="external_id" class="form-label fw-semibold">ID API</label>
                    <input type="text" name="external_id" id="external_id" class="form-control" value="<?= Security::e($externalId) ?>">
                </div>

                <div class="col-12 col-md-6">
                    <label for="external_league_id" class="form-label fw-semibold">ID Liga externa</label>
                    <input type="text" name="external_league_id" id="external_league_id" class="form-control" value="<?= Security::e($externalLeagueId) ?>">
                </div>

                <div class="col-12">
                    <label for="description" class="form-label fw-semibold">Descripción</label>
                    <textarea name="description" id="description" class="form-control" rows="3"><?= Security::e($description) ?></textarea>
                </div>

                <div class="col-12 col-md-6">
                    <label for="image_background_file" class="form-label fw-semibold">Imagen de fondo</label>
                    <input type="file" name="image_background_file" id="image_background_file" class="form-control" accept="image/jpeg,image/png,image/webp">

                    <?php if ($imageBackground !== ''): ?>
                        <div class="form-text">
                            Imagen actual:
                            <a href="<?= Security::e($imageBackground) ?>" target="_blank" rel="noopener">ver fondo</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-md-6">
                    <label for="image_banner_file" class="form-label fw-semibold">Imagen banner</label>
                    <input type="file" name="image_banner_file" id="image_banner_file" class="form-control" accept="image/jpeg,image/png,image/webp">

                    <?php if ($imageBanner !== ''): ?>
                        <div class="form-text">
                            Imagen actual:
                            <a href="<?= Security::e($imageBanner) ?>" target="_blank" rel="noopener">ver banner</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch p-3 rounded border bg-light">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= $isActive ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="is_active">
                            Liga activa
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-white d-grid">
            <button type="submit" class="btn btn-primary btn-lg rounded-pill">
                Guardar liga
            </button>
        </div>
    </form>
</div>