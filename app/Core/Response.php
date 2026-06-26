<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Representa la respuesta HTTP.
 */
class Response
{
    private int $statusCode = 200;

    private string $content = '';

    /**
     * Establecer código de estado HTTP.
     */
    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
        http_response_code($code);
    }

    /**
     * Establecer contenido HTML o texto plano.
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * Evitar que el navegador cachee ciertas respuestas (por ejemplo, JSON).
     */
    public function disableCache(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    /**
     * Enviar contenido al cliente.
     */
    public function send(): void
    {
        echo $this->content;
    }

    /**
     * Respuesta JSON rápida.
     *
     * @param array<string,mixed> $data
     */
    public function json(array $data, int $statusCode = 200): void
    {
        $this->disableCache();
        $this->setStatusCode($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
