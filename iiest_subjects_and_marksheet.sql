-- ============================================================
-- IIEST Shibpur — Subject Pool (Real Codes) + Marksheet Tables
-- Run against the `pub` database.
-- Safe to re-run: uses INSERT IGNORE / IF NOT EXISTS
-- ============================================================

USE `pub`;

-- ── MARKSHEET TABLES ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `marksheet` (
    `enrollment_no` VARCHAR(32)  NOT NULL,
    `subject_code`  VARCHAR(32)  NOT NULL,
    `semester`      VARCHAR(16)  NOT NULL,
    `letter_grade`  VARCHAR(4)   NOT NULL,
    `grade_points`  DECIMAL(8,2) NOT NULL,
    PRIMARY KEY (`enrollment_no`, `subject_code`),
    KEY `idx_semester` (`semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `marksheet_registration` (
    `enrollment_no`      VARCHAR(32)   NOT NULL,
    `semester`           VARCHAR(16)   NOT NULL,
    `fee_amount`         DECIMAL(12,2) DEFAULT NULL,
    `fee_date`           DATE          DEFAULT NULL,
    `txn_id`             VARCHAR(128)  DEFAULT NULL,
    `registration_json`  LONGTEXT      NOT NULL,
    `created_at`         TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`enrollment_no`, `semester`),
    KEY `idx_semester` (`semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── CLEAR OLD DUMMY SUBJECTS ──────────────────────────────────
DELETE FROM `subjects_pool` WHERE `subject_code` IN (
    'CS101','CS102','CS103','CS104','CS105','CS1P1','CS1P2','CS1P3'
);

-- ============================================================
-- CST — Computer Science & Technology  (UG, 8 Semesters)
-- ============================================================
INSERT IGNORE INTO `subjects_pool`
    (`subject_code`,`year_of_introduction`,`subject_name`,`subject_type`,`subject_semester`,`taught_in`,`credit`,`lecture_hours`,`tutorial_hours`,`practical_hours`)
VALUES
-- Sem 1
('CST101','2023-01-01','Mathematics I',                          'Theory',    1,'CST',4,3,1,0),
('CST102','2023-01-01','Engineering Physics',                    'Theory',    1,'CST',4,3,1,0),
('CST103','2023-01-01','Engineering Chemistry',                  'Theory',    1,'CST',3,3,0,0),
('CST104','2023-01-01','English for Communication',              'Theory',    1,'CST',2,2,0,0),
('CST105','2023-01-01','Basics of Electrical Engineering',       'Theory',    1,'CST',3,3,0,0),
('CST191','2023-01-01','Engineering Physics Lab',                'Sessional', 1,'CST',1,0,0,3),
('CST192','2023-01-01','Engineering Chemistry Lab',              'Sessional', 1,'CST',1,0,0,3),
('CST193','2023-01-01','Workshop Practice',                      'Sessional', 1,'CST',1,0,0,3),
-- Sem 2
('CST201','2023-01-01','Mathematics II',                         'Theory',    2,'CST',4,3,1,0),
('CST202','2023-01-01','Data Structures',                        'Theory',    2,'CST',4,3,1,0),
('CST203','2023-01-01','Digital Electronics',                    'Theory',    2,'CST',4,3,1,0),
('CST204','2023-01-01','Object Oriented Programming',            'Theory',    2,'CST',3,3,0,0),
('CST205','2023-01-01','Discrete Mathematics',                   'Theory',    2,'CST',3,3,0,0),
('CST291','2023-01-01','Data Structures Lab',                    'Sessional', 2,'CST',2,0,0,3),
('CST292','2023-01-01','OOP Lab (Java/C++)',                     'Sessional', 2,'CST',2,0,0,3),
('CST293','2023-01-01','Digital Electronics Lab',                'Sessional', 2,'CST',1,0,0,3),
-- Sem 3
('CST301','2023-01-01','Mathematics III (Probability & Stats)',  'Theory',    3,'CST',4,3,1,0),
('CST302','2023-01-01','Algorithms',                             'Theory',    3,'CST',4,3,1,0),
('CST303','2023-01-01','Computer Organization & Architecture',   'Theory',    3,'CST',4,3,1,0),
('CST304','2023-01-01','Operating Systems',                      'Theory',    3,'CST',4,3,1,0),
('CST305','2023-01-01','Database Management Systems',            'Theory',    3,'CST',3,3,0,0),
('CST391','2023-01-01','Algorithms Lab',                         'Sessional', 3,'CST',2,0,0,3),
('CST392','2023-01-01','OS Lab',                                 'Sessional', 3,'CST',2,0,0,3),
('CST393','2023-01-01','DBMS Lab',                               'Sessional', 3,'CST',1,0,0,3),
-- Sem 4
('CST401','2023-01-01','Computer Networks',                      'Theory',    4,'CST',4,3,1,0),
('CST402','2023-01-01','Theory of Computation',                  'Theory',    4,'CST',4,3,1,0),
('CST403','2023-01-01','Software Engineering',                   'Theory',    4,'CST',3,3,0,0),
('CST404','2023-01-01','Microprocessors & Interfacing',          'Theory',    4,'CST',4,3,1,0),
('CST405','2023-01-01','Numerical Methods',                      'Theory',    4,'CST',3,3,0,0),
('CST491','2023-01-01','Networks Lab',                           'Sessional', 4,'CST',2,0,0,3),
('CST492','2023-01-01','Microprocessors Lab',                    'Sessional', 4,'CST',2,0,0,3),
('CST493','2023-01-01','Software Engineering Lab',               'Sessional', 4,'CST',1,0,0,3),
-- Sem 5
('CST501','2023-01-01','Compiler Design',                        'Theory',    5,'CST',4,3,1,0),
('CST502','2023-01-01','Artificial Intelligence',                'Theory',    5,'CST',4,3,1,0),
('CST503','2023-01-01','Information Security',                   'Theory',    5,'CST',3,3,0,0),
('CST504','2023-01-01','Elective I',                             'Theory',    5,'CST',3,3,0,0),
('CST505','2023-01-01','Elective II',                            'Theory',    5,'CST',3,3,0,0),
('CST591','2023-01-01','Compiler Lab',                           'Sessional', 5,'CST',2,0,0,3),
('CST592','2023-01-01','AI Lab',                                 'Sessional', 5,'CST',2,0,0,3),
('CST593','2023-01-01','Mini Project I',                         'Sessional', 5,'CST',2,0,0,3),
-- Sem 6
('CST601','2023-01-01','Machine Learning',                       'Theory',    6,'CST',4,3,1,0),
('CST602','2023-01-01','Distributed Systems',                    'Theory',    6,'CST',3,3,0,0),
('CST603','2023-01-01','Elective III',                           'Theory',    6,'CST',3,3,0,0),
('CST604','2023-01-01','Elective IV',                            'Theory',    6,'CST',3,3,0,0),
('CST605','2023-01-01','Open Elective I',                        'Theory',    6,'CST',3,3,0,0),
('CST691','2023-01-01','ML Lab',                                 'Sessional', 6,'CST',2,0,0,3),
('CST692','2023-01-01','Mini Project II',                        'Sessional', 6,'CST',2,0,0,3),
('CST693','2023-01-01','Seminar',                                'Sessional', 6,'CST',1,0,0,0),
-- Sem 7
('CST701','2023-01-01','Big Data Analytics',                     'Theory',    7,'CST',3,3,0,0),
('CST702','2023-01-01','Cloud Computing',                        'Theory',    7,'CST',3,3,0,0),
('CST703','2023-01-01','Elective V',                             'Theory',    7,'CST',3,3,0,0),
('CST704','2023-01-01','Open Elective II',                       'Theory',    7,'CST',3,3,0,0),
('CST791','2023-01-01','Project Work Phase I',                   'Sessional', 7,'CST',6,0,0,12),
-- Sem 8
('CST801','2023-01-01','Elective VI',                            'Theory',    8,'CST',3,3,0,0),
('CST891','2023-01-01','Project Work Phase II',                  'Sessional', 8,'CST',10,0,0,20),
('CST892','2023-01-01','Comprehensive Viva',                     'Sessional', 8,'CST',2,0,0,0);

-- ============================================================
-- EE — Electrical Engineering  (UG, 8 Semesters)
-- ============================================================
INSERT IGNORE INTO `subjects_pool`
    (`subject_code`,`year_of_introduction`,`subject_name`,`subject_type`,`subject_semester`,`taught_in`,`credit`,`lecture_hours`,`tutorial_hours`,`practical_hours`)
VALUES
('EE101','2023-01-01','Mathematics I',                           'Theory',    1,'EE',4,3,1,0),
('EE102','2023-01-01','Engineering Physics',                     'Theory',    1,'EE',4,3,1,0),
('EE103','2023-01-01','Engineering Chemistry',                   'Theory',    1,'EE',3,3,0,0),
('EE104','2023-01-01','English for Communication',               'Theory',    1,'EE',2,2,0,0),
('EE105','2023-01-01','Engineering Drawing',                     'Theory',    1,'EE',2,2,0,0),
('EE191','2023-01-01','Physics Lab',                             'Sessional', 1,'EE',1,0,0,3),
('EE192','2023-01-01','Chemistry Lab',                           'Sessional', 1,'EE',1,0,0,3),
('EE193','2023-01-01','Workshop Practice',                       'Sessional', 1,'EE',1,0,0,3),
('EE201','2023-01-01','Mathematics II',                          'Theory',    2,'EE',4,3,1,0),
('EE202','2023-01-01','Circuit Theory',                          'Theory',    2,'EE',4,3,1,0),
('EE203','2023-01-01','Electromagnetic Theory',                   'Theory',    2,'EE',4,3,1,0),
('EE204','2023-01-01','Electronics Devices & Circuits',          'Theory',    2,'EE',3,3,0,0),
('EE205','2023-01-01','Programming in C',                        'Theory',    2,'EE',3,3,0,0),
('EE291','2023-01-01','Circuits Lab',                            'Sessional', 2,'EE',2,0,0,3),
('EE292','2023-01-01','Electronics Lab',                         'Sessional', 2,'EE',2,0,0,3),
('EE293','2023-01-01','Programming Lab',                         'Sessional', 2,'EE',1,0,0,3),
('EE301','2023-01-01','Mathematics III',                         'Theory',    3,'EE',4,3,1,0),
('EE302','2023-01-01','Electrical Machines I',                   'Theory',    3,'EE',4,3,1,0),
('EE303','2023-01-01','Power Systems I',                         'Theory',    3,'EE',4,3,1,0),
('EE304','2023-01-01','Signals & Systems',                       'Theory',    3,'EE',4,3,1,0),
('EE305','2023-01-01','Analog Electronics',                      'Theory',    3,'EE',3,3,0,0),
('EE391','2023-01-01','Electrical Machines Lab I',               'Sessional', 3,'EE',2,0,0,3),
('EE392','2023-01-01','Analog Electronics Lab',                  'Sessional', 3,'EE',2,0,0,3),
('EE393','2023-01-01','Simulation Lab',                          'Sessional', 3,'EE',1,0,0,3),
('EE401','2023-01-01','Electrical Machines II',                  'Theory',    4,'EE',4,3,1,0),
('EE402','2023-01-01','Power Systems II',                        'Theory',    4,'EE',4,3,1,0),
('EE403','2023-01-01','Control Systems',                         'Theory',    4,'EE',4,3,1,0),
('EE404','2023-01-01','Digital Electronics',                     'Theory',    4,'EE',3,3,0,0),
('EE405','2023-01-01','Measurement & Instrumentation',           'Theory',    4,'EE',3,3,0,0),
('EE491','2023-01-01','Electrical Machines Lab II',              'Sessional', 4,'EE',2,0,0,3),
('EE492','2023-01-01','Control Systems Lab',                     'Sessional', 4,'EE',2,0,0,3),
('EE493','2023-01-01','Measurement Lab',                         'Sessional', 4,'EE',1,0,0,3),
('EE501','2023-01-01','Power Electronics',                       'Theory',    5,'EE',4,3,1,0),
('EE502','2023-01-01','Power System Protection',                 'Theory',    5,'EE',4,3,1,0),
('EE503','2023-01-01','Electric Drives',                         'Theory',    5,'EE',4,3,1,0),
('EE504','2023-01-01','Elective I',                              'Theory',    5,'EE',3,3,0,0),
('EE505','2023-01-01','Elective II',                             'Theory',    5,'EE',3,3,0,0),
('EE591','2023-01-01','Power Electronics Lab',                   'Sessional', 5,'EE',2,0,0,3),
('EE592','2023-01-01','Electric Drives Lab',                     'Sessional', 5,'EE',2,0,0,3),
('EE593','2023-01-01','Mini Project I',                          'Sessional', 5,'EE',2,0,0,3),
('EE601','2023-01-01','High Voltage Engineering',                'Theory',    6,'EE',3,3,0,0),
('EE602','2023-01-01','Renewable Energy Systems',                'Theory',    6,'EE',3,3,0,0),
('EE603','2023-01-01','Elective III',                            'Theory',    6,'EE',3,3,0,0),
('EE604','2023-01-01','Elective IV',                             'Theory',    6,'EE',3,3,0,0),
('EE605','2023-01-01','Open Elective I',                         'Theory',    6,'EE',3,3,0,0),
('EE691','2023-01-01','High Voltage Lab',                        'Sessional', 6,'EE',2,0,0,3),
('EE692','2023-01-01','Mini Project II',                         'Sessional', 6,'EE',2,0,0,3),
('EE693','2023-01-01','Seminar',                                 'Sessional', 6,'EE',1,0,0,0),
('EE701','2023-01-01','Smart Grid Technology',                   'Theory',    7,'EE',3,3,0,0),
('EE702','2023-01-01','Elective V',                              'Theory',    7,'EE',3,3,0,0),
('EE703','2023-01-01','Open Elective II',                        'Theory',    7,'EE',3,3,0,0),
('EE791','2023-01-01','Project Work Phase I',                    'Sessional', 7,'EE',6,0,0,12),
('EE801','2023-01-01','Elective VI',                             'Theory',    8,'EE',3,3,0,0),
('EE891','2023-01-01','Project Work Phase II',                   'Sessional', 8,'EE',10,0,0,20),
('EE892','2023-01-01','Comprehensive Viva',                      'Sessional', 8,'EE',2,0,0,0);

-- ============================================================
-- ME — Mechanical Engineering  (UG, 8 Semesters)
-- ============================================================
INSERT IGNORE INTO `subjects_pool`
    (`subject_code`,`year_of_introduction`,`subject_name`,`subject_type`,`subject_semester`,`taught_in`,`credit`,`lecture_hours`,`tutorial_hours`,`practical_hours`)
VALUES
('ME101','2023-01-01','Mathematics I',                           'Theory',    1,'ME',4,3,1,0),
('ME102','2023-01-01','Engineering Physics',                     'Theory',    1,'ME',4,3,1,0),
('ME103','2023-01-01','Engineering Chemistry',                   'Theory',    1,'ME',3,3,0,0),
('ME104','2023-01-01','English for Communication',               'Theory',    1,'ME',2,2,0,0),
('ME105','2023-01-01','Engineering Drawing',                     'Theory',    1,'ME',3,2,1,0),
('ME191','2023-01-01','Physics Lab',                             'Sessional', 1,'ME',1,0,0,3),
('ME192','2023-01-01','Chemistry Lab',                           'Sessional', 1,'ME',1,0,0,3),
('ME193','2023-01-01','Workshop Practice',                       'Sessional', 1,'ME',2,0,0,6),
('ME201','2023-01-01','Mathematics II',                          'Theory',    2,'ME',4,3,1,0),
('ME202','2023-01-01','Thermodynamics',                          'Theory',    2,'ME',4,3,1,0),
('ME203','2023-01-01','Engineering Mechanics',                   'Theory',    2,'ME',4,3,1,0),
('ME204','2023-01-01','Material Science',                        'Theory',    2,'ME',3,3,0,0),
('ME205','2023-01-01','Basics of Electrical Engineering',        'Theory',    2,'ME',3,3,0,0),
('ME291','2023-01-01','Thermodynamics Lab',                      'Sessional', 2,'ME',1,0,0,3),
('ME292','2023-01-01','Material Science Lab',                    'Sessional', 2,'ME',1,0,0,3),
('ME293','2023-01-01','Computer Aided Drawing',                  'Sessional', 2,'ME',2,0,0,3),
('ME301','2023-01-01','Mathematics III',                         'Theory',    3,'ME',3,3,0,0),
('ME302','2023-01-01','Mechanics of Solids',                     'Theory',    3,'ME',4,3,1,0),
('ME303','2023-01-01','Fluid Mechanics',                         'Theory',    3,'ME',4,3,1,0),
('ME304','2023-01-01','Manufacturing Processes I',               'Theory',    3,'ME',4,3,1,0),
('ME305','2023-01-01','Kinematics of Machines',                  'Theory',    3,'ME',3,3,0,0),
('ME391','2023-01-01','Fluid Mechanics Lab',                     'Sessional', 3,'ME',2,0,0,3),
('ME392','2023-01-01','Manufacturing Processes Lab',             'Sessional', 3,'ME',2,0,0,3),
('ME393','2023-01-01','Mechanics of Solids Lab',                 'Sessional', 3,'ME',1,0,0,3),
('ME401','2023-01-01','Heat Transfer',                           'Theory',    4,'ME',4,3,1,0),
('ME402','2023-01-01','Dynamics of Machines',                    'Theory',    4,'ME',4,3,1,0),
('ME403','2023-01-01','Manufacturing Processes II',              'Theory',    4,'ME',4,3,1,0),
('ME404','2023-01-01','Metrology & Quality Control',             'Theory',    4,'ME',3,3,0,0),
('ME405','2023-01-01','Industrial Engineering',                  'Theory',    4,'ME',3,3,0,0),
('ME491','2023-01-01','Heat Transfer Lab',                       'Sessional', 4,'ME',2,0,0,3),
('ME492','2023-01-01','Dynamics Lab',                            'Sessional', 4,'ME',2,0,0,3),
('ME493','2023-01-01','Metrology Lab',                           'Sessional', 4,'ME',1,0,0,3),
('ME501','2023-01-01','Refrigeration & Air Conditioning',        'Theory',    5,'ME',4,3,1,0),
('ME502','2023-01-01','Machine Design I',                        'Theory',    5,'ME',4,3,1,0),
('ME503','2023-01-01','Turbomachinery',                          'Theory',    5,'ME',4,3,1,0),
('ME504','2023-01-01','Elective I',                              'Theory',    5,'ME',3,3,0,0),
('ME505','2023-01-01','Elective II',                             'Theory',    5,'ME',3,3,0,0),
('ME591','2023-01-01','RAC Lab',                                 'Sessional', 5,'ME',2,0,0,3),
('ME592','2023-01-01','Machine Design Lab',                      'Sessional', 5,'ME',2,0,0,3),
('ME593','2023-01-01','Mini Project I',                          'Sessional', 5,'ME',2,0,0,3),
('ME601','2023-01-01','Finite Element Analysis',                 'Theory',    6,'ME',3,3,0,0),
('ME602','2023-01-01','Machine Design II',                       'Theory',    6,'ME',4,3,1,0),
('ME603','2023-01-01','Elective III',                            'Theory',    6,'ME',3,3,0,0),
('ME604','2023-01-01','Elective IV',                             'Theory',    6,'ME',3,3,0,0),
('ME605','2023-01-01','Open Elective I',                         'Theory',    6,'ME',3,3,0,0),
('ME691','2023-01-01','FEA Lab',                                 'Sessional', 6,'ME',2,0,0,3),
('ME692','2023-01-01','Mini Project II',                         'Sessional', 6,'ME',2,0,0,3),
('ME693','2023-01-01','Seminar',                                 'Sessional', 6,'ME',1,0,0,0),
('ME701','2023-01-01','Elective V',                              'Theory',    7,'ME',3,3,0,0),
('ME702','2023-01-01','Open Elective II',                        'Theory',    7,'ME',3,3,0,0),
('ME791','2023-01-01','Project Work Phase I',                    'Sessional', 7,'ME',6,0,0,12),
('ME801','2023-01-01','Elective VI',                             'Theory',    8,'ME',3,3,0,0),
('ME891','2023-01-01','Project Work Phase II',                   'Sessional', 8,'ME',10,0,0,20),
('ME892','2023-01-01','Comprehensive Viva',                      'Sessional', 8,'ME',2,0,0,0);

-- ============================================================
-- CE — Civil Engineering  (UG, 8 Semesters)
-- ============================================================
INSERT IGNORE INTO `subjects_pool`
    (`subject_code`,`year_of_introduction`,`subject_name`,`subject_type`,`subject_semester`,`taught_in`,`credit`,`lecture_hours`,`tutorial_hours`,`practical_hours`)
VALUES
('CE101','2023-01-01','Mathematics I',                           'Theory',    1,'CE',4,3,1,0),
('CE102','2023-01-01','Engineering Physics',                     'Theory',    1,'CE',4,3,1,0),
('CE103','2023-01-01','Engineering Chemistry',                   'Theory',    1,'CE',3,3,0,0),
('CE104','2023-01-01','English for Communication',               'Theory',    1,'CE',2,2,0,0),
('CE105','2023-01-01','Engineering Drawing',                     'Theory',    1,'CE',3,2,1,0),
('CE191','2023-01-01','Physics Lab',                             'Sessional', 1,'CE',1,0,0,3),
('CE192','2023-01-01','Chemistry Lab',                           'Sessional', 1,'CE',1,0,0,3),
('CE193','2023-01-01','Drawing Sessional',                       'Sessional', 1,'CE',2,0,0,3),
('CE201','2023-01-01','Mathematics II',                          'Theory',    2,'CE',4,3,1,0),
('CE202','2023-01-01','Building Materials & Construction',       'Theory',    2,'CE',3,3,0,0),
('CE203','2023-01-01','Engineering Mechanics',                   'Theory',    2,'CE',4,3,1,0),
('CE204','2023-01-01','Surveying I',                             'Theory',    2,'CE',3,3,0,0),
('CE205','2023-01-01','Fluid Mechanics I',                       'Theory',    2,'CE',3,3,0,0),
('CE291','2023-01-01','Building Materials Lab',                  'Sessional', 2,'CE',1,0,0,3),
('CE292','2023-01-01','Surveying Lab I',                         'Sessional', 2,'CE',2,0,0,3),
('CE293','2023-01-01','Fluid Mechanics Lab I',                   'Sessional', 2,'CE',1,0,0,3),
('CE301','2023-01-01','Mathematics III',                         'Theory',    3,'CE',3,3,0,0),
('CE302','2023-01-01','Mechanics of Solids',                     'Theory',    3,'CE',4,3,1,0),
('CE303','2023-01-01','Fluid Mechanics II',                      'Theory',    3,'CE',4,3,1,0),
('CE304','2023-01-01','Surveying II',                            'Theory',    3,'CE',3,3,0,0),
('CE305','2023-01-01','Soil Mechanics',                          'Theory',    3,'CE',4,3,1,0),
('CE391','2023-01-01','Mechanics of Solids Lab',                 'Sessional', 3,'CE',2,0,0,3),
('CE392','2023-01-01','Fluid Mechanics Lab II',                  'Sessional', 3,'CE',2,0,0,3),
('CE393','2023-01-01','Soil Mechanics Lab',                      'Sessional', 3,'CE',1,0,0,3),
('CE401','2023-01-01','Structural Analysis I',                   'Theory',    4,'CE',4,3,1,0),
('CE402','2023-01-01','Foundation Engineering',                  'Theory',    4,'CE',4,3,1,0),
('CE403','2023-01-01','Transportation Engineering I',            'Theory',    4,'CE',3,3,0,0),
('CE404','2023-01-01','Water Resources Engineering',             'Theory',    4,'CE',4,3,1,0),
('CE405','2023-01-01','Environmental Engineering I',             'Theory',    4,'CE',3,3,0,0),
('CE491','2023-01-01','Structural Analysis Lab',                 'Sessional', 4,'CE',2,0,0,3),
('CE492','2023-01-01','Foundation Engg Lab',                     'Sessional', 4,'CE',2,0,0,3),
('CE493','2023-01-01','Surveying Lab II',                        'Sessional', 4,'CE',1,0,0,3),
('CE501','2023-01-01','Structural Analysis II',                  'Theory',    5,'CE',4,3,1,0),
('CE502','2023-01-01','Design of Steel Structures',              'Theory',    5,'CE',4,3,1,0),
('CE503','2023-01-01','Environmental Engineering II',            'Theory',    5,'CE',3,3,0,0),
('CE504','2023-01-01','Elective I',                              'Theory',    5,'CE',3,3,0,0),
('CE505','2023-01-01','Elective II',                             'Theory',    5,'CE',3,3,0,0),
('CE591','2023-01-01','Concrete Technology Lab',                 'Sessional', 5,'CE',2,0,0,3),
('CE592','2023-01-01','Environmental Engg Lab',                  'Sessional', 5,'CE',2,0,0,3),
('CE593','2023-01-01','Mini Project I',                          'Sessional', 5,'CE',2,0,0,3),
('CE601','2023-01-01','Design of RCC Structures',                'Theory',    6,'CE',4,3,1,0),
('CE602','2023-01-01','Transportation Engineering II',           'Theory',    6,'CE',3,3,0,0),
('CE603','2023-01-01','Elective III',                            'Theory',    6,'CE',3,3,0,0),
('CE604','2023-01-01','Elective IV',                             'Theory',    6,'CE',3,3,0,0),
('CE605','2023-01-01','Open Elective I',                         'Theory',    6,'CE',3,3,0,0),
('CE691','2023-01-01','RCC Lab',                                 'Sessional', 6,'CE',2,0,0,3),
('CE692','2023-01-01','Mini Project II',                         'Sessional', 6,'CE',2,0,0,3),
('CE693','2023-01-01','Seminar',                                 'Sessional', 6,'CE',1,0,0,0),
('CE701','2023-01-01','Elective V',                              'Theory',    7,'CE',3,3,0,0),
('CE702','2023-01-01','Open Elective II',                        'Theory',    7,'CE',3,3,0,0),
('CE791','2023-01-01','Project Work Phase I',                    'Sessional', 7,'CE',6,0,0,12),
('CE801','2023-01-01','Elective VI',                             'Theory',    8,'CE',3,3,0,0),
('CE891','2023-01-01','Project Work Phase II',                   'Sessional', 8,'CE',10,0,0,20),
('CE892','2023-01-01','Comprehensive Viva',                      'Sessional', 8,'CE',2,0,0,0);

-- ============================================================
-- ECE — Electronics & Communication Engineering
-- ============================================================
INSERT IGNORE INTO `subjects_pool`
    (`subject_code`,`year_of_introduction`,`subject_name`,`subject_type`,`subject_semester`,`taught_in`,`credit`,`lecture_hours`,`tutorial_hours`,`practical_hours`)
VALUES
('ECE101','2023-01-01','Mathematics I',                          'Theory',    1,'ECE',4,3,1,0),
('ECE102','2023-01-01','Engineering Physics',                    'Theory',    1,'ECE',4,3,1,0),
('ECE103','2023-01-01','Engineering Chemistry',                  'Theory',    1,'ECE',3,3,0,0),
('ECE104','2023-01-01','English for Communication',              'Theory',    1,'ECE',2,2,0,0),
('ECE105','2023-01-01','Basic Electrical Engineering',           'Theory',    1,'ECE',3,3,0,0),
('ECE191','2023-01-01','Physics Lab',                            'Sessional', 1,'ECE',1,0,0,3),
('ECE192','2023-01-01','Chemistry Lab',                          'Sessional', 1,'ECE',1,0,0,3),
('ECE193','2023-01-01','Workshop Practice',                      'Sessional', 1,'ECE',1,0,0,3),
('ECE201','2023-01-01','Mathematics II',                         'Theory',    2,'ECE',4,3,1,0),
('ECE202','2023-01-01','Circuit Theory',                         'Theory',    2,'ECE',4,3,1,0),
('ECE203','2023-01-01','Electronic Devices & Circuits',          'Theory',    2,'ECE',4,3,1,0),
('ECE204','2023-01-01','Digital Electronics',                    'Theory',    2,'ECE',3,3,0,0),
('ECE205','2023-01-01','Programming in C',                       'Theory',    2,'ECE',3,3,0,0),
('ECE291','2023-01-01','Circuits Lab',                           'Sessional', 2,'ECE',2,0,0,3),
('ECE292','2023-01-01','Electronic Devices Lab',                 'Sessional', 2,'ECE',2,0,0,3),
('ECE293','2023-01-01','Digital Electronics Lab',                'Sessional', 2,'ECE',1,0,0,3),
('ECE301','2023-01-01','Mathematics III (Signals & Systems)',     'Theory',    3,'ECE',4,3,1,0),
('ECE302','2023-01-01','Analog Communication',                   'Theory',    3,'ECE',4,3,1,0),
('ECE303','2023-01-01','Electromagnetic Fields & Waves',         'Theory',    3,'ECE',4,3,1,0),
('ECE304','2023-01-01','Microprocessors',                        'Theory',    3,'ECE',4,3,1,0),
('ECE305','2023-01-01','Network Theory',                         'Theory',    3,'ECE',3,3,0,0),
('ECE391','2023-01-01','Communication Lab I',                    'Sessional', 3,'ECE',2,0,0,3),
('ECE392','2023-01-01','Microprocessors Lab',                    'Sessional', 3,'ECE',2,0,0,3),
('ECE393','2023-01-01','Network Lab',                            'Sessional', 3,'ECE',1,0,0,3),
('ECE401','2023-01-01','Digital Communication',                  'Theory',    4,'ECE',4,3,1,0),
('ECE402','2023-01-01','VLSI Design',                            'Theory',    4,'ECE',4,3,1,0),
('ECE403','2023-01-01','Antenna & Wave Propagation',             'Theory',    4,'ECE',4,3,1,0),
('ECE404','2023-01-01','Control Systems',                        'Theory',    4,'ECE',3,3,0,0),
('ECE405','2023-01-01','Digital Signal Processing',              'Theory',    4,'ECE',4,3,1,0),
('ECE491','2023-01-01','VLSI Lab',                               'Sessional', 4,'ECE',2,0,0,3),
('ECE492','2023-01-01','DSP Lab',                                'Sessional', 4,'ECE',2,0,0,3),
('ECE493','2023-01-01','Communication Lab II',                   'Sessional', 4,'ECE',1,0,0,3),
('ECE501','2023-01-01','Wireless Communication',                 'Theory',    5,'ECE',4,3,1,0),
('ECE502','2023-01-01','Microwave Engineering',                  'Theory',    5,'ECE',4,3,1,0),
('ECE503','2023-01-01','Embedded Systems',                       'Theory',    5,'ECE',3,3,0,0),
('ECE504','2023-01-01','Elective I',                             'Theory',    5,'ECE',3,3,0,0),
('ECE505','2023-01-01','Elective II',                            'Theory',    5,'ECE',3,3,0,0),
('ECE591','2023-01-01','Wireless Lab',                           'Sessional', 5,'ECE',2,0,0,3),
('ECE592','2023-01-01','Embedded Systems Lab',                   'Sessional', 5,'ECE',2,0,0,3),
('ECE593','2023-01-01','Mini Project I',                         'Sessional', 5,'ECE',2,0,0,3),
('ECE601','2023-01-01','Optical Fiber Communication',            'Theory',    6,'ECE',3,3,0,0),
('ECE602','2023-01-01','IoT & Sensor Networks',                  'Theory',    6,'ECE',3,3,0,0),
('ECE603','2023-01-01','Elective III',                           'Theory',    6,'ECE',3,3,0,0),
('ECE604','2023-01-01','Elective IV',                            'Theory',    6,'ECE',3,3,0,0),
('ECE605','2023-01-01','Open Elective I',                        'Theory',    6,'ECE',3,3,0,0),
('ECE691','2023-01-01','Optical Comm Lab',                       'Sessional', 6,'ECE',2,0,0,3),
('ECE692','2023-01-01','Mini Project II',                        'Sessional', 6,'ECE',2,0,0,3),
('ECE693','2023-01-01','Seminar',                                'Sessional', 6,'ECE',1,0,0,0),
('ECE701','2023-01-01','5G & Beyond',                            'Theory',    7,'ECE',3,3,0,0),
('ECE702','2023-01-01','Elective V',                             'Theory',    7,'ECE',3,3,0,0),
('ECE791','2023-01-01','Project Work Phase I',                   'Sessional', 7,'ECE',6,0,0,12),
('ECE801','2023-01-01','Elective VI',                            'Theory',    8,'ECE',3,3,0,0),
('ECE891','2023-01-01','Project Work Phase II',                  'Sessional', 8,'ECE',10,0,0,20),
('ECE892','2023-01-01','Comprehensive Viva',                     'Sessional', 8,'ECE',2,0,0,0);

-- ============================================================
-- IT — Information Technology  (UG, 8 Semesters)
-- ============================================================
INSERT IGNORE INTO `subjects_pool`
    (`subject_code`,`year_of_introduction`,`subject_name`,`subject_type`,`subject_semester`,`taught_in`,`credit`,`lecture_hours`,`tutorial_hours`,`practical_hours`)
VALUES
('IT101','2023-01-01','Mathematics I',                           'Theory',    1,'IT',4,3,1,0),
('IT102','2023-01-01','Engineering Physics',                     'Theory',    1,'IT',4,3,1,0),
('IT103','2023-01-01','Engineering Chemistry',                   'Theory',    1,'IT',3,3,0,0),
('IT104','2023-01-01','English for Communication',               'Theory',    1,'IT',2,2,0,0),
('IT105','2023-01-01','Basics of Electrical Engineering',        'Theory',    1,'IT',3,3,0,0),
('IT191','2023-01-01','Physics Lab',                             'Sessional', 1,'IT',1,0,0,3),
('IT192','2023-01-01','Chemistry Lab',                           'Sessional', 1,'IT',1,0,0,3),
('IT193','2023-01-01','Workshop Practice',                       'Sessional', 1,'IT',1,0,0,3),
('IT201','2023-01-01','Mathematics II',                          'Theory',    2,'IT',4,3,1,0),
('IT202','2023-01-01','Data Structures',                         'Theory',    2,'IT',4,3,1,0),
('IT203','2023-01-01','Digital Logic & Computer Organization',   'Theory',    2,'IT',4,3,1,0),
('IT204','2023-01-01','Object Oriented Programming',             'Theory',    2,'IT',3,3,0,0),
('IT205','2023-01-01','Discrete Mathematics',                    'Theory',    2,'IT',3,3,0,0),
('IT291','2023-01-01','Data Structures Lab',                     'Sessional', 2,'IT',2,0,0,3),
('IT292','2023-01-01','OOP Lab',                                 'Sessional', 2,'IT',2,0,0,3),
('IT293','2023-01-01','Digital Logic Lab',                       'Sessional', 2,'IT',1,0,0,3),
('IT301','2023-01-01','Mathematics III',                         'Theory',    3,'IT',3,3,0,0),
('IT302','2023-01-01','Algorithms',                              'Theory',    3,'IT',4,3,1,0),
('IT303','2023-01-01','Operating Systems',                       'Theory',    3,'IT',4,3,1,0),
('IT304','2023-01-01','Database Systems',                        'Theory',    3,'IT',4,3,1,0),
('IT305','2023-01-01','Web Technologies',                        'Theory',    3,'IT',3,3,0,0),
('IT391','2023-01-01','Algorithms Lab',                          'Sessional', 3,'IT',2,0,0,3),
('IT392','2023-01-01','OS Lab',                                  'Sessional', 3,'IT',2,0,0,3),
('IT393','2023-01-01','Web Technology Lab',                      'Sessional', 3,'IT',1,0,0,3),
('IT401','2023-01-01','Computer Networks',                       'Theory',    4,'IT',4,3,1,0),
('IT402','2023-01-01','Software Engineering',                    'Theory',    4,'IT',3,3,0,0),
('IT403','2023-01-01','Information Security',                    'Theory',    4,'IT',4,3,1,0),
('IT404','2023-01-01','Theory of Computation',                   'Theory',    4,'IT',4,3,1,0),
('IT405','2023-01-01','Numerical Methods',                       'Theory',    4,'IT',3,3,0,0),
('IT491','2023-01-01','Networks Lab',                            'Sessional', 4,'IT',2,0,0,3),
('IT492','2023-01-01','Security Lab',                            'Sessional', 4,'IT',2,0,0,3),
('IT493','2023-01-01','Software Engg Lab',                       'Sessional', 4,'IT',1,0,0,3),
('IT501','2023-01-01','Machine Learning',                        'Theory',    5,'IT',4,3,1,0),
('IT502','2023-01-01','Cloud Computing',                         'Theory',    5,'IT',3,3,0,0),
('IT503','2023-01-01','Mobile Application Development',          'Theory',    5,'IT',3,3,0,0),
('IT504','2023-01-01','Elective I',                              'Theory',    5,'IT',3,3,0,0),
('IT505','2023-01-01','Elective II',                             'Theory',    5,'IT',3,3,0,0),
('IT591','2023-01-01','ML Lab',                                  'Sessional', 5,'IT',2,0,0,3),
('IT592','2023-01-01','Mobile Dev Lab',                          'Sessional', 5,'IT',2,0,0,3),
('IT593','2023-01-01','Mini Project I',                          'Sessional', 5,'IT',2,0,0,3),
('IT601','2023-01-01','Big Data & Analytics',                    'Theory',    6,'IT',3,3,0,0),
('IT602','2023-01-01','IoT Systems',                             'Theory',    6,'IT',3,3,0,0),
('IT603','2023-01-01','Elective III',                            'Theory',    6,'IT',3,3,0,0),
('IT604','2023-01-01','Elective IV',                             'Theory',    6,'IT',3,3,0,0),
('IT605','2023-01-01','Open Elective I',                         'Theory',    6,'IT',3,3,0,0),
('IT691','2023-01-01','Big Data Lab',                            'Sessional', 6,'IT',2,0,0,3),
('IT692','2023-01-01','Mini Project II',                         'Sessional', 6,'IT',2,0,0,3),
('IT693','2023-01-01','Seminar',                                 'Sessional', 6,'IT',1,0,0,0),
('IT701','2023-01-01','Elective V',                              'Theory',    7,'IT',3,3,0,0),
('IT702','2023-01-01','Open Elective II',                        'Theory',    7,'IT',3,3,0,0),
('IT791','2023-01-01','Project Work Phase I',                    'Sessional', 7,'IT',6,0,0,12),
('IT801','2023-01-01','Elective VI',                             'Theory',    8,'IT',3,3,0,0),
('IT891','2023-01-01','Project Work Phase II',                   'Sessional', 8,'IT',10,0,0,20),
('IT892','2023-01-01','Comprehensive Viva',                      'Sessional', 8,'IT',2,0,0,0);

-- ============================================================
-- MCA — Master of Computer Applications  (PG, 4 Semesters)
-- ============================================================
INSERT IGNORE INTO `subjects_pool`
    (`subject_code`,`year_of_introduction`,`subject_name`,`subject_type`,`subject_semester`,`taught_in`,`credit`,`lecture_hours`,`tutorial_hours`,`practical_hours`)
VALUES
('MCA101','2023-01-01','Discrete Mathematics & Logic',           'Theory',    1,'MCA',4,3,1,0),
('MCA102','2023-01-01','Programming in C & C++',                 'Theory',    1,'MCA',4,3,1,0),
('MCA103','2023-01-01','Data Structures',                        'Theory',    1,'MCA',4,3,1,0),
('MCA104','2023-01-01','Computer Organization',                  'Theory',    1,'MCA',3,3,0,0),
('MCA105','2023-01-01','Accounting & Financial Management',      'Theory',    1,'MCA',3,3,0,0),
('MCA191','2023-01-01','Programming Lab',                        'Sessional', 1,'MCA',2,0,0,3),
('MCA192','2023-01-01','Data Structures Lab',                    'Sessional', 1,'MCA',2,0,0,3),
('MCA201','2023-01-01','Operating Systems',                      'Theory',    2,'MCA',4,3,1,0),
('MCA202','2023-01-01','Database Management Systems',            'Theory',    2,'MCA',4,3,1,0),
('MCA203','2023-01-01','Java Programming',                       'Theory',    2,'MCA',4,3,1,0),
('MCA204','2023-01-01','Computer Networks',                      'Theory',    2,'MCA',3,3,0,0),
('MCA205','2023-01-01','Software Engineering',                   'Theory',    2,'MCA',3,3,0,0),
('MCA291','2023-01-01','DBMS Lab',                               'Sessional', 2,'MCA',2,0,0,3),
('MCA292','2023-01-01','Java Lab',                               'Sessional', 2,'MCA',2,0,0,3),
('MCA301','2023-01-01','Web Technologies',                       'Theory',    3,'MCA',4,3,1,0),
('MCA302','2023-01-01','Machine Learning',                       'Theory',    3,'MCA',4,3,1,0),
('MCA303','2023-01-01','Information Security',                   'Theory',    3,'MCA',3,3,0,0),
('MCA304','2023-01-01','Elective I',                             'Theory',    3,'MCA',3,3,0,0),
('MCA391','2023-01-01','Web Technologies Lab',                   'Sessional', 3,'MCA',2,0,0,3),
('MCA392','2023-01-01','ML Lab',                                 'Sessional', 3,'MCA',2,0,0,3),
('MCA393','2023-01-01','Mini Project',                           'Sessional', 3,'MCA',2,0,0,3),
('MCA401','2023-01-01','Elective II',                            'Theory',    4,'MCA',3,3,0,0),
('MCA491','2023-01-01','Project Work',                           'Sessional', 4,'MCA',10,0,0,20),
('MCA492','2023-01-01','Comprehensive Viva',                     'Sessional', 4,'MCA',2,0,0,0);

-- ============================================================
-- Update demo student registration_no if missing
-- ============================================================
UPDATE `student_info` SET `registration_no` = '2024-CSTB-001'
WHERE `student_id` = 3 AND (`registration_no` IS NULL OR `registration_no` = '');
