-- ============================================================
-- MIGRATION: Add Student Portal tables to the `pub` database
-- Run this ONCE against your existing pub database.
-- ============================================================

-- 1. Add 'student' as a valid role in the login table
--    (assumes 'role' column is an ENUM or VARCHAR — adjust if needed)
ALTER TABLE `index`
    MODIFY COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'faculty';

-- 2. department table (may already exist — use CREATE IF NOT EXISTS)
CREATE TABLE IF NOT EXISTS `department` (
    `department_id`    VARCHAR(5)  NOT NULL,
    `department_name`  VARCHAR(50) NOT NULL,
    `department_phone` BIGINT      DEFAULT NULL,
    `department_email` VARCHAR(50) DEFAULT NULL,
    `department_hod`   VARCHAR(30) DEFAULT NULL,
    PRIMARY KEY (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. student_info
CREATE TABLE IF NOT EXISTS `student_info` (
    `student_id`           VARCHAR(30) NOT NULL,
    `student_type`         VARCHAR(10) NOT NULL,          -- 'UG' or 'PG'
    `department_id`        VARCHAR(5)  DEFAULT NULL,
    `gender`               VARCHAR(10) NOT NULL,
    `first_name`           VARCHAR(20) NOT NULL,
    `middle_name`          VARCHAR(20) DEFAULT NULL,
    `last_name`            VARCHAR(20) DEFAULT NULL,
    `dob`                  DATE        DEFAULT NULL,
    `mobile_no`            BIGINT      DEFAULT NULL,
    `email`                VARCHAR(50) NOT NULL,
    `address`              VARCHAR(50) DEFAULT NULL,
    `city`                 VARCHAR(20) DEFAULT NULL,
    `state`                VARCHAR(20) DEFAULT NULL,
    `pin`                  VARCHAR(10) DEFAULT NULL,
    `country`              VARCHAR(20) DEFAULT NULL,
    `guardian_first_name`  VARCHAR(20) NOT NULL,
    `guardian_middle_name` VARCHAR(20) DEFAULT NULL,
    `guardian_last_name`   VARCHAR(20) DEFAULT NULL,
    `guardain_mobile_no`   BIGINT      DEFAULT NULL,
    `guardian_email`       VARCHAR(50) DEFAULT NULL,
    `enrolment_date`       DATE        DEFAULT NULL,
    `blood_group`          VARCHAR(5)  DEFAULT NULL,
    `registration_no`      VARCHAR(30) DEFAULT NULL,
    `registration_date`    DATE        DEFAULT NULL,
    `category_of_phd`      VARCHAR(30) DEFAULT NULL,
    `status`               VARCHAR(20) NOT NULL DEFAULT 'active',
    `degree_awarded_date`  DATE        DEFAULT NULL,
    `verification_flag`    BIGINT      DEFAULT 0,
    `draft_saved`          INT         DEFAULT NULL,
    `verified_by`          VARCHAR(30) DEFAULT NULL,
    `verification_date`    DATE        DEFAULT NULL,
    PRIMARY KEY (`student_id`),
    FOREIGN KEY (`department_id`) REFERENCES `department`(`department_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. semester_registration
CREATE TABLE IF NOT EXISTS `semester_registration` (
    `student_id`        VARCHAR(30) NOT NULL,
    `semester`          INT         NOT NULL,
    `sem_reg_date`      DATE        DEFAULT NULL,
    `verification_flag` BIGINT      DEFAULT 0,
    PRIMARY KEY (`student_id`),
    FOREIGN KEY (`student_id`) REFERENCES `student_info`(`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. subjects_pool
CREATE TABLE IF NOT EXISTS `subjects_pool` (
    `subject_code`         VARCHAR(10) NOT NULL,
    `year_of_introduction` DATE        NOT NULL,
    `subject_name`         VARCHAR(50) DEFAULT NULL,
    `subject_type`         VARCHAR(20) DEFAULT NULL,     -- 'Theory' or 'Practical'
    `subject_semester`     INT         NOT NULL,
    `taught_in`            VARCHAR(10) NOT NULL,         -- department_id
    `credit`               INT         DEFAULT NULL,
    `lecture_hours`        INT         DEFAULT NULL,
    `tutorial_hours`       INT         DEFAULT NULL,
    `practical_hours`      INT         DEFAULT NULL,
    PRIMARY KEY (`subject_code`, `year_of_introduction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. subjects_offered  (which subjects are active this year/session)
CREATE TABLE IF NOT EXISTS `subjects_offered` (
    `subject_code`         VARCHAR(10) NOT NULL,
    `year_of_introduction` DATE        NOT NULL,
    `subject_semester`     INT         NOT NULL,
    `taught_in`            VARCHAR(10) NOT NULL,
    `academic_year`        INT         DEFAULT NULL,
    `session`              VARCHAR(10) DEFAULT NULL,     -- 'ODD' or 'EVEN'
    PRIMARY KEY (`subject_code`, `year_of_introduction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. subject_enrollement  (student's chosen subjects)
CREATE TABLE IF NOT EXISTS `subject_enrollement` (
    `student_id`        VARCHAR(30) NOT NULL,
    `subject_code`      VARCHAR(10) NOT NULL,
    `academic_year`     INT         DEFAULT NULL,
    `session`           VARCHAR(10) DEFAULT NULL,
    `verification_flag` BIGINT      DEFAULT 0,
    PRIMARY KEY (`student_id`, `subject_code`),
    FOREIGN KEY (`student_id`)   REFERENCES `student_info`(`student_id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_code`) REFERENCES `subjects_pool`(`subject_code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SAMPLE DATA — remove or edit before production use
-- ============================================================

-- A sample department (skip if already exists)
INSERT IGNORE INTO `department` (`department_id`, `department_name`, `department_hod`)
VALUES ('CST', 'Computer Science & Technology', 'Dr. Example HOD');

-- A sample student login in the `index` table
-- Password is 'student123' — hashed with PHP's password_hash()
-- To generate a real hash: php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
INSERT IGNORE INTO `index` (`user_id`, `user_name`, `password`, `role`, `password_changed`, `profile_completed`)
VALUES (
    'STU2024001',
    'stu2024001',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- 'password'
    'student',
    1,
    1
);

-- Matching student_info row (bare minimum so dashboard loads)
INSERT IGNORE INTO `student_info`
    (`student_id`, `student_type`, `department_id`, `gender`, `first_name`, `guardian_first_name`, `email`, `status`)
VALUES
    ('STU2024001', 'UG', 'CST', 'Male', 'Demo', 'Demo Guardian', 'demo@iiest.ac.in', 'active');

-- Sample subjects for semester 1
INSERT IGNORE INTO `subjects_pool`
    (`subject_code`, `year_of_introduction`, `subject_name`, `subject_type`, `subject_semester`, `taught_in`, `credit`)
VALUES
    ('CS101', '2024-01-01', 'Mathematics I',                 'Theory',    1, 'CST', 4),
    ('CS102', '2024-01-01', 'Engineering Physics',           'Theory',    1, 'CST', 4),
    ('CS103', '2024-01-01', 'Introduction to Programming',   'Theory',    1, 'CST', 3),
    ('CS104', '2024-01-01', 'English & Communication',       'Theory',    1, 'CST', 2),
    ('CS105', '2024-01-01', 'Engineering Drawing',           'Theory',    1, 'CST', 2),
    ('CS1P1', '2024-01-01', 'Programming Lab',               'Practical', 1, 'CST', 2),
    ('CS1P2', '2024-01-01', 'Physics Lab',                   'Practical', 1, 'CST', 1),
    ('CS1P3', '2024-01-01', 'Engineering Drawing Sessional', 'Practical', 1, 'CST', 1);

-- Assign semester 1 to the demo student
INSERT IGNORE INTO `semester_registration` (`student_id`, `semester`, `sem_reg_date`, `verification_flag`)
VALUES ('STU2024001', 1, CURDATE(), 0);
