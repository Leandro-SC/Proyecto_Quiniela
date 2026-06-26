<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use PDO;
use RuntimeException;

/**
 * Cliente para APIs deportivas con caché y fallback.
 *
 * Soporta:
 * - TheSportsDB (respuesta típica: events[])
 * - API-Football (respuesta típica: response[])
 *
 * Este cliente:
 * - Lee la configuración de config/app.php si no se le pasa nada en el constructor.
 * - Cachea respuestas en sports_cache.
 * - Registra logs en sports_api_logs.
 */
class SportsApiClient
{
    private PDO $pdo;

    /** @var array<string,mixed> */
    private array $config;

    /**
     * @param array<string,mixed> $config Configuración opcional:
     *   - Si incluye la clave 'sports_api', se usará esa sección.
     *   - Si viene vacío, se carga automáticamente desde config/app.php.
     */
    public function __construct(array $config = [])
    {
        $this->pdo = Database::getConnection();

        if (isset($config['sports_api'])) {
            $this->config = $config['sports_api'];
        } elseif (!empty($config)) {
            $this->config = $config;
        } else {
            // Cargar config/app.php automáticamente
            $configPath = dirname(__DIR__, 2) . '/config/app.php';
            $appConfig  = is_file($configPath) ? require $configPath : [];
            $this->config = $appConfig['sports_api'] ?? [];
        }
    }

    /**
     * Obtener fixtures por ID externo de liga y fecha (formato Y-m-d).
     *
     * @return array<mixed>
     */
    public function getFixtures(string $leagueExternalId, ?string $date = null): array
    {
        $params = ['league_id' => $leagueExternalId];
        if ($date !== null) {
            $params['date'] = $date;
        }

        // Endpoint genérico; adáptalo según tu proveedor real si lo deseas.
        return $this->requestWithFallback('/fixtures', $params, 300);
    }

    /**
     * Obtener resultados por ID externo de partido (ejemplo).
     *
     * @return array<string,mixed>
     */
    public function getMatchResult(string $matchExternalId): array
    {
        $params = ['match_id' => $matchExternalId];

        $data = $this->requestWithFallback('/match', $params, 120);
        return is_array($data) ? $data : [];
    }

    /**
     * Método de alto nivel usado por el Admin:
     *
     * Dado un slug de liga ('liga-mx', 'uefa-champions') y el rango de fechas
     * de la jornada (open_at, close_at), intenta obtener los partidos desde la API
     * y los devuelve normalizados a un arreglo estándar:
     *
     * [
     *   [
     *     'external_match_id' => '123',
     *     'home_team_name'    => 'Benfica',
     *     'away_team_name'    => 'Napoli',
     *     'home_team_logo'    => 'https://...',
     *     'away_team_logo'    => 'https://...',
     *     'kickoff_at'        => '2025-12-10 14:00:00',
     *   ],
     *   ...
     * ]
     *
     * @return array<int,array<string,mixed>>
     */
    public function fetchFixturesForRound(string $leagueSlug, string $openAt, string $closeAt): array
    {
        // 1) Resolver external_id de la liga mediante la tabla leagues
        $sql = 'SELECT external_id
                FROM leagues
                WHERE slug = :slug
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':slug' => $leagueSlug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || empty($row['external_id'])) {
            Logger::warning('SportsApiClient: no se encontró external_id para la liga', [
                'slug' => $leagueSlug,
            ]);
            return [];
        }

        $externalLeagueId = (string)$row['external_id'];

        // 2) Elegir una fecha de referencia (usamos la fecha de apertura de la jornada)
        $date = substr($openAt, 0, 10); // Y-m-d

        $raw = $this->getFixtures($externalLeagueId, $date);

