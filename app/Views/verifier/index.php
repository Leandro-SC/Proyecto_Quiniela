<?php

declare(strict_types=1);

if (!function_exists('h')) {
    /**
     * Escapa texto para HTML.
     *
     * @param mixed $s Valor.
     * @return string
     */
    function h(mixed $s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('qvVerifierPickLabel')) {
    /**
     * Traduce L/E/V a texto legible.
     *
     * @param string $pick Pronóstico.
     * @return string
     */
    function qvVerifierPickLabel(string $pick): string
    {
        return match (strtoupper($pick)) {
            'L' => 'Local',
            'E' => 'Empate',
            'V' => 'Visita',
            default => '-',
        };
    }
}

if (!function_exists('qvVerifierStatusLabel')) {
    /**
     * Traduce estado técnico.
     *
     * @param string $status Estado.
     * @return string
     */
    function qvVerifierStatusLabel(string $status): string
    {
        return match (strtoupper($status)) {
            'PAID' => 'Pagado',
            'PENDING' => 'Pendiente',
            'CANCELLED' => 'Cancelado',
            'REJECTED' => 'Rechazado',
            default => $status !== '' ? ucfirst(strtolower($status)) : '-',
        };
    }
}

if (!function_exists('qvVerifierStatusClass')) {
    /**
     * Devuelve clase visual por estado.
     *
     * @param string $status Estado.
     * @return string
     */
    function qvVerifierStatusClass(string $status): string
    {
        return match (strtoupper($status)) {
            'PAID' => 'bg-success',
            'PENDING' => 'bg-warning text-dark',
            'CANCELLED', 'REJECTED' => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}

if (!function_exists('qvVerifierMaskedPhone')) {
    /**
     * Oculta teléfono dejando últimos 4 dígitos.
     *
     * @param mixed $phone Teléfono.
     * @return string
     */
    function qvVerifierMaskedPhone(mixed $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string)$phone) ?? '';

        if (strlen($digits) <= 4) {
            return $digits;
        }

        return str_repeat('•', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }
}

$ticket = $ticket ?? null;
$items = $items ?? [];
$matches = $matches ?? [];
$searchQuery = $searchQuery ?? ($ticketCode ?? '');
$searchType = $searchType ?? 'auto';
$error = $error ?? null;
$rank = $rank ?? null;

$ticketStatus = strtoupper((string)($ticket['status'] ?? ''));
$playerName = (string)($ticket['user_name'] ?? $ticket['player_name'] ?? '');
$ticketCodeResult = (string)($ticket['ticket_code'] ?? '');
$leagueName = (string)($ticket['league_name'] ?? 'Liga');
$roundName = (string)($ticket['round_name'] ?? 'Jornada');
$points = (int)($ticket['points'] ?? 0);

$currentUrl = '/verificador';

if ($ticketCodeResult !== '') {
    $currentUrl .= '?q=' . rawurlencode($ticketCodeResult) . '&type=code';
}

$shareText = $ticket
    ? 'Mi ticket de quiniela es ' . $ticketCodeResult . '. Verifica resultados y ranking aquí: ' . $currentUrl
    : '';

$whatsappShareUrl = $shareText !== ''
    ? 'https://wa.me/?text=' . rawurlencode($shareText)
    : '';
?>

<div class="container py-5 qv-verifier-page" style="max-width: 1040px;">

    <div class="text-center mb-4">
        <span class="badge rounded-pill bg-warning text-dark fw-bold px-3 py-2 mb-3">
            Verificación oficial
        </span>

        <h1 class="h3 fw-bold text-uppercase mb-2">
            Verificador de Quiniela
        </h1>

        <p class="text-muted mb-0">
            Busca tu ticket por código, teléfono o nombre y consulta tus puntos.
        </p>
    </div>

    <div class="card shadow-sm border-0 mb-4 overflow-hidden">
        <div class="card-body p-4 bg-light">
            <form method="get" action="/verificador" class="row g-3 align-items-end justify-content-center">
                <div class="col-12 col-md-3">
                    <label for="type" class="form-label fw-bold small text-uppercase text-muted">
                        Buscar por
                    </label>

                    <select name="type" id="type" class="form-select form-select-lg">
                        <option value="auto" <?= $searchType === 'auto' ? 'selected' : '' ?>>
                            Automático
                        </option>
                        <option value="code" <?= $searchType === 'code' ? 'selected' : '' ?>>
                            Código
                        </option>
                        <option value="phone" <?= $searchType === 'phone' ? 'selected' : '' ?>>
                            Teléfono
                        </option>
                        <option value="name" <?= $searchType === 'name' ? 'selected' : '' ?>>
                            Nombre
                        </option>
                    </select>
                </div>

                <div class="col-12 col-md-7">
                    <label for="q" class="form-label fw-bold small text-uppercase text-muted">
                        Dato de búsqueda
                    </label>

                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-ticket-perforated"></i>
                        </span>

                        <input
                            id="q"
                            type="text"
                            name="q"
                            class="form-control fw-bold"
                            placeholder="Ej: QV-0001-00001, teléfono o nombre"
                            value="<?= h($searchQuery) ?>"
                            autocomplete="off"
                            required
                        >
                    </div>
                </div>

                <div class="col-12 col-md-2 d-grid">
                    <button class="btn btn-primary btn-lg fw-bold" type="submit">
                        <i class="bi bi-search me-1"></i>
                        Buscar
                    </button>
                </div>
            </form>

            <?php if ($error): ?>
                <div class="alert alert-danger mt-3 text-center mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <?= h($error) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($matches !== [] && !$ticket): ?>
        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="card-header bg-dark text-white fw-bold text-uppercase">
                Tickets encontrados
            </div>

            <div class="list-group list-group-flush">
                <?php foreach ($matches as $match): ?>
                    <a
                        href="/verificador?ticket_id=<?= (int)$match['id'] ?>"
                        class="list-group-item list-group-item-action p-3"
                    >
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                            <div>
                                <div class="fw-bold text-primary text-uppercase">
                                    <?= h($match['ticket_code'] ?? '') ?>
                                </div>

                                <div class="fw-bold">
                                    <?= h($match['user_name'] ?? $match['player_name'] ?? '') ?>
                                </div>

                                <div class="small text-muted">
                                    <?= h($match['league_name'] ?? 'Liga') ?>
                                    —
                                    <?= h($match['round_name'] ?? 'Jornada') ?>
                                </div>
                            </div>

                            <div class="text-md-end">
                                <span class="badge <?= h(qvVerifierStatusClass((string)($match['status'] ?? ''))) ?>">
                                    <?= h(qvVerifierStatusLabel((string)($match['status'] ?? ''))) ?>
                                </span>

                                <div class="small text-muted mt-2">
                                    Tel: <?= h(qvVerifierMaskedPhone($match['phone'] ?? '')) ?>
                                </div>

                                <div class="small fw-bold">
                                    Puntos: <?= (int)($match['points'] ?? 0) ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="card-footer bg-light small text-muted">
                Selecciona el ticket correcto para ver el detalle completo.
            </div>
        </div>
    <?php endif; ?>

    <?php if ($ticket): ?>
        <div class="card mb-4 border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-dark text-white py-3 text-center">
                <div class="small text-uppercase text-warning fw-bold mb-1">
                    Ticket encontrado
                </div>

                <h2 class="h5 mb-0 text-uppercase">
                    <?= h($leagueName) ?>
                    —
                    <?= h($roundName) ?>
                </h2>
            </div>

            <div class="card-body bg-white">
                <div class="row g-3 text-center">
                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-4 bg-light h-100">
                            <div class="small text-muted text-uppercase fw-bold">
                                Jugador
                            </div>

                            <div class="fw-bold fs-5">
                                <?= h($playerName) ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-4 bg-light h-100">
                            <div class="small text-muted text-uppercase fw-bold">
                                Código
                            </div>

                            <div class="fw-bold fs-5 text-primary text-uppercase">
                                <?= h($ticketCodeResult) ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-4 bg-light h-100">
                            <div class="small text-muted text-uppercase fw-bold">
                                Estado
                            </div>

                            <span class="badge <?= h(qvVerifierStatusClass($ticketStatus)) ?> px-3 py-2">
                                <?= h(qvVerifierStatusLabel($ticketStatus)) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row g-3 align-items-center text-center">
                    <div class="col-6">
                        <div class="p-3 rounded-4 border">
                            <div class="small text-muted text-uppercase fw-bold">
                                Puntos
                            </div>

                            <div class="display-5 fw-bold text-dark">
                                <?= $points ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="p-3 rounded-4 border">
                            <div class="small text-muted text-uppercase fw-bold">
                                Posición
                            </div>

                            <div class="display-5 fw-bold text-primary">
                                <?= $rank ? '#' . h($rank) : '-' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($whatsappShareUrl !== ''): ?>
                    <div class="text-center mt-4 d-flex flex-column flex-md-row gap-2 justify-content-center">
                        <a
                            href="<?= h($whatsappShareUrl) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-success fw-bold"
                        >
                            <i class="bi bi-whatsapp me-1"></i>
                            Compartir ticket
                        </a>

                        <a href="/ranking" class="btn btn-outline-primary fw-bold">
                            Ver ranking
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-primary text-white text-center fw-bold text-uppercase py-3">
                Detalle de resultados
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 text-center align-middle" style="font-size: 0.9rem;">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th style="width: 36%">Partido</th>
                                <th style="width: 18%">Tu pronóstico</th>
                                <th style="width: 18%">Resultado</th>
                                <th style="width: 14%">Marcador</th>
                                <th style="width: 14%">Estado</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($items === []): ?>
                                <tr>
                                    <td colspan="5" class="py-4 text-muted">
                                        Este ticket no tiene pronósticos registrados.
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($items as $item): ?>
                                <?php
                                $myPick = strtoupper((string)($item['selection'] ?? $item['pick'] ?? ''));
                                $official = strtoupper((string)($item['result_outcome'] ?? ''));
                                $matchStatus = strtoupper((string)($item['match_status'] ?? ''));

                                $hasOfficialResult = $official !== '';
                                $isHit = $hasOfficialResult && $myPick === $official;

                                $pickClass = '';
                                $icon = '⏳';

                                if ($hasOfficialResult) {
                                    $pickClass = $isHit ? 'bg-success text-white' : 'bg-danger text-white';
                                    $icon = $isHit ? '✅' : '❌';
                                }

                                $homeScore = $item['home_score'] ?? null;
                                $awayScore = $item['away_score'] ?? null;
                                $scoreLabel = '-';

                                if (
                                    $homeScore !== null &&
                                    $homeScore !== '' &&
                                    $awayScore !== null &&
                                    $awayScore !== ''
                                ) {
                                    $scoreLabel = (int)$homeScore . ' - ' . (int)$awayScore;
                                }
                                ?>

                                <tr>
                                    <td class="text-start ps-3">
                                        <div class="fw-bold">
                                            <?= h($item['home_team_name'] ?? '') ?>
                                        </div>

                                        <div class="small text-muted">
                                            vs
                                        </div>

                                        <div class="fw-bold">
                                            <?= h($item['away_team_name'] ?? '') ?>
                                        </div>
                                    </td>

                                    <td class="fw-bold <?= h($pickClass) ?>">
                                        <?= h(qvVerifierPickLabel($myPick)) ?>

                                        <div class="small">
                                            <?= h($myPick ?: '-') ?>
                                        </div>
                                    </td>

                                    <td class="fw-bold text-muted">
                                        <?= h($hasOfficialResult ? qvVerifierPickLabel($official) : 'Pendiente') ?>

                                        <div class="small">
                                            <?= h($official ?: '-') ?>
                                        </div>
                                    </td>

                                    <td class="fw-bold">
                                        <?= h($scoreLabel) ?>
                                    </td>

                                    <td class="fs-5">
                                        <div><?= $icon ?></div>

                                        <div class="small text-muted">
                                            <?= h($matchStatus ?: 'PENDIENTE') ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-light text-center small text-muted">
                * Los resultados se actualizan cuando el administrador registra los marcadores.
            </div>
        </div>
    <?php endif; ?>
</div>