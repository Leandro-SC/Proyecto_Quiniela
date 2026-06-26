<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Request;
use App\Core\Response;
use App\Models\PromotionModel;
use RuntimeException;
use Throwable;

class PromotionAdminController extends BaseAdminController
{
    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $promotionModel = new PromotionModel();
        $promotions = $promotionModel->getAll();

        $this->render('admin/promotions/index', [
            'pageTitle' => 'Admin · Promociones',
            'promotions' => $promotions,
        ]);
    }

    public function create(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $this->render('admin/promotions/form', [
            'pageTitle' => 'Crear promoción',
            'promotion' => null,
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();

        try {
            $data = $this->sanitizePromotionData($_POST);

            $uploadedImage = $this->uploadImage('image_file')
                ?: $this->uploadImage('image')
                ?: trim((string)($_POST['image_path'] ?? $_POST['image'] ?? ''));

            if ($uploadedImage !== '') {
                $data['image_path'] = $uploadedImage;
                $data['image'] = $uploadedImage;
            }

            $promotionModel = new PromotionModel();
            $promotionModel->create($data);

            header('Location: /admin/promotions');
            exit;
        } catch (Throwable $e) {
            error_log('Error creando promoción: ' . $e->getMessage());

            $this->render('admin/promotions/form', [
                'pageTitle' => 'Crear promoción',
                'promotion' => $this->normalizePromotionForView($_POST),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/promotions');
            exit;
        }

        $promotion = $this->findPromotionById($id);

        if (!$promotion) {
            header('Location: /admin/promotions');
            exit;
        }

        $this->render('admin/promotions/form', [
            'pageTitle' => 'Editar promoción',
            'promotion' => $promotion,
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/promotions');
            exit;
        }

        try {
            $existing = $this->findPromotionById($id);

            if (!$existing) {
                throw new RuntimeException('La promoción no existe.');
            }

            $data = $this->sanitizePromotionData($_POST);
            $data['id'] = $id;

            $uploadedImage = $this->uploadImage('image_file')
                ?: $this->uploadImage('image')
                ?: trim((string)($_POST['image_path'] ?? $_POST['image'] ?? ''));

            if ($uploadedImage === '') {
                $uploadedImage = (string)($existing['image_path'] ?? $existing['image'] ?? '');
            }

            if ($uploadedImage !== '') {
                $data['image_path'] = $uploadedImage;
                $data['image'] = $uploadedImage;
            }

            $promotionModel = new PromotionModel();
            $promotionModel->update($data);

            header('Location: /admin/promotions');
            exit;
        } catch (Throwable $e) {
            error_log('Error actualizando promoción: ' . $e->getMessage());

            $promotion = $this->normalizePromotionForView($_POST);
            $promotion['id'] = $id;

            $this->render('admin/promotions/form', [
                'pageTitle' => 'Editar promoción',
                'promotion' => $promotion,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            try {
                $promotionModel = new PromotionModel();
                $promotionModel->delete($id);
            } catch (Throwable $e) {
                error_log('Error eliminando promoción: ' . $e->getMessage());
            }
        }

        header('Location: /admin/promotions');
        exit;
    }

    private function findPromotionById(int $id): ?array
    {
        $promotionModel = new PromotionModel();
        $promotions = $promotionModel->getAll();

        foreach ($promotions as $promotion) {
            if ((int)$promotion['id'] === $id) {
                return $this->normalizePromotionForView($promotion);
            }
        }

        return null;
    }

    private function sanitizePromotionData(array $input): array
    {
        $title = trim((string)($input['title'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $discountType = strtoupper(trim((string)($input['discount_type'] ?? 'NONE')));
        $discountValue = (float)($input['discount_value'] ?? 0);
        $ctaText = trim((string)($input['cta_text'] ?? ''));
        $ctaUrl = trim((string)($input['cta_url'] ?? $input['cta_link'] ?? ''));
        $startsAt = trim((string)($input['starts_at'] ?? $input['start_date'] ?? ''));
        $endsAt = trim((string)($input['ends_at'] ?? $input['end_date'] ?? ''));
        $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;
        $displayOrder = (int)($input['display_order'] ?? 0);

        if ($title === '') {
            throw new RuntimeException('El título de la promoción es obligatorio.');
        }

        if (!in_array($discountType, ['NONE', 'PERCENT', 'FIXED'], true)) {
            $discountType = 'NONE';
        }

        if ($discountType === 'NONE') {
            $discountValue = 0;
        }

        if ($discountValue < 0) {
            $discountValue = 0;
        }

        if ($discountType === 'PERCENT' && $discountValue > 100) {
            $discountValue = 100;
        }

        return [
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'cta_text' => $ctaText !== '' ? $ctaText : null,
            'cta_url' => $ctaUrl !== '' ? $ctaUrl : null,
            'cta_link' => $ctaUrl !== '' ? $ctaUrl : null,
            'starts_at' => $startsAt !== '' ? $this->normalizeDateTime($startsAt) : null,
            'start_date' => $startsAt !== '' ? $this->normalizeDateTime($startsAt) : null,
            'ends_at' => $endsAt !== '' ? $this->normalizeDateTime($endsAt) : null,
            'end_date' => $endsAt !== '' ? $this->normalizeDateTime($endsAt) : null,
            'is_active' => $isActive === 1 ? 1 : 0,
            'display_order' => $displayOrder,
        ];
    }

    private function normalizePromotionForView(array $promotion): array
    {
        $image = (string)($promotion['image_path'] ?? $promotion['image'] ?? '');
        $start = (string)($promotion['starts_at'] ?? $promotion['start_date'] ?? '');
        $end = (string)($promotion['ends_at'] ?? $promotion['end_date'] ?? '');
        $cta = (string)($promotion['cta_url'] ?? $promotion['cta_link'] ?? '');

        $promotion['image_path'] = $image;
        $promotion['image'] = $image;

        $promotion['starts_at'] = $start;
        $promotion['start_date'] = $start;

        $promotion['ends_at'] = $end;
        $promotion['end_date'] = $end;

        $promotion['cta_url'] = $cta;
        $promotion['cta_link'] = $cta;

        $promotion['is_active'] = isset($promotion['is_active']) ? (int)$promotion['is_active'] : 1;
        $promotion['display_order'] = isset($promotion['display_order']) ? (int)$promotion['display_order'] : 0;

        return $promotion;
    }

    private function normalizeDateTime(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (str_contains($value, 'T')) {
            $value = str_replace('T', ' ', $value);
        }

        if (strlen($value) === 10) {
            $value .= ' 00:00:00';
        }

        if (strlen($value) === 16) {
            $value .= ':00';
        }

        return $value;
    }

    private function uploadImage(string $inputName): ?string
    {
        if (
            !isset($_FILES[$inputName]) ||
            !isset($_FILES[$inputName]['error']) ||
            $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK
        ) {
            return null;
        }

        $uploadDir = dirname(__DIR__, 3) . '/assets/uploads/promotions/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalName = basename((string)$_FILES[$inputName]['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)) {
            throw new RuntimeException('Formato de imagen no permitido.');
        }

        $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName) ?: 'promotion.' . $extension;
        $fileName = 'promo_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . $cleanName;

        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file((string)$_FILES[$inputName]['tmp_name'], $targetPath)) {
            throw new RuntimeException('No se pudo subir la imagen.');
        }

        return '/assets/uploads/promotions/' . $fileName;
    }
}