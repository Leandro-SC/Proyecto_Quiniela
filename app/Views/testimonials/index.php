<?php

declare(strict_types=1);

use App\Core\Security;

/**
 * @var array<int,array<string,mixed>> $testimonials
 */

$testimonials = $testimonials ?? [];

if (!function_exists('qvTestimonialPhoto')) {
    /**
     * Devuelve una imagen segura para el testimonio.
     *
     * @param mixed $photoPath Ruta guardada en BD.
     * @return string
     */
    function qvTestimonialPhoto(mixed $photoPath): string
    {
        $photoPath = trim((string)$photoPath);

        if ($photoPath === '') {
            return '/assets/img/logo_quiniela.png';
        }

        if (!str_starts_with($photoPath, '/assets/')) {
            return '/assets/img/logo_quiniela.png';
        }

        if (str_contains($photoPath, '..')) {
            return '/assets/img/logo_quiniela.png';
        }

        return $photoPath;
    }
}

if (!function_exists('qvTestimonialStars')) {
    /**
     * Renderiza estrellas según calificación.
     *
     * @param mixed $rating Calificación.
     * @return string
     */
    function qvTestimonialStars(mixed $rating): string
    {
        $rating = max(1, min(5, (int)$rating));
        $html = '';

        for ($i = 1; $i <= 5; $i++) {
            $html .= $i <= $rating
                ? '<i class="bi bi-star-fill"></i>'
                : '<i class="bi bi-star"></i>';
        }

        return $html;
    }
}

if (!function_exists('qvTestimonialDate')) {
    /**
     * Formatea fecha para mostrarla en español simple.
     *
     * @param mixed $date Fecha original.
     * @return string
     */
    function qvTestimonialDate(mixed $date): string
    {
        $timestamp = strtotime((string)$date);

        if (!$timestamp) {
            return '';
        }

        $months = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        $day = date('j', $timestamp);
        $month = $months[(int)date('n', $timestamp)] ?? '';
        $year = date('Y', $timestamp);

        return $day . ' de ' . $month . ' de ' . $year;
    }
}

$totalTestimonials = count($testimonials);
?>

<section class="qv-testimonials-dark-hero">
    <div class="container">
        <div class="qv-testimonials-dark-hero__content">
            <span class="qv-public-eyebrow">
                Comunidad de ganadores
            </span>

            <h1>
                Testimonios reales de jugadores
            </h1>

            <p>
                Historias de participantes que viven la emoción de cada fecha,
                siguen sus resultados y comparten la pasión del fútbol latino.
            </p>

            <div class="qv-testimonials-dark-hero__actions">
                <a href="/" class="btn btn-primary btn-lg">
                    <i class="bi bi-trophy-fill me-1"></i>
                    Participar ahora
                </a>

                <a href="/ranking" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-bar-chart-fill me-1"></i>
                    Ver ranking
                </a>
            </div>
        </div>
    </div>
</section>

<section class="qv-testimonials-dark-page">
    <div class="container">
        <div class="qv-testimonials-dark-heading">
            <div>
                <span class="qv-public-eyebrow">
                    Prueba social
                </span>

                <h2>
                    Ganadores y jugadores felices
                </h2>
            </div>

            <p>
                Cards modernas con fotos, comentarios y experiencia visual tipo plataforma deportiva premium.
            </p>
        </div>

        <?php if ($testimonials === []): ?>
            <article class="qv-empty-premium qv-empty-premium--dark">
                <div class="qv-empty-premium-icon">
                    <i class="bi bi-chat-heart-fill"></i>
                </div>

                <h2>
                    Aún no hay testimonios activos
                </h2>

                <p>
                    Cuando actives testimonios desde el panel administrativo,
                    aparecerán automáticamente en esta página.
                </p>

                <a href="/" class="btn btn-primary">
                    Volver a la quiniela
                </a>
            </article>
        <?php else: ?>
            <div class="qv-testimonials-dark-summary">
                <div>
                    <strong><?= number_format($totalTestimonials) ?></strong>
                    <span>testimonios activos</span>
                </div>

                <div>
                    <strong>5★</strong>
                    <span>experiencia destacada</span>
                </div>

                <div>
                    <strong>USA</strong>
                    <span>comunidad latina</span>
                </div>
            </div>

            <div class="qv-winner-testimonials-grid">
                <?php foreach ($testimonials as $testimonial): ?>
                    <?php
                    $name = (string)($testimonial['name'] ?? '');
                    $country = (string)($testimonial['country'] ?? '');
                    $comment = (string)($testimonial['comment'] ?? '');
                    $rating = (int)($testimonial['rating'] ?? 5);
                    $photo = qvTestimonialPhoto($testimonial['photo_path'] ?? '');
                    $dateLabel = qvTestimonialDate($testimonial['created_at'] ?? '');
                    ?>

                    <article class="qv-winner-testimonial-card">
                        <div class="qv-winner-testimonial-card__image">
                            <img
                                src="<?= Security::e($photo) ?>"
                                alt="Foto de <?= Security::e($name) ?>"
                                loading="lazy"
                                decoding="async"
                            >

                            <div class="qv-winner-testimonial-card__imageOverlay"></div>

                            <div class="qv-winner-testimonial-card__badge">
                                <i class="bi bi-award"></i>
                                <?= qvTestimonialStars($rating) ?>
                            </div>
                        </div>

                        <div class="qv-winner-testimonial-card__body">
                            <h3>
                                <?= Security::e($name) ?>
                            </h3>

                            <?php if ($country !== ''): ?>
                                <div class="qv-winner-testimonial-card__subtitle">
                                    <?= Security::e(strtoupper($country)) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($dateLabel !== ''): ?>
                                <div class="qv-winner-testimonial-card__date">
                                    <i class="bi bi-calendar3"></i>
                                    <?= Security::e($dateLabel) ?>
                                </div>
                            <?php endif; ?>

                            <div class="qv-winner-testimonial-card__quote">
                                <div class="qv-winner-testimonial-card__quoteIcon">
                                    <i class="bi bi-quote"></i>
                                </div>

                                <p>
                                    <?= Security::e($comment) ?>
                                </p>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="qv-testimonials-final-cta qv-testimonials-final-cta--dark">
    <div class="container">
        <article>
            <span class="qv-public-eyebrow">
                Próxima jornada
            </span>

            <h2>
                Demuestra tu conocimiento futbolero
            </h2>

            <p>
                Elige tus pronósticos, guarda tu ticket y revisa tu posición
                en el ranking cuando se actualicen los resultados.
            </p>

            <div class="qv-testimonials-final-actions">
                <a href="/" class="btn btn-primary btn-lg">
                    Jugar quiniela
                </a>

                <a href="/verificador" class="btn btn-outline-light btn-lg">
                    Verificar ticket
                </a>
            </div>
        </article>
    </div>
</section>