<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Controlador de configuración general del sistema.
 *
 * Centraliza los valores administrables de marca, negocio, contacto,
 * fondos públicos y modo mantenimiento usando la tabla settings.
 */
class SettingsAdminController extends BaseAdminController
{
    private PDO $pdo;

    /**
     * Inicializa la conexión a base de datos.
     *
     * @return void
     */
    private function boot(): void
    {
        if (!isset($this->pdo)) {
            $this->pdo = Database::getConnection();
        }
    }

    /**
     * Muestra el formulario de configuración.
     *
     * @param Request $request Petición HTTP.
     * @param Response $response Respuesta HTTP.
     * @return void
     */
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

    /**
     * Guarda la configuración general del sistema.
     *
     * @param Request $request Petición HTTP.
     * @param Response $response Respuesta HTTP.
     * @return void
     */
    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();
        $this->boot();

        try {
            $data = $this->sanitizeSettingsData($_POST);

            $this->pdo->beginTransaction();

            foreach ($data as $key => $value) {
                $this->upsertSetting((string)$key, (string)$value);
            }

            $this->pdo->commit();

            $_SESSION['flash_success'] = 'Configuración actualizada correctamente.';
        } catch (Throwable $e) {
            if (isset($this->pdo) && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('Error actualizando settings: ' . $e->getMessage());
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header('Location: /admin/settings');
        exit;
    }

    /**
     * Obtiene settings existentes combinados con valores por defecto.
     *
     * @return array<string,string>
     */
    private function getSettings(): array
    {
        $settings = $this->defaultSettings();

        try {
            $stmt = $this->pdo->query('
                SELECT setting_key, setting_value
                FROM settings
                ORDER BY setting_key ASC
            ');

            $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

            foreach ($rows as $row) {
                $key = (string)($row['setting_key'] ?? '');
                $value = (string)($row['setting_value'] ?? '');

                if ($key !== '') {
                    $settings[$key] = $value;
                }
            }
        } catch (Throwable $e) {
            error_log('Error leyendo settings: ' . $e->getMessage());
        }

        if (($settings['whatsapp_phone'] ?? '') === '' && ($settings['contact_whatsapp_number'] ?? '') !== '') {
            $settings['whatsapp_phone'] = (string)$settings['contact_whatsapp_number'];
        }

        if (($settings['contact_whatsapp_number'] ?? '') === '' && ($settings['whatsapp_phone'] ?? '') !== '') {
            $settings['contact_whatsapp_number'] = (string)$settings['whatsapp_phone'];
        }

        return $settings;
    }

    /**
     * Define valores por defecto para evitar pantallas vacías.
     *
     * @return array<string,string>
     */
    private function defaultSettings(): array
    {
        return [
            'site_name' => 'Quinielas Villa',
            'site_description' => 'Sistema de quinielas deportivas',
            'site_logo' => '/assets/img/logo_quiniela.png',
            'site_favicon' => '/assets/img/logo_quiniela.png',
            'whatsapp_phone' => '',
            'contact_whatsapp_number' => '',
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

    /**
     * Valida y normaliza los valores enviados desde el formulario.
     *
     * @param array<string,mixed> $input Datos enviados por POST.
     * @return array<string,string>
     */
    private function sanitizeSettingsData(array $input): array
    {
        $siteName = trim((string)($input['site_name'] ?? 'Quinielas Villa'));
        $siteDescription = trim((string)($input['site_description'] ?? ''));

        // Compatibilidad: el sistema tiene dos claves históricas para WhatsApp.
        // El formulario usa whatsapp_phone, pero el frontend puede leer contact_whatsapp_number.
        $currentSettings = $this->getSettings();
        $whatsappInput = (string)($input['whatsapp_phone'] ?? '');

        if (trim($whatsappInput) === '') {
            $whatsappInput = (string)($currentSettings['whatsapp_phone'] ?? $currentSettings['contact_whatsapp_number'] ?? '');
        }

        $whatsappPhone = $this->normalizePhone($whatsappInput);
        $supportPhone = $this->normalizePhone((string)($input['support_phone'] ?? ''));

        $defaultCountry = strtoupper(trim((string)($input['default_country'] ?? 'MX')));
        $defaultCurrency = strtoupper(trim((string)($input['default_currency'] ?? 'MXN')));

        $ticketCostMxn = $this->normalizeMoney($input['ticket_default_cost_mxn'] ?? 200.00, 200.00);
        $ticketCostUsd = $this->normalizeMoney($input['ticket_default_cost_usd'] ?? 10.00, 10.00);

        $pool = $this->normalizePercent($input['prize_pool_percent'] ?? 45.00, 45.00);
        $first = $this->normalizePercent($input['first_place_percent'] ?? 30.00, 30.00);
        $second = $this->normalizePercent($input['second_place_percent'] ?? 15.00, 15.00);

        $termsUrl = trim((string)($input['terms_url'] ?? ''));
        $privacyUrl = trim((string)($input['privacy_url'] ?? ''));
        $supportEmail = trim((string)($input['support_email'] ?? ''));

        $maintenanceMode = isset($input['maintenance_mode']) ? '1' : '0';

        $currentHeroDesktop = $this->sanitizeCurrentAssetPath(
            (string)($input['current_public_hero_bg_desktop'] ?? '')
        );
        $currentHeroMobile = $this->sanitizeCurrentAssetPath(
            (string)($input['current_public_hero_bg_mobile'] ?? '')
        );

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

        // No bloqueamos el guardado completo si WhatsApp está vacío.
        // Esto permite activar mantenimiento o guardar fondos aunque el negocio lo complete después.
        if ($supportEmail !== '' && !filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El correo de soporte no tiene un formato válido.');
        }

        if ($defaultCountry === '') {
            $defaultCountry = 'MX';
        }

        if ($defaultCurrency === '') {
            $defaultCurrency = 'MXN';
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
            'contact_whatsapp_number' => $whatsappPhone,
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

    /**
     * Normaliza teléfonos dejando solo dígitos.
     *
     * @param string $phone Teléfono recibido.
     * @return string
     */
    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    /**
     * Normaliza importes monetarios.
     *
     * @param mixed $value Valor recibido.
     * @param float $default Valor por defecto.
     * @return float
     */
    private function normalizeMoney(mixed $value, float $default): float
    {
        $amount = (float)$value;

        return $amount > 0 ? $amount : $default;
    }

    /**
     * Normaliza porcentajes.
     *
     * @param mixed $value Valor recibido.
     * @param float $default Valor por defecto.
     * @return float
     */
    private function normalizePercent(mixed $value, float $default): float
    {
        $percent = (float)$value;

        if ($percent < 0 || $percent > 100) {
            return $default;
        }

        return $percent;
    }

    /**
     * Limpia rutas actuales de imágenes para evitar rutas externas o traversal.
     *
     * @param string $path Ruta actual guardada.
     * @return string
     */
    private function sanitizeCurrentAssetPath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        if (!str_starts_with($path, '/assets/')) {
            return '';
        }

        if (str_contains($path, '..')) {
            return '';
        }

        return $path;
    }

    /**
     * Sube una imagen de configuración y devuelve su ruta pública.
     *
     * Si no se sube archivo nuevo, conserva el valor actual.
     *
     * @param string $fieldName Nombre del input file.
     * @param string $currentValue Ruta pública actual.
     * @param string $prefix Prefijo para el archivo generado.
     * @return string
     */
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

        $mime = $this->detectMimeType($tmpName);

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
        $filename = strtolower($safePrefix) . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('No se pudo guardar la imagen subida.');
        }

        return '/assets/img/backgrounds/' . $filename;
    }

    /**
     * Detecta el MIME real de un archivo temporal.
     *
     * @param string $tmpName Ruta temporal.
     * @return string
     */
    private function detectMimeType(string $tmpName): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo) {
                $mime = (string)finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                return $mime;
            }
        }

        return '';
    }

