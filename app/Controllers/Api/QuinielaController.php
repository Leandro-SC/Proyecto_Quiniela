<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\RoundModel;
use App\Models\MatchModel;
use Throwable;

class QuinielaController
{
    public function current(Request $request, Response $response): void
    {
        try {
            $leagueSlug = trim((string)($_GET['league'] ?? 'liga-mx'));

            $roundModel = new RoundModel();
            $matchModel = new MatchModel();

            $currentRound = $roundModel->getCurrentRoundForLeagueSlug($leagueSlug);
            $matches = $currentRound
                ? $matchModel->getPublicMatchesByRound((int)$currentRound['id'])
                : [];

            $this->json([
                'success' => true,
                'league' => $leagueSlug,
                'currentRound' => $currentRound,
                'matches' => $matches,
            ]);
        } catch (Throwable $e) {
            error_log('Error Api\\QuinielaController@current: ' . $e->getMessage());

            $this->json([
                'success' => false,
                'message' => 'No se pudo cargar la quiniela actual.',
            ], 500);
        }
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