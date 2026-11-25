-- =============================================================
-- AMOURÉ SALON: Salon Appointment Scheduling and Customer Management System
-- Database schema (MySQL 8 / MariaDB 10.4+)
-- Import in phpMyAdmin: Import > choose this file > Go
-- =============================================================

CREATE DATABASE IF NOT EXISTS amoure_salon_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE amoure_salon_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS trusted_devices;
DROP TABLE IF EXISTS auth_codes;
DROP TABLE IF EXISTS email_logs;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS stylist_services;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS stylists;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS settings;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- users: everyone who can log in (admin, staff, customer)
-- -------------------------------------------------------------
CREATE TABLE users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(100) NOT NULL,
  email          VARCHAR(150) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  role           ENUM('admin','staff','customer') NOT NULL DEFAULT 'customer',
  is_active      TINYINT(1) NOT NULL DEFAULT 1,
  two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,  -- 2-step verification by email code
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------------
-- customers: customer profiles
-- user_id is NULL for walk-in / phone customers without an account
-- -------------------------------------------------------------
CREATE TABLE customers (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NULL,
  full_name   VARCHAR(100) NOT NULL,
  phone       VARCHAR(20)  NULL,
  email       VARCHAR(150) NULL,          -- needed for confirmation, cancellation and reminder emails
  notes       TEXT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_customers_user (user_id),
  KEY idx_customers_name (full_name),
  CONSTRAINT fk_customers_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------------
-- stylists
-- -------------------------------------------------------------
CREATE TABLE stylists (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name  VARCHAR(100) NOT NULL,
  specialty  VARCHAR(100) NULL,
  is_active  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------------
-- services: what the salon offers
-- -------------------------------------------------------------
CREATE TABLE services (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name              VARCHAR(100) NOT NULL,
  category          VARCHAR(50)  NOT NULL DEFAULT 'Other',   -- Haircut, Hair Color, Nail Care, ...
  price             DECIMAL(10,2) NOT NULL,
  duration_minutes  SMALLINT UNSIGNED NOT NULL,
  is_active         TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_services_name (name),
  CONSTRAINT chk_services_price    CHECK (price >= 0),
  CONSTRAINT chk_services_duration CHECK (duration_minutes > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------------
-- stylist_services: which stylist can perform which service
-- -------------------------------------------------------------
CREATE TABLE stylist_services (
  stylist_id  INT UNSIGNED NOT NULL,
  service_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (stylist_id, service_id),
  CONSTRAINT fk_ss_stylist FOREIGN KEY (stylist_id)
    REFERENCES stylists (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ss_service FOREIGN KEY (service_id)
    REFERENCES services (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------------
-- appointments
-- -------------------------------------------------------------
CREATE TABLE appointments (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id       INT UNSIGNED NOT NULL,
  stylist_id        INT UNSIGNED NOT NULL,
  service_id        INT UNSIGNED NOT NULL,
  appt_date         DATE NOT NULL,
  start_time        TIME NOT NULL,
  end_time          TIME NOT NULL,          -- start_time + service duration
  status            ENUM('pending','confirmed','completed','cancelled','no_show') NOT NULL DEFAULT 'pending',
  source            ENUM('online','walk_in','phone') NOT NULL DEFAULT 'online',
  reminder_sent_at  DATETIME NULL,          -- NULL until the reminder email is sent
  created_by        INT UNSIGNED NULL,      -- user who created the booking
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_appt_overlap  (stylist_id, appt_date, start_time),  -- overlap check
  KEY idx_appt_date     (appt_date, status),                  -- daily limit, dashboard, reports, reminders
  KEY idx_appt_customer (customer_id),
  CONSTRAINT chk_appt_time CHECK (end_time > start_time),
  CONSTRAINT fk_appt_customer FOREIGN KEY (customer_id)
    REFERENCES customers (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_appt_stylist FOREIGN KEY (stylist_id)
    REFERENCES stylists (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_appt_service FOREIGN KEY (service_id)
    REFERENCES services (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_appt_created_by FOREIGN KEY (created_by)
    REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------------
-- reviews: one per completed appointment, shown after admin approval
-- -------------------------------------------------------------
CREATE TABLE reviews (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  appointment_id  INT UNSIGNED NOT NULL,
  rating          TINYINT UNSIGNED NOT NULL,
  comment         TEXT NULL,
  status          ENUM('pending','approved','removed') NOT NULL DEFAULT 'pending',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_reviews_appointment (appointment_id),
  KEY idx_reviews_status (status),
  CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT fk_reviews_appointment FOREIGN KEY (appointment_id)
    REFERENCES appointments (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------------
-- email_logs: every confirmation, cancellation and reminder email
-- -------------------------------------------------------------
CREATE TABLE email_logs (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  appointment_id  INT UNSIGNED NOT NULL,
  type            ENUM('confirmation','cancellation','reminder') NOT NULL,
  recipient       VARCHAR(150) NOT NULL,
  status          ENUM('sent','failed') NOT NULL,
  error_message   TEXT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_email_logs_appt (appointment_id, type),
  CONSTRAINT fk_email_logs_appointment FOREIGN KEY (appointment_id)
    REFERENCES appointments (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------------
-- settings: values the administrator can change
-- -------------------------------------------------------------
CREATE TABLE settings (
  setting_key    VARCHAR(50) PRIMARY KEY,
  setting_value  VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------- activity log (audit trail; add-only) ----------
CREATE TABLE IF NOT EXISTS activity_logs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NULL,            -- who did it (kept as a number even if the account is later changed)
  user_name   VARCHAR(100) NOT NULL,        -- name at the time of the action
  user_role   VARCHAR(20)  NOT NULL,        -- admin / staff / customer / guest
  category    VARCHAR(30)  NOT NULL,        -- appointments, customers, services, ...
  action      VARCHAR(40)  NOT NULL,        -- confirm, create, login, ...
  details     VARCHAR(500) NOT NULL,        -- readable sentence
  ip_address  VARCHAR(45)  NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_activity_created (created_at),
  KEY idx_activity_user (user_id),
  KEY idx_activity_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Make the log permanent: block changes and deletes.
DROP TRIGGER IF EXISTS trg_activity_logs_no_update;
DROP TRIGGER IF EXISTS trg_activity_logs_no_delete;

DELIMITER $$
CREATE TRIGGER trg_activity_logs_no_update
BEFORE UPDATE ON activity_logs
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Activity log entries cannot be changed.';
END$$

CREATE TRIGGER trg_activity_logs_no_delete
BEFORE DELETE ON activity_logs
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Activity log entries cannot be deleted.';
END$$
DELIMITER ;

-- ---------- forgot password + 2-step verification ----------
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
