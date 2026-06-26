<?php
declare(strict_types=1);

/** @var array $round */
/** @var array|null $match */
/** @var array $clubs */
/** @var array $countries */

$isEdit = $match !== null;
$clubs = $clubs ?? [];
$countries = $countries ?? [];
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<style>
    /* Ajustes finos sobre el tema Bootstrap 5 */
    .select2-container--bootstrap-5 .select2-selection {
        border-color: #dee2e6; /* Borde suave igual a inputs */
    }
    /* Asegurar que el texto de las opciones sea negro y legible */
    .select2-container--bootstrap-5 .select2-dropdown .select2-results__option {
        color: #212529;
        padding: 8px 12px;
    }
    .select2-container--bootstrap-5 .select2-dropdown .select2-results__option--highlighted {
        background-color: #0d6efd; /* Azul bootstrap al pasar mouse */
        color: #fff;
    }
    
    /* Caja de previsualización de imagen */
    .img-preview-box {
        min-height: 120px;
        background-color: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        transition: all 0.3s;
        overflow: hidden;
    }
    .img-preview-box:hover {
        border-color: #adb5bd;
        background-color: #e9ecef;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 m-0"><?= $isEdit ? 'Editar Partido' : 'Nuevo Partido' ?></h1>
    <a href="/admin/rounds/matches?round_id=<?= (int)$round['id'] ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card shadow-sm mb-4 border-0">
    <div class="card-header bg-dark text-white py-3">
        <div class="d-flex align-items-center">
            <i class="bi bi-calendar-event me-2"></i>
            <div>
                <strong>Jornada:</strong> <?= htmlspecialchars($round['name']) ?>
                <span class="opacity-75 small border-start ms-2 ps-2 border-secondary"><?= htmlspecialchars($round['league_name'] ?? '') ?></span>
            </div>
        </div>
    </div>
    <div class="card-body p-4">
        
        <form method="post" id="matchForm" enctype="multipart/form-data">
            
            <div class="row g-4">
                
                <div class="col-md-6">
                    <div class="card h-100 border-primary shadow-sm">
                        <div class="card-header bg-primary text-white fw-bold text-center py-2">
                            LOCAL (CASA)
                        </div>
                        <div class="card-body">
                            

                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold text-uppercase"><i class="bi bi-search"></i> Buscar Club</label>
                                <select class="form-select club-selector" id="club_selector_home" data-target="home" style="width: 100%;">
                                    <option value="">-- Escribe para buscar... --</option>
                                    <?php foreach($clubs as $c): ?>
                                        <option value="<?= $c['id'] ?>" 
                                                data-name="<?= htmlspecialchars($c['name']) ?>"
                                                data-logo="<?= htmlspecialchars($c['badge_path'] ?? '') ?>"
                                                data-country-id="<?= (int)($c['country_id'] ?? 0) ?>">
                                            <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <hr class="text-muted opacity-25">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Nombre del Equipo</label>
                                <input type="text" name="home_team_name" id="home_team_name" 
                                       class="form-control fw-bold text-primary"
                                       value="<?= htmlspecialchars($match['home_team_name'] ?? '') ?>" 
                                       placeholder="Nombre del equipo" required>
                                <input type="hidden" name="home_team_id" id="home_team_id">
                                <input type="hidden" name="home_country_id" id="home_country_id">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-muted">Escudo</label>
                                <div class="img-preview-box">
                                    <img id="preview_home" src="<?= htmlspecialchars($match['home_team_logo'] ?? '') ?>" 
                                         style="max-height: 90px; max-width: 90%; display: <?= empty($match['home_team_logo']) ? 'none' : 'block' ?>;">
                                    <span id="text_home" class="text-muted small" style="display: <?= empty($match['home_team_logo']) ? 'block' : 'none' ?>;">
                                        <i class="bi bi-image fs-4 d-block mx-auto mb-1"></i> Sin imagen
                                    </span>
                                </div>
                                <input type="hidden" name="home_team_logo" id="home_team_logo" value="<?= htmlspecialchars($match['home_team_logo'] ?? '') ?>">
                                <input type="file" name="home_logo_file" id="home_logo_file" class="form-control form-control-sm mt-2" onchange="previewFile('home')">
                            </div>

                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 border-danger shadow-sm">
                        <div class="card-header bg-danger text-white fw-bold text-center py-2">
                            VISITA (FUERA)
                        </div>
                        <div class="card-body">
                            

                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold text-uppercase"><i class="bi bi-search"></i> Buscar Club</label>
                                <select class="form-select club-selector" id="club_selector_away" data-target="away" style="width: 100%;">
                                    <option value="">-- Escribe para buscar... --</option>
                                    <?php foreach($clubs as $c): ?>
                                        <option value="<?= $c['id'] ?>" 
                                                data-name="<?= htmlspecialchars($c['name']) ?>"
                                                data-logo="<?= htmlspecialchars($c['badge_path'] ?? '') ?>"
                                                data-country-id="<?= (int)($c['country_id'] ?? 0) ?>">
                                            <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <hr class="text-muted opacity-25">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Nombre del Equipo</label>
                                <input type="text" name="away_team_name" id="away_team_name" 
                                       class="form-control fw-bold text-danger"
                                       value="<?= htmlspecialchars($match['away_team_name'] ?? '') ?>" 
                                       placeholder="Nombre del equipo" required>
                                <input type="hidden" name="away_team_id" id="away_team_id">
                                <input type="hidden" name="away_country_id" id="away_country_id">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-muted">Escudo</label>
                                <div class="img-preview-box">
                                    <img id="preview_away" src="<?= htmlspecialchars($match['away_team_logo'] ?? '') ?>" 
                                         style="max-height: 90px; max-width: 90%; display: <?= empty($match['away_team_logo']) ? 'none' : 'block' ?>;">
                                    <span id="text_away" class="text-muted small" style="display: <?= empty($match['away_team_logo']) ? 'block' : 'none' ?>;">
                                        <i class="bi bi-image fs-4 d-block mx-auto mb-1"></i> Sin imagen
                                    </span>
                                </div>
                                <input type="hidden" name="away_team_logo" id="away_team_logo" value="<?= htmlspecialchars($match['away_team_logo'] ?? '') ?>">
                                <input type="file" name="away_logo_file" id="away_logo_file" class="form-control form-control-sm mt-2" onchange="previewFile('away')">
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <div class="card mt-4 border-secondary shadow-sm">
                <div class="card-header bg-secondary text-white fw-bold">
                    <i class="bi bi-sliders me-2"></i> Configuración del Encuentro
                </div>
                <div class="card-body bg-light">
                    <div class="row align-items-end g-3">
                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold text-primary">Goles Local</label>
                            <input type="number" name="home_score" class="form-control text-center fs-5 fw-bold" value="<?= $match['home_score'] ?? '' ?>" placeholder="-">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold text-danger">Goles Visita</label>
                            <input type="number" name="away_score" class="form-control text-center fs-5 fw-bold" value="<?= $match['away_score'] ?? '' ?>" placeholder="-">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Fecha y Hora</label>
                            <?php 
                                $kickoff = !empty($match['kickoff_at']) ? date('Y-m-d\TH:i', strtotime($match['kickoff_at'])) : '';
                            ?>
                            <input type="datetime-local" name="kickoff_at" class="form-control" value="<?= $kickoff ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select fw-bold">
                                <option value="SCHEDULED" <?= ($match['status']??'') == 'SCHEDULED' ? 'selected' : '' ?>>📅 Programado</option>
                                <option value="LIVE" class="text-danger" <?= ($match['status']??'') == 'LIVE' ? 'selected' : '' ?>>🔴 En Vivo</option>
                                <option value="FINISHED" class="text-success" <?= ($match['status']??'') == 'FINISHED' ? 'selected' : '' ?>>✅ Finalizado</option>
                                <option value="POSTPONED" class="text-warning" <?= ($match['status']??'') == 'POSTPONED' ? 'selected' : '' ?>>⚠️ Postergado</option>
                                <option value="CANCELLED" class="text-muted" <?= ($match['status']??'') == 'CANCELLED' ? 'selected' : '' ?>>❌ Cancelado</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-end">
                <button type="submit" class="btn btn-success px-5 py-3 fw-bold shadow-lg rounded-pill">
                    <i class="bi bi-check-circle-fill me-2"></i> GUARDAR PARTIDO
                </button>
            </div>

        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    
    // Almacenar clubes en memoria
    var allClubs = [];
    $('#club_selector_home option').each(function() {
        if($(this).val()) {
            allClubs.push({
                id: $(this).val(),
                text: $(this).text(),
                logo: $(this).data('logo'),
                countryId: $(this).data('country-id'),
                name: $(this).data('name')
            });
        }
    });

    // Inicializar Select2 con TEMA BOOTSTRAP-5
    $('.club-selector').select2({
        theme: 'bootstrap-5', // CLAVE: Usar el tema de bootstrap
        placeholder: "Escribe para buscar equipo...",
        allowClear: true,
        width: '100%',
        language: { noResults: () => "No se encontraron equipos" }
    });

    // Lógica de Filtrado por País
    $('.country-filter').on('change', function() {
        var countryId = $(this).val();
        var target = $(this).data('target'); 
        var $selectClub = $('#club_selector_' + target);

        // Limpiar
        $selectClub.empty().append('<option value="">-- Selecciona --</option>');

        // Filtrar y repoblar
        allClubs.forEach(function(club) {
            if (countryId === 'all' || club.countryId == countryId) {
                var option = new Option(club.text, club.id, false, false);
                $(option).attr('data-logo', club.logo);
                $(option).attr('data-name', club.name);
                $(option).attr('data-country-id', club.countryId);
                $selectClub.append(option);
            }
        });

        // Actualizar Select2 (sin esto se ve vacío)
        $selectClub.trigger('change');
        
        // Limpiar UI manual
        updateUI(target, '', '', '');
    });

    // Lógica al Seleccionar Club
    $('.club-selector').on('change', function() {
        var target = $(this).data('target');
        var selectedData = $(this).select2('data')[0];

        if (selectedData && selectedData.id) {
            var $opt = $(this).find(':selected');
            updateUI(target, $opt.data('name'), $opt.data('logo'), $opt.data('country-id'), $opt.val());
        }
    });

    function updateUI(side, name, logo, countryId, clubId = '') {
        if(name) $('#' + side + '_team_name').val(name);
        $('#' + side + '_team_id').val(clubId);
        $('#' + side + '_country_id').val(countryId);
        $('#' + side + '_team_logo').val(logo);

        var img = document.getElementById('preview_' + side);
        var txt = document.getElementById('text_' + side);
        
        if (logo) {
            img.src = logo; img.style.display = 'block'; txt.style.display = 'none';
        } else {
            img.style.display = 'none'; txt.style.display = 'block';
        }
        if(clubId) $('#' + side + '_logo_file').val('');
    }
});

function previewFile(side) {
    const fileInput = document.getElementById(side + '_logo_file');
    const img = document.getElementById('preview_' + side);
    const txt = document.getElementById('text_' + side);
    const file = fileInput.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result; img.style.display = 'block'; txt.style.display = 'none';
            document.getElementById(side + '_team_logo').value = ''; 
        }
        reader.readAsDataURL(file);
    }
}
</script>