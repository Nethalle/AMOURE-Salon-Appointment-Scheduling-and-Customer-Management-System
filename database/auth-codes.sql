-- =============================================================
-- AMOURÉ SALON: Forgot password + 2-step verification (2FA)
-- Run once: phpMyAdmin > amoure_salon_db > Import > this file > Go.
-- Safe to run again.
-- =============================================================

USE amoure_salon_db;

-- Each account can turn 2-step verification on or off.
ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;

-- One-time 6-digit codes sent by email (stored hashed, like passwords).
CREATE TABLE IF NOT EXISTS auth_codes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  purpose     ENUM('reset','login') NOT NULL,     -- reset = forgot password, login = 2-step code
  code_hash   VARCHAR(255) NOT NULL,
  attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0, -- wrong tries on this code
  expires_at  DATETIME NOT NULL,
  used_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_auth_codes_user (user_id, purpose, created_at),
  CONSTRAINT fk_auth_codes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- "Remember this device for 30 days" after a 2-step login (only a hash of the browser's token is kept).
CREATE TABLE IF NOT EXISTS trusted_devices (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  token_hash  CHAR(64) NOT NULL,
  expires_at  DATETIME NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_trusted_token (token_hash),
  KEY idx_trusted_user (user_id),
  CONSTRAINT fk_trusted_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
