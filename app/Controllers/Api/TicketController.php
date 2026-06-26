<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\TicketService;
use Throwable;

class TicketController
{
    private array $config = [];
    private TicketService $ticketService;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        date_default_timezone_set('America/Mexico_City');

        $configPath = dirname(__DIR__, 3) . '/config/app.php';

        if (is_file($configPath)) {
            $this->config = require $configPath;
        }

        $this->ticketService = new TicketService();
    }

    public function create(Request $request, Response $response): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                $this->json([
                    'success' => false,
                    'message' => 'Método no permitido.',
                ], 405);
                return;
            }

            $raw = file_get_contents('php://input');
            $payload = json_decode($raw ?: '', true);

            if (!is_array($payload)) {
                $this->json([
                    'success' => false,
                    'message' => 'JSON inválido.',
                ], 400);
                return;
            }

            $result = $this->ticketService->createFromRequest($payload);

            $whatsAppUrl = $this->buildWhatsAppUrl(
                $payload,
                $result['league_name'],
                $result['tickets']
            );

            $this->json([
                'success' => true,
                'whatsAppUrl' => $whatsAppUrl,
                'ticketCodes' => array_column($result['tickets'], 'code'),
                'tickets' => $result['tickets'],
            ]);
        } catch (Throwable $exception) {
            error_log('ERROR TICKET CONTROLLER: ' . $exception->getMessage());

            $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    private function buildWhatsAppUrl(array $payload, string $leagueName, array $tickets): string
    {
        $phoneConf = (string)($this->config['whatsapp']['phone'] ?? '19513770102');
        $target = preg_replace('/\D+/', '', $phoneConf) ?: '19513770102';

        $name = trim((string)($payload['name'] ?? $payload['user_name'] ?? 'Cliente'));
        $currency = (string)($payload['currency'] ?? ($_SESSION['geo_currency'] ?? 'MXN'));
        $matchdayLabel = trim((string)($payload['matchday'] ?? 'Jornada'));

        $message = "*PEDIDO QUINIELA*\n\n";
        $message .= "*Cliente:* {$name}\n";
        $message .= "*Liga:* {$leagueName}\n";
        $message .= "*{$matchdayLabel}*\n";

        $total = 0.0;

        foreach ($tickets as $ticket) {
            $code = (string)($ticket['code'] ?? '');
            $sequence = (string)($ticket['sequence'] ?? '');
            $amount = (float)($ticket['amount'] ?? 0);

            $message .= "\n🎟 Ticket {$sequence}: {$code} ($" . number_format($amount, 2) . ")";
            $total += $amount;
        }

        $message .= "\n\n*TOTAL: $" . number_format($total, 2) . " {$currency}*";

        return 'https://wa.me/' . $target . '?text=' . rawurlencode($message);
    }

    private function json(array $payload, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}