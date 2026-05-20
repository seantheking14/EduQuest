-- ============================================================
-- Notifications Table
-- In-app notification system for teachers and students.
-- recipient_id references teachers.id or students.id (no FK
-- constraint since recipients span two separate profile tables).
-- ============================================================

USE eduquest;

CREATE TABLE IF NOT EXISTS notifications (
    id             INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    recipient_id   INT UNSIGNED    NOT NULL,
    recipient_role ENUM('teacher','student') NOT NULL,
    message        VARCHAR(500)    NOT NULL,
    link           VARCHAR(255)    NULL,
    is_read        TINYINT(1)      NOT NULL DEFAULT 0,
    created_at     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_recipient        (recipient_id, recipient_role),
    INDEX idx_recipient_unread (recipient_id, recipient_role, is_read),
    INDEX idx_created          (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
