<?php

declare(strict_types=1);

use App\Core\Security;

/**
 * @var array<int,array<string,mixed>> $clubs
 * @var array<int,array<string,mixed>> $leagues
 * @var array<int,array<string,mixed>> $countries
 * @var array<string,mixed> $filters
 */

require __DIR__ . '/../partials/nav.php';

$clubs = $clubs ?? [];
$leagues = $leagues ?? [];
$countries = $countries ?? [];
$filters = $filters ?? [];

$filterLeagueId = (int)($filters['league_id'] ?? 0);
$filterCountryId = (int)($filters['country_id'] ?? 0);
$filterQuery = (string)($filters['q'] ?? '');

$totalClubs = count($clubs);
$activeClubs = 0;
$inactiveClubs = 0;

foreach ($clubs as $club) {
    if ((int)($club['is_active'] ?? 1) === 1) {
        $activeClubs++;
    } else {
        $inactiveClubs++;
    }
}
?>

<section class="admin-mobile-page qv-admin-clubs-page">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Equipos</span>
            <h1>Clubes</h1>
            <p>
                Administra equipos, escudos, países y ligas asociadas para tus jornadas deportivas.
            </p>
        </div>

        <a href="/admin/clubs/create" class="btn btn-primary qv-admin-primary-action">
            <i class="bi bi-plus-circle-fill me-1"></i>
            Nuevo club
        </a>
    </header>

    <section class="qv-admin-kpi-grid qv-admin-club-kpis" aria-label="Resumen de clubes">
        <article class="qv-admin-kpi-card">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-shield-shaded"></i>
            </div>

            <div>
                <span>Total</span>
                <strong><?= number_format($totalClubs) ?></strong>
                <small>Clubes filtrados</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-success">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>

            <div>
                <span>Activos</span>
                <strong><?= number_format($activeClubs) ?></strong>
                <small>Disponibles para partidos</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-warning">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-pause-circle-fill"></i>
            </div>

            <div>
                <span>Inactivos</span>
                <strong><?= number_format($inactiveClubs) ?></strong>
                <small>No disponibles</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-money">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-globe-americas"></i>
            </div>

            <div>
                <span>Catálogo</span>
                <strong>Equipos</strong>
                <small>Base deportiva del sistema</small>
            </div>
        </article>
    </section>

    <form class="qv-admin-filter-panel" method="get" action="/admin/clubs">
        <div class="qv-admin-club-filter-grid">
            <div>
                <label for="league_id" class="form-label">Liga</label>
                <select name="league_id" id="league_id" class="form-select">
                    <option value="0">Todas las ligas</option>

                    <?php foreach ($leagues as $league): ?>
                        <?php $leagueId = (int)($league['id'] ?? 0); ?>
                        <option value="<?= $leagueId ?>" <?= $filterLeagueId === $leagueId ? 'selected' : '' ?>>
                            <?= Security::e((string)($league['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="country_id" class="form-label">País</label>
                <select name="country_id" id="country_id" class="form-select">
                    <option value="0">Todos los países</option>

                    <?php foreach ($countries as $country): ?>
                        <?php $countryId = (int)($country['id'] ?? 0); ?>
                        <option value="<?= $countryId ?>" <?= $filterCountryId === $countryId ? 'selected' : '' ?>>
                            <?= Security::e((string)($country['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="q" class="form-label">Buscar</label>
                <div class="input-group">
                    <input
                        type="search"
                        name="q"
                        id="q"
                        class="form-control"
                        value="<?= Security::e($filterQuery) ?>"
                        placeholder="Nombre o nombre corto"
                    >

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                        Buscar
                    </button>
                </div>
            </div>
        </div>
    </form>

    <?php if ($clubs === []): ?>
        <section class="qv-admin-empty-state">
            <i class="bi bi-shield-x"></i>
            <strong>No hay clubes registrados.</strong>
            <span>Crea un club para poder usarlo en los partidos de tus jornadas.</span>

            <a href="/admin/clubs/create" class="btn btn-primary mt-3">
                Crear primer club
            </a>
        </section>
    <?php else: ?>
        <section class="qv-admin-club-grid">
            <?php foreach ($clubs as $club): ?>
                <?php
                $id = (int)($club['id'] ?? 0);
                $name = (string)($club['name'] ?? '');
                $shortName = (string)($club['short_name'] ?? '');
                $countryName = (string)($club['country_name'] ?? '');
                $leagueName = (string)($club['league_name'] ?? '');
                $badgePath = (string)($club['badge_path'] ?? $club['logo_path'] ?? '');
                $isActive = (int)($club['is_active'] ?? 1) === 1;
                ?>

                <article class="qv-admin-club-card">
                    <div class="qv-admin-club-logo">
                        <?php if ($badgePath !== ''): ?>
                            <img src="<?= Security::e($badgePath) ?>" alt="<?= Security::e($name) ?>">
                        <?php else: ?>
                            <i class="bi bi-shield-shaded"></i>
                        <?php endif; ?>
                    </div>

                    <div class="qv-admin-club-body">
                        <div class="qv-admin-club-title-row">
                            <div>
                                <h2><?= Security::e($name) ?></h2>

                                <?php if ($shortName !== '' && $shortName !== $name): ?>
                                    <span><?= Security::e($shortName) ?></span>
                                <?php endif; ?>
                            </div>

                            <span class="qv-admin-status <?= $isActive ? 'qv-status-paid' : 'qv-status-muted' ?>">
                                <?= $isActive ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </div>

                        <div class="qv-admin-club-meta">
                            <div>
                                <span>País</span>
                                <strong><?= $countryName !== '' ? Security::e($countryName) : 'Sin país' ?></strong>
                            </div>

                            <div>
                                <span>Liga</span>
                                <strong><?= $leagueName !== '' ? Security::e($leagueName) : 'Sin liga' ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="qv-admin-club-actions">
                        <a href="/admin/clubs/edit?id=<?= $id ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil-fill"></i>
                            Editar
                        </a>

                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteClub(<?= $id ?>)">
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
    function confirmDeleteClub(id) {
        window.qvConfirmDelete(
            '¿Eliminar club?',
            'Si el club tiene partidos asociados, será desactivado en lugar de eliminarse.',
            function () {
                window.enviarFormularioAdmin('/admin/clubs/delete', {
                    id: id
                });
            }
        );
    }
</script>