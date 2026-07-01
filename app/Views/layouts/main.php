<?php

declare(strict_types=1);

/**
 * Layout principal público/admin.
 *
 * Este archivo separa correctamente:
 * - Vista pública.
 * - Vista administrativa.
 * - Assets públicos.
 * - Assets exclusivos del admin.
 */

$pageTitle = $pageTitle ?? 'Villa Quiniela';
$metaDescription = $metaDescription ?? 'Participa en la mejor quiniela de Liga MX y gana premios en efectivo.';

$siteName = 'Mickey Quinielas';
$siteUrl = 'https://mickeyquinielass.com';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$currentPath = strtok($requestUri, '?') ?: '/';
$currentHost = $_SERVER['HTTP_HOST'] ?? 'mickeyquinielass.com';
$currentUrl = 'https://' . $currentHost . $currentPath;

$isAdminArea = str_starts_with($currentPath, '/admin');

$canonicalUrl = $canonicalUrl ?? $currentUrl;

$ogTitle = $ogTitle ?? $pageTitle;
$ogDescription = $ogDescription ?? $metaDescription;
$ogImage = $ogImage ?? $siteUrl . '/assets/img/logo_quiniela.png?v=3';

$appCssPath = dirname(__DIR__, 3) . '/assets/css/app.css';
$appCssVersion = is_file($appCssPath) ? filemtime($appCssPath) : time();

$coachCssPath = dirname(__DIR__, 3) . '/assets/css/coach.css';
$coachCssVersion = is_file($coachCssPath) ? filemtime($coachCssPath) : time();

$adminModernCssPath = dirname(__DIR__, 3) . '/assets/css/admin-modern.css';
$adminModernCssVersion = is_file($adminModernCssPath) ? filemtime($adminModernCssPath) : time();

$publicModernCssPath = dirname(__DIR__, 3) . '/assets/css/public-modern.css';
$publicModernCssVersion = is_file($publicModernCssPath) ? filemtime($publicModernCssPath) : time();

$appJsPath = dirname(__DIR__, 3) . '/assets/js/app.js';
$appJsVersion = is_file($appJsPath) ? filemtime($appJsPath) : time();

$publicModernJsPath = dirname(__DIR__, 3) . '/assets/js/public-modern.js';
$publicModernJsVersion = is_file($publicModernJsPath) ? filemtime($publicModernJsPath) : time();

$coachJsPath = dirname(__DIR__, 3) . '/assets/js/coach.js';
$coachJsVersion = is_file($coachJsPath) ? filemtime($coachJsPath) : time();

$adminModernJsPath = dirname(__DIR__, 3) . '/assets/js/admin-modern.js';
$adminModernJsVersion = is_file($adminModernJsPath) ? filemtime($adminModernJsPath) : time();

$whatsappNumber = ltrim((string)($_ENV['WHATSAPP_NUMBER'] ?? '51904472452'), '+');

$bodyClass = $isAdminArea ? 'qv-admin-body' : 'qv-public-body bg-dark text-light';
$mainClass = $isAdminArea ? 'qv-admin-main' : 'qv-public-main';
$mainContainerClass = $isAdminArea ? 'container-fluid qv-admin-content-wrap' : 'container-fluid px-0 qv-public-content-wrap';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="index,follow">
    <meta name="theme-color" content="#06142f">

    <?php if ($isAdminArea): ?>
        <meta name="csrf-token" content="<?= \App\Core\Security::e(\App\Core\Security::csrfToken()) ?>">
    <?php endif; ?>

    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">

    <link rel="icon" type="image/png" href="/assets/img/logo_quiniela.png?v=3">
    <link rel="shortcut icon" href="/assets/img/logo_quiniela.png?v=3">
    <link rel="apple-touch-icon" href="/assets/img/logo_quiniela.png?v=3">

    <meta property="og:site_name" content="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <?php if ($isAdminArea): ?>
        <link rel="stylesheet" href="/assets/css/app.css?v=<?= $appCssVersion ?>">
    <?php else: ?>
        <link rel="stylesheet" href="/assets/css/public-modern.css?v=<?= $publicModernCssVersion ?>">
    <?php endif; ?>

    <?php if ($isAdminArea): ?>
        <script>
            (function() {
                try {
                    var savedTheme = localStorage.getItem('qv_admin_theme');

                    if (savedTheme === 'dark') {
                        document.documentElement.classList.add('qv-admin-theme-dark');
                    }
                } catch (error) {
                    // Si localStorage no está disponible, el admin carga en modo claro.
                }
            })();
        </script>

        <link rel="stylesheet" href="/assets/css/admin-modern.css?v=<?= $adminModernCssVersion ?>">
    <?php endif; ?>

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>",
            "url": "<?= htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') ?>",
            "logo": "<?= htmlspecialchars($siteUrl . '/assets/img/logo_quiniela.png', ENT_QUOTES, 'UTF-8') ?>"
        }
    </script>
