<?php

declare(strict_types=1);

use App\Core\Security;

/** @var array<int,array<string,mixed>> $promotions */

require __DIR__ . '/../partials/nav.php';

/**
 * Normaliza ruta de imagen sin romper imágenes antiguas.
 *
 * @param string $image
 * @return string
 */
$promotionImageUrl = static function (string $image): string {
    $image = trim($image);

    if ($image === '') {
        return '';
    }

    if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, '/')) {
        return $image;
    }

    return '/assets/img/' . ltrim($image, '/');
};

$totalPromotions = count($promotions ?? []);
$activePromotions = 0;

foreach (($promotions ?? []) as $promotion) {
    if ((int)($promotion['is_active'] ?? 0) === 1) {
        $activePromotions++;
    }
}

$inactivePromotions = max(0, $totalPromotions - $activePromotions);
?>

<section class="admin-mobile-page qv-admin-promotions-page">
    <header class="qv-admin-page-head">
        <div>
            <span class="qv-admin-eyebrow">Marketing</span>
            <h1>Promociones</h1>
            <p>
                Administra campañas visibles, llamadas a la acción, imágenes promocionales y vigencia comercial.
            </p>
        </div>

        <button class="btn btn-primary qv-admin-primary-action" type="button" onclick="openCreateModal()">
            <i class="bi bi-stars me-1"></i>
            Nueva promoción
        </button>
    </header>

    <section class="qv-admin-kpi-grid qv-admin-promo-kpis" aria-label="Resumen de promociones">
        <article class="qv-admin-kpi-card">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-megaphone-fill"></i>
            </div>

            <div>
                <span>Total</span>
                <strong><?= number_format($totalPromotions) ?></strong>
                <small>Promociones creadas</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-success">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-broadcast-pin"></i>
            </div>

            <div>
                <span>Activas</span>
                <strong><?= number_format($activePromotions) ?></strong>
                <small>Visibles actualmente</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-warning">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-pause-circle-fill"></i>
            </div>

            <div>
                <span>Inactivas</span>
                <strong><?= number_format($inactivePromotions) ?></strong>
                <small>No visibles</small>
            </div>
        </article>

        <article class="qv-admin-kpi-card qv-admin-kpi-money">
            <div class="qv-admin-kpi-icon">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>

            <div>
                <span>Acción rápida</span>
                <strong>CTA</strong>
                <small>Gestiona enlaces y botones</small>
            </div>
        </article>
    </section>

    <?php if (empty($promotions)): ?>
        <section class="qv-admin-empty-state">
            <i class="bi bi-inbox"></i>
            <strong>No hay promociones registradas.</strong>
            <span>Crea una promoción para mostrarla en la vista pública.</span>

            <button class="btn btn-primary mt-3" type="button" onclick="openCreateModal()">
                Crear primera promoción
            </button>
        </section>
    <?php else: ?>
        <section class="qv-admin-promo-grid">
            <?php foreach ($promotions as $promotion): ?>
                <?php
                $id = (int)($promotion['id'] ?? 0);
                $title = (string)($promotion['title'] ?? '');
                $description = (string)($promotion['description'] ?? '');
                $image = (string)($promotion['image_path'] ?? $promotion['image'] ?? '');
                $imageUrl = $promotionImageUrl($image);
                $ctaText = (string)($promotion['cta_text'] ?? '');
                $ctaLink = (string)($promotion['cta_url'] ?? $promotion['cta_link'] ?? '');
                $startsAt = (string)($promotion['starts_at'] ?? $promotion['start_date'] ?? '');
                $endsAt = (string)($promotion['ends_at'] ?? $promotion['end_date'] ?? '');
                $discountType = (string)($promotion['discount_type'] ?? 'NONE');
                $discountValue = (float)($promotion['discount_value'] ?? 0);
                $displayOrder = (int)($promotion['display_order'] ?? 0);
                $isActive = (int)($promotion['is_active'] ?? 0) === 1;
                ?>

                <article class="qv-admin-promo-card">
                    <div class="qv-admin-promo-media">
                        <?php if ($imageUrl !== ''): ?>
                            <img src="<?= Security::e($imageUrl) ?>" alt="<?= Security::e($title) ?>">
                        <?php else: ?>
                            <div class="qv-admin-promo-placeholder">
                                <i class="bi bi-image"></i>
                            </div>
                        <?php endif; ?>

                        <span class="qv-admin-status <?= $isActive ? 'qv-status-paid' : 'qv-status-muted' ?>">
                            <?= $isActive ? 'Activa' : 'Inactiva' ?>
                        </span>
                    </div>

                    <div class="qv-admin-promo-body">
                        <div class="qv-admin-promo-title-row">
                            <h2><?= Security::e($title) ?></h2>
                            <span>Orden <?= $displayOrder ?></span>
                        </div>

                        <p>
                            <?= Security::e(mb_substr($description, 0, 160)) ?><?= mb_strlen($description) > 160 ? '...' : '' ?>
                        </p>

                        <div class="qv-admin-promo-meta">
                            <div>
                                <span>Descuento</span>
                                <strong>
                                    <?= Security::e($discountType) ?>
                                    <?php if ($discountValue > 0): ?>
                                        · <?= number_format($discountValue, 2) ?>
                                    <?php endif; ?>
                                </strong>
                            </div>

                            <div>
                                <span>Vigencia</span>
                                <strong>
                                    <?= $startsAt !== '' ? date('d/m/y', strtotime($startsAt)) : 'Sin inicio' ?>
                                    —
                                    <?= $endsAt !== '' ? date('d/m/y', strtotime($endsAt)) : 'Sin fin' ?>
                                </strong>
                            </div>
                        </div>

                        <?php if ($ctaText !== '' || $ctaLink !== ''): ?>
                            <div class="qv-admin-promo-cta">
                                <i class="bi bi-link-45deg"></i>
                                <span>
                                    <?= Security::e($ctaText !== '' ? $ctaText : 'CTA') ?>
                                    <?= $ctaLink !== '' ? '· ' . Security::e($ctaLink) : '' ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="qv-admin-promo-actions">
                        <button
                            class="btn btn-sm btn-outline-primary"
                            type="button"
                            onclick='openEditModal(<?= json_encode($promotion, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'
                        >
                            <i class="bi bi-pencil-fill"></i>
                            Editar
                        </button>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            onclick="confirmDeletePromo(<?= $id ?>)"
                        >
                            <i class="bi bi-trash-fill"></i>
                            Eliminar
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</section>

