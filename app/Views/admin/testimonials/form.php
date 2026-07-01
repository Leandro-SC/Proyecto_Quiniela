<?php

declare(strict_types=1);

use App\Core\Security;

/**
 * @var array<string,mixed>|null $testimonial
 * @var string|null $error
 */

require __DIR__ . '/../partials/nav.php';

$isEdit = is_array($testimonial) && !empty($testimonial['id']);
$action = $isEdit ? '/admin/testimonials/update' : '/admin/testimonials/store';

$id = (int)($testimonial['id'] ?? 0);
$name = (string)($testimonial['name'] ?? '');
$country = (string)($testimonial['country'] ?? '');
$comment = (string)($testimonial['comment'] ?? '');
$rating = max(1, min(5, (int)($testimonial['rating'] ?? 5)));
$photoPath = (string)($testimonial['photo_path'] ?? '');
$displayOrder = (int)($testimonial['display_order'] ?? 0);
$statusValue = strtoupper((string)($testimonial['status'] ?? 'ACTIVE'));
$status = $statusValue !== 'INACTIVE';
?>

<section class="admin-mobile-page qv-admin-testimonial-form-page">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Prueba social</span>
            <h1><?= $isEdit ? 'Editar testimonio' : 'Nuevo testimonio' ?></h1>
            <p>
                Agrega experiencias de usuarios para reforzar la confianza en la vista pública.
            </p>
        </div>

        <a href="/admin/testimonials" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Volver
        </a>
    </header>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= Security::e($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= Security::e($action) ?>" enctype="multipart/form-data" class="qv-admin-edit-shell">
        <?= Security::csrfInput() ?>

        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= $id ?>">
        <?php endif; ?>

        <input type="hidden" name="photo_path" value="<?= Security::e($photoPath) ?>">

        <section class="qv-admin-edit-main">
            <article class="qv-admin-form-card">
                <div class="qv-admin-form-card-head">
                    <i class="bi bi-chat-quote-fill"></i>
                    <div>
                        <strong>Información del testimonio</strong>
                        <span>Nombre, país, comentario y valoración.</span>
                    </div>
                </div>

                <div class="qv-admin-form-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="name" class="form-label">Nombre</label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control"
                                value="<?= Security::e($name) ?>"
                                required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="country" class="form-label">País</label>
                            <input
                                type="text"
                                name="country"
                                id="country"
                                class="form-control"
                                value="<?= Security::e($country) ?>">
                        </div>

                        <div class="col-12">
                            <label for="comment" class="form-label">Comentario</label>
                            <textarea
                                name="comment"
                                id="comment"
                                class="form-control"
                                rows="5"
                                required><?= Security::e($comment) ?></textarea>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="rating" class="form-label">Rating</label>
                            <select name="rating" id="rating" class="form-select">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <option value="<?= $i ?>" <?= $rating === $i ? 'selected' : '' ?>>
                                        <?= $i ?> estrella<?= $i === 1 ? '' : 's' ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="display_order" class="form-label">Orden</label>
                            <input
                                type="number"
                                name="display_order"
                                id="display_order"
                                class="form-control"
                                value="<?= $displayOrder ?>"
                                min="0">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch p-3 rounded border bg-light">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="status"
                                    id="status"
                                    value="1"
                                    <?= $status ? 'checked' : '' ?>>

                                <label class="form-check-label fw-bold" for="status">
                                    Testimonio activo
                                </label>

                                <div class="small text-muted">
                                    Los testimonios activos pueden mostrarse en la vista pública.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <aside class="qv-admin-edit-side">
            <article class="qv-admin-form-card">
                <div class="qv-admin-form-card-head">
                    <i class="bi bi-person-bounding-box"></i>
                    <div>
                        <strong>Fotografía</strong>
                        <span>Imagen de la persona o referencia visual.</span>
                    </div>
                </div>

                <div class="qv-admin-form-card-body">
                    <div class="qv-admin-testimonial-photo-preview" id="testimonialPhotoPreview">
                        <?php if ($photoPath !== ''): ?>
                            <img src="<?= Security::e($photoPath) ?>" alt="Foto actual">
                        <?php else: ?>
                            <span>
                                <i class="bi bi-person-fill"></i>
                                Sin foto
                            </span>
                        <?php endif; ?>
                    </div>

                    <label for="photo_file" class="form-label mt-3">Subir foto</label>
                    <input
                        type="file"
                        name="photo_file"
                        id="photo_file"
                        class="form-control"
                        accept="image/png,image/jpeg,image/jpg,image/webp,image/avif">

                    <div class="form-text">
                        Recomendado: imagen cuadrada, fondo limpio.
                    </div>
                </div>
            </article>

            <div class="qv-admin-sticky-actions qv-admin-edit-actions">
                <a href="/admin/testimonials" class="btn btn-outline-secondary">
                    Cancelar
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Guardar testimonio
                </button>
            </div>
        </aside>
    </form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('photo_file');
        var preview = document.getElementById('testimonialPhotoPreview');

        if (!input || !preview) {
            return;
        }

        input.addEventListener('change', function () {
            var file = input.files && input.files[0] ? input.files[0] : null;

            if (!file) {
                return;
            }

            if (!file.type || !file.type.match(/^image\//)) {
                alert('Selecciona una imagen válida.');
                input.value = '';
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                alert('La imagen no debe superar los 5 MB.');
                input.value = '';
                return;
            }

            var reader = new FileReader();

            reader.onload = function (event) {
                preview.innerHTML = '';

                var image = document.createElement('img');
                image.src = String(event.target.result || '');
                image.alt = 'Vista previa del testimonio';

                preview.appendChild(image);
            };

            reader.readAsDataURL(file);
        });
    });
</script>

</section>