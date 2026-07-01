<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;
use RuntimeException;
use Throwable;

class SettingsAdminController extends BaseAdminController
{
    private PDO $pdo;

    private function boot(): void
    {
        if (!isset($this->pdo)) {
            $this->pdo = Database::getConnection();
        }
    }

    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $this->render('admin/settings/index', [
            'pageTitle' => 'Admin · Configuración',
            'settings' => $this->getSettings(),
            'countries' => $this->getCountries(),
            'currencies' => $this->getCurrencies(),
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        try {
            $data = $this->sanitizeSettingsData($_POST);

            foreach ($data as $key => $value) {
                $this->upsertSetting($key, $value);
            }

            $_SESSION['flash_success'] = 'Configuración actualizada correctamente.';
        } catch (Throwable $e) {
            error_log('Error actualizando settings: ' . $e->getMessage());
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header('Location: /admin/settings');
        exit;
    }

    private function getSettings(): array
    {
        $settings = $this->defaultSettings();

        try {
            $stmt = $this->pdo->query('
                SELECT `key`, `value`
                FROM settings
                ORDER BY `key` ASC
            ');

            $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

            foreach ($rows as $row) {
                $key = (string)($row['key'] ?? '');
                $value = (string)($row['value'] ?? '');

                if ($key !== '') {
                    $settings[$key] = $value;
                }
            }
        } catch (Throwable $e) {
            error_log('Error leyendo settings: ' . $e->getMessage());
        }

        return $settings;
    }

    private function defaultSettings(): array
    {
        return [
            'site_name' => 'Quinielas Villa',
            'site_description' => 'Sistema de quinielas deportivas',
            'whatsapp_phone' => '',
            'default_country' => 'MX',
            'default_currency' => 'MXN',
            'ticket_default_cost_mxn' => '200.00',
            'ticket_default_cost_usd' => '10.00',
            'prize_pool_percent' => '45.00',
            'first_place_percent' => '30.00',
            'second_place_percent' => '15.00',
            'terms_url' => '',
            'privacy_url' => '',
            'support_email' => '',
            'support_phone' => '',
            'maintenance_mode' => '0',
            'public_hero_bg_desktop' => '',
            'public_hero_bg_mobile' => '',
            'public_hero_overlay_opacity' => '0.72',
        ];
    }

    private function sanitizeSettingsData(array $input): array
    {
        $siteName = trim((string)($input['site_name'] ?? 'Quinielas Villa'));
        $siteDescription = trim((string)($input['site_description'] ?? ''));
        $whatsappPhone = preg_replace('/\D+/', '', (string)($input['whatsapp_phone'] ?? '')) ?? '';

        $defaultCountry = strtoupper(trim((string)($input['default_country'] ?? 'MX')));
        $defaultCurrency = strtoupper(trim((string)($input['default_currency'] ?? 'MXN')));

        $ticketCostMxn = (float)($input['ticket_default_cost_mxn'] ?? 200.00);
        $ticketCostUsd = (float)($input['ticket_default_cost_usd'] ?? 10.00);

        $pool = (float)($input['prize_pool_percent'] ?? 45.00);
        $first = (float)($input['first_place_percent'] ?? 30.00);
        $second = (float)($input['second_place_percent'] ?? 15.00);

        $termsUrl = trim((string)($input['terms_url'] ?? ''));
        $privacyUrl = trim((string)($input['privacy_url'] ?? ''));
        $supportEmail = trim((string)($input['support_email'] ?? ''));
        $supportPhone = trim((string)($input['support_phone'] ?? ''));

        $maintenanceMode = isset($input['maintenance_mode']) ? '1' : '0';

        $currentHeroDesktop = trim((string)($input['current_public_hero_bg_desktop'] ?? ''));
        $currentHeroMobile = trim((string)($input['current_public_hero_bg_mobile'] ?? ''));

        $publicHeroBgDesktop = $this->uploadSettingImage(
            'public_hero_bg_desktop_file',
            $currentHeroDesktop,
            'hero-desktop'
        );

        $publicHeroBgMobile = $this->uploadSettingImage(
            'public_hero_bg_mobile_file',
            $currentHeroMobile,
            'hero-mobile'
        );

        $publicHeroOverlayOpacity = (float)($input['public_hero_overlay_opacity'] ?? 0.72);

        if ($siteName === '') {
            throw new RuntimeException('El nombre del sitio es obligatorio.');
        }

        if ($defaultCountry === '') {
            $defaultCountry = 'MX';
        }

        if ($defaultCurrency === '') {
            $defaultCurrency = 'MXN';
        }

        if ($ticketCostMxn <= 0) {
            $ticketCostMxn = 200.00;
        }

        if ($ticketCostUsd <= 0) {
            $ticketCostUsd = 10.00;
        }

        if ($pool <= 0 || $pool > 100) {
            $pool = 45.00;
        }

        if ($first < 0 || $second < 0 || ($first + $second) > $pool) {
            $first = 30.00;
            $second = 15.00;
        }

        if ($publicHeroOverlayOpacity < 0.35 || $publicHeroOverlayOpacity > 0.95) {
            $publicHeroOverlayOpacity = 0.72;
        }

        return [
            'site_name' => $siteName,
            'site_description' => $siteDescription,
            'whatsapp_phone' => $whatsappPhone,
            'default_country' => $defaultCountry,
            'default_currency' => $defaultCurrency,
            'ticket_default_cost_mxn' => number_format($ticketCostMxn, 2, '.', ''),
            'ticket_default_cost_usd' => number_format($ticketCostUsd, 2, '.', ''),
            'prize_pool_percent' => number_format($pool, 2, '.', ''),
            'first_place_percent' => number_format($first, 2, '.', ''),
            'second_place_percent' => number_format($second, 2, '.', ''),
            'terms_url' => $termsUrl,
            'privacy_url' => $privacyUrl,
            'support_email' => $supportEmail,
            'support_phone' => $supportPhone,
            'maintenance_mode' => $maintenanceMode,
            'public_hero_bg_desktop' => $publicHeroBgDesktop,
            'public_hero_bg_mobile' => $publicHeroBgMobile,
            'public_hero_overlay_opacity' => number_format($publicHeroOverlayOpacity, 2, '.', ''),
        ];
    }

    private function uploadSettingImage(string $fieldName, string $currentValue, string $prefix): string
    {
        if (
            !isset($_FILES[$fieldName]) ||
            !is_array($_FILES[$fieldName]) ||
            ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
        ) {
            return $currentValue;
        }

        $file = $_FILES[$fieldName];

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir la imagen seleccionada.');
        }

        $tmpName = (string)($file['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('El archivo subido no es válido.');
        }

        if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new RuntimeException('La imagen no debe pesar más de 5MB.');
        }

        $mime = '';

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo) {
                $mime = (string)finfo_file($finfo, $tmpName);
                finfo_close($finfo);
            }
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
        ];

        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Formato no permitido. Usa JPG, PNG, WEBP o AVIF.');
        }

