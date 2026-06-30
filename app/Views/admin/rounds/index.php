<?php

declare(strict_types=1);

use App\Core\Security;

/** @var array<int,array<string,mixed>> $rounds */
/** @var array<int,array<string,mixed>> $leagues */

require __DIR__ . '/../partials/nav.php';
?>

<div class="admin-mobile-page qv-admin-rounds">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Operación deportiva</span>
            <h1>Gestión de jornadas</h1>
            <p>
                Crea, edita, configura premios y administra partidos por jornada.
            </p>
        </div>

        <button type="button" class="btn btn-primary qv-admin-primary-action" onclick="openCreateModal()">
            <i class="bi bi-calendar-plus me-2"></i>
            Nueva jornada
        </button>
    </header>

    <?php if (empty($rounds)): ?>
        <section class="qv-admin-empty-state">
            <i class="bi bi-calendar-x"></i>
            <strong>No hay jornadas creadas.</strong>
            <span>Crea una jornada para empezar a recibir quinielas.</span>

            <button type="button" class="btn btn-primary mt-3" onclick="openCreateModal()">
                Crear primera jornada
            </button>
        </section>
    <?php else: ?>
        <section class="qv-admin-round-grid">
            <?php foreach ($rounds as $round): ?>
                <?php
                $roundId = (int)($round['id'] ?? 0);
                $leagueName = (string)($round['league_name'] ?? '');
                $name = (string)($round['name'] ?? '');
                $customTitle = (string)($round['custom_title'] ?? '');
                $roundNumber = (int)($round['round_number'] ?? 0);
                $status = (string)($round['status'] ?? 'PENDING');
                $pool = (float)($round['prize_pool_percent'] ?? 45);
                $closeAt = (string)($round['close_at'] ?? '');
                $ticketMxn = (float)($round['ticket_cost_mxn'] ?? 0);
                $ticketUsd = (float)($round['ticket_cost_usd'] ?? 0);

                $statusClass = match ($status) {
                    'OPEN' => 'qv-status-paid',
                    'LIVE' => 'qv-status-danger',
                    'CLOSED' => 'qv-status-pending',
                    'FINISHED' => 'qv-status-muted',
                    default => 'qv-status-soft',
                };

                $closeLabel = $closeAt !== '' ? date('d/m/Y H:i', strtotime($closeAt)) : 'Sin cierre';
                ?>

                <article class="qv-admin-round-card">
                    <div class="qv-admin-round-top">
                        <div>
                            <span class="qv-admin-round-league">
                                <?= Security::e($leagueName) ?>
                            </span>

                            <h2>
                                <?= Security::e($name) ?>
                            </h2>

                            <span class="qv-admin-round-number">
                                Jornada <?= $roundNumber ?>
                            </span>
                        </div>

                        <span class="qv-admin-status <?= $statusClass ?>">
                            <?= Security::e($status) ?>
                        </span>
                    </div>

                    <?php if ($customTitle !== ''): ?>
                        <div class="qv-admin-round-highlight">
                            <i class="bi bi-megaphone-fill"></i>
                            <span><?= Security::e($customTitle) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="qv-admin-round-metrics">
                        <div>
                            <span>Cierre</span>
                            <strong><?= Security::e($closeLabel) ?></strong>
                        </div>

                        <div>
                            <span>Bolsa</span>
                            <strong><?= number_format($pool, 1) ?>%</strong>
                        </div>

                        <div>
                            <span>Costo MXN</span>
                            <strong>$<?= number_format($ticketMxn, 2) ?></strong>
                        </div>

                        <div>
                            <span>Costo USD</span>
                            <strong>$<?= number_format($ticketUsd, 2) ?></strong>
                        </div>
                    </div>

                    <div class="qv-admin-round-actions">
                        <a
                            href="/admin/rounds/matches?round_id=<?= $roundId ?>"
                            class="btn btn-outline-info btn-sm"
                        >
                            <i class="bi bi-list-ol me-1"></i>
                            Partidos
                        </a>

                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm"
                            onclick='openEditModal(<?= json_encode($round, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'
                        >
                            <i class="bi bi-pencil-fill me-1"></i>
                            Editar
                        </button>

                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            onclick="confirmDeleteRound(<?= $roundId ?>)"
                        >
                            <i class="bi bi-trash-fill me-1"></i>
                            Eliminar
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</div>

