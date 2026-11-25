-- =============================================================
-- AMOURÉ SALON: Activity log (audit trail)
-- Run once: phpMyAdmin > amoure_salon_db > Import > this file > Go.
-- Every action by admin, staff and customers is recorded here.
-- Rows can be ADDED only: the triggers below block editing and deleting,
-- even from phpMyAdmin.
-- =============================================================

USE amoure_salon_db;

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
