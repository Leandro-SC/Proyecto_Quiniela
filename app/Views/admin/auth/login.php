<?php

declare(strict_types=1);

/**
 * Vista: Login de administrador
 *
 * Variables esperadas:
 * - string|null $error  Mensaje de error (opcional).
 * - array<string,mixed>|null $old Datos previos del formulario (opcional).
 */

$old = $old ?? [];
$usernameValue = htmlspecialchars((string)($old['username'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="d-flex justify-content-center align-items-center" aria-labelledby="admin-login-title">
    <div class="w-100" style="max-width: 420px; margin-top: 3rem; margin-bottom: 3rem;">
        <div class="card shadow-lg border-0 bg-body">
            <div class="card-header bg-dark text-light text-center py-3">
                <h1 id="admin-login-title" class="h5 mb-0 text-uppercase fw-bold">
                    Acceso administrador
                </h1>
                <p class="small mb-0 text-secondary">
                    Panel de control · Villa Quiniela
                </p>
            </div>

            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger small" role="alert">
                        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

               <form action="/admin/login" method="post" novalidate aria-describedby="admin-login-help">
    <?= \App\Core\Security::csrfInput() ?>
                    <div class="mb-3">
                        <label for="admin-username" class="form-label">
                            Usuario
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="admin-username"
                            name="username"
                            required
                            autocomplete="username"
                            value="<?= $usernameValue ?>">
                    </div>

                    <div class="mb-3">
                        <label for="admin-password" class="form-label">
                            Contraseña
                        </label>
                        <input
                            type="password"
                            class="form-control"
                            id="admin-password"
                            name="password"
                            required
                            autocomplete="current-password">
                    </div>

                    <p id="admin-login-help" class="form-text small text-muted mb-3">
                        Acceso restringido al personal autorizado. Asegúrate de cerrar sesión al finalizar.
                    </p>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary fw-bold">
                            Ingresar al panel
                        </button>
                    </div>
                </form>
            </div>

            <div class="card-footer text-center small text-muted">
                Villa Quiniela · Módulo administrador
            </div>
        </div>
    </div>
</section>