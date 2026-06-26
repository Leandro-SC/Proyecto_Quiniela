<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
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

        $settings = $this->getSettings();

        $this->render('admin/settings/index', [
            'pageTitle' => 'Admin · Configuración',
            'settings' => $settings,
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

            header('Location: /admin/settings');
            exit;
        } catch (Throwable $e) {
            error_log('Error actualizando settings: ' . $e->getMessage());

            $_SESSION['flash_error'] = $e->getMessage();

            header('Location: /admin/settings');
            exit;
        }
    }

    private function getSettings(): array
    {
        $stmt = $this->pdo->query('
            SELECT setting_key, setting_value
            FROM settings
            ORDER BY setting_key ASC
        ');

        $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        $settings = [];

        foreach ($rows as $row) {
            $settings[(string)$row['setting_key']] = (string)($row['setting_value'] ?? '');
        }

        return array_merge($this->defaultSettings(), $settings);
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
        ];
    }

    private function upsertSetting(string $key, string $value): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO settings (
                setting_key,
                setting_value,
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
                setting_value = VALUES(setting_value),
                updated_at = NOW()
        ');

        $stmt->execute([
            ':setting_key' => $key,
            ':setting_value' => $value,
        ]);
    }

    private function getCountries(): array
    {
        $stmt = $this->pdo->query('
            SELECT id, name, iso_code
            FROM countries
            WHERE is_active = 1
            ORDER BY name ASC
        ');

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    private function getCurrencies(): array
    {
        $stmt = $this->pdo->query('
            SELECT DISTINCT
                currency_code,
                currency_name,
                currency_symbol
            FROM country_currency
            ORDER BY currency_code ASC
        ');

        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }
}