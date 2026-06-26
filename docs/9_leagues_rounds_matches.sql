-- Tabla de ligas (Liga MX, Champions, etc.)
CREATE TABLE IF NOT EXISTS leagues (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE, -- ej. 'liga-mx', 'uefa-champions'
    name VARCHAR(100) NOT NULL,       -- ej. 'Liga MX', 'UEFA Champions League'
    external_league_id VARCHAR(50) NULL, -- id para Sports API (futuro)
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de jornadas / rondas
CREATE TABLE IF NOT EXISTS rounds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    league_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,     -- ej. 'Jornada 10', 'Fecha 3', 'Matchday 4'
    round_number INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('OPEN','CLOSED') NOT NULL DEFAULT 'OPEN',
    open_at DATETIME NOT NULL,      -- inicio de venta
    close_at DATETIME NOT NULL,     -- cierre de quiniela / límite de envío
    ticket_cost_mxn DECIMAL(10,2) NOT NULL DEFAULT 200.00,
    ticket_cost_usd DECIMAL(10,2) NOT NULL DEFAULT 10.00,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_rounds_league FOREIGN KEY (league_id)
        REFERENCES leagues (id) ON DELETE CASCADE,
    KEY idx_rounds_league_status (league_id, status),
    KEY idx_rounds_close_at (close_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de partidos por jornada
CREATE TABLE IF NOT EXISTS matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    round_id BIGINT UNSIGNED NOT NULL,
    home_team VARCHAR(100) NOT NULL,
    away_team VARCHAR(100) NOT NULL,
    kickoff_at DATETIME NOT NULL,
    external_match_id VARCHAR(50) NULL, -- id para Sports API (futuro)
    status ENUM('SCHEDULED','LIVE','FINISHED','CANCELLED') NOT NULL DEFAULT 'SCHEDULED',
    result VARCHAR(10) NULL, -- ej. 'L','E','V' cuando termine
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_matches_round FOREIGN KEY (round_id)
        REFERENCES rounds (id) ON DELETE CASCADE,
    KEY idx_matches_round (round_id),
    KEY idx_matches_status (status),
    KEY idx_matches_kickoff (kickoff_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Semilla mínima: Liga MX y Champions
INSERT IGNORE INTO leagues (slug, name, external_league_id, is_active, created_at, updated_at)
VALUES
('liga-mx', 'Liga MX', NULL, 1, NOW(), NOW()),
('uefa-champions', 'UEFA Champions League', NULL, 1, NOW(), NOW());
