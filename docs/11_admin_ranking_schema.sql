-- ============================================================
-- ADMIN + RANKING + PROMOCIONES
-- Versión pensada para producción (MySQL 8+)
-- ============================================================

-- -----------------------------
-- Tabla: admin_users
-- -----------------------------
CREATE TABLE IF NOT EXISTS admin_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(150) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Usuario admin inicial (cambia la contraseña en producción).
-- password_hash de "admin123" usando PASSWORD_DEFAULT de PHP
INSERT IGNORE INTO admin_users (username, password_hash, email, is_active, created_at, updated_at)
VALUES (
    'admin',
    '$2y$10$VtB/wFoYopUT642Ff1lIUOlqC6JeZC2pNGYfYQpZIkDqUQX6q4SAG', -- admin123
    'admin@example.com',
    1,
    NOW(),
    NOW()
);

-- -----------------------------
-- Tabla: promotions
-- Promociones aplicables a tickets
-- -----------------------------
CREATE TABLE IF NOT EXISTS promotions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    country_code CHAR(2) NULL, -- MX, US, PE, NULL=global
    type ENUM('PERCENT','FIXED','2x1','3x2') NOT NULL,
    value DECIMAL(10,2) NULL, -- para PERCENT / FIXED
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    valid_from DATETIME NULL,
    valid_to DATETIME NULL,
    priority INT NOT NULL DEFAULT 100, -- menor = más prioridad
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_promotions_active (is_active),
    KEY idx_promotions_country (country_code),
    KEY idx_promotions_valid (valid_from, valid_to)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Tabla: round_prize_config
-- Configuración de premios por jornada (10 = 10%, etc.)
-- -----------------------------
CREATE TABLE IF NOT EXISTS round_prize_config (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    round_id BIGINT UNSIGNED NOT NULL UNIQUE,
    total_pool_percent DECIMAL(5,2) NOT NULL DEFAULT 45.00, -- porcentaje del total recaudado destinado a premios
    first_place_percent DECIMAL(5,2) NOT NULL DEFAULT 30.00,
    second_place_percent DECIMAL(5,2) NOT NULL DEFAULT 15.00,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_round_prize_config_round
        FOREIGN KEY (round_id) REFERENCES rounds(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- -----------------------------
-- Tabla: round_ticket_scores
-- Resultado de cada ticket en una jornada (para ranking)
-- -----------------------------
CREATE TABLE IF NOT EXISTS round_ticket_scores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    round_id BIGINT UNSIGNED NOT NULL,
    ticket_id BIGINT UNSIGNED NOT NULL,
    phone VARCHAR(30) NOT NULL,         -- para ranking por jugador (por teléfono)
    user_name VARCHAR(150) NOT NULL,
    score INT NOT NULL DEFAULT 0,       -- sumatoria de puntos (ej. +3 acierto exacto, +1 tendencia)
    position INT NULL,                  -- 1, 2, 3, ... (se calcula al cerrar la jornada)
    is_winner TINYINT(1) NOT NULL DEFAULT 0,
    prize_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_rts_round FOREIGN KEY (round_id)
        REFERENCES rounds(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_rts_ticket FOREIGN KEY (ticket_id)
        REFERENCES tickets(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_round_ticket (round_id, ticket_id),
    KEY idx_rts_round_score (round_id, score DESC),
    KEY idx_rts_phone (phone)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Notas de uso:
--
-- 1) Cuando un ticket pasa a estado PAID en el admin:
--    - se calcula su puntuación (score) para la jornada (round_id)
--    - se inserta/actualiza en round_ticket_scores
--
-- 2) Cuando se cierra la jornada:
--    - se recalcula ranking (orden por score DESC, tiebreaks según reglas),
--    - se marcan position=1,2,... y is_winner=1 para los dos primeros,
--    - se calculan prize_amount usando round_prize_config + total recaudado de tickets PAID.
--
-- 3) Un ticket REJECTED o PENDING no debe existir en round_ticket_scores
--    (o puede eliminarse al cambiar de estado para "liberar el cupo").
-- ============================================================
