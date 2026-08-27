-- Marksheet OCR + registration subject migration for project2.
-- Run this against the existing project2 application database.
-- It intentionally does not create or change database users/passwords.

START TRANSACTION;

-- Match marksheet subject-code collation with subjects_pool to avoid
-- "Illegal mix of collations" errors during joins/comparisons.
ALTER TABLE marksheet
    MODIFY subject_code varchar(32) COLLATE utf8mb4_0900_ai_ci NOT NULL;

-- Hide old/placeholder CST semester-1 subjects without deleting history.
UPDATE subjects_pool
SET taught_in = 'CST_OLD'
WHERE taught_in = 'CST'
  AND subject_semester = 1
  AND subject_code NOT IN (
      'AM1101', 'CH1101', 'CS1101', 'HU1101', 'MA1101',
      'AM1171', 'CH1171', 'CS1171', 'SA1171'
  );

-- Convert overlapping placeholder rows to the real marksheet subjects.
UPDATE subjects_pool
SET year_of_introduction = '2024-01-01',
    subject_name = 'Introduction to Computing',
    subject_type = 'Theory',
    subject_semester = 1,
    taught_in = 'CST',
    credit = 3,
    lecture_hours = 3,
    tutorial_hours = 0,
    practical_hours = 0
WHERE subject_code = 'CS1101'
  AND taught_in = 'CST'
  AND subject_semester = 1;

UPDATE subjects_pool
SET year_of_introduction = '2024-01-01',
    subject_name = 'Computer Lab.',
    subject_type = 'Sessional',
    subject_semester = 1,
    taught_in = 'CST',
    credit = 2,
    lecture_hours = 0,
    tutorial_hours = 0,
    practical_hours = 3
WHERE subject_code = 'CS1171'
  AND taught_in = 'CST'
  AND subject_semester = 1;

-- Upsert the finalized CST semester-1 subject set.
INSERT INTO subjects_pool
    (subject_code, year_of_introduction, subject_name, subject_type,
     subject_semester, taught_in, credit, lecture_hours, tutorial_hours, practical_hours)
VALUES
    ('AM1101', '2024-01-01', 'Mechanics', 'Theory', 1, 'CST', 4, 3, 1, 0),
    ('CH1101', '2024-01-01', 'Chemistry', 'Theory', 1, 'CST', 3, 3, 0, 0),
    ('CS1101', '2024-01-01', 'Introduction to Computing', 'Theory', 1, 'CST', 3, 3, 0, 0),
    ('HU1101', '2024-01-01', 'Professional Communication in English', 'Theory', 1, 'CST', 3, 3, 0, 0),
    ('MA1101', '2024-01-01', 'Mathematics I', 'Theory', 1, 'CST', 4, 3, 1, 0),
    ('AM1171', '2024-01-01', 'Drawing Practice', 'Sessional', 1, 'CST', 3, 0, 0, 3),
    ('CH1171', '2024-01-01', 'Chemistry Lab.', 'Sessional', 1, 'CST', 2, 0, 0, 3),
    ('CS1171', '2024-01-01', 'Computer Lab.', 'Sessional', 1, 'CST', 2, 0, 0, 3),
    ('SA1171', '2024-01-01', 'NSS/NCC/PT/Yoga', 'Sessional', 1, 'CST', 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE
    subject_name = VALUES(subject_name),
    subject_type = VALUES(subject_type),
    subject_semester = VALUES(subject_semester),
    taught_in = VALUES(taught_in),
    credit = VALUES(credit),
    lecture_hours = VALUES(lecture_hours),
    tutorial_hours = VALUES(tutorial_hours),
    practical_hours = VALUES(practical_hours);

COMMIT;

