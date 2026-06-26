<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

class RegulationModel
{
    private PDO $pdo;
    private array $columns = [];

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->columns = $this->getTableColumns();
    }

    public function getCurrent(): ?array
    {
        $sql = '
            SELECT *
            FROM regulations
            WHERE is_active = 1
            ORDER BY id DESC
            LIMIT 1
        ';

        $stmt = $this->pdo->query($sql);

        if ($stmt === false) {
            throw new RuntimeException('No se pudo leer la tabla regulations.');
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function save(string $content): bool
    {
        $content = trim($content);

        $current = $this->getCurrent();

        if ($current) {
            $stmt = $this->pdo->prepare('
                UPDATE regulations
                SET content = :content,
                    updated_at = NOW()
                WHERE id = :id
            ');

            return $stmt->execute([
                ':content' => $content,
                ':id' => (int)$current['id'],
            ]);
        }

        $data = [
            'content' => $content,
            'is_active' => 1,
        ];

        if ($this->hasColumn('title')) {
            $data['title'] = 'Reglamento general';
        }

        if ($this->hasColumn('slug')) {
            $data['slug'] = 'reglamento-general';
        }

        if ($this->hasColumn('type')) {
            $data['type'] = 'GENERAL';
        }

        if ($this->hasColumn('display_order')) {
            $data['display_order'] = 1;
        }

        if ($this->hasColumn('created_at')) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        if ($this->hasColumn('updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $data = array_filter(
            $data,
            fn(string $column): bool => $this->hasColumn($column),
            ARRAY_FILTER_USE_KEY
        );

        $columns = array_keys($data);
        $placeholders = array_map(fn(string $column): string => ':' . $column, $columns);

        $sql = '
            INSERT INTO regulations (' . implode(', ', $columns) . ')
            VALUES (' . implode(', ', $placeholders) . ')
        ';

        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }

        return $stmt->execute();
    }

    private function getTableColumns(): array
    {
        $stmt = $this->pdo->query('SHOW COLUMNS FROM regulations');

        if (!$stmt) {
            return [];
        }

        $columns = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[] = $column['Field'];
        }

        return $columns;
    }

    private function hasColumn(string $column): bool
    {
        return in_array($column, $this->columns, true);
    }
}