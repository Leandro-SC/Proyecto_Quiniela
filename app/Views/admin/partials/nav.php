<?php
declare(strict_types=1);

/*
 * Menú de navegación para el módulo Admin.
 * Bootstrap 5.3, responsivo y compacto.
 */

/**
 * Determina si la ruta actual coincide con el patrón dado,
 * para aplicar la clase "active".
 */
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';

/**
 * Función simple para marcar activo.
 */
$adminIsActive = static function (string $pattern) use ($currentUri): string {
    if ($pattern === '/admin') {
        return ($currentUri === '/admin' || strpos($currentUri, '/admin?') === 0) ? 'active' : '';
    }

    return str_starts_with($currentUri, $pattern) ? 'active' : '';
};
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3" aria-label="Navegación principal del administrador">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="/admin">
            Panel Admin
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#adminNavbar" aria-controls="adminNavbar"
                aria-expanded="false" aria-label="Mostrar/ocultar menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link <?= $adminIsActive('/admin') ?>"
                       href="/admin">
                        Dashboard
                    </a>
                </li>
                
                <li class="nav-item">
    <a class="nav-link" href="/admin/leagues">
        <i class="bi bi-trophy-fill me-2"></i> Ligas
    </a>
</li>

                <li class="nav-item">
                    <a class="nav-link <?= $adminIsActive('/admin/rounds') ?>"
                       href="/admin/rounds">
                        Jornadas
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $adminIsActive('/admin/tickets') ?>"
                       href="/admin/tickets">
                        Tickets
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $adminIsActive('/admin/ranking') ?>"
                       href="/admin/ranking">
                        Ranking
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $adminIsActive('/admin/promotions') ?>"
                       href="/admin/promotions">
                        Promociones
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= $adminIsActive('/admin/countries') ?>"
                       href="/admin/countries">
                        <i class="bi bi-flag-fill me-2"></i> Países
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $adminIsActive('/admin/clubs') ?>"
                       href="/admin/clubs">
                        <i class="bi bi-shield-shaded me-2"></i> Clubes
                    </a>
                </li>
                
                
                <li class="nav-item">
    <a class="nav-link" href="/admin/regulations">
        <i class="bi bi-file-text me-2"></i> Reglamento
    </a>
</li>

            </ul>

            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item">
                    <span class="navbar-text small text-muted me-3">
                        Módulo administrador
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/logout">
                        Cerrar sesión
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
