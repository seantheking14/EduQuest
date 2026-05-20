-- ============================================================
-- Teacher Whitelist Table
-- Only emails present here may register or log in as a teacher.
-- ============================================================

USE eduquest;

CREATE TABLE IF NOT EXISTS teacher_whitelist (
    id         INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL UNIQUE,
    notes      VARCHAR(255) NULL,
    added_by   INT          UNSIGNED NULL,
    added_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_whitelist_admin
        FOREIGN KEY (added_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_wl_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
