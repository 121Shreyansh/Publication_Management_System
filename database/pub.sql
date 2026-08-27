-- ============================================================
-- IIEST Shibpur Portal — Complete Database
-- Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

CREATE DATABASE IF NOT EXISTS `pub`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_general_ci;

USE `pub`;

SET FOREIGN_KEY_CHECKS = 0;

-- ── LOGIN / AUTH ─────────────────────────────────────────────
DROP TABLE IF EXISTS `index`;
CREATE TABLE `index` (
    `user_id`                    INT          NOT NULL AUTO_INCREMENT,
    `user_name`                  VARCHAR(50)  NOT NULL,
    `password`                   VARCHAR(255) NOT NULL,
    `role`                       VARCHAR(20)  NOT NULL,
    `change_password`            INT          DEFAULT 0,
    `profile_completed`          TINYINT(1)   DEFAULT 0,
    `last_changed_password_date` DATE         DEFAULT NULL,
    `last_changed_password_time` TIME         DEFAULT NULL,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uq_user_name` (`user_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- ── SECURITY QUESTIONS ───────────────────────────────────────
DROP TABLE IF EXISTS `security_questions`;
CREATE TABLE `security_questions` (
    `security_questions_id` INT          NOT NULL AUTO_INCREMENT,
    `question`              VARCHAR(255) NOT NULL,
    PRIMARY KEY (`security_questions_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `security_questions` (`question`) VALUES
('What city were you born in?'),
('What is your mother''s maiden name?'),
('What was your first pet name?'),
('What was the name of your favorite childhood book?'),
('What was the first sports team you supported?'),
('What was the first game you loved playing?'),
('What was the name of your first best friend?'),
('What was the name of the first teacher you liked the most?'),
('What was the first subject you enjoyed in school?'),
('What was your favorite place to visit during childhood?'),
('What was the name of the first place you visited outside your hometown?');

-- ── USER SECURITY ANSWERS ────────────────────────────────────
DROP TABLE IF EXISTS `user_security_answers`;
CREATE TABLE `user_security_answers` (
    `security_ans_id` INT          NOT NULL AUTO_INCREMENT,
    `user_id`         INT          NOT NULL,
    `question_id`     INT          NOT NULL,
    `answer_hash`     VARCHAR(255) NOT NULL,
    `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`security_ans_id`),
    UNIQUE KEY `uniq_user_question` (`user_id`, `question_id`),
    KEY `idx_user_question` (`user_id`, `question_id`),
    FOREIGN KEY (`user_id`)     REFERENCES `index`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `security_questions`(`security_questions_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── DEPARTMENT ───────────────────────────────────────────────
DROP TABLE IF EXISTS `department`;
CREATE TABLE `department` (
    `department_id`    VARCHAR(5)  NOT NULL,
    `department_name`  VARCHAR(50) DEFAULT NULL,
    `department_phone` BIGINT      DEFAULT NULL,
    `department_email` VARCHAR(50) DEFAULT NULL,
    `department_hod`   VARCHAR(30) DEFAULT NULL,
    PRIMARY KEY (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── HOD ──────────────────────────────────────────────────────
DROP TABLE IF EXISTS `hod`;
CREATE TABLE `hod` (
    `hod_id`          INT         NOT NULL,
    `first_name`      VARCHAR(20) NOT NULL,
    `middle_name`     VARCHAR(20) DEFAULT NULL,
    `last_name`       VARCHAR(20) DEFAULT NULL,
    `designation`     VARCHAR(25) DEFAULT NULL,
    `mobile_no`       BIGINT      DEFAULT NULL,
    `email`           VARCHAR(50) DEFAULT NULL,
    `department_name` VARCHAR(50) DEFAULT NULL,
    `doj`             DATE        DEFAULT NULL,
    `dol`             DATE        DEFAULT NULL,
    PRIMARY KEY (`hod_id`),
    FOREIGN KEY (`hod_id`) REFERENCES `index`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── EMPLOYEE INFO ────────────────────────────────────────────
DROP TABLE IF EXISTS `employee_info`;
CREATE TABLE `employee_info` (
    `emp_id`              INT          NOT NULL,
    `emp_type`            VARCHAR(10)  NOT NULL,
    `department_id`       VARCHAR(5)   DEFAULT NULL,
    `gender`              VARCHAR(10)  NOT NULL,
    `first_name`          VARCHAR(20)  NOT NULL,
    `middle_name`         VARCHAR(20)  DEFAULT NULL,
    `last_name`           VARCHAR(20)  DEFAULT NULL,
    `emp_designation`     VARCHAR(50)  DEFAULT NULL,
    `dob`                 DATE         DEFAULT NULL,
    `mobile_no_1`         BIGINT       NOT NULL,
    `mobile_no_2`         BIGINT       DEFAULT NULL,
    `email`               VARCHAR(50)  NOT NULL,
    `address`             VARCHAR(50)  DEFAULT NULL,
    `city`                VARCHAR(20)  DEFAULT NULL,
    `state`               VARCHAR(20)  DEFAULT NULL,
    `pin`                 INT          DEFAULT NULL,
    `country`             VARCHAR(20)  DEFAULT NULL,
    `date_join`           DATE         NOT NULL,
    `date_leave`          DATE         DEFAULT NULL,
    `highest_degree`      VARCHAR(20)  DEFAULT NULL,
    `highest_degree_univ` VARCHAR(50)  DEFAULT NULL,
    `highest_degree_date` DATE         DEFAULT NULL,
    `area_specialization` VARCHAR(100) DEFAULT NULL,
    `pan`                 VARCHAR(20)  DEFAULT NULL,
    `draft_saved`         INT          DEFAULT 0,
    PRIMARY KEY (`emp_id`),
    FOREIGN KEY (`emp_id`)        REFERENCES `index`(`user_id`)            ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `department`(`department_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── STUDENT INFO ─────────────────────────────────────────────
DROP TABLE IF EXISTS `student_info`;
CREATE TABLE `student_info` (
    `student_id`           INT          NOT NULL,
    `student_type`         VARCHAR(10)  NOT NULL,
    `department_id`        VARCHAR(5)   DEFAULT NULL,
    `gender`               VARCHAR(10)  NOT NULL,
    `first_name`           VARCHAR(20)  NOT NULL,
    `middle_name`          VARCHAR(20)  DEFAULT NULL,
    `last_name`            VARCHAR(20)  DEFAULT NULL,
    `dob`                  DATE         DEFAULT NULL,
    `mobile_no`            BIGINT       DEFAULT NULL,
    `email`                VARCHAR(50)  NOT NULL,
    `address`              VARCHAR(50)  DEFAULT NULL,
    `city`                 VARCHAR(20)  DEFAULT NULL,
    `state`                VARCHAR(20)  DEFAULT NULL,
    `pin`                  VARCHAR(10)  DEFAULT NULL,
    `country`              VARCHAR(20)  DEFAULT NULL,
    `guardian_first_name`  VARCHAR(20)  NOT NULL,
    `guardian_middle_name` VARCHAR(20)  DEFAULT NULL,
    `guardian_last_name`   VARCHAR(20)  DEFAULT NULL,
    `guardain_mobile_no`   BIGINT       DEFAULT NULL,
    `guardian_email`       VARCHAR(50)  DEFAULT NULL,
    `enrolment_date`       DATE         DEFAULT NULL,
    `blood_group`          VARCHAR(5)   DEFAULT NULL,
    `registration_no`      VARCHAR(30)  DEFAULT NULL,
    `registration_date`    DATE         DEFAULT NULL,
    `category_of_phd`      VARCHAR(30)  DEFAULT NULL,
    `status`               VARCHAR(20)  NOT NULL DEFAULT 'Ongoing',
    `degree_awarded_date`  DATE         DEFAULT NULL,
    `verification_flag`    BIGINT       DEFAULT 0,
    `verification_status`  VARCHAR(20)  DEFAULT 'Pending',
    `draft_saved`          INT          DEFAULT NULL,
    `verified_by`          INT          DEFAULT NULL,
    `verification_date`    DATE         DEFAULT NULL,
    PRIMARY KEY (`student_id`),
    FOREIGN KEY (`student_id`)    REFERENCES `index`(`user_id`)            ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `department`(`department_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── VERIFICATION LOG ─────────────────────────────────────────
DROP TABLE IF EXISTS `verification`;
CREATE TABLE `verification` (
    `student_id`        INT         NOT NULL,
    `emp_id`            INT         NOT NULL,
    `verification_type` VARCHAR(30) DEFAULT NULL,
    `verification_flag` BIGINT      DEFAULT NULL,
    `verification_date` DATE        NOT NULL,
    `verification_time` TIME        NOT NULL,
    PRIMARY KEY (`student_id`, `verification_date`, `verification_time`),
    FOREIGN KEY (`student_id`) REFERENCES `student_info`(`student_id`) ON DELETE CASCADE,
    FOREIGN KEY (`emp_id`)     REFERENCES `index`(`user_id`)           ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── SUBJECTS POOL ────────────────────────────────────────────
DROP TABLE IF EXISTS `subjects_pool`;
CREATE TABLE `subjects_pool` (
    `subject_code`         VARCHAR(10) NOT NULL,
    `year_of_introduction` DATE        NOT NULL,
    `subject_name`         VARCHAR(50) DEFAULT NULL,
    `subject_type`         VARCHAR(20) DEFAULT NULL,
    `subject_semester`     INT         NOT NULL,
    `taught_in`            VARCHAR(10) NOT NULL,
    `credit`               INT         DEFAULT NULL,
    `lecture_hours`        INT         DEFAULT NULL,
    `tutorial_hours`       INT         DEFAULT NULL,
    `practical_hours`      INT         DEFAULT NULL,
    PRIMARY KEY (`subject_code`, `year_of_introduction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── SEMESTER REGISTRATION ────────────────────────────────────
DROP TABLE IF EXISTS `semester_registration`;
CREATE TABLE `semester_registration` (
    `student_id`        INT    NOT NULL,
    `semester`          INT    NOT NULL,
    `sem_reg_date`      DATE   DEFAULT NULL,
    `verification_flag` BIGINT DEFAULT 0,
    PRIMARY KEY (`student_id`),
    FOREIGN KEY (`student_id`) REFERENCES `student_info`(`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── SUBJECTS OFFERED ─────────────────────────────────────────
DROP TABLE IF EXISTS `subjects_offered`;
CREATE TABLE `subjects_offered` (
    `subject_code`         VARCHAR(10) NOT NULL,
    `year_of_introduction` DATE        NOT NULL,
    `subject_semester`     INT         NOT NULL,
    `taught_in`            VARCHAR(10) NOT NULL,
    `academic_year`        INT         DEFAULT NULL,
    `session`              VARCHAR(10) DEFAULT NULL,
    PRIMARY KEY (`subject_code`, `year_of_introduction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── SUBJECT ENROLLEMENT ──────────────────────────────────────
DROP TABLE IF EXISTS `subject_enrollement`;
CREATE TABLE `subject_enrollement` (
    `student_id`        INT         NOT NULL,
    `subject_code`      VARCHAR(10) NOT NULL,
    `academic_year`     INT         DEFAULT NULL,
    `session`           VARCHAR(10) DEFAULT NULL,
    `verification_flag` BIGINT      DEFAULT 0,
    PRIMARY KEY (`student_id`, `subject_code`),
    FOREIGN KEY (`student_id`)   REFERENCES `student_info`(`student_id`)    ON DELETE CASCADE,
    FOREIGN KEY (`subject_code`) REFERENCES `subjects_pool`(`subject_code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── STUDENT MARKS ────────────────────────────────────────────
DROP TABLE IF EXISTS `student_marks`;
CREATE TABLE `student_marks` (
    `student_id`           INT         NOT NULL,
    `subject_code`         VARCHAR(10) NOT NULL,
    `academic_year`        INT         NOT NULL DEFAULT 0,
    `subject_marks`        INT         DEFAULT NULL,
    `subject_grade_points` INT         DEFAULT NULL,
    `verification_flag`    BIGINT      DEFAULT 0,
    PRIMARY KEY (`student_id`, `subject_code`, `academic_year`),
    FOREIGN KEY (`student_id`)   REFERENCES `student_info`(`student_id`)          ON DELETE CASCADE,
    FOREIGN KEY (`subject_code`) REFERENCES `subject_enrollement`(`subject_code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── JOB DETAILS ──────────────────────────────────────────────
DROP TABLE IF EXISTS `job_details`;
CREATE TABLE `job_details` (
    `job_type_id` INT          NOT NULL AUTO_INCREMENT,
    `job_name`    VARCHAR(100) NOT NULL,
    PRIMARY KEY (`job_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `job_details` (`job_name`) VALUES
('Student ID and Password Generation'),
('Student Record Verification'),
('Student Marks Verification'),
('Student Marks Upload'),
('Subject Pooling');

-- ── JOB ASSIGNMENT ───────────────────────────────────────────
DROP TABLE IF EXISTS `job_assignment`;
CREATE TABLE `job_assignment` (
    `assignment_id`             INT         NOT NULL AUTO_INCREMENT,
    `job_assign_type_id`        INT         DEFAULT NULL,
    `assigned_by`               INT         DEFAULT NULL,
    `assigned_to`               INT         DEFAULT NULL,
    `assigned_for`              INT         DEFAULT NULL,
    `semester`                  INT         DEFAULT NULL,
    `year`                      INT         DEFAULT NULL,
    `job_assignment_start_date` DATE        DEFAULT NULL,
    `job_assignment_due_date`   DATE        DEFAULT NULL,
    `job_assignment_status`     VARCHAR(20) DEFAULT 'Pending',
    PRIMARY KEY (`assignment_id`),
    FOREIGN KEY (`job_assign_type_id`) REFERENCES `job_details`(`job_type_id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_by`)        REFERENCES `employee_info`(`emp_id`)    ON DELETE SET NULL,
    FOREIGN KEY (`assigned_to`)        REFERENCES `employee_info`(`emp_id`)    ON DELETE SET NULL,
    FOREIGN KEY (`assigned_for`)        REFERENCES `student_info`(`student_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── JOB COMPLETION LOG ───────────────────────────────────────
DROP TABLE IF EXISTS `job_completion_log`;
CREATE TABLE `job_completion_log` (
    `job_completion_log_id` INT         NOT NULL AUTO_INCREMENT,
    `assignment_id`         INT         DEFAULT NULL,
    `done_by`               INT         DEFAULT NULL,
    `done_for`              INT         DEFAULT NULL,
    `job_completion_status` VARCHAR(20) DEFAULT NULL,
    `job_completion_date`   DATE        DEFAULT NULL,
    `job_completion_time`   TIME        DEFAULT NULL,
    PRIMARY KEY (`job_completion_log_id`),
    FOREIGN KEY (`assignment_id`) REFERENCES `job_assignment`(`assignment_id`) ON DELETE SET NULL,
    FOREIGN KEY (`done_by`)       REFERENCES `employee_info`(`emp_id`)        ON DELETE SET NULL,
    FOREIGN KEY (`done_for`)      REFERENCES `student_info`(`student_id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════
-- PUBLICATION MANAGEMENT
-- ════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS `publication_author`;
CREATE TABLE `publication_author` (
    `publication_id`     INT         NOT NULL AUTO_INCREMENT,
    `author_id`          INT         NOT NULL,
    `author_type`        VARCHAR(30) NOT NULL DEFAULT 'Main',
    `publication_type`   VARCHAR(30) NOT NULL,
    `publication_status` VARCHAR(30) NOT NULL DEFAULT 'National',
    `verification_flag`  INT         DEFAULT 0,
    PRIMARY KEY (`publication_id`),
    FOREIGN KEY (`author_id`) REFERENCES `index`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `journal_publication_details`;
CREATE TABLE `journal_publication_details` (
    `journal_publication_id`    INT          NOT NULL,
    `journal_publication_title` VARCHAR(250) NOT NULL,
    `journal_title`             VARCHAR(250) DEFAULT NULL,
    `journal_publication_date`  DATE         DEFAULT NULL,
    `journal_publisher`         VARCHAR(50)  DEFAULT NULL,
    `publication_volume_no`     INT          DEFAULT NULL,
    `publication_issue_no`      INT          DEFAULT NULL,
    `published_city`            VARCHAR(50)  DEFAULT NULL,
    `published_country`         VARCHAR(30)  DEFAULT NULL,
    `publication_doi`           VARCHAR(100) DEFAULT NULL,
    `first_page_no`             INT          DEFAULT NULL,
    `last_page_no`              INT          DEFAULT NULL,
    `pdf_path`                  VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`journal_publication_id`),
    FOREIGN KEY (`journal_publication_id`) REFERENCES `publication_author`(`publication_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `conference_publication_details`;
CREATE TABLE `conference_publication_details` (
    `conference_publication_id`    INT          NOT NULL,
    `conference_publication_title` VARCHAR(250) NOT NULL,
    `conference_title`             VARCHAR(250) DEFAULT NULL,
    `conference_start_date`        DATE         DEFAULT NULL,
    `conference_end_date`          DATE         DEFAULT NULL,
    `conference_city`              VARCHAR(50)  DEFAULT NULL,
    `conference_country`           VARCHAR(30)  DEFAULT NULL,
    `conference_publisher`         VARCHAR(50)  DEFAULT NULL,
    `publication_doi`              VARCHAR(100) DEFAULT NULL,
    `conference_location`          VARCHAR(255) DEFAULT NULL,
    `first_page_no`                INT          DEFAULT NULL,
    `last_page_no`                 INT          DEFAULT NULL,
    `pdf_path`                     VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`conference_publication_id`),
    FOREIGN KEY (`conference_publication_id`) REFERENCES `publication_author`(`publication_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `book_chapter_publication_details`;
CREATE TABLE `book_chapter_publication_details` (
    `book_chapter_publication_id`    INT          NOT NULL,
    `book_chapter_publication_title` VARCHAR(250) NOT NULL,
    `book_chapter_title`             VARCHAR(250) DEFAULT NULL,
    `book_chapter_publication_date`  DATE         DEFAULT NULL,
    `book_chapter_publisher`         VARCHAR(50)  DEFAULT NULL,
    `publication_volume_no`          INT          DEFAULT NULL,
    `publication_issue_no`           INT          DEFAULT NULL,
    `published_city`                 VARCHAR(50)  DEFAULT NULL,
    `published_country`              VARCHAR(30)  DEFAULT NULL,
    `publication_doi`                VARCHAR(100) DEFAULT NULL,
    `first_page_no`                  INT          DEFAULT NULL,
    `last_page_no`                   INT          DEFAULT NULL,
    `pdf_path`                       VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`book_chapter_publication_id`),
    FOREIGN KEY (`book_chapter_publication_id`) REFERENCES `publication_author`(`publication_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `outside_author`;
CREATE TABLE `outside_author` (
    `os_author_id`   VARCHAR(30) NOT NULL,
    `gender`         VARCHAR(10) NOT NULL DEFAULT 'Other',
    `first_name`     VARCHAR(20) NOT NULL,
    `middle_name`    VARCHAR(20) DEFAULT NULL,
    `last_name`      VARCHAR(20) DEFAULT NULL,
    `mobile_no`      BIGINT      DEFAULT NULL,
    `email`          VARCHAR(50) NOT NULL,
    `institute_name` VARCHAR(50) NOT NULL,
    `address`        VARCHAR(50) DEFAULT NULL,
    `city`           VARCHAR(20) DEFAULT NULL,
    `state`          VARCHAR(20) DEFAULT NULL,
    `pin`            INT         DEFAULT NULL,
    `country`        VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (`os_author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `co_author_requests`;
CREATE TABLE `co_author_requests` (
    `request_id`     INT         NOT NULL AUTO_INCREMENT,
    `requester_id`   INT         NOT NULL,
    `requested_id`   VARCHAR(30) NOT NULL,
    `publication_id` INT         NOT NULL,
    `status`         VARCHAR(20) NOT NULL DEFAULT 'Pending',
    `requested_at`   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `responded_at`   DATETIME    DEFAULT NULL,
    PRIMARY KEY (`request_id`),
    KEY `idx_requester`   (`requester_id`),
    KEY `idx_publication` (`publication_id`),
    FOREIGN KEY (`requester_id`)   REFERENCES `index`(`user_id`)                     ON DELETE CASCADE,
    FOREIGN KEY (`publication_id`) REFERENCES `publication_author`(`publication_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════
-- RESEARCH PROJECT AND CONSULTANCY
-- ════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS `rc_and_investigator`;
CREATE TABLE `rc_and_investigator` (
    `rc_id`             INT         NOT NULL AUTO_INCREMENT,
    `investigator_id`   INT         NOT NULL,
    `investigator_type` VARCHAR(30) NOT NULL DEFAULT 'Principal Investigator',
    `research_type`     VARCHAR(30) NOT NULL DEFAULT 'Project',
    `verification_flag` INT         DEFAULT 0,
    PRIMARY KEY (`rc_id`),
    KEY `idx_investigator` (`investigator_id`),
    FOREIGN KEY (`investigator_id`) REFERENCES `index`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;

DROP TABLE IF EXISTS `r_and_c_details`;
CREATE TABLE `r_and_c_details` (
    `rc_id`                INT          NOT NULL,
    `rc_title`             VARCHAR(250) NOT NULL,
    `funding_agency`       VARCHAR(250) NOT NULL,
    `rc_duration_month`    INT          NOT NULL,
    `rc_start_date`        DATE         DEFAULT NULL,
    `rc_end_date`          DATE         DEFAULT NULL,
    `rc_sanctioned_amount` BIGINT       DEFAULT NULL,
    `rc_sanction_no`       VARCHAR(50)  DEFAULT NULL,
    PRIMARY KEY (`rc_id`),
    FOREIGN KEY (`rc_id`) REFERENCES `rc_and_investigator`(`rc_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `outside_investigator`;
CREATE TABLE `outside_investigator` (
    `os_investigator_id` VARCHAR(30) NOT NULL,
    `gender`             VARCHAR(10) NOT NULL DEFAULT 'Other',
    `first_name`         VARCHAR(20) NOT NULL,
    `middle_name`        VARCHAR(20) DEFAULT NULL,
    `last_name`          VARCHAR(20) DEFAULT NULL,
    `mobile_no`          BIGINT      NOT NULL,
    `phone_no`           BIGINT      DEFAULT NULL,
    `email`              VARCHAR(50) NOT NULL,
    `institute_name`     VARCHAR(50) NOT NULL,
    `address`            VARCHAR(50) DEFAULT NULL,
    `city`               VARCHAR(20) DEFAULT NULL,
    `state`              VARCHAR(20) DEFAULT NULL,
    `pin`                INT         DEFAULT NULL,
    `country`            VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (`os_investigator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ════════════════════════════════════════════════════════════
-- SEED DATA
-- ════════════════════════════════════════════════════════════

INSERT IGNORE INTO `department` (`department_id`, `department_name`) VALUES
('AE','Aerospace Engineering'),('AR','Architecture'),('BT','Biotechnology'),
('CE','Civil Engineering'),('CH','Chemical Engineering'),
('CST','Computer Science & Technology'),('EE','Electrical Engineering'),
('ECE','Electronics & Communication Engineering'),('IT','Information Technology'),
('IE','Industrial Engineering'),('ME','Mechanical Engineering'),
('MCA','Master of Computer Applications'),('MT','Metallurgical & Material Engineering'),
('MSC','Mining Engineering'),('PH','Physics'),('MA','Mathematics'),
('CH2','Chemistry'),('HUM','Humanities & Social Sciences'),
('MGT','Management Studies'),('EN','Energy Studies');

-- user_id auto-assigned: 1=HOD, 2=Faculty, 3=Student
-- Password for HOD & Student = 'password' | Faculty = custom hash
INSERT INTO `index` (`user_name`,`password`,`role`,`change_password`,`profile_completed`) VALUES
('hod_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','HOD',1,1),
('sreenjoy',  '$2y$10$gN3sTzzf6abozl0esZd/EuWo/XDTdDJUz2aykEDxiNj0U.yFnQFZG','Employee',1,1),
('stu2024001','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student-UG',1,1);

INSERT INTO `employee_info`
(`emp_id`,`emp_type`,`department_id`,`gender`,`first_name`,`last_name`,`emp_designation`,
 `dob`,`mobile_no_1`,`email`,`address`,`city`,`state`,`pin`,`country`,
 `date_join`,`highest_degree`,`highest_degree_univ`,`highest_degree_date`,`area_specialization`,`draft_saved`)
VALUES
(2,'Employee','CST','Male','Sreenjoy','Chakrabarty','Assistant Professor',
 '2005-03-05',9875488188,'sreenjoy2005@gmail.com','34 Purbachal Bidhan Road',
 'Kolkata','West Bengal',700078,'India','2026-03-09','Ph.D','IIEST','2026-03-20','Machine Learning',1);

INSERT INTO `student_info`
(`student_id`,`student_type`,`department_id`,`gender`,`first_name`,`last_name`,
 `guardian_first_name`,`email`,`status`,`verification_flag`,`verification_status`)
VALUES
(3,'UG','CST','Male','Demo','Student','Demo Guardian','demo@iiest.ac.in','Ongoing',0,'Pending');

INSERT IGNORE INTO `subjects_pool`
(`subject_code`,`year_of_introduction`,`subject_name`,`subject_type`,`subject_semester`,`taught_in`,`credit`)
VALUES
('CS101','2024-01-01','Mathematics I',               'Theory',   1,'UG',4),
('CS102','2024-01-01','Engineering Physics',          'Theory',   1,'UG',4),
('CS103','2024-01-01','Introduction to Programming',  'Theory',   1,'UG',3),
('CS104','2024-01-01','English & Communication',      'Theory',   1,'UG',2),
('CS105','2024-01-01','Engineering Drawing',          'Theory',   1,'UG',2),
('CS1P1','2024-01-01','Programming Lab',              'Sessional',1,'UG',2),
('CS1P2','2024-01-01','Physics Lab',                  'Sessional',1,'UG',1),
('CS1P3','2024-01-01','Engg Drawing Sessional',       'Sessional',1,'UG',1);

INSERT IGNORE INTO `semester_registration` (`student_id`,`semester`,`sem_reg_date`,`verification_flag`)
VALUES (3, 1, CURDATE(), 0);
