<?php
declare(strict_types=1);

/** @var array<int,array<string,mixed>> $leagues */
/** @var array<string,mixed>|null $round */

$isEdit = $round !== null;
$action = $isEdit ? '/admin/rounds/update' : '/admin/rounds/store';

$id              = $round['id']               ?? null;
$leagueId        = $round['league_id']        ?? '';
$name            = $round['name']             ?? '';
$roundNumber     = $round['round_number']     ?? 1;
$status          = $round['status']           ?? 'OPEN';
$openAt          = $round['open_at']          ?? '';
$closeAt         = $round['close_at']         ?? '';
$ticketCostMxn   = $round['ticket_cost_mxn']  ?? 200.00;
$ticketCostUsd   = $round['ticket_cost_usd']  ?? 10.00;
$poolPercent     = $round['prize_pool_percent']   ?? 45.00;
$firstPercent    = $round['first_place_percent']  ?? 30.00;
$secondPercent   = $round['second_place_percent'] ?? 15.00;

// Normalizar fechas para input datetime-local (si vienen en formato "Y-m-d H:i:s")
$openValue  = $openAt !== ''  ? str_replace(' ', 'T', substr((string)$openAt, 0, 16))  : '';
$closeValue = $closeAt !== '' ? str_replace(' ', 'T', substr((string)$closeAt, 0, 16)) : '';

require __DIR__ . '/../partials/nav.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">
        <?= $isEdit ? 'Editar jornada' : 'Crear jornada' ?>
    </h1>
    <a href="/admin/rounds" class="btn btn-sm btn-outline-secondary">
        Volver a jornadas
    </a>
</div>

<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>">
    <?= \App\Core\Security::csrfInput() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
    <div class="col-12 col-md-4">
        <label class="form-label small fw-bold" for="league_id">Liga</label>
        <select name="league_id" id="league_id" class="form-select form-select-sm" required>
            <option value="">Selecciona liga</option>
            <?php foreach ($leagues as $l): ?>
                <option value="<?= (int)$l['id'] ?>" <?= (int)$leagueId === (int)$l['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$l['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label small fw-bold" for="name">Nombre Base (Interno)</label>
        <input type="text" name="name" id="name" class="form-control form-control-sm" 
               value="<?= htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') ?>" required>
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label small fw-bold" for="round_number">N° jornada</label>
        <input type="number" name="round_number" id="round_number" class="form-control form-control-sm" 
               min="1" value="<?= (int)$roundNumber ?>">
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label small fw-bold" for="status">Estado</label>
        <select name="status" id="status" class="form-select form-select-sm">
            <option value="OPEN" <?= $status === 'OPEN' ? 'selected' : '' ?>>Abierta</option>
            <option value="CLOSED" <?= $status === 'CLOSED' ? 'selected' : '' ?>>Cerrada</option>
        </select>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <label class="form-label small fw-bold text-primary" for="custom_title">Título o Frase de la Jornada (Aparecerá en el Ranking público)</label>
        <input type="text" name="custom_title" id="custom_title" class="form-control form-control-sm border-primary" 
               value="<?= htmlspecialchars($round['custom_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
               placeholder="Ej: Gran Final Clausura 2024 - ¡Gana la Bolsa Acumulada!">
    </div>
</div>

            <hr>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label small" for="open_at">Fecha/hora de apertura</label>
                    <input type="datetime-local" name="open_at" id="open_at"
                           class="form-control form-control-sm"
                           value="<?= htmlspecialchars($openValue, ENT_QUOTES, 'UTF-8') ?>"
                           required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small" for="close_at">Fecha/hora de cierre</label>
                    <input type="datetime-local" name="close_at" id="close_at"
                           class="form-control form-control-sm"
                           value="<?= htmlspecialchars($closeValue, ENT_QUOTES, 'UTF-8') ?>"
                           required>
                </div>
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <label class="form-label small" for="ticket_cost_mxn">Costo ticket (MXN)</label>
                    <input type="number" step="0.01" min="0"
                           name="ticket_cost_mxn" id="ticket_cost_mxn"
                           class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string)$ticketCostMxn, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small" for="ticket_cost_usd">Costo ticket (USD)</label>
                    <input type="number" step="0.01" min="0"
                           name="ticket_cost_usd" id="ticket_cost_usd"
                           class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string)$ticketCostUsd, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">Distribución del pozo</label>
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Pozo</span>
                                <input type="number" step="0.01" min="0" max="100"
                                       name="prize_pool_percent"
                                       class="form-control"
                                       value="<?= htmlspecialchars((string)$poolPercent, ENT_QUOTES, 'UTF-8') ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">1.º</span>
                                <input type="number" step="0.01" min="0" max="100"
                                       name="first_place_percent"
                                       class="form-control"
                                       value="<?= htmlspecialchars((string)$firstPercent, ENT_QUOTES, 'UTF-8') ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">2.º</span>
                                <input type="number" step="0.01" min="0" max="100"
                                       name="second_place_percent"
                                       class="form-control"
                                       value="<?= htmlspecialchars((string)$secondPercent, ENT_QUOTES, 'UTF-8') ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="small text-muted mt-1">
                        Ejemplo por defecto: Pozo 45% (30% primer lugar, 15% segundo).
                    </div>
                </div>
            </div>
        </div>
        
 

        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-sm btn-primary">
                Guardar jornada
            </button>
        </div>
    </div>
</form>
