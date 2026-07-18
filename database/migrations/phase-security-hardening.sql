-- Sigma Panels & Paint - Security hardening migration (REVIEW BEFORE RUNNING)
-- Idempotent where practical. Non-destructive: adds columns/tables only.
-- Apply on the live DB via phpMyAdmin AFTER rotating the DB password.
--
-- Provides the data model for:
--   * "logout all sessions" / stolen-session invalidation  (admins.auth_version)
--   * password-change bookkeeping                          (admins.password_changed_at)
--   * login rate limiting                                  (login_attempts)
--   * private security event log                           (security_log)

SET FOREIGN_KEY_CHECKS = 0;

-- 1) Session invalidation + password bookkeeping on admins ---------------------
-- Add auth_version (bump to invalidate every existing session for that admin)
SET @db := DATABASE();

SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `admins` ADD COLUMN `auth_version` INT NOT NULL DEFAULT 1',
    'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admins' AND COLUMN_NAME='auth_version');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `admins` ADD COLUMN `password_changed_at` DATETIME NULL',
    'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='admins' AND COLUMN_NAME='password_changed_at');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Incident response: invalidate ALL current admin sessions right now.
-- (Every logged-in session becomes invalid on the next protected request
--  once the application checks auth_version.)
UPDATE `admins` SET `auth_version` = `auth_version` + 1;

-- 2) Login rate limiting -------------------------------------------------------
-- One row per failed attempt. Query recent rows per (identifier_hash, ip).
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `identifier_hash` CHAR(64) NOT NULL,   -- SHA-256 of normalized email (never the raw email/password)
    `ip` VARBINARY(16) NOT NULL,           -- REMOTE_ADDR only (never trust X-Forwarded-For)
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_la_ident_time` (`identifier_hash`, `created_at`),
    INDEX `idx_la_ip_time` (`ip`, `created_at`),
    INDEX `idx_la_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Private security event log ------------------------------------------------
-- Never store passwords, hashes, tokens, cookies, or session IDs here.
CREATE TABLE IF NOT EXISTS `security_log` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `event` VARCHAR(64) NOT NULL,          -- login_success, login_fail, rate_limited, csrf_reject, ...
    `admin_id` INT NULL,
    `ip` VARBINARY(16) NULL,
    `route` VARCHAR(255) NULL,
    `meta` VARCHAR(255) NULL,              -- short, non-sensitive context only
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_seclog_event_time` (`event`, `created_at`),
    INDEX `idx_seclog_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Retention guidance (run periodically, e.g. a monthly scheduled task):
--   DELETE FROM `login_attempts` WHERE `created_at` < (NOW() - INTERVAL 30 DAY);
--   DELETE FROM `security_log`   WHERE `created_at` < (NOW() - INTERVAL 180 DAY);