<div class="modal fade qv-admin-modal" id="roundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" id="roundForm" method="POST">
            <div class="modal-header">
                <div>
                    <span class="qv-admin-eyebrow">Jornada</span>
                    <h2 class="modal-title h5" id="modalTitle">
                        Nueva jornada
                    </h2>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <?= \App\Core\Security::csrfInput() ?>

                <input type="hidden" name="id" id="roundId">

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="roundLeague" class="form-label fw-bold">Liga</label>
                        <select name="league_id" id="roundLeague" class="form-select" required>
                            <?php foreach ($leagues as $league): ?>
                                <option value="<?= (int)$league['id'] ?>">
                                    <?= Security::e($league['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="roundName" class="form-label fw-bold">Nombre base</label>
                        <input
                            type="text"
                            name="name"
                            id="roundName"
                            class="form-control"
                            placeholder="Ej. Jornada 10"
                            required
                        >
                    </div>

                    <div class="col-12 col-md-2">
                        <label for="roundNum" class="form-label fw-bold">Número</label>
                        <input type="number" name="round_number" id="roundNum" class="form-control" required>
                    </div>

                    <div class="col-12">
                        <label for="roundCustomTitle" class="form-label fw-bold">
                            Título público / frase especial
                        </label>
                        <input
                            type="text"
                            name="custom_title"
                            id="roundCustomTitle"
                            class="form-control"
                            placeholder="Ej. Gran final de temporada"
                        >
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="roundOpen" class="form-label fw-bold">Apertura</label>
                        <input type="datetime-local" name="open_at" id="roundOpen" class="form-control" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="roundClose" class="form-label fw-bold">Cierre</label>
                        <input type="datetime-local" name="close_at" id="roundClose" class="form-control" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="roundStatus" class="form-label fw-bold">Estado</label>
                        <select name="status" id="roundStatus" class="form-select">
                            <option value="OPEN">OPEN · Abierta</option>
                            <option value="LIVE">LIVE · En juego</option>
                            <option value="CLOSED">CLOSED · Cerrada</option>
                            <option value="FINISHED">FINISHED · Finalizada</option>
                            <option value="PENDING">PENDING · Futura</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="qv-admin-form-divider">
                            <i class="bi bi-cash-coin"></i>
                            Costos de participación
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="roundMxn" class="form-label fw-bold">Costo ticket MXN</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input
                                type="number"
                                step="0.01"
                                name="ticket_cost_mxn"
                                id="roundMxn"
                                class="form-control"
                                value="200.00"
                            >
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="roundUsd" class="form-label fw-bold">Costo ticket USD</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input
                                type="number"
                                step="0.01"
                                name="ticket_cost_usd"
                                id="roundUsd"
                                class="form-control"
                                value="10.00"
                            >
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="qv-admin-form-divider">
                            <i class="bi bi-trophy-fill"></i>
                            Configuración de premios
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="poolPercent" class="form-label fw-bold">% bolsa a repartir</label>
                        <div class="input-group">
                            <input
                                type="number"
                                step="0.1"
                                name="prize_pool_percent"
                                id="poolPercent"
                                class="form-control"
                                value="45.0"
                            >
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text">
                            Del total recaudado.
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="firstPercent" class="form-label fw-bold">% primer lugar</label>
                        <div class="input-group">
                            <span class="input-group-text">🥇</span>
                            <input
                                type="number"
                                step="0.1"
                                name="first_place_percent"
                                id="firstPercent"
                                class="form-control"
                                value="30.0"
                            >
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="secondPercent" class="form-label fw-bold">% segundo lugar</label>
                        <div class="input-group">
                            <span class="input-group-text">🥈</span>
                            <input
                                type="number"
                                step="0.1"
                                name="second_place_percent"
                                id="secondPercent"
                                class="form-control"
                                value="15.0"
                            >
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <button type="submit" class="btn btn-primary px-4">
                    Guardar jornada
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    var roundModal;

    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('roundModal');

        if (modalElement && typeof bootstrap !== 'undefined') {
            roundModal = new bootstrap.Modal(modalElement);
        }
    });

    function formatDateTimeLocal(dateString) {
        if (!dateString) {
            return '';
        }

        return String(dateString).replace(' ', 'T').slice(0, 16);
    }

    function openCreateModal() {
        var form = document.getElementById('roundForm');

        form.reset();
        form.action = '/admin/rounds/store';

        document.getElementById('modalTitle').innerText = 'Nueva jornada';
        document.getElementById('roundId').value = '';
        document.getElementById('roundStatus').value = 'OPEN';
        document.getElementById('roundCustomTitle').value = '';

        document.getElementById('poolPercent').value = '45.0';
        document.getElementById('firstPercent').value = '30.0';
        document.getElementById('secondPercent').value = '15.0';
        document.getElementById('roundMxn').value = '200.00';
        document.getElementById('roundUsd').value = '10.00';

        roundModal.show();
    }

    function openEditModal(data) {
        document.getElementById('roundForm').action = '/admin/rounds/update';
        document.getElementById('modalTitle').innerText = 'Editar jornada';

        document.getElementById('roundId').value = data.id || '';
        document.getElementById('roundLeague').value = data.league_id || '';
        document.getElementById('roundName').value = data.name || '';
        document.getElementById('roundNum').value = data.round_number || '';
        document.getElementById('roundOpen').value = formatDateTimeLocal(data.open_at);
        document.getElementById('roundClose').value = formatDateTimeLocal(data.close_at);
        document.getElementById('roundStatus').value = data.status || 'OPEN';
        document.getElementById('roundMxn').value = data.ticket_cost_mxn || '200.00';
        document.getElementById('roundUsd').value = data.ticket_cost_usd || '10.00';
        document.getElementById('roundCustomTitle').value = data.custom_title || '';

        document.getElementById('poolPercent').value = data.prize_pool_percent || '45.0';
        document.getElementById('firstPercent').value = data.first_place_percent || '30.0';
        document.getElementById('secondPercent').value = data.second_place_percent || '15.0';

        roundModal.show();
    }

    function confirmDeleteRound(id) {
    window.qvConfirmDelete(
        '¿Borrar jornada?',
        'Se eliminarán los partidos y pronósticos asociados. Esta acción puede ser irreversible.',
        function () {
            window.enviarFormularioAdmin('/admin/rounds/delete', {
                id: id
            });
        }
    );
}


</script>