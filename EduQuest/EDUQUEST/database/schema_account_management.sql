-- =============================================================================
-- Account Management Schema Additions
-- Run once against the `eduquest` database.
-- If re-running, comment out ALTER TABLE lines that already applied.
-- =============================================================================

USE eduquest;

-- -----------------------------------------------------------------------------
-- 1. Add account management columns to `users`
-- -----------------------------------------------------------------------------
ALTER TABLE users
    ADD COLUMN account_status     ENUM('active','inactive','suspended','archived')
                                  NOT NULL DEFAULT 'active'
                                  AFTER is_active,
    ADD COLUMN suspended_until    TIMESTAMP NULL DEFAULT NULL
                                  AFTER account_status,
    ADD COLUMN suspension_reason  VARCHAR(500) NULL DEFAULT NULL
                                  AFTER suspended_until,
    ADD COLUMN force_password_reset TINYINT(1) NOT NULL DEFAULT 0
                                  AFTER suspension_reason;

-- Retroactively sync: existing inactive users get account_status = 'inactive'
UPDATE users
SET    account_status = 'inactive'
WHERE  is_active = 0
  AND  account_status = 'active';

-- Index for fast status queries
ALTER TABLE users
    ADD INDEX idx_users_account_status (account_status);

-- -----------------------------------------------------------------------------
-- 2. Admin Audit Log
--    Records every account action taken by an admin.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_audit_log (
    id              INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    admin_id        INT UNSIGNED     NOT NULL,          -- admins.id
    action          VARCHAR(100)     NOT NULL,           -- e.g. 'deactivate', 'suspend', 'delete'
    target_user_id  INT UNSIGNED     NOT NULL,           -- users.id of affected user
    target_role     VARCHAR(50)      NULL,               -- 'Teacher' | 'Student'
    target_email    VARCHAR(255)     NULL,
    target_name     VARCHAR(200)     NULL,
    reason          VARCHAR(500)     NULL,
    metadata_json   TEXT             NULL,               -- JSON blob (e.g. suspended_until, deleted counts)
    created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_aaudit_admin   (admin_id),
    INDEX idx_aaudit_target  (target_user_id),
    INDEX idx_aaudit_action  (action),
    INDEX idx_aaudit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
