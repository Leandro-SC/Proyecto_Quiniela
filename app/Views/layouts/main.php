<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($pageTitle ?? 'Villa Quiniela', ENT_QUOTES, 'UTF-8') ?></title>
    
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?? 'Participa en la mejor quiniela de Liga MX y gana premios en efectivo.', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="index,follow">
    <meta name="theme-color" content="#001f3f">

    <link rel="icon" type="image/png" href="/assets/img/logo_quiniela.png?v=2">
    <link rel="shortcut icon" href="/assets/img/logo_quiniela.png?v=2">
    <link rel="apple-touch-icon" href="/assets/img/logo_quiniela.png?v=2">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/app.css') ?>">
    
    <meta property="og:site_name" content="Quinielas Villas">
    <meta property="og:title" content="Quinielas Villas">
    <meta property="og:description" content="Participa en las mejores quinielas deportivas con Liga MX y Champions.">
    <meta property="og:url" content="https://quinielasvillas.com/">
    <meta property="og:type" content="website">
    
    <meta property="og:image" content="https://quinielasvillas.com/assets/img/logo_quiniela.png?v=3">
    <meta property="og:image:secure_url" content="https://quinielasvillas.com/assets/img/logo_quiniela.png?v=3">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="512"> 
    <meta property="og:image:height" content="512">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "Quinielas Villas",
      "url": "https://quinielasvillas.com",
      "logo": "https://quinielasvillas.com/assets/img/logo_quiniela.png"
    }
    </script>

    <style>
        .btn-whatsapp-float {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background-color: #25d366;
            color: #FFF;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            text-align: center;
            font-size: 35px;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.3);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-whatsapp-float:hover {
            background-color: #128c7e;
            color: #FFF;
            transform: scale(1.1);
        }
    </style>
</head>

<body class="bg-dark text-light">

   <nav class="navbar navbar-expand-lg navbar-light-custom navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="/">
                <img src="/assets/img/logo_quiniela.png" alt="Villa Quiniela" 
                     style="height: 55px; width: auto; object-fit: contain;" class="me-2">
                <span class="d-none d-sm-inline" style="color: #001f3f;">Quinielas Villas</span>
            </a>

            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="/">Inicio</a></li>
                    <li class="nav-item">
        <a class="nav-link text-warning fw-bold" href="/ranking">
            <i class="bi bi-trophy-fill me-1"></i> Registro quiniela al momento
        </a>
    </li>
                    <li class="nav-item"><a class="nav-link" href="/quiniela/anterior">Quiniela anterior</a></li>
                    <li class="nav-item"><a class="nav-link" href="/reglamento">Reglamento</a></li>
                    <li class="nav-item">
                        <a class="nav-link text-danger fw-bold" href="/verificador">
                            <i class="bi bi-search"></i> Verificador
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <?php if (!empty($geoCountryName ?? '') && !empty($geoCurrencyCode ?? '')): ?>
        <div class="bg-geo-banner text-center text-dark py-2" role="status" aria-live="polite">
            <div class="container d-flex flex-column flex-md-row justify-content-center align-items-center gap-2">
                <span class="small">
                    Detectamos tu país: <strong><?= htmlspecialchars((string)$geoCountryName, ENT_QUOTES, 'UTF-8') ?></strong>
                    (moneda: <strong><?= htmlspecialchars((string)$geoCurrencyCode, ENT_QUOTES, 'UTF-8') ?></strong>).
                </span>
                <button type="button" class="btn btn-sm btn-outline-dark geo-change-btn" data-geo-action="open-modal">
                    ¿No es correcto? Cámbialo aquí
                </button>
            </div>
        </div>
    <?php endif; ?>

    <main class="py-4">
        <div class="container">
            <?php require $viewFile; ?>
        </div>
    </main>

    <footer class="bg-black text-center text-secondary py-3 mt-4">
        <small>© <?= date('Y') ?> Desarrollado por Digimarketing. Todos los derechos reservados.</small>
    </footer>

    <a href="https://wa.me/<?= ltrim($_ENV['WHATSAPP_NUMBER'] ?? '51904472452', '+') ?>" 
       class="btn-whatsapp-float" 
       target="_blank" 
       rel="noopener"
       aria-label="Chatea con nosotros">
        <i class="bi bi-whatsapp"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="/assets/js/app.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/app.js') ?>"></script>
    <link rel="stylesheet" href="/assets/css/coach.css?v=<?= time() ?>">
    <script src="/assets/js/coach.js?v=<?= time() ?>"></script>
</body>
</html>