        // 3) Normalizar según proveedor
        return $this->normalizeFixtures($raw);
    }

    /**
     * Normaliza distintas estructuras de respuesta de las APIs a un formato estándar
     * de fixtures usable por el Admin.
     *
     * @param array<mixed> $raw
     * @return array<int,array<string,mixed>>
     */
    private function normalizeFixtures(array $raw): array
    {
        $fixtures = [];

        // Caso típico TheSportsDB: events[]
        if (isset($raw['events']) && is_array($raw['events'])) {
            foreach ($raw['events'] as $event) {
                if (!is_array($event)) {
                    continue;
                }

                $home = trim((string)($event['strHomeTeam'] ?? ''));
                $away = trim((string)($event['strAwayTeam'] ?? ''));

                if ($home === '' || $away === '') {
                    continue;
                }

                $fixtures[] = [
                    'external_match_id' => (string)($event['idEvent'] ?? ''),
                    'home_team_name'    => $home,
                    'away_team_name'    => $away,
                    'home_team_logo'    => $event['strHomeTeamBadge'] ?? ($event['strThumb'] ?? null),
                    'away_team_logo'    => $event['strAwayTeamBadge'] ?? null,
                    'kickoff_at'        => $event['dateEvent'] ?? null, // puedes enriquecer con timeEvent si lo necesitas
                ];
            }

            return $fixtures;
        }

        // Caso típico API-Football: response[]
        if (isset($raw['response']) && is_array($raw['response'])) {
            foreach ($raw['response'] as $match) {
                if (!is_array($match)) {
                    continue;
                }

                $homeName = trim((string)($match['teams']['home']['name'] ?? ''));
                $awayName = trim((string)($match['teams']['away']['name'] ?? ''));

                if ($homeName === '' || $awayName === '') {
                    continue;
                }

                $fixtures[] = [
                    'external_match_id' => (string)($match['fixture']['id'] ?? ''),
                    'home_team_name'    => $homeName,
                    'away_team_name'    => $awayName,
                    'home_team_logo'    => $match['teams']['home']['logo'] ?? null,
                    'away_team_logo'    => $match['teams']['away']['logo'] ?? null,
                    'kickoff_at'        => $match['fixture']['date'] ?? null,
                ];
            }

            return $fixtures;
        }

        // Si llega otro formato, lo dejamos en blanco pero sin romper
        Logger::info('SportsApiClient: formato de fixtures no reconocido, se devuelve vacío', []);
        return [];
    }

    /**
     * Capa de fallback: intenta con el proveedor primario y, si falla, usa el proveedor secundario.
     *
     * @param array<string,mixed> $params
     * @return array<mixed>
     */
    private function requestWithFallback(string $endpoint, array $params, int $ttl): array
    {
        $primary  = $this->config['primary'] ?? null;
        $fallback = $this->config['fallback'] ?? null;

        if ($primary === null) {
            throw new RuntimeException('SportsApiClient: proveedor primario no configurado.');
        }

        // Primero intentamos con el proveedor primario
        $data = $this->requestWithCache('primary', $endpoint, $params, $ttl);
        if (!empty($data)) {
            return $data;
        }

        // Si no hay resultados, probamos el proveedor de fallback (si está configurado)
        if ($fallback !== null) {
            $data = $this->requestWithCache('fallback', $endpoint, $params, $ttl);
            if (!empty($data)) {
                return $data;
            }
        }

        Logger::warning('SportsApiClient: no se pudo obtener datos de ningún proveedor.', [
            'endpoint' => $endpoint,
            'params'   => $params,
        ]);

        return [];
    }

    /**
     * Revisa caché en BD; si no existe o expiró, llama a la API y guarda la respuesta.
     *
     * @param array<string,mixed> $params
     * @return array<mixed>
     */
    private function requestWithCache(string $providerKey, string $endpoint, array $params, int $ttl): array
    {
        $providerConfig = $this->config[$providerKey] ?? null;
        if ($providerConfig === null) {
            return [];
        }

        $providerName = (string)($providerConfig['provider'] ?? 'UNKNOWN');
        $paramsHash   = hash('sha256', json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // 1) Buscar en caché
        $cached = $this->findInCache($providerName, $endpoint, $paramsHash);
        if ($cached !== null) {
            return $cached;
        }

        // 2) Llamar a la API
        $responseData = $this->callProvider($providerConfig, $endpoint, $params);
        if ($responseData === null) {
            return [];
        }

        // 3) Guardar en caché
        $expiresAt = (new \DateTimeImmutable('now'))
            ->add(new \DateInterval('PT' . max($ttl, 60) . 'S'))
            ->format('Y-m-d H:i:s');

        $this->storeInCache(
            $providerName,
            $endpoint,
            $paramsHash,
            json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            200,
            $expiresAt
        );

        return $responseData;
    }

    /**
     * Buscar respuesta en la tabla sports_cache.
     *
     * @return array<mixed>|null
     */
    private function findInCache(string $provider, string $endpoint, string $paramsHash): ?array
    {
        $sql = 'SELECT response_body, expires_at FROM sports_cache
                WHERE provider = :provider
                  AND endpoint = :endpoint
                  AND params_hash = :hash
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':provider' => $provider,
            ':endpoint' => $endpoint,
            ':hash'     => $paramsHash,
        ]);

        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $now       = new \DateTimeImmutable('now');
        $expiresAt = new \DateTimeImmutable((string)$row['expires_at']);

        if ($expiresAt <= $now) {
            return null;
        }

        $body = (string)$row['response_body'];
        $data = json_decode($body, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Guardar respuesta en caché.
     */
    private function storeInCache(
        string $provider,
        string $endpoint,
        string $paramsHash,
        string $responseBody,
        int $statusCode,
        string $expiresAt
    ): void {
        $sql = 'INSERT INTO sports_cache
                (provider, endpoint, params_hash, response_body, status_code, expires_at, created_at, updated_at)
                VALUES (:provider, :endpoint, :hash, :body, :code, :expires, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    response_body = VALUES(response_body),
                    status_code   = VALUES(status_code),
                    expires_at    = VALUES(expires_at),
                    updated_at    = NOW()';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':provider' => $provider,
            ':endpoint' => $endpoint,
            ':hash'     => $paramsHash,
            ':body'     => $responseBody,
            ':code'     => $statusCode,
            ':expires'  => $expiresAt,
        ]);
    }

    /**
     * Llamar a un proveedor específico (TheSportsDB, API-Football, etc.).
     *
     * @param array<string,mixed> $config
     * @param array<string,mixed> $params
     * @return array<mixed>|null
     */
    private function callProvider(array $config, string $endpoint, array $params): ?array
    {
        $baseUrl = rtrim((string)($config['base_url'] ?? ''), '/');
        $apiKey  = (string)($config['api_key'] ?? '');
        $timeout = (int)($config['timeout'] ?? 10);
        $providerName = (string)($config['provider'] ?? 'UNKNOWN');

        if ($baseUrl === '' || $apiKey === '') {
            Logger::warning('SportsApiClient: base_url o api_key faltantes.', ['provider' => $providerName]);
            return null;
        }

        $url      = $baseUrl . $endpoint;
        $query    = http_build_query($params);
        $fullUrl  = $url . '?' . $query;

        $headers = [];
        if ($providerName === 'API_FOOTBALL') {
            $headers[] = 'x-apisports-key: ' . $apiKey;
        } else {
            // Para TheSportsDB y otros que usan api_key en la URL:
            $fullUrl = rtrim($baseUrl, '/') . '/' . rawurlencode($apiKey) . $endpoint . '?' . $query;
        }

        $options = [
            'http' => [
                'method'  => 'GET',
                'timeout' => $timeout,
                'header'  => implode("\r\n", $headers),
            ],
        ];

        $context = stream_context_create($options);

        $startTime = microtime(true);
        $body = @file_get_contents($fullUrl, false, $context);
        $duration = microtime(true) - $startTime;

        $statusCode = 0;

        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('#HTTP/\S+\s+(\d{3})#', $header, $matches)) {
                    $statusCode = (int)$matches[1];
                    break;
                }
            }
        }

        $this->logApiCall($providerName, $endpoint, $params, $statusCode, $body ?: '', $duration);

        if ($body === false || $statusCode < 200 || $statusCode >= 300) {
            Logger::warning('SportsApiClient: error en llamada a API', [
                'provider' => $providerName,
                'endpoint' => $endpoint,
                'status'   => $statusCode,
            ]);
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            Logger::warning('SportsApiClient: respuesta no JSON', [
                'provider' => $providerName,
                'endpoint' => $endpoint,
                'status'   => $statusCode,
            ]);
            return null;
        }

        return $data;
    }

    /**
     * Registrar llamada en sports_api_logs.
     *
     * @param array<string,mixed> $params
     */
    private function logApiCall(
        string $provider,
        string $endpoint,
        array $params,
        int $statusCode,
        string $body,
        float $duration
    ): void {
        $sql = 'INSERT INTO sports_api_logs
                (provider, endpoint, request_params, response_code, error_message, created_at)
                VALUES (:provider, :endpoint, :params, :code, :error, NOW())';

        $error = '';
        if ($statusCode < 200 || $statusCode >= 300) {
            $error = 'HTTP ' . $statusCode;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':provider' => $provider,
            ':endpoint' => $endpoint,
            ':params'   => json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':code'     => $statusCode,
            ':error'    => $error,
        ]);

        Logger::info('SportsApiClient: llamada a API', [
            'provider' => $provider,
            'endpoint' => $endpoint,
            'status'   => $statusCode,
            'duration' => round($duration, 3),
        ]);
    }

    /**
 * Obtener todos los equipos de una liga (TheSportsDB: lookup_all_teams.php).
 *
 * @return array<int,array<string,mixed>>
 */
