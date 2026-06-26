<?php require __DIR__ . '/../partials/nav.php'; ?>

<style>
    /* Estilos para visibilidad del modal */
    .modal-body, .form-label, .form-control, .form-select, .input-group-text { color: #212529 !important; }
    .form-control, .form-select { background-color: #fff !important; border: 1px solid #ced4da; }
    
    /* Diseño Elegante */
    .elegant-modal .modal-content { border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    .elegant-modal .modal-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border-bottom: none; }
    .elegant-modal .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>🗓️ Gestión de Jornadas</h3>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
            <i class="bi bi-calendar-plus me-2"></i> Nueva Jornada
        </button>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Liga</th>
                        <th>Jornada</th>
                        <th>Cierre / Bolsa %</th>
                        <th>Estado</th>
                        <th>Costos (MXN/USD)</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(empty($rounds)): ?>
                    <tr><td colspan="6" class="text-center py-4">No hay jornadas creadas.</td></tr>
                <?php else: ?>
                    <?php foreach ($rounds as $r): ?>
                        <?php 
                            $statusBadge = match($r['status']) {
                                'OPEN' => 'bg-success', 'LIVE' => 'bg-danger', 'CLOSED' => 'bg-secondary', 'FINISHED' => 'bg-dark', default => 'bg-light text-dark'
                            };
                            $pool = $r['prize_pool_percent'] ?? 45;
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($r['league_name']) ?></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($r['name']) ?></div>
                                <?php if(!empty($r['custom_title'])): ?>
                                    <small class="text-info d-block" style="font-size: 0.75rem;">
                                        <i class="bi bi-megaphone-fill"></i> <?= htmlspecialchars($r['custom_title']) ?>
                                    </small>
                                <?php endif; ?>
                                <small class="text-muted">N° <?= $r['round_number'] ?></small>
                            </td>
                            <td>
                                <div class="small font-monospace"><?= date('d/m/Y H:i', strtotime($r['close_at'])) ?></div>
                                <span class="badge bg-info text-dark" title="% de Recaudación destinado a premios">Bolsa: <?= $pool ?>%</span>
                            </td>
                            <td><span class="badge <?= $statusBadge ?>"><?= $r['status'] ?></span></td>
                            <td class="small">$<?= $r['ticket_cost_mxn'] ?> / $<?= $r['ticket_cost_usd'] ?></td>
                            <td class="text-end pe-4">
                                <a href="/admin/rounds/matches?round_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="Gestionar Partidos">
                                    <i class="bi bi-list-ol"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-primary me-1" 
                                        onclick='openEditModal(<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteRound(<?= $r['id'] ?>)" title="Eliminar">
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

<div class="modal fade elegant-modal" id="roundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" id="roundForm" method="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nueva Jornada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 row g-3">
                <input type="hidden" name="id" id="roundId">

                <div class="col-md-6">
                    <label class="form-label fw-bold">Liga</label>
                    <select name="league_id" id="roundLeague" class="form-select" required>
                        <?php foreach ($leagues as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Nombre Base (Ej. Jornada 10)</label>
                    <input type="text" name="name" id="roundName" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Número</label>
                    <input type="number" name="round_number" id="roundNum" class="form-control" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-bold text-primary">Título Público / Frase Especial (Aparecerá en el Ranking)</label>
                    <input type="text" name="custom_title" id="roundCustomTitle" class="form-control border-primary" 
                           placeholder="Ej: Gran Final Clausura 2024 - ¡Gana la Bolsa Acumulada!">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label fw-bold">Apertura (Inicio venta)</label>
                    <input type="datetime-local" name="open_at" id="roundOpen" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Cierre (Deadline)</label>
                    <input type="datetime-local" name="close_at" id="roundClose" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Estado Inicial</label>
                    <select name="status" id="roundStatus" class="form-select">
                        <option value="OPEN">OPEN (Abierta para jugar)</option>
                        <option value="LIVE">LIVE (En juego)</option>
                        <option value="CLOSED">CLOSED (Cerrada/Calculando)</option>
                        <option value="FINISHED">FINISHED (Finalizada)</option>
                        <option value="PENDING">PENDING (Futura)</option>
                    </select>
                </div>

                <div class="col-12"><hr class="text-muted"></div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Costo Ticket (MXN)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" name="ticket_cost_mxn" id="roundMxn" class="form-control" value="200.00">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Costo Ticket (USD)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" name="ticket_cost_usd" id="roundUsd" class="form-control" value="10.00">
                    </div>
                </div>

                <div class="col-12"><hr class="text-muted"></div>
                <h6 class="text-primary fw-bold"><i class="bi bi-trophy"></i> Configuración de Premios</h6>

                <div class="col-md-4">
                    <label class="form-label fw-bold">% Bolsa a Repartir</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="prize_pool_percent" id="poolPercent" class="form-control" value="45.0">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text small">Del total recaudado.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">% 1er Lugar</label>
                    <div class="input-group">
                        <span class="input-group-text">🥇</span>
                        <input type="number" step="0.1" name="first_place_percent" id="firstPercent" class="form-control" value="30.0">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text small">De la bolsa total.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">% 2do Lugar</label>
                    <div class="input-group">
                        <span class="input-group-text">🥈</span>
                        <input type="number" step="0.1" name="second_place_percent" id="secondPercent" class="form-control" value="15.0">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text small">De la bolsa total.</div>
                </div>

            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    var roundModal;
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('roundModal');
        if (el) roundModal = new bootstrap.Modal(el);
    });

    function formatDateTimeLocal(dateString) {
        if (!dateString) return '';
        return dateString.replace(' ', 'T').slice(0, 16);
    }

    function openCreateModal() {
        document.getElementById('roundForm').reset();
        document.getElementById('roundForm').action = '/admin/rounds/store';
        document.getElementById('modalTitle').innerText = 'Nueva Jornada';
        document.getElementById('roundId').value = '';
        document.getElementById('roundStatus').value = 'OPEN';
        
        // Limpiamos el nuevo campo de título
        document.getElementById('roundCustomTitle').value = '';
        
        // Valores por defecto
        document.getElementById('poolPercent').value = '45.0';
        document.getElementById('firstPercent').value = '30.0';
        document.getElementById('secondPercent').value = '15.0';
        document.getElementById('roundMxn').value = '200.00';
        document.getElementById('roundUsd').value = '10.00';
        
        roundModal.show();
    }

    function openEditModal(data) {
        document.getElementById('roundForm').action = '/admin/rounds/update';
        document.getElementById('modalTitle').innerText = 'Editar Jornada';
        
        document.getElementById('roundId').value = data.id;
        document.getElementById('roundLeague').value = data.league_id;
        document.getElementById('roundName').value = data.name;
        document.getElementById('roundNum').value = data.round_number;
        document.getElementById('roundOpen').value = formatDateTimeLocal(data.open_at);
        document.getElementById('roundClose').value = formatDateTimeLocal(data.close_at);
        document.getElementById('roundStatus').value = data.status;
        document.getElementById('roundMxn').value = data.ticket_cost_mxn;
        document.getElementById('roundUsd').value = data.ticket_cost_usd;

        // Cargamos el título personalizado en el input del modal
        document.getElementById('roundCustomTitle').value = data.custom_title || '';

        document.getElementById('poolPercent').value = data.prize_pool_percent || '45.0';
        document.getElementById('firstPercent').value = data.first_place_percent || '30.0';
        document.getElementById('secondPercent').value = data.second_place_percent || '15.0';

        roundModal.show();
    }

    function confirmDeleteRound(id) {
        Swal.fire({
            title: '¿Borrar Jornada?',
            text: "Se eliminarán todos los partidos y pronósticos asociados. ¡Irreversible!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, borrar todo'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form'); form.method = 'POST'; form.action = '/admin/rounds/delete';
                const input = document.createElement('input'); input.type = 'hidden'; input.name = 'id'; input.value = id;
                form.appendChild(input); document.body.appendChild(form); form.submit();
            }
        });
    }
</script>