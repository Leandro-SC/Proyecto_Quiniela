<?php require __DIR__ . '/../partials/nav.php'; ?>

<style>
    /* Forzar texto oscuro en inputs y etiquetas */
    .modal-body, .form-label, .form-control, .form-select, .form-check-label, .form-text {
        color: #212529 !important;
    }
    .form-control, .form-select {
        background-color: #fff !important; border: 1px solid #ced4da;
    }

    /* Diseño Elegante */
    .elegant-modal .modal-content { border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    .elegant-modal .modal-header { background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); padding: 1.5rem; border-bottom: none; }
    .elegant-modal .modal-title { color: #ffffff !important; font-weight: bold; }
    .elegant-modal .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>🏆 Gestión de Ligas y Banners</h3>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-2"></i> Nueva Liga
        </button>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nombre</th>
                        <th>País</th>
                        <th>Slug / ID Externo</th>
                        <th>Fondo / Banner</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(empty($leagues)): ?>
                    <tr><td colspan="6" class="text-center py-4">No hay ligas registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($leagues as $l): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?= htmlspecialchars($l['name']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($l['country_code'] ?? 'N/A') ?></span></td>
                            <td>
                                <div class="small font-monospace"><?= $l['slug'] ?></div>
                                <small class="text-muted">API ID: <?= $l['external_id'] ?: '-' ?></small>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <?php if($l['image_background']): ?>
                                        <img src="/assets/img/leagues/<?= $l['image_background'] ?>" style="height: 30px; width: 30px; object-fit: cover;" class="rounded-circle border" title="Fondo">
                                    <?php else: ?><span class="badge bg-light text-muted">Sin Fondo</span><?php endif; ?>
                                    
                                    <?php if($l['image_banner']): ?>
                                        <img src="/assets/img/leagues/<?= $l['image_banner'] ?>" style="height: 30px; width: 50px; object-fit: cover;" class="rounded border" title="Banner">
                                    <?php else: ?><span class="badge bg-light text-muted">Sin Banner</span><?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?= $l['is_active'] ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-secondary">Inactiva</span>' ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light text-primary border me-1" 
                                        onclick='openEditModal(<?= json_encode($l, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-light text-danger border" onclick="confirmDeleteLeague(<?= $l['id'] ?>)" title="Eliminar">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade elegant-modal" id="leagueModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" id="leagueForm" method="POST" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="bi bi-trophy me-2"></i> Nueva Liga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 row g-3">
                <input type="hidden" name="id" id="leagueId">

                <div class="col-md-8">
                    <label class="form-label fw-bold">Nombre de la Liga</label>
                    <input type="text" name="name" id="leagueName" class="form-control" placeholder="Ej. Premier League" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">País</label>
                    <select name="country_id" id="leagueCountry" class="form-select" required>
                        <?php foreach($countries as $c): ?>
                            <option value="<?= $c['id'] ?>" data-iso="<?= $c['iso_code'] ?>">
                                <?= htmlspecialchars($c['name']) ?> (<?= $c['iso_code'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                </div> <div class="col-md-12">
    <label class="form-label fw-bold">Color Distintivo</label>
    <div class="d-flex align-items-center gap-2">
        <input type="color" name="color" id="leagueColor" class="form-control form-control-color" value="#6c757d" title="Elige un color">
        <small class="text-muted">Se usará en los botones de la quiniela.</small>
    </div>
</div>
<div class="col-md-12">
                
                <div class="col-md-12">
                    <label class="form-label fw-bold">ID API (Opcional)</label>
                    <input type="number" name="external_id" id="leagueExtId" class="form-control" placeholder="Ej. 39">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Imagen de Fondo (General)</label>
                    <input type="file" name="image_background" class="form-control" accept="image/*">
                    <div class="form-text small">Se usará en el fondo de la página de inicio.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Imagen del Banner (Logo/Título)</label>
                    <input type="file" name="image_banner" class="form-control" accept="image/*">
                    <div class="form-text small">Aparecerá sobre el título de la liga.</div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch p-3 bg-light rounded border d-flex align-items-center">
                        <input class="form-check-input ms-0 me-3" type="checkbox" name="is_active" id="leagueActive" checked style="width: 3em; height: 1.5em;">
                        <div>
                            <label class="form-check-label fw-bold d-block" for="leagueActive">Liga Visible</label>
                            <small class="text-muted">Si se desactiva, no aparecerá en el menú público.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    var leagueModal;
    
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('leagueModal');
        if (el) leagueModal = new bootstrap.Modal(el);
    });

    function openCreateModal() {
        document.getElementById('leagueForm').reset();
        document.getElementById('leagueForm').action = '/admin/leagues/store';
        document.getElementById('modalTitle').innerHTML = '<i class="bi bi-trophy me-2"></i> Nueva Liga';
        document.getElementById('leagueId').value = '';
        document.getElementById('leagueActive').checked = true;
        // Reset select to first option or specific default if needed
        document.getElementById('leagueCountry').selectedIndex = 0;
        document.getElementById('leagueColor').value = '#6c757d';
        leagueModal.show();
    }

    function openEditModal(data) {
        document.getElementById('leagueForm').action = '/admin/leagues/update';
        document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Editar Liga';
        document.getElementById('leagueId').value = data.id;
        document.getElementById('leagueName').value = data.name;
        document.getElementById('leagueExtId').value = data.external_id;
        document.getElementById('leagueActive').checked = (data.is_active == 1);
        document.getElementById('leagueColor').value = data.color || '#6c757d';
        
        // Seleccionar el país correcto basado en el ISO code guardado en la liga
        const countrySelect = document.getElementById('leagueCountry');
        const targetIso = data.country_code;
        for (let i = 0; i < countrySelect.options.length; i++) {
            if (countrySelect.options[i].getAttribute('data-iso') === targetIso) {
                countrySelect.selectedIndex = i;
                break;
            }
        }

        leagueModal.show();
    }

    function confirmDeleteLeague(id) {
        Swal.fire({
            title: '¿Borrar Liga?',
            text: "Se eliminarán también sus jornadas y partidos. ¡Cuidado!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, borrar todo'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form'); form.method = 'POST'; form.action = '/admin/leagues/delete';
                const input = document.createElement('input'); input.type = 'hidden'; input.name = 'id'; input.value = id;
                form.appendChild(input); document.body.appendChild(form); form.submit();
            }
        });
    }
</script>