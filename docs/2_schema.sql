/* 
  Paso 2 · Esquema inicial de base de datos para Sistema de Quiniela Automatizada
  - MySQL 8+
  - Enfoque en seguridad, trazabilidad histórica y buen rendimiento.
*/

/* ============================================================================
   1. Creación de base de datos (ajustar nombre y credenciales en tu entorno)
   ============================================================================ */

CREATE DATABASE IF NOT EXISTS quiniela
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE quiniela;

/* ============================================================================
   2. Opciones globales recomendadas (ejecutar con privilegios suficientes)
   Nota: Ajustar según políticas del hosting.
   ============================================================================ */

-- Habilitar almacenamiento de checks y utf8mb4 para seguridad y compatibilidad.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

/* ============================================================================
   3. Tablas de configuración y catálogos base
   ============================================================================ */

-- Tabla de parámetros globales
CREATE TABLE settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(191) NOT NULL,
  `value` TEXT NOT NULL,
  `description` VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_settings_key UNIQUE (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catálogo país ↔ moneda
CREATE TABLE country_currency (
  country_code CHAR(2) NOT NULL,
  country_name VARCHAR(191) NOT NULL,
  currency_code CHAR(3) NOT NULL,
  currency_symbol VARCHAR(10) NOT NULL DEFAULT '$',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (country_code),
  KEY idx_country_currency_currency (currency_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ============================================================================
   4. Catálogos deportivos: ligas, temporadas, equipos, jornadas, partidos
   ============================================================================ */

CREATE TABLE leagues (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  external_id VARCHAR(64) NULL,
  name VARCHAR(191) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  country_code CHAR(2) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_leagues_slug UNIQUE (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE seasons (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  league_id BIGINT UNSIGNED NOT NULL,
  external_id VARCHAR(64) NULL,
  name VARCHAR(100) NOT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  is_current TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_seasons_league FOREIGN KEY (league_id) REFERENCES leagues (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  KEY idx_seasons_league_current (league_id, is_current)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teams (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  league_id BIGINT UNSIGNED NOT NULL,
  external_id VARCHAR(64) NULL,
  name VARCHAR(191) NOT NULL,
  short_name VARCHAR(64) NULL,
  logo_url VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_teams_league FOREIGN KEY (league_id) REFERENCES leagues (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  KEY idx_teams_league_name (league_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE matchdays (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  season_id BIGINT UNSIGNED NOT NULL,
  league_id BIGINT UNSIGNED NOT NULL,
  `number` INT NOT NULL,
  `name` VARCHAR(191) NULL,
  status ENUM('SCHEDULED','OPEN','CLOSED','FINISHED') NOT NULL DEFAULT 'SCHEDULED',
  open_at DATETIME NULL,
  close_at DATETIME NOT NULL,
  base_price_mxn DECIMAL(10,2) NULL,
  base_price_usd DECIMAL(10,2) NULL,
  max_tickets_per_user INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_matchdays_season FOREIGN KEY (season_id) REFERENCES seasons (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_matchdays_league FOREIGN KEY (league_id) REFERENCES leagues (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  KEY idx_matchdays_league_number (league_id, `number`),
  KEY idx_matchdays_status_close (status, close_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE matches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  matchday_id BIGINT UNSIGNED NOT NULL,
  league_id BIGINT UNSIGNED NOT NULL,
  season_id BIGINT UNSIGNED NOT NULL,
  external_id VARCHAR(64) NULL,
  home_team_id BIGINT UNSIGNED NOT NULL,
  away_team_id BIGINT UNSIGNED NOT NULL,
  kickoff_at DATETIME NULL,
  status ENUM('SCHEDULED','LIVE','FINISHED','CANCELED','POSTPONED') NOT NULL DEFAULT 'SCHEDULED',
  home_score INT NULL,
  away_score INT NULL,
  result_outcome ENUM('L','E','V') NULL,
  extra_data JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_matches_matchday FOREIGN KEY (matchday_id) REFERENCES matchdays (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_matches_league FOREIGN KEY (league_id) REFERENCES leagues (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_matches_season FOREIGN KEY (season_id) REFERENCES seasons (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_matches_home_team FOREIGN KEY (home_team_id) REFERENCES teams (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_matches_away_team FOREIGN KEY (away_team_id) REFERENCES teams (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  KEY idx_matches_matchday_status (matchday_id, status),
  KEY idx_matches_kickoff (kickoff_at),
  KEY idx_matches_external (external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ============================================================================
   5. Jugadores (usuarios finales)
   ============================================================================ */

CREATE TABLE players (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  email VARCHAR(191) NULL,
  country_code CHAR(2) NULL,
  preferred_currency CHAR(3) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_players_phone (phone),
  KEY idx_players_email (email)
  -- No se usa UNIQUE en phone para permitir cambios de formato o países distintos
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ============================================================================
   6. Promociones
   ============================================================================ */

CREATE TABLE promotions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  country_scope VARCHAR(10) NOT NULL DEFAULT 'GLOBAL',
  `type` ENUM('PERCENT','FIXED','2X1','3X2') NOT NULL,
  `value` DECIMAL(10,2) NULL,
  min_amount DECIMAL(10,2) NULL,
  min_tickets INT NULL,
  max_uses_per_day INT NULL,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_promotions_scope_active (country_scope, is_active, starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ============================================================================
   7. Sesiones de compra y tickets
   ============================================================================ */

CREATE TABLE purchase_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  player_id BIGINT UNSIGNED NOT NULL,
  session_code VARCHAR(50) NOT NULL,
  user_name VARCHAR(191) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  country_code CHAR(2) NOT NULL,
  currency CHAR(3) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  promotion_id BIGINT UNSIGNED NULL,
  gross_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  net_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('PENDING','PAID_PARTIAL','PAID','REJECTED') NOT NULL DEFAULT 'PENDING',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_purchase_sessions_code UNIQUE (session_code),
  CONSTRAINT fk_ps_player FOREIGN KEY (player_id) REFERENCES players (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_ps_promotion FOREIGN KEY (promotion_id) REFERENCES promotions (id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  KEY idx_ps_status_created (status, created_at),
  KEY idx_ps_phone_created (phone, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tickets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  player_id BIGINT UNSIGNED NOT NULL,
  ticket_code VARCHAR(50) NOT NULL,
  purchase_session_id BIGINT UNSIGNED NOT NULL,
  matchday_id BIGINT UNSIGNED NOT NULL,
  league_id BIGINT UNSIGNED NOT NULL,
  user_name VARCHAR(191) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  items JSON NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  currency CHAR(3) NOT NULL,
  country_code CHAR(2) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  promotion_id BIGINT UNSIGNED NULL,
  voucher_path VARCHAR(255) NULL,
  status ENUM('PENDING','PAID','REJECTED') NOT NULL DEFAULT 'PENDING',
  points INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_tickets_code UNIQUE (ticket_code),
  CONSTRAINT fk_tickets_player FOREIGN KEY (player_id) REFERENCES players (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_tickets_ps FOREIGN KEY (purchase_session_id) REFERENCES purchase_sessions (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_tickets_matchday FOREIGN KEY (matchday_id) REFERENCES matchdays (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_tickets_league FOREIGN KEY (league_id) REFERENCES leagues (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_tickets_promotion FOREIGN KEY (promotion_id) REFERENCES promotions (id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  KEY idx_tickets_matchday_status (matchday_id, status),
  KEY idx_tickets_player (player_id, status),
  KEY idx_tickets_phone_created (phone, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id BIGINT UNSIGNED NOT NULL,
  match_id BIGINT UNSIGNED NOT NULL,
  selection ENUM('L','E','V') NOT NULL,
  result_outcome ENUM('L','E','V') NULL,
  points INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ticket_items_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_ticket_items_match FOREIGN KEY (match_id) REFERENCES matches (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  KEY idx_ticket_items_ticket (ticket_id),
  KEY idx_ticket_items_match (match_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ============================================================================
   8. Pozo y premios por jornada
   ============================================================================ */

CREATE TABLE matchday_prizes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  matchday_id BIGINT UNSIGNED NOT NULL,
  total_pool_percent DECIMAL(5,2) NOT NULL DEFAULT 45.00,
  first_place_percent DECIMAL(5,2) NOT NULL DEFAULT 30.00,
  second_place_percent DECIMAL(5,2) NOT NULL DEFAULT 15.00,
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_matchday_prizes_matchday FOREIGN KEY (matchday_id) REFERENCES matchdays (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT uq_matchday_prizes_unique UNIQUE (matchday_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ============================================================================
   9. Ranking y snapshots
   ============================================================================ */

CREATE TABLE ranking_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  matchday_id BIGINT UNSIGNED NULL,
  `type` ENUM('MATCHDAY','GLOBAL') NOT NULL DEFAULT 'MATCHDAY',
  generated_at DATETIME NOT NULL,
  `hash` CHAR(64) NOT NULL,
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ranking_snapshots_matchday FOREIGN KEY (matchday_id) REFERENCES matchdays (id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  KEY idx_ranking_snapshots_type (type, matchday_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ranking_snapshot_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  snapshot_id BIGINT UNSIGNED NOT NULL,
  position INT NOT NULL,
  ticket_id BIGINT UNSIGNED NOT NULL,
  user_name VARCHAR(191) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  points INT NOT NULL,
  prize_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rsi_snapshot FOREIGN KEY (snapshot_id) REFERENCES ranking_snapshots (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_rsi_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  KEY idx_rsi_snapshot_position (snapshot_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ============================================================================
   10. Usuarios administradores
   ============================================================================ */

CREATE TABLE admin_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(191) NOT NULL,
  email VARCHAR(191) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('SUPERADMIN','ADMIN','OPERATOR') NOT NULL DEFAULT 'ADMIN',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_admin_users_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ============================================================================
   11. Cache y logs de APIs deportivas
   ============================================================================ */

CREATE TABLE sports_cache (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(50) NOT NULL,
  endpoint VARCHAR(191) NOT NULL,
  params_hash CHAR(64) NOT NULL,
  response_body MEDIUMTEXT NOT NULL,
  status_code INT NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_sports_cache_lookup (provider, endpoint, params_hash),
  KEY idx_sports_cache_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sports_api_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(50) NOT NULL,
  endpoint VARCHAR(191) NOT NULL,
  request_params JSON NULL,
  response_code INT NULL,
  error_message VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sports_api_logs_provider (provider, created_at),
  KEY idx_sports_api_logs_code (response_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ============================================================================
   12. Logs de crons
   ============================================================================ */

CREATE TABLE cron_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  status ENUM('OK','ERROR') NOT NULL DEFAULT 'OK',
  message TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cron_logs_name_date (`name`, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ============================================================================
   13. Comentario final
   - Todas las tablas usan InnoDB para integridad referencial y buen rendimiento.
   - Los campos created_at/updated_at permiten auditoría histórica.
   - No se definen cascadas destructivas en tickets para preservar historial.
   ============================================================================ */
