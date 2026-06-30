<?php

declare(strict_types=1);

use App\Core\Security;

/** @var string|null $error */

require __DIR__ . '/../partials/nav.php';
?>

<div class="admin-mobile-page">
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <div class="display-6 mb-3">
                <i class="bi bi-clock-history"></i>
            </div>

            <h1 class="h4 mb-2">
                Historial no disponible
            </h1>

            <p class="text-muted mb-4">
                No se encontró una jornada histórica para mostrar o todavía no existen tickets cerrados.
            </p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-warning text-start">
                    <?= Security::e($error) ?>
                </div>
            <?php endif; ?>

            <a href="/admin/rounds" class="btn btn-primary rounded-pill">
                Ir a jornadas
            </a>
        </div>
    </div>
</div>