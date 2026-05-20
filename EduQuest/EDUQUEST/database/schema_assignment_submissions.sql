-- ====================================================
-- Assignment Submissions Schema
-- Students upload file submissions for assignment-type
-- course_materials.
-- ====================================================

CREATE TABLE IF NOT EXISTS assignment_submissions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_id     INT UNSIGNED NOT NULL COMMENT 'FK → course_materials.id (assignment)',
    student_id      INT UNSIGNED NOT NULL COMMENT 'FK → students.id',
    original_filename VARCHAR(500) DEFAULT NULL,
    stored_filename   VARCHAR(500) DEFAULT NULL,
    file_size       INT UNSIGNED DEFAULT NULL,
    mime_type       VARCHAR(100) DEFAULT NULL,
    notes           TEXT DEFAULT NULL COMMENT 'Optional student notes with the submission',
    status          ENUM('submitted','graded','returned') DEFAULT 'submitted',
    grade           DECIMAL(5,2) DEFAULT NULL COMMENT 'Teacher grade (optional)',
    feedback        TEXT DEFAULT NULL COMMENT 'Teacher feedback',
    submitted_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    graded_at       TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_sub_material FOREIGN KEY (material_id) REFERENCES course_materials(id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_student  FOREIGN KEY (student_id)  REFERENCES students(id)         ON DELETE CASCADE,
    UNIQUE KEY uq_student_material (student_id, material_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes
CREATE INDEX idx_sub_material ON assignment_submissions(material_id);
CREATE INDEX idx_sub_student  ON assignment_submissions(student_id);
CREATE INDEX idx_sub_status   ON assignment_submissions(status);