    /**
     * Inserta o actualiza un setting usando las columnas reales de la tabla.
     *
     * @param string $key Clave de configuración.
     * @param string $value Valor de configuración.
     * @return void
     */
    private function upsertSetting(string $key, string $value): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO settings (
                setting_key,
                group_name,
                setting_value,
                setting_type,
                is_public,
                created_at,
                updated_at
            )
            VALUES (
                :setting_key,
                :group_name,
                :setting_value,
                :setting_type,
                :is_public,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                group_name = VALUES(group_name),
                setting_value = VALUES(setting_value),
                setting_type = VALUES(setting_type),
                is_public = VALUES(is_public),
                updated_at = NOW()
        ');

        $stmt->execute([
            ':setting_key' => $key,
            ':group_name' => $this->resolveSettingGroup($key),
            ':setting_value' => $value,
            ':setting_type' => $this->resolveSettingType($key),
            ':is_public' => $this->isPublicSetting($key) ? 1 : 0,
        ]);
    }

    /**
     * Clasifica un setting para mantener la tabla ordenada.
     *
     * @param string $key Clave de configuración.
     * @return string
     */
    private function resolveSettingGroup(string $key): string
    {
        if (str_starts_with($key, 'public_hero_') || str_contains($key, 'logo') || str_contains($key, 'favicon')) {
            return 'design';
        }

        if (str_contains($key, 'whatsapp') || str_contains($key, 'support') || str_contains($key, 'contact')) {
            return 'contact';
        }

        if (str_contains($key, 'ticket') || str_contains($key, 'prize') || str_contains($key, 'place_percent')) {
            return 'business';
        }

        if (str_contains($key, 'seo') || str_contains($key, 'url') || str_contains($key, 'terms') || str_contains($key, 'privacy')) {
            return 'seo';
        }

        if (str_contains($key, 'maintenance')) {
            return 'system';
        }

        return 'general';
    }

    /**
     * Define el tipo del setting según su clave.
     *
     * @param string $key Clave de configuración.
     * @return string
     */
    private function resolveSettingType(string $key): string
    {
        if (str_contains($key, 'bg') || str_contains($key, 'image') || str_contains($key, 'logo') || str_contains($key, 'favicon')) {
            return 'IMAGE';
        }

        if (str_contains($key, 'url')) {
            return 'URL';
        }

        if (str_contains($key, 'cost') || str_contains($key, 'percent') || str_contains($key, 'opacity')) {
            return 'NUMBER';
        }

        if (str_contains($key, 'description') || str_contains($key, 'comment')) {
            return 'TEXTAREA';
        }

        if (str_contains($key, 'maintenance')) {
            return 'BOOLEAN';
        }

        return 'TEXT';
    }

    /**
     * Determina si un setting puede exponerse al frontend público.
     *
     * @param string $key Clave de configuración.
     * @return bool
     */
    private function isPublicSetting(string $key): bool
    {
        $privateKeys = [
            'maintenance_mode',
            'ticket_default_cost_mxn',
            'ticket_default_cost_usd',
            'prize_pool_percent',
            'first_place_percent',
            'second_place_percent',
        ];

        return !in_array($key, $privateKeys, true);
    }

    /**
     * Lista países activos para select del formulario.
     *
     * @return array<int,array<string,mixed>>
     */
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

    /**
     * Lista monedas disponibles para select del formulario.
     *
     * @return array<int,array<string,mixed>>
     */
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