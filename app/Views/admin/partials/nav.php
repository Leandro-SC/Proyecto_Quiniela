<?php

declare(strict_types=1);

/**
 * Sidebar administrador moderno.
 *
 * Desktop:
 * - Menú lateral compacto.
 * - Se expande con hover.
 *
 * Mobile:
 * - Drawer lateral animado.
 */

$currentUri = $_SERVER['REQUEST_URI'] ?? '/';

$adminIsActive = static function (string $pattern) use ($currentUri): string {
    if ($pattern === '/admin') {
        return ($currentUri === '/admin' || $currentUri === '/admin/dashboard') ? 'active' : '';
    }

    return str_starts_with($currentUri, $pattern) ? 'active' : '';
};

$menuItems = [
    [
        'label' => 'Dashboard',
        'href' => '/admin',
        'icon' => 'bi-speedometer2',
        'active' => $adminIsActive('/admin'),
    ],
    [
        'label' => 'Jornadas',
        'href' => '/admin/rounds',
        'icon' => 'bi-calendar-event',
        'active' => $adminIsActive('/admin/rounds'),
    ],
    [
        'label' => 'Tickets',
        'href' => '/admin/tickets',
        'icon' => 'bi-receipt',
        'active' => $adminIsActive('/admin/tickets'),
    ],
    [
        'label' => 'Ranking',
        'href' => '/admin/ranking',
        'icon' => 'bi-trophy-fill',
        'active' => $adminIsActive('/admin/ranking'),
    ],
    [
        'label' => 'Promociones',
        'href' => '/admin/promotions',
        'icon' => 'bi-megaphone-fill',
        'active' => $adminIsActive('/admin/promotions'),
    ],
    [
        'label' => 'Testimonios',
        'href' => '/admin/testimonials',
        'icon' => 'bi-chat-quote-fill',
        'active' => $adminIsActive('/admin/testimonials'),
    ],
    [
        'label' => 'Ligas',
        'href' => '/admin/leagues',
        'icon' => 'bi-trophy',
        'active' => $adminIsActive('/admin/leagues'),
    ],
    [
        'label' => 'Clubes',
        'href' => '/admin/clubs',
        'icon' => 'bi-shield-shaded',
        'active' => $adminIsActive('/admin/clubs'),
    ],
    [
        'label' => 'Países',
        'href' => '/admin/countries',
        'icon' => 'bi-flag-fill',
        'active' => $adminIsActive('/admin/countries'),
    ],
    [
        'label' => 'Reglamento',
        'href' => '/admin/regulations',
        'icon' => 'bi-file-text',
        'active' => $adminIsActive('/admin/regulations'),
    ],
    [
        'label' => 'Configuración',
        'href' => '/admin/settings',
        'icon' => 'bi-gear-fill',
        'active' => $adminIsActive('/admin/settings'),
    ],
];
?>

<button
    type="button"
    class="qv-admin-mobile-toggle"
    data-admin-sidebar-open
    aria-label="Abrir menú administrador"
>
    <i class="bi bi-list"></i>
</button>

<div class="qv-admin-sidebar-backdrop" data-admin-sidebar-close></div>

<aside class="qv-admin-sidebar" aria-label="Menú administrador">
    <div class="qv-admin-sidebar-header">
        <a href="/admin" class="qv-admin-sidebar-brand">
            <span class="qv-admin-brand-mark">
                <img src="/assets/img/logo_quiniela.png" alt="Mickey Quinielass">
            </span>

            <span class="qv-admin-brand-copy">
                <strong>Mickey Quinielass</strong>
                <small>Panel administrador</small>
            </span>
        </a>

        <button
            type="button"
            class="qv-admin-sidebar-close"
            data-admin-sidebar-close
            aria-label="Cerrar menú"
        >
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <nav class="qv-admin-sidebar-nav">
        <?php foreach ($menuItems as $item): ?>
            <a
                href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
                class="qv-admin-sidebar-link <?= htmlspecialchars($item['active'], ENT_QUOTES, 'UTF-8') ?>"
                data-title="<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>"
            >
                <span class="qv-admin-sidebar-icon">
                    <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                </span>

                <span class="qv-admin-sidebar-text">
                    <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </a>
        <?php endforeach; ?>
    </nav>

<div class="qv-admin-sidebar-footer">
  <button
    type="button"
    class="qv-admin-theme-toggle"
    id="qvAdminThemeToggle"
    data-admin-theme-toggle
    aria-label="Activar modo oscuro"
    aria-pressed="false"
>
    <span class="qv-admin-theme-icon">
        <i class="bi bi-moon-stars-fill" data-admin-theme-icon></i>
    </span>

    <span class="qv-admin-theme-text" data-admin-theme-text>
        Modo oscuro
    </span>

    <span class="qv-admin-theme-switch" aria-hidden="true">
        <span class="qv-admin-theme-switch-dot"></span>
    </span>
</button>

    <div class="qv-admin-profile">
        <span class="qv-admin-profile-avatar">
            <i class="bi bi-person-fill"></i>
        </span>

        <span class="qv-admin-profile-copy">
            <strong>Administrador</strong>
            <small>Sesión activa</small>
        </span>
    </div>

    <a href="/admin/logout" class="qv-admin-logout">
        <i class="bi bi-box-arrow-right"></i>
        <span>Cerrar sesión</span>
    </a>
</div>
</aside>