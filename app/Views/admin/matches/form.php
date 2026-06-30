<?php

declare(strict_types=1);

use App\Core\Security;

/** @var array<string,mixed> $round */
/** @var array<string,mixed>|null $match */
/** @var array<int,array<string,mixed>> $clubs */
/** @var array<int,array<string,mixed>> $countries */

require __DIR__ . '/../partials/nav.php';

$isEdit = $match !== null;
$clubs = $clubs ?? [];

$roundId = (int)($round['id'] ?? 0);
$roundName = (string)($round['name'] ?? '');
$leagueName = (string)($round['league_name'] ?? '');

$homeLogo = (string)($match['home_team_logo'] ?? '');
$awayLogo = (string)($match['away_team_logo'] ?? '');
$kickoff = !empty($match['kickoff_at']) ? date('Y-m-d\TH:i', strtotime((string)$match['kickoff_at'])) : '';
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

<section class="admin-mobile-page qv-admin-match-form-page">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Partidos</span>
            <h1><?= $isEdit ? 'Editar partido' : 'Nuevo partido' ?></h1>
            <p>
                Configura equipos, escudos, horario, marcador y estado del encuentro.
            </p>
        </div>

        <a href="/admin/rounds/matches?round_id=<?= $roundId ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Volver
        </a>
    </header>

    <section class="qv-admin-match-round-card">
        <div class="qv-admin-match-round-icon">
            <i class="bi bi-calendar-event-fill"></i>
        </div>

        <div>
            <span>Jornada</span>
            <strong><?= Security::e($roundName) ?></strong>
            <small><?= Security::e($leagueName) ?></small>
        </div>
    </section>

    <form method="post" id="matchForm" enctype="multipart/form-data" class="qv-admin-match-form">
        <?= Security::csrfInput() ?>

        <section class="qv-admin-match-teams-grid">
            <article class="qv-admin-team-card qv-admin-team-home">
                <div class="qv-admin-team-card-head">
                    <span class="qv-admin-team-badge">Local</span>
                    <strong>Equipo de casa</strong>
                </div>

                <div class="qv-admin-team-card-body">
                    <div class="mb-3">
                        <label for="club_selector_home" class="form-label">
                            Buscar club
                        </label>

                        <select class="form-select club-selector" id="club_selector_home" data-target="home">
                            <option value="">Escribe para buscar...</option>

                            <?php foreach ($clubs as $club): ?>
                                <option
                                    value="<?= (int)($club['id'] ?? 0) ?>"
                                    data-name="<?= Security::e((string)($club['name'] ?? '')) ?>"
                                    data-logo="<?= Security::e((string)($club['badge_path'] ?? '')) ?>"
                                    data-country-id="<?= (int)($club['country_id'] ?? 0) ?>"
                                >
                                    <?= Security::e((string)($club['name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="home_team_name" class="form-label">
                            Nombre del equipo
                        </label>

                        <input
                            type="text"
                            name="home_team_name"
                            id="home_team_name"
                            class="form-control qv-admin-team-input-home"
                            value="<?= Security::e((string)($match['home_team_name'] ?? '')) ?>"
                            placeholder="Nombre del equipo local"
                            required
                        >

                        <input type="hidden" name="home_team_id" id="home_team_id" value="<?= Security::e((string)($match['home_team_id'] ?? '')) ?>">
                        <input type="hidden" name="home_country_id" id="home_country_id" value="<?= Security::e((string)($match['home_country_id'] ?? '')) ?>">
                    </div>

                    <div>
                        <label for="home_logo_file" class="form-label">
                            Escudo
                        </label>

                        <div class="qv-admin-logo-preview-box">
                            <img
                                id="preview_home"
                                src="<?= Security::e($homeLogo) ?>"
                                alt="Escudo local"
                                class="qv-admin-logo-preview-img"
                                <?= $homeLogo === '' ? 'hidden' : '' ?>
                            >

                            <span id="text_home" class="qv-admin-logo-preview-empty" <?= $homeLogo !== '' ? 'hidden' : '' ?>>
                                <i class="bi bi-image"></i>
                                Sin imagen
                            </span>
                        </div>

                        <input type="hidden" name="home_team_logo" id="home_team_logo" value="<?= Security::e($homeLogo) ?>">
                        <input type="file" name="home_logo_file" id="home_logo_file" class="form-control mt-2" accept="image/*" onchange="previewFile('home')">
                    </div>
                </div>
            </article>

            <article class="qv-admin-team-card qv-admin-team-away">
                <div class="qv-admin-team-card-head">
                    <span class="qv-admin-team-badge">Visita</span>
                    <strong>Equipo visitante</strong>
                </div>

                <div class="qv-admin-team-card-body">
                    <div class="mb-3">
                        <label for="club_selector_away" class="form-label">
                            Buscar club
                        </label>

                        <select class="form-select club-selector" id="club_selector_away" data-target="away">
                            <option value="">Escribe para buscar...</option>

                            <?php foreach ($clubs as $club): ?>
                                <option
                                    value="<?= (int)($club['id'] ?? 0) ?>"
                                    data-name="<?= Security::e((string)($club['name'] ?? '')) ?>"
                                    data-logo="<?= Security::e((string)($club['badge_path'] ?? '')) ?>"
                                    data-country-id="<?= (int)($club['country_id'] ?? 0) ?>"
                                >
                                    <?= Security::e((string)($club['name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="away_team_name" class="form-label">
                            Nombre del equipo
                        </label>

                        <input
                            type="text"
                            name="away_team_name"
                            id="away_team_name"
                            class="form-control qv-admin-team-input-away"
                            value="<?= Security::e((string)($match['away_team_name'] ?? '')) ?>"
                            placeholder="Nombre del equipo visitante"
                            required
                        >

                        <input type="hidden" name="away_team_id" id="away_team_id" value="<?= Security::e((string)($match['away_team_id'] ?? '')) ?>">
                        <input type="hidden" name="away_country_id" id="away_country_id" value="<?= Security::e((string)($match['away_country_id'] ?? '')) ?>">
                    </div>

                    <div>
                        <label for="away_logo_file" class="form-label">
                            Escudo
                        </label>

                        <div class="qv-admin-logo-preview-box">
                            <img
                                id="preview_away"
                                src="<?= Security::e($awayLogo) ?>"
                                alt="Escudo visitante"
                                class="qv-admin-logo-preview-img"
                                <?= $awayLogo === '' ? 'hidden' : '' ?>
                            >

                            <span id="text_away" class="qv-admin-logo-preview-empty" <?= $awayLogo !== '' ? 'hidden' : '' ?>>
                                <i class="bi bi-image"></i>
                                Sin imagen
                            </span>
                        </div>

                        <input type="hidden" name="away_team_logo" id="away_team_logo" value="<?= Security::e($awayLogo) ?>">
                        <input type="file" name="away_logo_file" id="away_logo_file" class="form-control mt-2" accept="image/*" onchange="previewFile('away')">
                    </div>
                </div>
            </article>
        </section>

        <section class="qv-admin-match-config-card">
            <div class="qv-admin-match-config-head">
                <i class="bi bi-sliders"></i>
                <div>
                    <strong>Configuración del encuentro</strong>
                    <span>Marcador, horario y estado del partido.</span>
                </div>
            </div>

            <div class="qv-admin-match-config-grid">
                <div>
                    <label for="home_score" class="form-label">Goles local</label>
                    <input
                        type="number"
                        name="home_score"
                        id="home_score"
                        class="form-control qv-admin-score-input qv-admin-team-input-home"
                        value="<?= Security::e((string)($match['home_score'] ?? '')) ?>"
                        placeholder="-"
                    >
                </div>

                <div>
                    <label for="away_score" class="form-label">Goles visita</label>
                    <input
                        type="number"
                        name="away_score"
                        id="away_score"
                        class="form-control qv-admin-score-input qv-admin-team-input-away"
                        value="<?= Security::e((string)($match['away_score'] ?? '')) ?>"
                        placeholder="-"
                    >
                </div>

                <div>
                    <label for="kickoff_at" class="form-label">Fecha y hora</label>
                    <input
                        type="datetime-local"
                        name="kickoff_at"
                        id="kickoff_at"
                        class="form-control"
                        value="<?= Security::e($kickoff) ?>"
                        required
                    >
                </div>

                <div>
                    <label for="status" class="form-label">Estado</label>
                    <select name="status" id="status" class="form-select">
                        <option value="SCHEDULED" <?= ($match['status'] ?? '') === 'SCHEDULED' ? 'selected' : '' ?>>Programado</option>
                        <option value="LIVE" <?= ($match['status'] ?? '') === 'LIVE' ? 'selected' : '' ?>>En vivo</option>
                        <option value="FINISHED" <?= ($match['status'] ?? '') === 'FINISHED' ? 'selected' : '' ?>>Finalizado</option>
                        <option value="POSTPONED" <?= ($match['status'] ?? '') === 'POSTPONED' ? 'selected' : '' ?>>Postergado</option>
                        <option value="CANCELLED" <?= ($match['status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>
            </div>
        </section>

        <div class="qv-admin-sticky-actions">
            <a href="/admin/rounds/matches?round_id=<?= $roundId ?>" class="btn btn-outline-secondary">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-circle-fill me-1"></i>
                Guardar partido
            </button>
        </div>
    </form>
</section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        $('.club-selector').select2({
            theme: 'bootstrap-5',
            placeholder: 'Escribe para buscar equipo...',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function () {
                    return 'No se encontraron equipos';
                }
            }
        });

        $('.club-selector').on('change', function () {
            var target = $(this).data('target');
            var selectedData = $(this).select2('data')[0];

            if (selectedData && selectedData.id) {
                var option = $(this).find(':selected');

                updateTeamUI(
                    target,
                    option.data('name'),
                    option.data('logo'),
                    option.data('country-id'),
                    option.val()
                );
            }
        });
    });

    function updateTeamUI(side, name, logo, countryId, clubId) {
        if (name) {
            document.getElementById(side + '_team_name').value = name;
        }

        document.getElementById(side + '_team_id').value = clubId || '';
        document.getElementById(side + '_country_id').value = countryId || '';
        document.getElementById(side + '_team_logo').value = logo || '';

        var img = document.getElementById('preview_' + side);
        var text = document.getElementById('text_' + side);

        if (logo) {
            img.src = logo;
            img.hidden = false;
            text.hidden = true;
        } else {
            img.hidden = true;
            text.hidden = false;
        }

        if (clubId) {
            document.getElementById(side + '_logo_file').value = '';
        }
    }

    function previewFile(side) {
        var fileInput = document.getElementById(side + '_logo_file');
        var img = document.getElementById('preview_' + side);
        var text = document.getElementById('text_' + side);
        var file = fileInput.files[0];

        if (!file) {
            return;
        }

        var reader = new FileReader();

        reader.onload = function (event) {
            img.src = event.target.result;
            img.hidden = false;
            text.hidden = true;
            document.getElementById(side + '_team_logo').value = '';
        };

        reader.readAsDataURL(file);
    }
</script>