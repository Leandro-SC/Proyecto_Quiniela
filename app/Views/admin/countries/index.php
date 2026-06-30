<?php

declare(strict_types=1);

use App\Core\Security;

/**
 * @var array<int,array<string,mixed>> $countries
 * @var array<string,mixed> $filters
 */

require __DIR__ . '/../partials/nav.php';

$countries = $countries ?? [];
$q = (string)($filters['q'] ?? '');

$totalCountries = count($countries);
$activeCountries = 0;
$inactiveCountries = 0;

foreach ($countries as $country) {
    if ((int)($country['is_active'] ?? 1) === 1) {
        $activeCountries++;
    } else {
        $inactiveCountries++;
    }
}
?>

<section class="admin-mobile-page qv-admin-countries-page">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Ubicación y moneda</span>
            <h1>Países</h1>
            <p>
                Administra países, banderas, códigos ISO y moneda asociada para la experiencia internacional.
            </p>
        </div>

        <a href="/admin/countries/create" class="btn btn-primary qv-admin-primary-action">
            <i class="bi bi-plus-circle-fill me-1"></i>
            Nuevo país
        </a>
    </header>

    <section class="qv-admin-kpi-grid qv-admin-country-kpis" aria-label="Resumen de países">
        <article class="qv-admin-kpi-card">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-globe-americas"></i>
            </div>

            <div>
                <span>Total</span>
                <strong><?= number_format($totalCountries) ?></strong>
                <small>Países filtrados</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-success">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>

            <div>
                <span>Activos</span>
                <strong><?= number_format($activeCountries) ?></strong>
                <small>Disponibles</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-warning">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-pause-circle-fill"></i>
            </div>

            <div>
                <span>Inactivos</span>
                <strong><?= number_format($inactiveCountries) ?></strong>
                <small>No visibles</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-money">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-currency-exchange"></i>
            </div>

            <div>
                <span>Monedas</span>
                <strong>ISO</strong>
                <small>Código y símbolo</small>
            </div>
        </article>
    </section>

    <form method="get" action="/admin/countries" class="qv-admin-filter-panel">
        <div class="qv-admin-country-filter-grid">
            <div>
                <label for="q" class="form-label">Buscar país</label>
                <div class="input-group">
                    <input
                        type="search"
                        name="q"
                        id="q"
                        class="form-control"
                        value="<?= Security::e($q) ?>"
                        placeholder="Nombre, ISO o nombre externo"
                    >

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                        Buscar
                    </button>
                </div>
            </div>
        </div>
    </form>

    <?php if ($countries === []): ?>
        <section class="qv-admin-empty-state">
            <i class="bi bi-flag"></i>
            <strong>No hay países registrados.</strong>
            <span>Agrega países para asociarlos a monedas, equipos y experiencia regional.</span>

            <a href="/admin/countries/create" class="btn btn-primary mt-3">
                Crear primer país
            </a>
        </section>
    <?php else: ?>
        <section class="qv-admin-country-grid">
            <?php foreach ($countries as $country): ?>
                <?php
                $id = (int)($country['id'] ?? 0);
                $name = (string)($country['name'] ?? '');
                $isoCode = (string)($country['iso_code'] ?? '');
                $flagPath = (string)($country['flag_path'] ?? '');
                $externalCountryName = (string)($country['external_country_name'] ?? '');
                $currencyCode = (string)($country['currency_code'] ?? '');
                $currencySymbol = (string)($country['currency_symbol'] ?? '');
                $isActive = (int)($country['is_active'] ?? 1) === 1;
                ?>

                <article class="qv-admin-country-card">
                    <div class="qv-admin-country-flag">
                        <?php if ($flagPath !== ''): ?>
                            <img src="<?= Security::e($flagPath) ?>" alt="Bandera de <?= Security::e($name) ?>">
                        <?php else: ?>
                            <i class="bi bi-flag-fill"></i>
                        <?php endif; ?>
                    </div>

                    <div class="qv-admin-country-body">
                        <div class="qv-admin-country-title-row">
                            <div>
                                <h2><?= Security::e($name) ?></h2>

                                <?php if ($externalCountryName !== ''): ?>
                                    <span><?= Security::e($externalCountryName) ?></span>
                                <?php endif; ?>
                            </div>

                            <span class="qv-admin-status <?= $isActive ? 'qv-status-paid' : 'qv-status-muted' ?>">
                                <?= $isActive ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </div>

                        <div class="qv-admin-country-meta">
                            <div>
                                <span>ISO</span>
                                <strong><?= Security::e($isoCode) ?></strong>
                            </div>

                            <div>
                                <span>Moneda</span>
                                <strong>
                                    <?= Security::e(trim($currencyCode . ' ' . $currencySymbol)) ?>
                                </strong>
                            </div>
                        </div>
                    </div>

                    <div class="qv-admin-country-actions">
                        <a href="/admin/countries/edit?id=<?= $id ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil-fill"></i>
                            Editar
                        </a>

                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteCountry(<?= $id ?>)">
                            <i class="bi bi-trash-fill"></i>
                            Eliminar
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</section>

<script>
    function confirmDeleteCountry(id) {
        window.qvConfirmDelete(
            '¿Eliminar país?',
            'Si el país está asociado a ligas o equipos, puede desactivarse en lugar de eliminarse.',
            function () {
                window.enviarFormularioAdmin('/admin/countries/delete', {
                    id: id
                });
            }
        );
    }
</script>