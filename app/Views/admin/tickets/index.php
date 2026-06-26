<?php
/**
 * @var array<int,array<string,mixed>> $tickets
 * @var array<int,array<string,mixed>> $rounds
 * @var array<string,mixed>            $filters
 */
require __DIR__ . '/../partials/nav.php';
?>
<section class="container-fluid py-3">
    <h1 class="h4 mb-3">Admin · Tickets</h1>

    <form class="card mb-3 shadow-sm" method="get" action="/admin/tickets">
        <div class="card-body row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label fw-bold">Jornada</label>
                <select name="round_id" class="form-select">
                    <option value="0">Todas</option>
                    <?php foreach ($rounds as $r): ?>
                        <?php
                        $id   = (int)$r['id'];
                        $name = $r['name'] ?: ('Jornada ' . (string)$r['number']);
                        $label = $name . ' · ' . (string)$r['league_name'];
                        $selected = ($filters['round_id'] ?? 0) === $id ? 'selected' : '';
                        ?>
                        <option value="<?= $id ?>" <?= $selected ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label">Estado</label>
                <?php $fStatus = (string)($filters['status'] ?? ''); ?>
                <select name="status" class="form-select">
                    <option value="ALL">Todos</option>
                    <option value="PENDING"  <?= $fStatus === 'PENDING'  ? 'selected' : '' ?>>Pendiente</option>
                    <option value="PAID"      <?= $fStatus === 'PAID'      ? 'selected' : '' ?>>Pagado</option>
                    <option value="REJECTED" <?= $fStatus === 'REJECTED' ? 'selected' : '' ?>>Rechazado</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Desde</label>
                <input type="date" name="from" class="form-control"
                       value="<?= htmlspecialchars((string)($filters['from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Hasta</label>
                <input type="date" name="to" class="form-control"
                       value="<?= htmlspecialchars((string)($filters['to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label">Buscar</label>
                <div class="input-group">
                    <input type="text" name="q" class="form-control"
                           placeholder="Ticket, nombre..."
                           value="<?= htmlspecialchars((string)($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr class="small text-uppercase text-muted">
                        <th>Fecha</th>
                        <th>Ticket</th>
                        <th>Jornada</th>
                        <th>Cliente</th>
                        <th class="text-center">Pts</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Cambiar Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="9" class="text-center small py-4 text-muted">
                                No se encontraron tickets con los filtros actuales.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                            <?php
                            $id        = (int)$t['id'];
                            $code      = (string)$t['ticket_code'];
                            $createdAt = (string)$t['created_at'];
                            $league    = (string)($t['league_name'] ?? '');
                            $roundName = (string)($t['matchday_name'] ?? '');
                            $client    = (string)$t['user_name'];
                            $phone     = (string)$t['phone'];
                            $points    = (int)$t['points'];
                            $amount    = (float)$t['total_amount'];
                            $currency  = (string)$t['currency'];
                            $status    = (string)$t['status'];

                            $badgeClass = 'bg-secondary';
                            if ($status === 'PENDING') { $badgeClass = 'bg-warning text-dark'; }
                            elseif ($status === 'PAID') { $badgeClass = 'bg-success'; }
                            elseif ($status === 'REJECTED') { $badgeClass = 'bg-danger'; }
                            ?>
                            <tr>
                                <td class="small text-muted">
                                    <?= date('d/m/y H:i', strtotime($createdAt)) ?>
                                </td>
                                
                                <td>
                                    <div class="font-monospace fw-bold text-primary">
                                        <?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <small class="text-muted">#<?= (int)$t['round_ticket_number'] ?></small>
                                </td>
                                
                                <td class="small">
                                    <div class="fw-bold"><?= htmlspecialchars($league, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted"><?= htmlspecialchars($roundName, ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($client, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                
                                <td class="text-center fw-bold fs-5">
                                    <?= $points ?>
                                </td>
                                
                                <td class="text-end text-muted small">
                                    <?= sprintf('%s %0.2f', $currency, $amount) ?>
                                </td>
                                
                                <td class="text-center">
                                    <span class="badge <?= $badgeClass ?> rounded-pill">
                                        <?= $status ?>
                                    </span>
                                </td>
                                
                                <td class="text-center">
                                    <form method="post" action="/admin/tickets/update-status" class="d-flex align-items-center justify-content-center gap-1">
                                        <input type="hidden" name="ticket_id" value="<?= $id ?>">
                                        <select name="status" class="form-select form-select-sm" style="width: auto;">
                                            <option value="PENDING"  <?= $status === 'PENDING'  ? 'selected' : '' ?>>PENDING</option>
                                            <option value="PAID"      <?= $status === 'PAID'      ? 'selected' : '' ?>>PAID</option>
                                            <option value="REJECTED" <?= $status === 'REJECTED' ? 'selected' : '' ?>>REJECTED</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                </td>
                                
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="/admin/tickets/show?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary" title="Ver Detalle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" onclick="confirmDeleteTicket(<?= $id ?>)" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDeleteTicket(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Se borrará este ticket y todos sus pronósticos. No se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Crear formulario invisible para enviar POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/tickets/delete';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ticket_id';
            input.value = id;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
