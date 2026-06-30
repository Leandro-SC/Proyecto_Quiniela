<?php

declare(strict_types=1);

use App\Core\Security;

/**
 * @var array<string,mixed>|null $country
 * @var string|null $error
 */

require __DIR__ . '/../partials/nav.php';

$isEdit = is_array($country) && !empty($country['id']);
$action = $isEdit ? '/admin/countries/update' : '/admin/countries/store';

$id = (int)($country['id'] ?? 0);
$name = (string)($country['name'] ?? '');
$isoCode = (string)($country['iso_code'] ?? '');
$externalCountryName = (string)($country['external_country_name'] ?? $country['sportsdb_country_name'] ?? '');
$flagPath = (string)($country['flag_path'] ?? '');
$currencyCode = (string)($country['currency_code'] ?? 'USD');
$currencySymbol = (string)($country['currency_symbol'] ?? '$');
$isActive = (int)($country['is_active'] ?? 1) === 1;
?>

<section class="admin-mobile-page qv-admin-country-form-page">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Ubicación y moneda</span>
            <h1><?= $isEdit ? 'Editar país' : 'Nuevo país' ?></h1>
            <p>
                Define la identidad regional, bandera, código ISO y moneda asociada.
            </p>
        </div>

        <a href="/admin/countries" class="btn btn-outline-secondary">
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

        <section class="qv-admin-edit-main">
            <article class="qv-admin-form-card">
                <div class="qv-admin-form-card-head">
                    <i class="bi bi-globe-americas"></i>
                    <div>
                        <strong>Información del país</strong>
                        <span>Datos principales y referencia externa.</span>
                    </div>
                </div>

                <div class="qv-admin-form-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label for="name" class="form-label">Nombre</label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control form-control-lg"
                                value="<?= Security::e($name) ?>"
                                required
                            >
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="iso_code" class="form-label">Código ISO</label>
                            <input
                                type="text"
                                name="iso_code"
                                id="iso_code"
                                class="form-control text-uppercase"
                                value="<?= Security::e($isoCode) ?>"
                                maxlength="2"
                                required
                            >
                        </div>

                        <div class="col-12">
                            <label for="external_country_name" class="form-label">
                                Nombre externo / API
                            </label>
                            <input
                                type="text"
                                name="external_country_name"
                                id="external_country_name"
                                class="form-control"
                                value="<?= Security::e($externalCountryName) ?>"
                                placeholder="Ej. United States"
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
                                    País activo
                                </label>

                                <div class="small text-muted">
                                    Los países activos pueden usarse en ligas, equipos y configuración.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="qv-admin-form-card">
                <div class="qv-admin-form-card-head">
                    <i class="bi bi-currency-exchange"></i>
                    <div>
                        <strong>Moneda</strong>
                        <span>Código y símbolo asociado al país.</span>
                    </div>
                </div>

                <div class="qv-admin-form-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="currency_code" class="form-label">Código de moneda</label>
                            <input
                                type="text"
                                name="currency_code"
                                id="currency_code"
                                class="form-control text-uppercase"
                                value="<?= Security::e($currencyCode) ?>"
                                maxlength="3"
                                placeholder="USD"
                            >
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="currency_symbol" class="form-label">Símbolo de moneda</label>
                            <input
                                type="text"
                                name="currency_symbol"
                                id="currency_symbol"
                                class="form-control"
                                value="<?= Security::e($currencySymbol) ?>"
                                maxlength="10"
                                placeholder="$"
                            >
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <aside class="qv-admin-edit-side">
            <article class="qv-admin-form-card">
                <div class="qv-admin-form-card-head">
                    <i class="bi bi-flag-fill"></i>
                    <div>
                        <strong>Bandera</strong>
                        <span>Imagen representativa.</span>
                    </div>
                </div>

                <div class="qv-admin-form-card-body">
                    <div class="qv-admin-flag-preview-box">
                        <?php if ($flagPath !== ''): ?>
                            <img src="<?= Security::e($flagPath) ?>" alt="Bandera actual">
                        <?php else: ?>
                            <span>
                                <i class="bi bi-flag"></i>
                                Sin bandera
                            </span>
                        <?php endif; ?>
                    </div>

                    <label for="flag" class="form-label mt-3">Subir bandera</label>
                    <input
                        type="file"
                        name="flag"
                        id="flag"
                        class="form-control"
                        accept="image/png,image/jpeg,image/gif,image/webp"
                    >

                    <div class="form-text">
                        Recomendado: PNG o WEBP horizontal.
                    </div>
                </div>
            </article>

            <div class="qv-admin-sticky-actions qv-admin-edit-actions">
                <a href="/admin/countries" class="btn btn-outline-secondary">
                    Cancelar
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Guardar país
                </button>
            </div>
        </aside>
    </form>
</section>