<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class RegulationModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

public function getCurrent(): ?array
    {
        $sql = "SELECT * FROM regulations WHERE is_active = 1 ORDER BY id DESC LIMIT 1";
        
        // Ejecutamos la consulta
        $stmt = $this->pdo->query($sql);

        // --- BLOQUE DE DIAGNÓSTICO (INICIO) ---
        if ($stmt === false) {
            echo "<div style='background:red; color:white; padding:20px; z-index:9999; position:relative;'>";
            echo "<h1>🛑 ERROR CRÍTICO DE BASE DE DATOS</h1>";
            echo "<h3>No se pudo leer la tabla 'regulations'.</h3>";
            echo "<strong>Detalle del error SQL:</strong><br>";
            echo "<pre>";
            print_r($this->pdo->errorInfo());
            echo "</pre>";
            echo "</div>";
            die(); // Detenemos todo para que veas el mensaje
        }
        // --- BLOQUE DE DIAGNÓSTICO (FIN) ---

        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $res ?: null;
    }
    public function save(string $content): bool
    {
        $current = $this->getCurrent();
        if ($current) {
            $stmt = $this->pdo->prepare("UPDATE regulations SET content = :content, updated_at = NOW() WHERE id = :id");
            return $stmt->execute([':content' => $content, ':id' => $current['id']]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO regulations (content, is_active, created_at) VALUES (:content, 1, NOW())");
            return $stmt->execute([':content' => $content]);
        }
    }
}