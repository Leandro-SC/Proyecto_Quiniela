<?php

declare(strict_types=1);

use App\Core\Security;

/**
 * @var array<int,array<string,mixed>> $leagues
 * @var array<int,array<string,mixed>> $countries
 */

require __DIR__ . '/../partials/nav.php';

$leagues = $leagues ?? [];
$countries = $countries ?? [];

$totalLeagues = count($leagues);
$activeLeagues = 0;
$inactiveLeagues = 0;

foreach ($leagues as $league) {
    if ((int)($league['is_active'] ?? 1) === 1) {
        $activeLeagues++;
    } else {
        $inactiveLeagues++;
    }
}
?>

<section class="admin-mobile-page qv-admin-leagues-page">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Competencias</span>
            <h1>Ligas</h1>
            <p>
                Administra competencias, imágenes, país, color de marca e identificadores externos.
            </p>
        </div>

        <button type="button" class="btn btn-primary qv-admin-primary-action" onclick="openCreateLeagueModal()">
            <i class="bi bi-plus-circle-fill me-1"></i>
            Nueva liga
        </button>
    </header>

    <section class="qv-admin-kpi-grid qv-admin-league-kpis" aria-label="Resumen de ligas">
        <article class="qv-admin-kpi-card">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-trophy-fill"></i>
            </div>

            <div>
                <span>Total</span>
                <strong><?= number_format($totalLeagues) ?></strong>
                <small>Ligas registradas</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-success">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>

            <div>
                <span>Activas</span>
                <strong><?= number_format($activeLeagues) ?></strong>
                <small>Disponibles públicamente</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-warning">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-pause-circle-fill"></i>
            </div>

            <div>
                <span>Inactivas</span>
                <strong><?= number_format($inactiveLeagues) ?></strong>
                <small>No visibles</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-money">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-palette-fill"></i>
            </div>

            <div>
                <span>Branding</span>
                <strong>Color</strong>
                <small>Identidad por liga</small>
            </div>
        </article>
    </section>

    <?php if ($leagues === []): ?>
        <section class="qv-admin-empty-state">
            <i class="bi bi-trophy"></i>
            <strong>No hay ligas registradas.</strong>
            <span>Crea una liga para poder organizar jornadas y partidos.</span>

            <button type="button" class="btn btn-primary mt-3" onclick="openCreateLeagueModal()">
                Crear primera liga
            </button>
        </section>
    <?php else: ?>
        <section class="qv-admin-league-grid">
            <?php foreach ($leagues as $league): ?>
                <?php
                $id = (int)($league['id'] ?? 0);
                $name = (string)($league['name'] ?? '');
                $slug = (string)($league['slug'] ?? '');
                $countryName = (string)($league['country_name'] ?? '');
                $color = (string)($league['color'] ?? '#4169f5');
                $background = (string)($league['image_background'] ?? '');
                $banner = (string)($league['image_banner'] ?? '');
                $externalId = (string)($league['external_id'] ?? '');
                $externalLeagueId = (string)($league['external_league_id'] ?? '');
                $displayOrder = (int)($league['display_order'] ?? 0);
                $isActive = (int)($league['is_active'] ?? 1) === 1;
                ?>

                <article class="qv-admin-league-card">
                    <div class="qv-admin-league-media" style="--league-color: <?= Security::e($color) ?>;">
                        <?php if ($background !== ''): ?>
                            <img src="<?= Security::e($background) ?>" alt="<?= Security::e($name) ?>">
                        <?php elseif ($banner !== ''): ?>
                            <img src="<?= Security::e($banner) ?>" alt="<?= Security::e($name) ?>">
                        <?php else: ?>
                            <i class="bi bi-trophy-fill"></i>
                        <?php endif; ?>

                        <span class="qv-admin-status <?= $isActive ? 'qv-status-paid' : 'qv-status-muted' ?>">
                            <?= $isActive ? 'Activa' : 'Inactiva' ?>
                        </span>
                    </div>

                    <div class="qv-admin-league-body">
                        <div class="qv-admin-league-title-row">
                            <div>
                                <h2><?= Security::e($name) ?></h2>
                                <span><?= $countryName !== '' ? Security::e($countryName) : 'Sin país' ?></span>
                            </div>

                            <span class="qv-admin-league-color" style="background: <?= Security::e($color) ?>;"></span>
                        </div>

                        <div class="qv-admin-league-meta">
                            <div>
                                <span>Slug</span>
                                <strong><?= Security::e($slug !== '' ? $slug : '-') ?></strong>
                            </div>

                            <div>
                                <span>Orden</span>
                                <strong><?= $displayOrder ?></strong>
                            </div>

                            <div>
                                <span>API</span>
                                <strong>
                                    <?= Security::e(trim($externalId . ' ' . $externalLeagueId) ?: '-') ?>
                                </strong>
                            </div>
                        </div>
                    </div>

                    <div class="qv-admin-league-actions">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            onclick='openEditLeagueModal(<?= json_encode($league, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'
                        >
                            <i class="bi bi-pencil-fill"></i>
                            Editar
                        </button>

                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteLeague(<?= $id ?>)">
                            <i class="bi bi-trash-fill"></i>
                            Eliminar
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</section>