public function getLeagueTeams(string $leagueExternalId): array
{
    $endpoint = '/lookup_all_teams.php';
    $params   = ['id' => $leagueExternalId];

    $data = $this->requestWithFallback($endpoint, $params, 3600);
    if (!is_array($data) || !isset($data['teams']) || !is_array($data['teams'])) {
        return [];
    }

    return $data['teams'];
}

/**
 * Buscar equipo por nombre (searchteams.php?t=).
 *
 * @return array<int,array<string,mixed>>
 */
public function searchTeams(string $name): array
{
    $endpoint = '/searchteams.php';
    $params   = ['t' => $name];

    $data = $this->requestWithFallback($endpoint, $params, 600);
    if (!is_array($data) || !isset($data['teams']) || !is_array($data['teams'])) {
        return [];
    }

    return $data['teams'];
}

/**
 * Próximos eventos de una liga (eventsnextleague.php?id=).
 *
 * @return array<int,array<string,mixed>>
 */
public function getNextLeagueEvents(string $leagueExternalId): array
{
    $endpoint = '/eventsnextleague.php';
    $params   = ['id' => $leagueExternalId];

    $data = $this->requestWithFallback($endpoint, $params, 600);
    if (!is_array($data) || !isset($data['events']) || !is_array($data['events'])) {
        return [];
    }

    return $data['events'];
}

/**
 * Resultado de un evento por ID (eventresults.php?id=).
 *
 * @return array<string,mixed>|null
 */
public function getEventResultById(string $eventId): ?array
{
    $endpoint = '/eventresults.php';
    $params   = ['id' => $eventId];

    $data = $this->requestWithFallback($endpoint, $params, 300);
    if (!is_array($data) || !isset($data['results'][0])) {
        return null;
    }

    /** @var array<string,mixed> $result */
    $result = $data['results'][0];

    return $result;
}
}
