<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Core\Session;
use App\Core\Logger;

/**
 * Servicio de Geo-IP para detectar país y moneda.
 */
class GeoService
{
    /**
     * Ejecutar el middleware de geolocalización.
     *
     * @param array<string,mixed> $config
     */
    public static function boot(Request $request, array $config): void
    {
        // Si ya tenemos datos en sesión, no volvemos a llamar a la API.
        $countryCode  = Session::get('geo.country_code');
        $currencyCode = Session::get('geo.currency_code');

        if ($countryCode && $currencyCode) {
            return;
        }

        $ip = $request->getClientIp();

        // Evitar llamadas innecesarias en localhost: asumimos Perú (USD por regla "otros -> USD")
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $countryCode = 'PE';
            $countryName = 'Peru';
        } else {
            $result      = self::detectCountry($ip, $config);
            $countryCode = $result['country_code'] ?? 'US';
            $countryName = $result['country_name'] ?? 'United States';
        }

        $currencyDefaults = $config['currency_defaults'] ?? [];
        $currencyCode     = $currencyDefaults[$countryCode] ?? ($currencyDefaults['DEFAULT'] ?? 'USD');

        Session::set('geo.ip', $ip);
        Session::set('geo.country_code', $countryCode);
        Session::set('geo.country_name', $countryName);
        Session::set('geo.currency_code', $currencyCode);
    }

    /**
     * Detección de país usando proveedores configurados.
     *
     * @param array<string,mixed> $config
     * @return array{country_code?:string,country_name?:string}
     */
    private static function detectCountry(string $ip, array $config): array
    {
        $geoConfig = $config['geo_api'] ?? [];
        $primary   = $geoConfig['primary'] ?? [];
        $fallback  = $geoConfig['fallback'] ?? [];

        // Intento primario: ip-api.com
        if (($primary['provider'] ?? '') === 'IP_API') {
            $result = self::callIpApi($ip, $primary);
            if ($result !== null) {
                return $result;
            }
        }

        // Fallback: ipinfo.io
        if (($fallback['provider'] ?? '') === 'IPINFO') {
            $result = self::callIpInfo($ip, $fallback);
            if ($result !== null) {
                return $result;
            }
        }

        Logger::warning('GeoService: no se pudo detectar el país; usando valores por defecto.', ['ip' => $ip]);
        return [];
    }

    /**
     * Llamada a ip-api.com.
     *
     * @param array<string,mixed> $config
     * @return array{country_code?:string,country_name?:string}|null
     */
    private static function callIpApi(string $ip, array $config): ?array
    {
        $baseUrl = rtrim((string)($config['base_url'] ?? 'http://ip-api.com/json'), '/');
        $timeout = (int)($config['timeout'] ?? 5);

        $url = $baseUrl . '/' . rawurlencode($ip) . '?fields=status,country,countryCode,message';

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => $timeout,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            Logger::warning('GeoService: fallo al llamar ip-api.com', ['ip' => $ip]);
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            Logger::warning('GeoService: respuesta inesperada ip-api.com', ['ip' => $ip, 'body' => $body]);
            return null;
        }

        return [
            'country_code' => (string)($data['countryCode'] ?? ''),
            'country_name' => (string)($data['country'] ?? ''),
        ];
    }

    /**
     * Llamada a ipinfo.io.
     *
     * @param array<string,mixed> $config
     * @return array{country_code?:string,country_name?:string}|null
     */
    private static function callIpInfo(string $ip, array $config): ?array
    {
        $baseUrl = rtrim((string)($config['base_url'] ?? 'https://ipinfo.io'), '/');
        $timeout = (int)($config['timeout'] ?? 5);
        $token   = (string)($config['token'] ?? '');

        $url = $baseUrl . '/' . rawurlencode($ip) . '/json';
        if ($token !== '') {
            $url .= '?token=' . urlencode($token);
        }

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => $timeout,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            Logger::warning('GeoService: fallo al llamar ipinfo.io', ['ip' => $ip]);
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            Logger::warning('GeoService: respuesta inesperada ipinfo.io', ['ip' => $ip, 'body' => $body]);
            return null;
        }

        $countryCode = (string)($data['country'] ?? '');
        $countryName = (string)($data['country_name'] ?? '');

        return [
            'country_code' => $countryCode,
            'country_name' => $countryName !== '' ? $countryName : $countryCode,
        ];
    }
}
