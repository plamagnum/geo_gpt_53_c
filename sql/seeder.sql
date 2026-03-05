USE school_tests;

INSERT INTO classes (name) VALUES ('5'), ('6'), ('7'), ('8'), ('9'), ('10'), ('11')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (name, password_hash, class_id, role)
SELECT 'admin', '$2y$10$ypCa6roZyeevgQ4WenZFse.hQggUdeMKD5bAZ.jXZM.39ZIbdi7Xa', NULL, 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE name='admin');

INSERT INTO subjects (class_id, name)
SELECT (SELECT id FROM classes WHERE name='5'), 'Математика'
WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE name='Математика');

INSERT INTO topics (subject_id, name)
SELECT (SELECT id FROM subjects WHERE name='Математика' LIMIT 1), 'Дроби'
WHERE NOT EXISTS (SELECT 1 FROM topics WHERE name='Дроби');

INSERT INTO tests (topic_id, title, created_by)
SELECT (SELECT id FROM topics WHERE name='Дроби' LIMIT 1), 'Базовий тест: Дроби', 1
WHERE NOT EXISTS (SELECT 1 FROM tests WHERE title='Базовий тест: Дроби');

INSERT INTO questions (test_id, text)
SELECT (SELECT id FROM tests WHERE title='Базовий тест: Дроби' LIMIT 1), 'Який дріб дорівнює 0.5?'
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE text='Який дріб дорівнює 0.5?');

SET @q1 = (SELECT id FROM questions WHERE text='Який дріб дорівнює 0.5?' LIMIT 1);
INSERT INTO options (question_id, text, is_correct)
SELECT @q1, '1/4', 0 UNION ALL
SELECT @q1, '1/2', 1 UNION ALL
SELECT @q1, '2/5', 0 UNION ALL
SELECT @q1, '3/8', 0;
