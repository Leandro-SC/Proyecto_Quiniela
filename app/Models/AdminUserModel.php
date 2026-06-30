<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Modelo para usuarios administradores (admin_users).
 *
 * Esquema esperado:
 *
 * CREATE TABLE admin_users (
 *   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *   username VARCHAR(100) NOT NULL UNIQUE,
 *   password_hash VARCHAR(255) NOT NULL,
 *   email VARCHAR(150) NULL,
 *   is_active TINYINT(1) NOT NULL DEFAULT 1,
 *   created_at DATETIME NOT NULL,
 *   updated_at DATETIME NOT NULL
 * );
 */
class AdminUserModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Buscar administrador por username.
     *
     * @return array<string,mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        $sql = 'SELECT *
                FROM admin_users
                WHERE username = :username
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':username' => $username]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Buscar administrador por id.
     *
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT *
                FROM admin_users
                WHERE id = :id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Crear un nuevo usuario admin.
     *
     * @param string      $username      Nombre de usuario único.
     * @param string      $plainPassword Contraseña en texto plano (se encripta aquí).
     * @param string|null $email         Correo opcional.
     *
     * @return int ID del usuario creado.
     */
    public function createAdmin(string $username, string $plainPassword, ?string $email = null): int
    {
        $username = trim($username);
        if ($username === '') {
            throw new RuntimeException('El username es obligatorio.');
        }

        if (strlen($plainPassword) < 8) {
            throw new RuntimeException('La contraseña debe tener al menos 8 caracteres.');
        }

        if ($this->findByUsername($username) !== null) {
            throw new RuntimeException('El username ya está en uso.');
        }

        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new RuntimeException('No se pudo generar el hash de la contraseña.');
        }

        $sql = 'INSERT INTO admin_users
                (username, password_hash, email, is_active, created_at, updated_at)
                VALUES
                (:username, :password_hash, :email, 1, NOW(), NOW())';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':username'      => $username,
                ':password_hash' => $passwordHash,
                ':email'         => $email,
            ]);

            return (int)$this->pdo->lastInsertId();
        } catch (Throwable $e) {
            Logger::error('Error al crear usuario admin.', [
                'username' => $username,
                'error'    => $e->getMessage(),
            ]);
            throw new RuntimeException('No se pudo crear el usuario administrador.');
        }
    }

    /**
     * Actualizar la contraseña de un administrador.
     *
     * @param int    $id            ID del admin.
     * @param string $plainPassword Nueva contraseña en texto plano.
     */
    public function updatePassword(int $id, string $plainPassword): void
    {
        if ($id <= 0) {
            throw new RuntimeException('ID de usuario inválido.');
        }

        if (strlen($plainPassword) < 8) {
            throw new RuntimeException('La contraseña debe tener al menos 8 caracteres.');
        }

        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new RuntimeException('No se pudo generar el hash de la contraseña.');
        }

        $sql = 'UPDATE admin_users
                SET password_hash = :password_hash,
                    updated_at    = NOW()
                WHERE id = :id
                LIMIT 1';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':password_hash' => $passwordHash,
                ':id'            => $id,
            ]);
        } catch (Throwable $e) {
            Logger::error('Error al actualizar contraseña de admin.', [
                'admin_id' => $id,
                'error'    => $e->getMessage(),
            ]);
            throw new RuntimeException('No se pudo actualizar la contraseña del administrador.');
        }
    }

    /**
     * Verificar credenciales de login.
     *
     * @param string $username      Username ingresado.
     * @param string $plainPassword Contraseña en texto plano.
     *
     * @return array<string,mixed>|null Usuario si es válido e activo, null si no coincide.
     */
/**
 * Verifica credenciales de login.
 *
 * No registra contraseñas ni información sensible en logs.
 *
 * @param string $username Usuario ingresado.
 * @param string $plainPassword Contraseña en texto plano.
 * @return array<string,mixed>|null
 */
public function verifyCredentials(string $username, string $plainPassword): ?array
{
    $username = trim($username);

    if ($username === '' || $plainPassword === '') {
        return null;
    }

    $user = $this->findByUsername($username);

    if ($user === null) {
        return null;
    }

    if ((int)$user['is_active'] !== 1) {
        return null;
    }

    $hash = (string)($user['password_hash'] ?? '');

    if ($hash === '' || !password_verify($plainPassword, $hash)) {
        return null;
    }

    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        try {
            $this->updatePassword((int)$user['id'], $plainPassword);
            $user = $this->findById((int)$user['id']) ?? $user;
        } catch (Throwable $e) {
            Logger::warning('No se pudo actualizar el hash de contraseña del administrador.', [
                'admin_id' => $user['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    return $user;
}



    /**
     * Activar o desactivar un usuario admin.
     */
    public function setActive(int $id, bool $active): void
    {
        if ($id <= 0) {
            throw new RuntimeException('ID de usuario inválido.');
        }

        $sql = 'UPDATE admin_users
                SET is_active = :active,
                    updated_at = NOW()
                WHERE id = :id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':active' => $active ? 1 : 0,
            ':id'     => $id,
        ]);
    }
}
