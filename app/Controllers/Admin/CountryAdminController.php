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

class CountryAdminController extends BaseAdminController
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

        $q = trim((string)($_GET['q'] ?? ''));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(c.name LIKE :q OR c.iso_code LIKE :q OR c.phone_code LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = '
            SELECT
                c.*,
                cc.currency_code,
                cc.currency_name,
                cc.currency_symbol,
                cc.exchange_rate_to_usd
            FROM countries c
            LEFT JOIN country_currency cc ON cc.country_code = c.iso_code
        ';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY c.name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $countries = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->render('admin/countries/index', [
            'pageTitle' => 'Admin · Países',
            'countries' => $countries,
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    public function create(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $this->render('admin/countries/form', [
            'pageTitle' => 'Crear país',
            'country' => null,
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        try {
            $data = $this->sanitizeCountryData($_POST);

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                INSERT INTO countries (
                    name,
                    iso_code,
                    phone_code,
                    flag_emoji,
                    is_active,
                    created_at,
                    updated_at
                )
                VALUES (
                    :name,
                    :iso_code,
                    :phone_code,
                    :flag_emoji,
                    :is_active,
                    NOW(),
                    NOW()
                )
            ');

            $stmt->execute([
                ':name' => $data['name'],
                ':iso_code' => $data['iso_code'],
                ':phone_code' => $data['phone_code'],
                ':flag_emoji' => $data['flag_emoji'],
                ':is_active' => $data['is_active'],
            ]);

            $this->upsertCountryCurrency($data);

            $this->pdo->commit();

            header('Location: /admin/countries');
            exit;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('Error creando país: ' . $e->getMessage());

            $this->render('admin/countries/form', [
                'pageTitle' => 'Crear país',
                'country' => $_POST,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/countries');
            exit;
        }

        $country = $this->findCountryById($id);

        if (!$country) {
            header('Location: /admin/countries');
            exit;
        }

        $this->render('admin/countries/form', [
            'pageTitle' => 'Editar país',
            'country' => $country,
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/countries');
            exit;
        }

        try {
            $existing = $this->findCountryById($id);

            if (!$existing) {
                throw new RuntimeException('El país no existe.');
            }

            $data = $this->sanitizeCountryData($_POST);

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                UPDATE countries
                SET
                    name = :name,
                    iso_code = :iso_code,
                    phone_code = :phone_code,
                    flag_emoji = :flag_emoji,
                    is_active = :is_active,
                    updated_at = NOW()
                WHERE id = :id
            ');

            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':iso_code' => $data['iso_code'],
                ':phone_code' => $data['phone_code'],
                ':flag_emoji' => $data['flag_emoji'],
                ':is_active' => $data['is_active'],
            ]);

            $oldIsoCode = strtoupper((string)($existing['iso_code'] ?? ''));

            if ($oldIsoCode !== '' && $oldIsoCode !== $data['iso_code']) {
                $deleteOldCurrency = $this->pdo->prepare('
                    DELETE FROM country_currency
                    WHERE country_code = :country_code
                ');

                $deleteOldCurrency->execute([
                    ':country_code' => $oldIsoCode,
                ]);
            }

            $this->upsertCountryCurrency($data);

            $this->pdo->commit();

            header('Location: /admin/countries');
            exit;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('Error actualizando país: ' . $e->getMessage());

            $country = $_POST;
            $country['id'] = $id;

            $this->render('admin/countries/form', [
                'pageTitle' => 'Editar país',
                'country' => $country,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/countries');
            exit;
        }

        try {
            $country = $this->findCountryById($id);

            if (!$country) {
                header('Location: /admin/countries');
                exit;
            }

            $usedStmt = $this->pdo->prepare('
                SELECT
                    (
                        SELECT COUNT(*) FROM leagues WHERE country_id = :id
                    ) +
                    (
                        SELECT COUNT(*) FROM teams WHERE country_id = :id
                    ) AS total_used
            ');

            $usedStmt->execute([
                ':id' => $id,
            ]);

            $isUsed = (int)$usedStmt->fetchColumn() > 0;

            if ($isUsed) {
                $stmt = $this->pdo->prepare('
                    UPDATE countries
                    SET is_active = 0,
                        updated_at = NOW()
                    WHERE id = :id
                ');

                $stmt->execute([
                    ':id' => $id,
                ]);
            } else {
                $this->pdo->beginTransaction();

                $deleteCurrency = $this->pdo->prepare('
                    DELETE FROM country_currency
                    WHERE country_code = :country_code
                ');

                $deleteCurrency->execute([
                    ':country_code' => strtoupper((string)$country['iso_code']),
                ]);

                $deleteCountry = $this->pdo->prepare('
                    DELETE FROM countries
                    WHERE id = :id
                ');

                $deleteCountry->execute([
                    ':id' => $id,
                ]);

                $this->pdo->commit();
            }
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('Error eliminando país: ' . $e->getMessage());
        }

        header('Location: /admin/countries');
        exit;
    }

    private function findCountryById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                c.*,
                cc.currency_code,
                cc.currency_name,
                cc.currency_symbol,
                cc.exchange_rate_to_usd
            FROM countries c
            LEFT JOIN country_currency cc ON cc.country_code = c.iso_code
            WHERE c.id = :id
            LIMIT 1
        ');

        $stmt->execute([
            ':id' => $id,
        ]);

        $country = $stmt->fetch(PDO::FETCH_ASSOC);

        return $country ?: null;
    }

    private function sanitizeCountryData(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $isoCode = strtoupper(trim((string)($input['iso_code'] ?? $input['country_code'] ?? '')));
        $phoneCode = trim((string)($input['phone_code'] ?? ''));
        $flagEmoji = trim((string)($input['flag_emoji'] ?? ''));

        $currencyCode = strtoupper(trim((string)($input['currency_code'] ?? 'USD')));
        $currencyName = trim((string)($input['currency_name'] ?? 'US Dollar'));
        $currencySymbol = trim((string)($input['currency_symbol'] ?? '$'));
        $exchangeRate = (float)($input['exchange_rate_to_usd'] ?? 1);

        $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;

        if ($name === '') {
            throw new RuntimeException('El nombre del país es obligatorio.');
        }

        if ($isoCode === '') {
            throw new RuntimeException('El código ISO del país es obligatorio.');
        }

        if (strlen($isoCode) > 3) {
            throw new RuntimeException('El código ISO debe tener máximo 3 caracteres.');
        }

        if ($currencyCode === '') {
            $currencyCode = 'USD';
        }

        if ($currencyName === '') {
            $currencyName = $currencyCode;
        }

        if ($currencySymbol === '') {
            $currencySymbol = '$';
        }

        if ($exchangeRate <= 0) {
            $exchangeRate = 1;
        }

        return [
            'name' => $name,
            'iso_code' => $isoCode,
            'phone_code' => $phoneCode !== '' ? $phoneCode : null,
            'flag_emoji' => $flagEmoji !== '' ? $flagEmoji : null,
            'is_active' => $isActive === 1 ? 1 : 0,
            'currency_code' => $currencyCode,
            'currency_name' => $currencyName,
            'currency_symbol' => $currencySymbol,
            'exchange_rate_to_usd' => $exchangeRate,
        ];
    }

    private function upsertCountryCurrency(array $data): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO country_currency (
                country_code,
                currency_code,
                currency_name,
                currency_symbol,
                exchange_rate_to_usd,
                updated_at
            )
            VALUES (
                :country_code,
                :currency_code,
                :currency_name,
                :currency_symbol,
                :exchange_rate_to_usd,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                currency_code = VALUES(currency_code),
                currency_name = VALUES(currency_name),
                currency_symbol = VALUES(currency_symbol),
                exchange_rate_to_usd = VALUES(exchange_rate_to_usd),
                updated_at = NOW()
        ');

        $stmt->execute([
            ':country_code' => $data['iso_code'],
            ':currency_code' => $data['currency_code'],
            ':currency_name' => $data['currency_name'],
            ':currency_symbol' => $data['currency_symbol'],
            ':exchange_rate_to_usd' => $data['exchange_rate_to_usd'],
        ]);
    }
}