<div class="modal fade qv-admin-modal" id="promoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" id="promoForm" method="POST" enctype="multipart/form-data">
            <?= Security::csrfInput() ?>

            <input type="hidden" name="id" id="promoId">
            <input type="hidden" name="image_path" id="promoImagePath">

            <div class="modal-header">
                <div>
                    <span class="qv-admin-eyebrow">Campaña</span>
                    <h2 class="modal-title h5" id="modalTitle">
                        Crear promoción
                    </h2>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="promoTitle" class="form-label">Título</label>
                        <input type="text" name="title" id="promoTitle" class="form-control" required>
                    </div>

                    <div class="col-12">
                        <label for="promoDesc" class="form-label">Descripción</label>
                        <textarea name="description" id="promoDesc" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="promoDiscountType" class="form-label">Tipo descuento</label>
                        <select name="discount_type" id="promoDiscountType" class="form-select">
                            <option value="NONE">Sin descuento</option>
                            <option value="PERCENT">Porcentaje</option>
                            <option value="FIXED">Monto fijo</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="promoDiscountValue" class="form-label">Valor</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="discount_value"
                            id="promoDiscountValue"
                            class="form-control"
                            value="0"
                        >
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="promoDisplayOrder" class="form-label">Orden</label>
                        <input
                            type="number"
                            min="0"
                            name="display_order"
                            id="promoDisplayOrder"
                            class="form-control"
                            value="0"
                        >
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="promoStart" class="form-label">Inicio</label>
                        <input type="datetime-local" name="starts_at" id="promoStart" class="form-control">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="promoEnd" class="form-label">Fin</label>
                        <input type="datetime-local" name="ends_at" id="promoEnd" class="form-control">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="promoCTA" class="form-label">Texto del botón</label>
                        <input type="text" name="cta_text" id="promoCTA" class="form-control">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="promoLink" class="form-label">Enlace CTA</label>
                        <input type="text" name="cta_url" id="promoLink" class="form-control">
                    </div>

                    <div class="col-12">
                        <label for="promoImage" class="form-label">Imagen</label>
                        <input type="file" name="image_file" id="promoImage" class="form-control" accept="image/*">
                        <div class="form-text">
                            Si no subes una nueva imagen al editar, se conservará la actual.
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch p-3 rounded border bg-light">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="is_active"
                                value="1"
                                id="promoActive"
                                checked
                            >
                            <label class="form-check-label fw-bold" for="promoActive">
                                Promoción activa
                            </label>
                            <div class="small text-muted">
                                Las promociones inactivas no deberían mostrarse al cliente.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <button type="submit" class="btn btn-primary px-4">
                    Guardar promoción
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    var promoModal;

    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('promoModal');

        if (modalElement && typeof bootstrap !== 'undefined') {
            promoModal = new bootstrap.Modal(modalElement);
        }
    });

    function formatPromoDate(value) {
        if (!value) {
            return '';
        }

        return String(value).replace(' ', 'T').slice(0, 16);
    }

    function openCreateModal() {
        var form = document.getElementById('promoForm');

        form.reset();
        form.action = '/admin/promotions/store';

        document.getElementById('modalTitle').textContent = 'Crear promoción';
        document.getElementById('promoId').value = '';
        document.getElementById('promoImagePath').value = '';
        document.getElementById('promoDiscountType').value = 'NONE';
        document.getElementById('promoDiscountValue').value = '0';
        document.getElementById('promoDisplayOrder').value = '0';
        document.getElementById('promoActive').checked = true;

        promoModal.show();
    }

    function openEditModal(data) {
        var form = document.getElementById('promoForm');

        form.reset();
        form.action = '/admin/promotions/update';

        document.getElementById('modalTitle').textContent = 'Editar promoción';
        document.getElementById('promoId').value = data.id || '';
        document.getElementById('promoTitle').value = data.title || '';
        document.getElementById('promoDesc').value = data.description || '';
        document.getElementById('promoDiscountType').value = data.discount_type || 'NONE';
        document.getElementById('promoDiscountValue').value = data.discount_value || '0';
        document.getElementById('promoDisplayOrder').value = data.display_order || '0';
        document.getElementById('promoStart').value = formatPromoDate(data.starts_at || data.start_date);
        document.getElementById('promoEnd').value = formatPromoDate(data.ends_at || data.end_date);
        document.getElementById('promoCTA').value = data.cta_text || '';
        document.getElementById('promoLink').value = data.cta_url || data.cta_link || '';
        document.getElementById('promoImagePath').value = data.image_path || data.image || '';
        document.getElementById('promoActive').checked = String(data.is_active || '0') === '1';

        promoModal.show();
    }

    function confirmDeletePromo(id) {
        window.qvConfirmDelete(
            '¿Eliminar promoción?',
            'La promoción dejará de mostrarse y esta acción puede ser irreversible.',
            function () {
                window.enviarFormularioAdmin('/admin/promotions/delete', {
                    id: id
                });
            }
        );
    }
</script>