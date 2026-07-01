<?php

declare(strict_types=1);

/**
 * @var array<string,string> $settings
 * @var array<int,array<string,mixed>> $countries
 * @var array<int,array<string,mixed>> $currencies
 */

require __DIR__ . '/../partials/nav.php';

$settings = $settings ?? [];
$countries = $countries ?? [];
$currencies = $currencies ?? [];

$e = static function (mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$getSetting = static function (string $key, string $default = '') use ($settings): string {
    return (string)($settings[$key] ?? $default);
};

$csrfInput = '';

if (
    class_exists('\App\Core\Security') &&
    method_exists('\App\Core\Security', 'csrfInput')
) {
    $csrfInput = \App\Core\Security::csrfInput();
}

$maintenanceMode = $getSetting('maintenance_mode', '0') === '1';
?>

<section class="admin-mobile-page qv-admin-settings-page">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Sistema</span>
            <h1>Configuración</h1>
            <p>
                Administra datos generales, contacto, país por defecto, moneda, costos, premios y diseño público.
            </p>
        </div>
    </header>

    <form method="post" action="/admin/settings/update" enctype="multipart/form-data" class="qv-admin-settings-shell">
        <?= $csrfInput ?>

        <section class="qv-admin-edit-main">

            <article class="qv-admin-form-card">
                <div class="qv-admin-form-card-head">
                    <i class="bi bi-building"></i>
                    <div>
                        <strong>Información general</strong>
                        <span>Datos principales del sitio.</span>
                    </div>
                </div>

                <div class="qv-admin-form-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="site_name" class="form-label">Nombre del sitio</label>
                            <input
                                type="text"
                                name="site_name"
                                id="site_name"
                                class="form-control"
                                value="<?= $e($getSetting('site_name', 'Quinielas Villa')) ?>"
                            >
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="whatsapp_phone" class="form-label">WhatsApp principal</label>
                            <input
                                type="text"
                                name="whatsapp_phone"
                                id="whatsapp_phone"
                                class="form-control"
                                placeholder="Ej: 5219991234567"
                                value="<?= $e($getSetting('whatsapp_phone')) ?>"
                            >
                            <div class="form-text">
                                Ingresa solo números, incluyendo código de país.
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="site_description" class="form-label">Descripción del sitio</label>
                            <textarea
                                name="site_description"
                                id="site_description"
                                class="form-control"
                                rows="3"
                            ><?= $e($getSetting('site_description', 'Sistema de quinielas deportivas')) ?></textarea>
                        </div>
                    </div>
                </div>
            </article>

            <article class="qv-admin-form-card">
                <div class="qv-admin-form-card-head">
                    <i class="bi bi-image"></i>
                    <div>
                        <strong>Diseño público</strong>
                        <span>Fondos visuales para el banner principal del cliente.</span>
                    </div>
                </div>

                <div class="qv-admin-form-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label for="public_hero_bg_desktop_file" class="form-label">Fondo banner desktop</label>

                            <input
                                type="hidden"
                                name="current_public_hero_bg_desktop"
                                value="<?= $e($getSetting('public_hero_bg_desktop')) ?>"
                            >

                            <input
                                type="file"
                                name="public_hero_bg_desktop_file"
                                id="public_hero_bg_desktop_file"
                                class="form-control"
                                accept="image/jpeg,image/png,image/webp,image/avif"
                            >

                            <div class="form-text">
                                Recomendado: 1920x900 px. Formatos: JPG, PNG, WEBP o AVIF. Máximo 5MB.
                            </div>

                            <?php if ($getSetting('public_hero_bg_desktop') !== ''): ?>
                                <div class="qv-setting-preview mt-3">
                                    <img
                                        src="<?= $e($getSetting('public_hero_bg_desktop')) ?>"
                                        alt="Fondo desktop actual"
                                        loading="lazy"
                                    >
                                    <div>
                                        <strong>Imagen actual desktop</strong>
                                        <small><?= $e($getSetting('public_hero_bg_desktop')) ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label for="public_hero_bg_mobile_file" class="form-label">Fondo banner móvil</label>

                            <input
                                type="hidden"
                                name="current_public_hero_bg_mobile"
                                value="<?= $e($getSetting('public_hero_bg_mobile')) ?>"
                            >

                            <input
                                type="file"
                                name="public_hero_bg_mobile_file"
                                id="public_hero_bg_mobile_file"
                                class="form-control"
                                accept="image/jpeg,image/png,image/webp,image/avif"
                            >

                            <div class="form-text">
                                Opcional. Recomendado: 900x1200 px. Si no subes una imagen móvil, se usará la desktop.
                            </div>

                            <?php if ($getSetting('public_hero_bg_mobile') !== ''): ?>
                                <div class="qv-setting-preview mt-3">
                                    <img
                                        src="<?= $e($getSetting('public_hero_bg_mobile')) ?>"
                                        alt="Fondo móvil actual"
                                        loading="lazy"
                                    >
                                    <div>
                                        <strong>Imagen actual móvil</strong>
                                        <small><?= $e($getSetting('public_hero_bg_mobile')) ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="public_hero_overlay_opacity" class="form-label">Intensidad de contraste</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.35"
                                max="0.95"
                                name="public_hero_overlay_opacity"
                                id="public_hero_overlay_opacity"
                                class="form-control"
                                value="<?= $e($getSetting('public_hero_overlay_opacity', '0.72')) ?>"
                            >
                            <div class="form-text">
                                Recomendado: 0.65 a 0.78. Mientras más alto, más legible el texto.
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="qv-admin-form-card">
                <div class="qv-admin-form-card-head">
                    <i class="bi bi-globe-americas"></i>
                    <div>
                        <strong>País y moneda</strong>
                        <span>Valores predeterminados para la experiencia del cliente.</span>
                    </div>
                </div>

                <div class="qv-admin-form-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="default_country" class="form-label">País por defecto</label>
                            <select name="default_country" id="default_country" class="form-select">
                                <?php if ($countries === []): ?>
                                    <option value="<?= $e($getSetting('default_country', 'MX')) ?>" selected>
                                        <?= $e($getSetting('default_country', 'MX')) ?>
                                    </option>
                                <?php else: ?>
                                    <?php foreach ($countries as $country): ?>
                                        <?php
                                        $isoCode = (string)($country['iso_code'] ?? '');
                                        $countryName = (string)($country['name'] ?? '');
                                        ?>
                                        <option value="<?= $e($isoCode) ?>" <?= $getSetting('default_country', 'MX') === $isoCode ? 'selected' : '' ?>>
                                            <?= $e($countryName . ' · ' . $isoCode) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="default_currency" class="form-label">Moneda por defecto</label>
                            <select name="default_currency" id="default_currency" class="form-select">
                                <?php if ($currencies === []): ?>
                                    <option value="<?= $e($getSetting('default_currency', 'MXN')) ?>" selected>
                                        <?= $e($getSetting('default_currency', 'MXN')) ?>
                                    </option>
                                <?php else: ?>
                                    <?php foreach ($currencies as $currency): ?>
                                        <?php
                                        $currencyCode = (string)($currency['currency_code'] ?? '');
                                        $currencySymbol = (string)($currency['currency_symbol'] ?? '');
                                        ?>
                                        <option value="<?= $e($currencyCode) ?>" <?= $getSetting('default_currency', 'MXN') === $currencyCode ? 'selected' : '' ?>>
                                            <?= $e(trim($currencyCode . ' ' . $currencySymbol)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </article>

            <article class="qv-admin-form-card">
                <div class="qv-admin-form-card-head">
                    <i class="bi bi-cash-stack"></i>
                    <div>
                        <strong>Costos y premios</strong>
                        <span>Define costos de participación y porcentajes de bolsa.</span>
                    </div>
                </div>

                <div class="qv-admin-form-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="ticket_default_cost_mxn" class="form-label">Costo ticket MXN</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="ticket_default_cost_mxn"
                                    id="ticket_default_cost_mxn"
                                    class="form-control"
                                    value="<?= $e($getSetting('ticket_default_cost_mxn', '200.00')) ?>"
                                >
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="ticket_default_cost_usd" class="form-label">Costo ticket USD</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="ticket_default_cost_usd"
                                    id="ticket_default_cost_usd"
                                    class="form-control"
                                    value="<?= $e($getSetting('ticket_default_cost_usd', '10.00')) ?>"
                                >
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="prize_pool_percent" class="form-label">Bolsa a repartir</label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="100"
                                    name="prize_pool_percent"
                                    id="prize_pool_percent"
                                    class="form-control"
                                    value="<?= $e($getSetting('prize_pool_percent', '45.00')) ?>"
                                >
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="first_place_percent" class="form-label">Primer lugar</label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="100"
                                    name="first_place_percent"
                                    id="first_place_percent"
                                    class="form-control"
                                    value="<?= $e($getSetting('first_place_percent', '30.00')) ?>"
                                >
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="second_place_percent" class="form-label">Segundo lugar</label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="100"
                                    name="second_place_percent"
                                    id="second_place_percent"
                                    class="form-control"
                                    value="<?= $e($getSetting('second_place_percent', '15.00')) ?>"
                                >
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <aside class="qv-admin-edit-side">
            <article class="qv-admin-form-card">
                <div class="qv-admin-form-card-head">
                    <i class="bi bi-life-preserver"></i>
                    <div>
                        <strong>Soporte</strong>
                        <span>Datos de ayuda y enlaces legales.</span>
                    </div>
                </div>

                <div class="qv-admin-form-card-body">
                    <div class="mb-3">
                        <label for="support_email" class="form-label">Correo soporte</label>
                        <input
                            type="email"
                            name="support_email"
                            id="support_email"
                            class="form-control"
                            value="<?= $e($getSetting('support_email')) ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label for="support_phone" class="form-label">Teléfono soporte</label>
                        <input
                            type="text"
                            name="support_phone"
                            id="support_phone"
                            class="form-control"
                            value="<?= $e($getSetting('support_phone')) ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label for="terms_url" class="form-label">URL términos</label>
                        <input
                            type="text"
                            name="terms_url"
                            id="terms_url"
                            class="form-control"
                            value="<?= $e($getSetting('terms_url')) ?>"
                        >
                    </div>

                    <div>
                        <label for="privacy_url" class="form-label">URL privacidad</label>
                        <input
                            type="text"
                            name="privacy_url"
                            id="privacy_url"
                            class="form-control"
                            value="<?= $e($getSetting('privacy_url')) ?>"
                        >
                    </div>
                </div>
            </article>

            <article class="qv-admin-form-card">
                <div class="qv-admin-form-card-head">
                    <i class="bi bi-tools"></i>
                    <div>
                        <strong>Mantenimiento</strong>
                        <span>Control temporal del sistema.</span>
                    </div>
                </div>

                <div class="qv-admin-form-card-body">
                    <div class="form-check form-switch p-3 rounded border bg-light">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="maintenance_mode"
                            id="maintenance_mode"
                            value="1"
                            <?= $maintenanceMode ? 'checked' : '' ?>
                        >

                        <label class="form-check-label fw-bold" for="maintenance_mode">
                            Modo mantenimiento
                        </label>

                        <div class="small text-muted">
                            Actívalo solo cuando necesites pausar temporalmente la experiencia pública.
                        </div>
                    </div>
                </div>
            </article>

            <div class="qv-admin-sticky-actions qv-admin-edit-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Guardar configuración
                </button>
            </div>
        </aside>
    </form>
</section>