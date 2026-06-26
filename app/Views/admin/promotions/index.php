<?php require __DIR__ . '/../partials/nav.php'; ?>

<style>
    /* Diseño Elegante */
    .elegant-modal .modal-content { border-radius: 20px; border: none; box-shadow: 0 15px 40px rgba(0,0,0,0.2); }
    .elegant-modal .modal-header { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); padding: 1.5rem; border: none; }
    .elegant-modal .modal-title { color: white !important; font-weight: bold; }
    .elegant-modal .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
    
    /* Texto oscuro forzado para evitar letras blancas sobre fondo blanco */
    .elegant-modal .modal-body,
    .elegant-modal .form-label,
    .elegant-modal .form-control,
    .elegant-modal .form-check-label { 
        color: #212529 !important; 
    }
    
    .promo-card { transition: transform 0.2s; border: none; border-radius: 15px; overflow: hidden; }
    .promo-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .promo-img-container { height: 180px; background-color: #f8f9fa; position: relative; }
    .promo-img { width: 100%; height: 100%; object-fit: cover; }
    .promo-badge { position: absolute; top: 10px; right: 10px; }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>📢 Marketing y Promociones</h3>
        <button class="btn btn-dark rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
            <i class="bi bi-stars me-2"></i>Nueva Promo
        </button>
    </div>

    <div class="row g-4">
        <?php if(empty($promotions)): ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                No hay promociones activas.
            </div>
        <?php endif; ?>

        <?php foreach ($promotions as $p): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 promo-card shadow-sm">
                <div class="promo-img-container">
                    <?php if(!empty($p['image'])): ?>
                        <img src="/assets/img/<?= htmlspecialchars($p['image']) ?>" class="promo-img">
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                            <i class="bi bi-image fs-1"></i>
                        </div>
                    <?php endif; ?>
                    
                    <span class="badge bg-light text-dark promo-badge shadow-sm">
                        <?= $p['country_name'] ? '🏳️ ' . htmlspecialchars($p['country_name']) : '🌐 Global' ?>
                    </span>
                </div>

                <div class="card-body d-flex flex-column">
                    <h5 class="fw-bold mb-2"><?= htmlspecialchars($p['title']) ?></h5>
                    <p class="text-muted small mb-3 text-truncate"><?= htmlspecialchars($p['description']) ?></p>
                    
                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <span class="badge <?= $p['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                           <?= $p['is_active'] ? 'Activa' : 'Inactiva' ?>
                        </span>
                        
                        <div>
                            <button class="btn btn-sm btn-outline-primary me-1" 
                                    onclick='openEditModal(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    title="Editar">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="confirmDeletePromo(<?= $p['id'] ?>)"
                                    title="Eliminar">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade elegant-modal" id="promoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" id="promoForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="promoId">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Crear Promoción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold">Título</label>
                        <input type="text" name="title" id="promoTitle" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">País</label>
                        <select name="country_id" id="promoCountry" class="form-select">
                            <option value="0">🌐 Global (Todos)</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Inicio</label>
                        <input type="date" name="start_date" id="promoStart" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Fin</label>
                        <input type="date" name="end_date" id="promoEnd" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea name="description" id="promoDesc" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Texto Botón</label>
                        <input type="text" name="cta_text" id="promoCTA" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Enlace</label>
                        <input type="text" name="cta_link" id="promoLink" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Imagen</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="col-12 mt-3">
                        <div class="form-check form-switch bg-light p-3 rounded border">
                            <input class="form-check-input ms-0" type="checkbox" name="active" id="promoActive" checked style="margin-right: 10px;">
                            <label class="form-check-label fw-bold" for="promoActive">Activa</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    var promoModal;
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('promoModal');
        if(el) promoModal = new bootstrap.Modal(el);
    });

    function openCreateModal() {
        document.getElementById('promoForm').reset();
        document.getElementById('promoForm').action = '/admin/promotions/store';
        document.getElementById('modalTitle').innerHTML = '<i class="bi bi-megaphone-fill me-2"></i> Crear Promoción';
        document.getElementById('promoId').value = '';
        document.getElementById('promoActive').checked = true;
        promoModal.show();
    }

    function openEditModal(data) {
        document.getElementById('promoForm').action = '/admin/promotions/update';
        document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Editar Promoción';
        
        document.getElementById('promoId').value = data.id;
        document.getElementById('promoTitle').value = data.title;
        document.getElementById('promoDesc').value = data.description;
        document.getElementById('promoCountry').value = data.country_id || 0;
        document.getElementById('promoStart').value = data.start_date;
        document.getElementById('promoEnd').value = data.end_date;
        document.getElementById('promoCTA').value = data.cta_text;
        document.getElementById('promoLink').value = data.cta_link;
        
        // CORRECCIÓN: Usamos data.is_active
        document.getElementById('promoActive').checked = (data.is_active == 1);

        promoModal.show();
    }

    function confirmDeletePromo(id) {
        Swal.fire({
            title: '¿Eliminar Promoción?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                let form = document.createElement('form');
                form.action = '/admin/promotions/delete';
                form.method = 'POST';
                
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = id;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>