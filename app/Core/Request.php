<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Representa la petición HTTP.
 */
class Request
{
    /** @var array<string,mixed> */
    private array $server;

    /** @var array<string,mixed> */
    private array $get;

    /** @var array<string,mixed> */
    private array $post;

    /** @var array<string,mixed> */
    private array $cookies;

    /** @var array<string,mixed> */
    private array $files;

    /**
     * @param array<string,mixed> $server
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @param array<string,mixed> $cookies
     * @param array<string,mixed> $files
     */
    public function __construct(
        array $server,
        array $get,
        array $post,
        array $cookies,
        array $files
    ) {
        $this->server  = $server;
        $this->get     = $get;
        $this->post    = $post;
        $this->cookies = $cookies;
        $this->files   = $files;
    }

    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function getPath(): string
    {
        return (string)($this->server['REQUEST_URI'] ?? '/');
    }

    /**
     * Obtener todos los parámetros combinados (GET + POST).
     *
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    /**
     * Obtener un parámetro específico (prioriza POST sobre GET).
     */
    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }

        if (array_key_exists($key, $this->get)) {
            return $this->get[$key];
        }

        return $default;
    }

 /**
 * Obtiene la IP del cliente de forma conservadora.
 *
 * Solo toma X-Forwarded-For si existe un proxy/CDN delante.
 * Evita confiar ciegamente en cabeceras manipulables por el cliente.
 *
 * @return string
 */
public function getClientIp(): string
{
    $remoteAddr = (string)($this->server['REMOTE_ADDR'] ?? '0.0.0.0');

    $forwardedProto = (string)($this->server['HTTP_X_FORWARDED_PROTO'] ?? '');
    $forwardedFor = (string)($this->server['HTTP_X_FORWARDED_FOR'] ?? '');

    if ($forwardedProto !== '' && $forwardedFor !== '') {
        $ips = explode(',', $forwardedFor);
        $candidateIp = trim((string)($ips[0] ?? ''));

        if (filter_var($candidateIp, FILTER_VALIDATE_IP)) {
            return $candidateIp;
        }
    }

    if (filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
        return $remoteAddr;
    }

    return '0.0.0.0';
}


}