<div class="modal fade qv-admin-modal" id="leagueModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" id="leagueForm" method="post" enctype="multipart/form-data">
            <?= Security::csrfInput() ?>

            <input type="hidden" name="id" id="leagueId">

            <div class="modal-header">
                <div>
                    <span class="qv-admin-eyebrow">Liga</span>
                    <h2 class="modal-title h5" id="leagueModalTitle">
                        Nueva liga
                    </h2>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-8">
                        <label for="leagueName" class="form-label">Nombre</label>
                        <input type="text" name="name" id="leagueName" class="form-control" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="leagueSlug" class="form-label">Slug</label>
                        <input type="text" name="slug" id="leagueSlug" class="form-control">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="leagueCountry" class="form-label">País</label>
                        <select name="country_id" id="leagueCountry" class="form-select">
                            <option value="0">Sin país</option>

                            <?php foreach ($countries as $country): ?>
                                <option value="<?= (int)($country['id'] ?? 0) ?>">
                                    <?= Security::e((string)($country['name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="leagueColor" class="form-label">Color</label>
                        <input type="color" name="color" id="leagueColor" class="form-control form-control-color" value="#4169f5">
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="leagueOrder" class="form-label">Orden</label>
                        <input type="number" name="display_order" id="leagueOrder" class="form-control" value="0">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="leagueExternalId" class="form-label">External ID</label>
                        <input type="text" name="external_id" id="leagueExternalId" class="form-control">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="leagueExternalLeagueId" class="form-label">External League ID</label>
                        <input type="text" name="external_league_id" id="leagueExternalLeagueId" class="form-control">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="leagueBackgroundFile" class="form-label">Imagen fondo</label>
                        <input type="file" name="image_background_file" id="leagueBackgroundFile" class="form-control" accept="image/*">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="leagueBannerFile" class="form-label">Imagen banner</label>
                        <input type="file" name="image_banner_file" id="leagueBannerFile" class="form-control" accept="image/*">
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch p-3 rounded border bg-light">
                            <input class="form-check-input" type="checkbox" name="is_active" id="leagueActive" value="1" checked>

                            <label class="form-check-label fw-bold" for="leagueActive">
                                Liga activa
                            </label>

                            <div class="small text-muted">
                                Las ligas activas pueden mostrarse al cliente y usarse en jornadas.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <button type="submit" class="btn btn-primary px-4">
                    Guardar liga
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    var leagueModal;

    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('leagueModal');

        if (modalElement && typeof bootstrap !== 'undefined') {
            leagueModal = new bootstrap.Modal(modalElement);
        }
    });

    function openCreateLeagueModal() {
        var form = document.getElementById('leagueForm');

        form.reset();
        form.action = '/admin/leagues/store';

        document.getElementById('leagueModalTitle').textContent = 'Nueva liga';
        document.getElementById('leagueId').value = '';
        document.getElementById('leagueColor').value = '#4169f5';
        document.getElementById('leagueOrder').value = '0';
        document.getElementById('leagueActive').checked = true;

        leagueModal.show();
    }

    function openEditLeagueModal(data) {
        var form = document.getElementById('leagueForm');

        form.reset();
        form.action = '/admin/leagues/update';

        document.getElementById('leagueModalTitle').textContent = 'Editar liga';
        document.getElementById('leagueId').value = data.id || '';
        document.getElementById('leagueName').value = data.name || '';
        document.getElementById('leagueSlug').value = data.slug || '';
        document.getElementById('leagueCountry').value = data.country_id || '0';
        document.getElementById('leagueColor').value = data.color || '#4169f5';
        document.getElementById('leagueOrder').value = data.display_order || '0';
        document.getElementById('leagueExternalId').value = data.external_id || '';
        document.getElementById('leagueExternalLeagueId').value = data.external_league_id || '';
        document.getElementById('leagueActive').checked = String(data.is_active || '0') === '1';

        leagueModal.show();
    }

    function confirmDeleteLeague(id) {
        window.qvConfirmDelete(
            '¿Eliminar liga?',
            'Si la liga tiene jornadas, equipos o partidos, se desactivará en lugar de eliminarse.',
            function () {
                window.enviarFormularioAdmin('/admin/leagues/delete', {
                    id: id
                });
            }
        );
    }
</script>