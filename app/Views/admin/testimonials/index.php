<?php

declare(strict_types=1);

use App\Core\Security;

/**
 * @var array<int,array<string,mixed>> $testimonials
 */

require __DIR__ . '/../partials/nav.php';

$testimonials = $testimonials ?? [];

$totalTestimonials = count($testimonials);
$activeTestimonials = 0;
$inactiveTestimonials = 0;

foreach ($testimonials as $testimonial) {
    $statusValue = strtoupper((string)($testimonial['status'] ?? 'ACTIVE'));

    if ($statusValue === 'ACTIVE') {
        $activeTestimonials++;
    } else {
        $inactiveTestimonials++;
    }
}
?>

<section class="admin-mobile-page qv-admin-testimonials-page">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Prueba social</span>
            <h1>Testimonios</h1>
            <p>
                Administra comentarios, valoraciones, fotos y orden de visualización en la vista pública.
            </p>
        </div>

        <a href="/admin/testimonials/create" class="btn btn-primary qv-admin-primary-action">
            <i class="bi bi-plus-circle-fill me-1"></i>
            Nuevo testimonio
        </a>
    </header>

    <section class="qv-admin-kpi-grid qv-admin-testimonial-kpis" aria-label="Resumen de testimonios">
        <article class="qv-admin-kpi-card">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-chat-quote-fill"></i>
            </div>

            <div>
                <span>Total</span>
                <strong><?= number_format($totalTestimonials) ?></strong>
                <small>Testimonios registrados</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-success">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-eye-fill"></i>
            </div>

            <div>
                <span>Activos</span>
                <strong><?= number_format($activeTestimonials) ?></strong>
                <small>Visibles públicamente</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-warning">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-eye-slash-fill"></i>
            </div>

            <div>
                <span>Inactivos</span>
                <strong><?= number_format($inactiveTestimonials) ?></strong>
                <small>No visibles</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-money">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-star-fill"></i>
            </div>

            <div>
                <span>Rating</span>
                <strong>5★</strong>
                <small>Opiniones destacadas</small>
            </div>
        </article>
    </section>

    <?php if ($testimonials === []): ?>
        <section class="qv-admin-empty-state">
            <i class="bi bi-chat-square-quote"></i>
            <strong>No hay testimonios registrados.</strong>
            <span>Crea testimonios para reforzar la confianza en la vista pública.</span>

            <a href="/admin/testimonials/create" class="btn btn-primary mt-3">
                Crear primer testimonio
            </a>
        </section>
    <?php else: ?>
        <section class="qv-admin-testimonial-grid">
            <?php foreach ($testimonials as $testimonial): ?>
                <?php
                $id = (int)($testimonial['id'] ?? 0);
                $name = (string)($testimonial['name'] ?? '');
                $country = (string)($testimonial['country'] ?? '');
                $comment = (string)($testimonial['comment'] ?? '');
                $rating = max(1, min(5, (int)($testimonial['rating'] ?? 5)));
                $photoPath = (string)($testimonial['photo_path'] ?? '');
                $displayOrder = (int)($testimonial['display_order'] ?? 0);
                $statusValue = strtoupper((string)($testimonial['status'] ?? 'ACTIVE'));
                $status = $statusValue === 'ACTIVE';
                ?>

                <article class="qv-admin-testimonial-card">
                    <div class="qv-admin-testimonial-top">
                        <div class="qv-admin-testimonial-avatar">
                            <?php if ($photoPath !== ''): ?>
                                <img src="<?= Security::e($photoPath) ?>" alt="<?= Security::e($name) ?>">
                            <?php else: ?>
                                <i class="bi bi-person-fill"></i>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h2><?= Security::e($name) ?></h2>
                            <span><?= $country !== '' ? Security::e($country) : 'Sin país' ?></span>
                        </div>

                        <span class="qv-admin-status <?= $status ? 'qv-status-paid' : 'qv-status-muted' ?>">
                            <?= $status ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </div>

                    <div class="qv-admin-testimonial-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi <?= $i <= $rating ? 'bi-star-fill' : 'bi-star' ?>"></i>
                        <?php endfor; ?>
                    </div>

                    <p>
                        <?= Security::e(mb_substr($comment, 0, 190)) ?><?= mb_strlen($comment) > 190 ? '...' : '' ?>
                    </p>

                    <div class="qv-admin-testimonial-meta">
                        <span>Orden</span>
                        <strong><?= $displayOrder ?></strong>
                    </div>

                    <div class="qv-admin-testimonial-actions">
                        <a href="/admin/testimonials/edit?id=<?= $id ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil-fill"></i>
                            Editar
                        </a>

                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteTestimonial(<?= $id ?>)">
                            <i class="bi bi-trash-fill"></i>
                            Eliminar
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</section>

<script>
    function confirmDeleteTestimonial(id) {
        window.qvConfirmDelete(
            '¿Eliminar testimonio?',
            'Este testimonio dejará de mostrarse en la vista pública.',
            function() {
                window.enviarFormularioAdmin('/admin/testimonials/delete', {
                    id: id
                });
            }
        );
    }
</script>