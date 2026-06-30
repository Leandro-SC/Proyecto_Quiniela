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
 * Administración de países.
 *
 * Compatible con la estructura real:
 * countries:
 * - id
 * - name
 * - iso_code
 * - flag_path
 * - external_country_name
 * - is_active
 *
 * country_currency:
 * - country_code
 * - country_name
 * - currency_code
 * - currency_symbol
 */
class CountryAdminController extends BaseAdminController
{
    private PDO $pdo;

    /**
     * Inicializa conexión.
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
     * Lista países.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function index(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $q = trim((string)($_GET['q'] ?? ''));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(c.name LIKE :q OR c.iso_code LIKE :q OR c.external_country_name LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $sql = '
            SELECT
                c.id,
                c.name,
                c.iso_code,
                c.flag_path,
                c.external_country_name,
                c.is_active,
                c.created_at,
                c.updated_at,
                cc.currency_code,
                cc.currency_symbol
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

    /**
     * Muestra formulario de creación.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function create(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->boot();

        $this->render('admin/countries/form', [
            'pageTitle' => 'Crear país',
            'country' => null,
            'error' => null,
        ]);
    }

    /**
     * Crea país.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function store(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();
        $this->boot();

        try {
            $data = $this->sanitizeCountryData($_POST);
            $flagPath = $this->uploadFlag('flag');

            if ($flagPath !== null) {
                $data['flag_path'] = $flagPath;
            }

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                INSERT INTO countries (
                    name,
                    iso_code,
                    flag_path,
                    external_country_name,
                    is_active,
                    created_at,
                    updated_at
                )
                VALUES (
                    :name,
                    :iso_code,
                    :flag_path,
                    :external_country_name,
                    :is_active,
                    NOW(),
                    NOW()
                )
            ');

            $stmt->execute([
                ':name' => $data['name'],
                ':iso_code' => $data['iso_code'],
                ':flag_path' => $data['flag_path'],
                ':external_country_name' => $data['external_country_name'],
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

    /**
     * Muestra formulario de edición.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
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

        if ($country === null) {
            header('Location: /admin/countries');
            exit;
        }

        $this->render('admin/countries/form', [
            'pageTitle' => 'Editar país',
            'country' => $country,
            'error' => null,
        ]);
    }

    /**
     * Actualiza país.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function update(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();
        $this->boot();

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/countries');
            exit;
        }

        try {
            $existing = $this->findCountryById($id);

            if ($existing === null) {
                throw new RuntimeException('El país no existe.');
            }

            $data = $this->sanitizeCountryData($_POST);
            $flagPath = $this->uploadFlag('flag');

            if ($flagPath === null) {
                $flagPath = (string)($existing['flag_path'] ?? '');
            }

            $data['flag_path'] = $flagPath !== '' ? $flagPath : null;

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                UPDATE countries
                SET
                    name = :name,
                    iso_code = :iso_code,
                    flag_path = :flag_path,
                    external_country_name = :external_country_name,
                    is_active = :is_active,
                    updated_at = NOW()
                WHERE id = :id
                LIMIT 1
            ');

            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':iso_code' => $data['iso_code'],
                ':flag_path' => $data['flag_path'],
                ':external_country_name' => $data['external_country_name'],
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

    /**
     * Elimina o desactiva país si está en uso.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function delete(Request $request, Response $response): void
    {
        $this->requireAdmin();
        $this->requireValidCsrf();
        $this->boot();

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /admin/countries');
            exit;
        }

        try {
            $country = $this->findCountryById($id);

            if ($country === null) {
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
                    LIMIT 1
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
                    LIMIT 1
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

    /**
     * Busca país por ID.
     *
     * @param int $id
     * @return array<string,mixed>|null
     */
    private function findCountryById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                c.id,
                c.name,
                c.iso_code,
                c.flag_path,
                c.external_country_name,
                c.is_active,
                c.created_at,
                c.updated_at,
                cc.currency_code,
                cc.currency_symbol
            FROM countries c
            LEFT JOIN country_currency cc ON cc.country_code = c.iso_code
            WHERE c.id = :id
            LIMIT 1
        ');

        $stmt->execute([
            ':id' => $id,
        ]);

        $country = $stmt->fetch(PDO::FETCH_ASSOC);

        return $country !== false ? $country : null;
    }

    /**
     * Sanitiza país según columnas reales.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private function sanitizeCountryData(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $isoCode = strtoupper(trim((string)($input['iso_code'] ?? '')));
        $externalCountryName = trim((string)($input['external_country_name'] ?? $input['sportsdb_country_name'] ?? ''));
        $currencyCode = strtoupper(trim((string)($input['currency_code'] ?? 'USD')));
        $currencySymbol = trim((string)($input['currency_symbol'] ?? '$'));
        $isActive = isset($input['is_active']) ? 1 : 0;

        if ($name === '') {
            throw new RuntimeException('El nombre del país es obligatorio.');
        }

        if (!preg_match('/^[A-Z]{2}$/', $isoCode)) {
            throw new RuntimeException('El código ISO debe tener exactamente 2 letras.');
        }

        if (!preg_match('/^[A-Z]{3}$/', $currencyCode)) {
            $currencyCode = 'USD';
        }

        if ($currencySymbol === '') {
            $currencySymbol = '$';
        }

        return [
            'name' => $name,
            'iso_code' => $isoCode,
            'flag_path' => null,
            'external_country_name' => $externalCountryName !== '' ? $externalCountryName : null,
            'is_active' => $isActive,
            'currency_code' => $currencyCode,
            'currency_symbol' => $currencySymbol,
        ];
    }

    /**
     * Inserta o actualiza moneda del país.
     *
     * @param array<string,mixed> $data
     * @return void
     */
    private function upsertCountryCurrency(array $data): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO country_currency (
                country_code,
                country_name,
                currency_code,
                currency_symbol,
                created_at,
                updated_at
            )
            VALUES (
                :country_code,
                :country_name,
                :currency_code,
                :currency_symbol,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                country_name = VALUES(country_name),
                currency_code = VALUES(currency_code),
                currency_symbol = VALUES(currency_symbol),
                updated_at = NOW()
        ');

        $stmt->execute([
            ':country_code' => $data['iso_code'],
            ':country_name' => $data['name'],
            ':currency_code' => $data['currency_code'],
            ':currency_symbol' => $data['currency_symbol'],
        ]);
    }

    /**
     * Sube bandera de país.
     *
     * @param string $inputName
     * @return string|null
     */
    private function uploadFlag(string $inputName): ?string
    {
        if (
            !isset($_FILES[$inputName]) ||
            !isset($_FILES[$inputName]['error']) ||
            $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }

        if ($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir la bandera.');
        }

        $tmpName = (string)($_FILES[$inputName]['tmp_name'] ?? '');
        $size = (int)($_FILES[$inputName]['size'] ?? 0);

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Archivo de bandera inválido.');
        }

        if ($size <= 0 || $size > 1024 * 1024) {
            throw new RuntimeException('La bandera no debe superar 1 MB.');
        }

        $mimeType = mime_content_type($tmpName);

        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        if (!isset($allowedMimeTypes[$mimeType])) {
            throw new RuntimeException('Formato no permitido. Usa JPG, PNG, GIF o WEBP.');
        }

        $uploadDir = dirname(__DIR__, 3) . '/assets/uploads/flags/';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new RuntimeException('No se pudo crear el directorio de banderas.');
        }

        $extension = $allowedMimeTypes[$mimeType];
        $fileName = 'flag_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('No se pudo guardar la bandera.');
        }

        return '/assets/uploads/flags/' . $fileName;
    }
}