</head>

<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') ?>">

    <?php if (!$isAdminArea): ?>
        <nav class="navbar navbar-expand-lg navbar-light-custom navbar-light sticky-top">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center fw-bold" href="/" aria-label="Ir al inicio de Mickey Quinielas">
                    <img
                        src="/assets/img/logo_quiniela.png"
                        alt="Villa Quiniela"
                        class="qv-public-logo me-2">
                    <span class="d-none d-sm-inline qv-public-brand-name">
                        Mickey Quinielas
                    </span>
                </a>

                <button
                    class="navbar-toggler ms-auto"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mainNavbar"
                    aria-controls="mainNavbar"
                    aria-expanded="false"
                    aria-label="Abrir menú de navegación">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
                    <ul class="navbar-nav mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPath === '/' ? 'active' : '' ?>" href="/">
                                Inicio
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link text-warning fw-bold <?= $currentPath === '/ranking' ? 'active' : '' ?>" href="/ranking">
                                <i class="bi bi-trophy-fill me-1"></i>
                                Registro quiniela al momento
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?= $currentPath === '/quiniela/anterior' ? 'active' : '' ?>" href="/quiniela/anterior">
                                Quiniela anterior
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="/testimonios" class="nav-link">
                                Testimonios
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?= $currentPath === '/reglamento' ? 'active' : '' ?>" href="/reglamento">
                                Reglamento
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link text-danger fw-bold <?= $currentPath === '/verificador' ? 'active' : '' ?>" href="/verificador">
                                <i class="bi bi-search"></i>
                                Verificador
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <?php if (!$isAdminArea && !empty($geoCountryName ?? '') && !empty($geoCurrencyCode ?? '')): ?>
        <div class="bg-geo-banner qv-geo-banner text-center py-2" role="status" aria-live="polite">
            <div class="container d-flex flex-column flex-md-row justify-content-center align-items-center gap-2">
                <span class="small">
                    Detectamos tu país:
                    <strong><?= htmlspecialchars((string)$geoCountryName, ENT_QUOTES, 'UTF-8') ?></strong>
                    · Moneda:
                    <strong><?= htmlspecialchars((string)$geoCurrencyCode, ENT_QUOTES, 'UTF-8') ?></strong>
                </span>

                <button
                    type="button"
                    class="btn btn-sm qv-geo-change-btn"
                    data-geo-action="open-modal">
                    Cambiar ubicación
                </button>
            </div>
        </div>
    <?php endif; ?>

    <main class="<?= htmlspecialchars($mainClass, ENT_QUOTES, 'UTF-8') ?>">
        <div class="<?= htmlspecialchars($mainContainerClass, ENT_QUOTES, 'UTF-8') ?>">
            <?php require $viewFile; ?>
        </div>
    </main>

    <?php if (!$isAdminArea): ?>
        <footer class="qv-public-footer">
            <div class="container">
                <div class="qv-footer-card">
                    <div>
                        <strong>Mickey Quinielas</strong>
                        <span>Pronósticos deportivos para la comunidad latina.</span>
                    </div>

                    <small>
                        © <?= date('Y') ?> Desarrollado por Digimarketing. Todos los derechos reservados.
                    </small>
                </div>
            </div>
        </footer>

        <a
            href="https://wa.me/<?= htmlspecialchars($whatsappNumber, ENT_QUOTES, 'UTF-8') ?>"
            class="btn-whatsapp-float"
            target="_blank"
            rel="noopener"
            aria-label="Chatea con nosotros por WhatsApp">
            <i class="bi bi-whatsapp"></i>
        </a>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="/assets/js/app.js?v=<?= $appJsVersion ?>"></script>

    <?php if (!$isAdminArea && is_file($publicModernJsPath)): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
        <script src="/assets/js/public-modern.js?v=<?= $publicModernJsVersion ?>"></script>
    <?php endif; ?>

    <?php if ($isAdminArea && is_file($coachJsPath)): ?>
        <script src="/assets/js/coach.js?v=<?= $coachJsVersion ?>"></script>
    <?php endif; ?>

    <?php if ($isAdminArea): ?>
        <script src="/assets/js/admin-modern.js?v=<?= $adminModernJsVersion ?>"></script>
    <?php endif; ?>

</body>

</html>