        $extension = $allowed[$mime];

        $projectRoot = dirname(__DIR__, 3);
        $uploadDir = $projectRoot . '/assets/img/backgrounds';

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                throw new RuntimeException('No se pudo crear la carpeta de fondos.');
            }
        }

        if (!is_writable($uploadDir)) {
            throw new RuntimeException('La carpeta assets/img/backgrounds no tiene permisos de escritura.');
        }

        $safePrefix = preg_replace('/[^a-z0-9\-_]+/i', '-', $prefix) ?: 'hero';
        $filename = $safePrefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('No se pudo guardar la imagen subida.');
        }

        return '/assets/img/backgrounds/' . $filename;
    }

    private function upsertSetting(string $key, string $value): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO settings (
                `key`,
                `value`,
                created_at,
                updated_at
            )
            VALUES (
                :setting_key,
                :setting_value,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                `value` = VALUES(`value`),
                updated_at = NOW()
        ');

        $stmt->execute([
            ':setting_key' => $key,
            ':setting_value' => $value,
        ]);
    }

    private function getCountries(): array
    {
        try {
            $stmt = $this->pdo->query('
                SELECT id, name, iso_code
                FROM countries
                WHERE is_active = 1
                ORDER BY name ASC
            ');

            return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable $e) {
            error_log('Error leyendo países en settings: ' . $e->getMessage());
            return [];
        }
    }

    private function getCurrencies(): array
    {
        try {
            $stmt = $this->pdo->query('
                SELECT DISTINCT
                    currency_code,
                    currency_symbol
                FROM country_currency
                ORDER BY currency_code ASC
            ');

            return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable $e) {
            error_log('Error leyendo monedas en settings: ' . $e->getMessage());
            return [];
        }
    }
}