<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

class TicketItemModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function createMany(int $ticketId, array $items): void
    {
        if ($ticketId <= 0) {
            throw new RuntimeException('Ticket inválido.');
        }

        if ($items === []) {
            throw new RuntimeException('Debes seleccionar al menos un pronóstico.');
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO ticket_items (
                ticket_id,
                match_id,
                selection,
                result_outcome,
                points,
                created_at,
                updated_at
            )
            VALUES (
                :ticket_id,
                :match_id,
                :selection,
                NULL,
                0,
                NOW(),
                NOW()
            )
        ');

        foreach ($items as $item) {
            $matchId = (int)($item['match_id'] ?? 0);
            $selection = strtoupper(trim((string)($item['selection'] ?? $item['pick'] ?? '')));

            if ($matchId <= 0) {
                throw new RuntimeException('Partido inválido en la quiniela.');
            }

            if (!in_array($selection, ['L', 'E', 'V'], true)) {
                throw new RuntimeException('Pronóstico inválido. Usa L, E o V.');
            }

            $stmt->execute([
                ':ticket_id' => $ticketId,
                ':match_id' => $matchId,
                ':selection' => $selection,
            ]);
        }
    }
}