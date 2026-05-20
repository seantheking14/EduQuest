-- ============================================================
-- LEARNING MODULES SYSTEM
-- Subjects, lessons, quizzes, and student progress tracking
-- for Math, Self Care, and English
-- Run this against the eduquest database.
-- ============================================================

USE eduquest;

-- ============================================================
-- SUBJECTS (Math, Self Care, English)
-- ============================================================
CREATE TABLE IF NOT EXISTS subjects (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug            VARCHAR(50)     NOT NULL UNIQUE,       -- 'math', 'self_care', 'english'
    title           VARCHAR(100)    NOT NULL,
    description     TEXT,
    icon            VARCHAR(10)     DEFAULT '📚',           -- emoji icon
    color           VARCHAR(7)      DEFAULT '#6366f1',      -- theme color hex
    bg_color        VARCHAR(7)      DEFAULT '#eef2ff',      -- light background
    sort_order      INT UNSIGNED    DEFAULT 0,
    is_active       TINYINT(1)      DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- LESSONS (units within each subject)
-- ============================================================
CREATE TABLE IF NOT EXISTS lessons (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id      INT UNSIGNED    NOT NULL,
    title           VARCHAR(255)    NOT NULL,
    description     TEXT,
    lesson_order    INT UNSIGNED    DEFAULT 0,              -- sequence within subject
    difficulty      ENUM('easy','medium','hard') DEFAULT 'easy',
    xp_reward       INT UNSIGNED    DEFAULT 30,             -- XP for completing lesson
    estimated_minutes INT UNSIGNED  DEFAULT 10,             -- estimated time
    icon            VARCHAR(10)     DEFAULT '📖',
    content_type    ENUM('reading','interactive','video','mixed') DEFAULT 'mixed',
    is_active       TINYINT(1)      DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_lesson_subject FOREIGN KEY (subject_id)
        REFERENCES subjects(id) ON DELETE CASCADE
);

-- ============================================================
-- LESSON CONTENT (slides/pages within a lesson)
-- ============================================================
CREATE TABLE IF NOT EXISTS lesson_content (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lesson_id       INT UNSIGNED    NOT NULL,
    page_order      INT UNSIGNED    DEFAULT 0,
    title           VARCHAR(255),
    content_html    TEXT            NOT NULL,                -- HTML content rendered in viewer
    illustration    VARCHAR(100),                            -- CSS class for 2D illustration
    tip_text        TEXT,                                    -- optional hint / fun fact
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_content_lesson FOREIGN KEY (lesson_id)
        REFERENCES lessons(id) ON DELETE CASCADE
);

-- ============================================================
-- QUIZZES (attached to lessons)
-- ============================================================
CREATE TABLE IF NOT EXISTS quizzes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lesson_id       INT UNSIGNED    NOT NULL,
    title           VARCHAR(255)    NOT NULL,
    description     TEXT,
    pass_percentage INT UNSIGNED    DEFAULT 70,             -- minimum % to pass
    xp_reward       INT UNSIGNED    DEFAULT 50,             -- bonus XP for passing
    max_attempts    INT UNSIGNED    DEFAULT 3,              -- 0 = unlimited
    time_limit_sec  INT UNSIGNED    DEFAULT 0,              -- 0 = no limit
    is_active       TINYINT(1)      DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_quiz_lesson FOREIGN KEY (lesson_id)
        REFERENCES lessons(id) ON DELETE CASCADE
);

-- ============================================================
-- QUIZ QUESTIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS quiz_questions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id         INT UNSIGNED    NOT NULL,
    question_order  INT UNSIGNED    DEFAULT 0,
    question_text   TEXT            NOT NULL,
    question_type   ENUM('multiple_choice','true_false','fill_blank') DEFAULT 'multiple_choice',
    illustration    VARCHAR(100),                            -- optional CSS illustration class
    explanation     TEXT,                                    -- shown after answering
    points          INT UNSIGNED    DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_question_quiz FOREIGN KEY (quiz_id)
        REFERENCES quizzes(id) ON DELETE CASCADE
);

-- ============================================================
-- QUIZ ANSWERS (options for each question)
-- ============================================================
CREATE TABLE IF NOT EXISTS quiz_answers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id     INT UNSIGNED    NOT NULL,
    answer_text     VARCHAR(500)    NOT NULL,
    is_correct      TINYINT(1)      DEFAULT 0,
    answer_order    INT UNSIGNED    DEFAULT 0,

    CONSTRAINT fk_answer_question FOREIGN KEY (question_id)
        REFERENCES quiz_questions(id) ON DELETE CASCADE
);

-- ============================================================
-- STUDENT SUBJECT PROGRESS (tracks which subject student is on)
-- ============================================================
CREATE TABLE IF NOT EXISTS student_subject_progress (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED    NOT NULL,
    subject_id      INT UNSIGNED    NOT NULL,
    status          ENUM('locked','active','completed') DEFAULT 'locked',
    started_at      TIMESTAMP       NULL,
    completed_at    TIMESTAMP       NULL,
    total_xp_earned INT UNSIGNED    DEFAULT 0,

    UNIQUE KEY uq_student_subject (student_id, subject_id),
    CONSTRAINT fk_sp_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_sp_subject FOREIGN KEY (subject_id)
        REFERENCES subjects(id) ON DELETE CASCADE
);

-- ============================================================
-- STUDENT LESSON PROGRESS
-- ============================================================
CREATE TABLE IF NOT EXISTS student_lesson_progress (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED    NOT NULL,
    lesson_id       INT UNSIGNED    NOT NULL,
    status          ENUM('locked','available','in_progress','completed') DEFAULT 'locked',
    current_page    INT UNSIGNED    DEFAULT 0,              -- last viewed page
    started_at      TIMESTAMP       NULL,
    completed_at    TIMESTAMP       NULL,
    xp_earned       INT UNSIGNED    DEFAULT 0,
    time_spent_sec  INT UNSIGNED    DEFAULT 0,

    UNIQUE KEY uq_student_lesson (student_id, lesson_id),
    CONSTRAINT fk_slp_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_slp_lesson FOREIGN KEY (lesson_id)
        REFERENCES lessons(id) ON DELETE CASCADE
);

-- ============================================================
-- STUDENT QUIZ ATTEMPTS
-- ============================================================
CREATE TABLE IF NOT EXISTS student_quiz_attempts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED    NOT NULL,
    quiz_id         INT UNSIGNED    NOT NULL,
    attempt_number  INT UNSIGNED    DEFAULT 1,
    score           INT UNSIGNED    DEFAULT 0,              -- points earned
    max_score       INT UNSIGNED    DEFAULT 0,              -- total possible
    percentage      DECIMAL(5,2)    DEFAULT 0.00,
    passed          TINYINT(1)      DEFAULT 0,
    time_spent_sec  INT UNSIGNED    DEFAULT 0,
    xp_earned       INT UNSIGNED    DEFAULT 0,
    answers_json    JSON,                                   -- { questionId: answerId }
    started_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    completed_at    TIMESTAMP       NULL,

    CONSTRAINT fk_sqa_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_sqa_quiz FOREIGN KEY (quiz_id)
        REFERENCES quizzes(id) ON DELETE CASCADE
);

-- ============================================================
-- INDEXES
-- ============================================================
CREATE INDEX idx_lessons_subject       ON lessons(subject_id);
CREATE INDEX idx_lessons_order         ON lessons(subject_id, lesson_order);
CREATE INDEX idx_content_lesson        ON lesson_content(lesson_id);
CREATE INDEX idx_content_order         ON lesson_content(lesson_id, page_order);
CREATE INDEX idx_quizzes_lesson        ON quizzes(lesson_id);
CREATE INDEX idx_questions_quiz        ON quiz_questions(quiz_id);
CREATE INDEX idx_answers_question      ON quiz_answers(question_id);
CREATE INDEX idx_sp_student            ON student_subject_progress(student_id);
CREATE INDEX idx_slp_student           ON student_lesson_progress(student_id);
CREATE INDEX idx_slp_lesson            ON student_lesson_progress(lesson_id);
CREATE INDEX idx_sqa_student           ON student_quiz_attempts(student_id);
CREATE INDEX idx_sqa_quiz              ON student_quiz_attempts(quiz_id);

-- ============================================================
-- SEED DATA: Subjects
-- ============================================================
INSERT INTO subjects (slug, title, description, icon, color, bg_color, sort_order) VALUES
('math',      'Math Adventures',    'Explore numbers, shapes, and puzzles in fun math quests!',     '🔢', '#ef4444', '#fef2f2', 1),
('self_care', 'Self Care Journey',  'Learn about emotions, mindfulness, and healthy habits!',        '🌱', '#10b981', '#ecfdf5', 2),
('english',   'English Quest',      'Master reading, writing, and storytelling through adventures!', '📖', '#3b82f6', '#eff6ff', 3);

-- ============================================================
-- SEED DATA: Math Lessons
-- ============================================================
INSERT INTO lessons (subject_id, title, description, lesson_order, difficulty, xp_reward, estimated_minutes, icon, content_type) VALUES
-- Math (subject_id = 1)
(1, 'Counting Fun',          'Learn to count from 1 to 20 with fun objects!',                1, 'easy',   25, 8,  '🎯', 'interactive'),
(1, 'Addition Adventures',   'Discover how to add numbers together!',                       2, 'easy',   30, 10, '➕', 'mixed'),
(1, 'Subtraction Quest',     'Learn to subtract and find the difference!',                   3, 'easy',   30, 10, '➖', 'mixed'),
(1, 'Shapes Explorer',       'Identify and learn about different shapes!',                   4, 'medium', 35, 12, '🔷', 'interactive'),
(1, 'Multiplication Magic',  'Unlock the power of multiplication!',                         5, 'medium', 40, 15, '✖️', 'mixed'),
(1, 'Division Discovery',    'Share equally and learn to divide!',                           6, 'medium', 40, 15, '➗', 'mixed'),
(1, 'Fractions Feast',       'Slice pizzas and pies to learn fractions!',                    7, 'hard',   50, 18, '🍕', 'interactive'),
(1, 'Math Champion',         'Put all your math skills to the ultimate test!',               8, 'hard',   60, 20, '🏆', 'mixed');

-- Self Care (subject_id = 2)
INSERT INTO lessons (subject_id, title, description, lesson_order, difficulty, xp_reward, estimated_minutes, icon, content_type) VALUES
(2, 'My Feelings',           'Learn to name and understand your emotions!',                  1, 'easy',   25, 8,  '😊', 'mixed'),
(2, 'Breathing Buddy',       'Practice calm breathing exercises!',                           2, 'easy',   25, 8,  '🌬️', 'interactive'),
(2, 'Healthy Habits',        'Discover daily habits that keep you strong!',                  3, 'easy',   30, 10, '💪', 'mixed'),
(2, 'Friendship Garden',     'Learn about being a good friend!',                             4, 'medium', 35, 12, '🌻', 'mixed'),
(2, 'Mindfulness Mountain',  'Climb the mountain of mindfulness!',                           5, 'medium', 35, 12, '⛰️', 'interactive'),
(2, 'Problem Solving Path',  'Learn steps to solve problems calmly!',                        6, 'medium', 40, 15, '🧩', 'mixed'),
(2, 'Self Care Champion',    'Show what you know about taking care of yourself!',             7, 'hard',   50, 18, '🌟', 'mixed');

-- English (subject_id = 3)
INSERT INTO lessons (subject_id, title, description, lesson_order, difficulty, xp_reward, estimated_minutes, icon, content_type) VALUES
(3, 'Alphabet Adventure',    'Meet all 26 letters and their sounds!',                        1, 'easy',   25, 8,  '🔤', 'interactive'),
(3, 'Sight Words Safari',    'Spot and learn common sight words!',                           2, 'easy',   30, 10, '👀', 'mixed'),
(3, 'Story Time',            'Read short stories and answer fun questions!',                 3, 'easy',   30, 10, '📚', 'mixed'),
(3, 'Spelling Spell',        'Cast spelling spells to form words correctly!',                4, 'medium', 35, 12, '✨', 'interactive'),
(3, 'Grammar Garden',        'Grow sentences in the grammar garden!',                        5, 'medium', 40, 15, '🌿', 'mixed'),
(3, 'Creative Writing',      'Write your own mini stories!',                                 6, 'hard',   45, 18, '✍️', 'mixed'),
(3, 'Reading Champion',      'Prove your reading skills in the final challenge!',            7, 'hard',   50, 20, '🏆', 'mixed');

-- ============================================================
-- SEED DATA: Lesson Content (sample pages for first lessons)
-- ============================================================

-- Math: Counting Fun (lesson_id = 1)
INSERT INTO lesson_content (lesson_id, page_order, title, content_html, illustration, tip_text) VALUES
(1, 1, 'Welcome to Counting!',
 '<h2>Let''s Count Together! 🎉</h2><p>Counting is one of the most important skills you''ll ever learn. Numbers are everywhere — from the fingers on your hand to the stars in the sky!</p><p>In this lesson, you''ll learn to count from <strong>1 to 20</strong> using fun objects.</p>',
 'illust-stars', 'Fun fact: The number zero was invented in India over 1,500 years ago!'),

(1, 2, 'Counting 1 to 5',
 '<h2>Numbers 1 to 5 🖐️</h2><div class="count-grid"><div class="count-item"><span class="count-num">1</span><span class="count-obj">🍎</span><span>One apple</span></div><div class="count-item"><span class="count-num">2</span><span class="count-obj">🍎🍎</span><span>Two apples</span></div><div class="count-item"><span class="count-num">3</span><span class="count-obj">🍎🍎🍎</span><span>Three apples</span></div><div class="count-item"><span class="count-num">4</span><span class="count-obj">🍎🍎🍎🍎</span><span>Four apples</span></div><div class="count-item"><span class="count-num">5</span><span class="count-obj">🍎🍎🍎🍎🍎</span><span>Five apples</span></div></div>',
 'illust-apples', 'You have 5 fingers on each hand — just like 5 apples!'),

(1, 3, 'Counting 6 to 10',
 '<h2>Numbers 6 to 10 ✋✋</h2><div class="count-grid"><div class="count-item"><span class="count-num">6</span><span class="count-obj">⭐⭐⭐⭐⭐⭐</span><span>Six stars</span></div><div class="count-item"><span class="count-num">7</span><span class="count-obj">⭐⭐⭐⭐⭐⭐⭐</span><span>Seven stars</span></div><div class="count-item"><span class="count-num">8</span><span class="count-obj">⭐⭐⭐⭐⭐⭐⭐⭐</span><span>Eight stars</span></div><div class="count-item"><span class="count-num">9</span><span class="count-obj">⭐⭐⭐⭐⭐⭐⭐⭐⭐</span><span>Nine stars</span></div><div class="count-item"><span class="count-num">10</span><span class="count-obj">⭐⭐⭐⭐⭐⭐⭐⭐⭐⭐</span><span>Ten stars</span></div></div>',
 'illust-stars', 'There are 10 digits: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9. Every number is made from these!'),

(1, 4, 'Counting to 20',
 '<h2>All the Way to 20! 🎊</h2><p>Great job! Now let''s count higher. After 10, we keep going!</p><div class="number-line"><span>11</span><span>12</span><span>13</span><span>14</span><span>15</span><span>16</span><span>17</span><span>18</span><span>19</span><span>20</span></div><p>Notice a pattern? After 10, we say the <strong>tens</strong> word first, then add 1-9!</p>',
 'illust-numbers', 'If you count all your fingers and toes, you get 20!'),

(1, 5, 'Practice Time!',
 '<h2>Let''s Practice! 🎮</h2><p>You''ve learned to count from 1 to 20. Amazing work!</p><p>Now take the quiz to test your counting skills and earn <strong>XP rewards</strong>!</p><div class="lesson-complete-box"><p>✅ Lesson Complete!</p><p>Take the quiz to earn bonus XP!</p></div>',
 'illust-celebrate', 'Practice counting objects around your room — books, toys, or crayons!');

-- Self Care: My Feelings (lesson_id = 9, assuming Math has IDs 1-8)
INSERT INTO lesson_content (lesson_id, page_order, title, content_html, illustration, tip_text) VALUES
(9, 1, 'Welcome to Feelings!',
 '<h2>Understanding Your Feelings 💛</h2><p>Everyone has feelings — happy, sad, angry, scared, and many more! Feelings are normal and important.</p><p>In this lesson, you''ll learn to <strong>name your feelings</strong> and understand why they happen.</p>',
 'illust-heart', 'It''s okay to feel any emotion. What matters is how we handle them!'),

(9, 2, 'Happy & Sad',
 '<h2>Happy 😊 and Sad 😢</h2><div class="feeling-cards"><div class="feeling-card happy"><div class="feeling-emoji">😊</div><h3>Happy</h3><p>You feel happy when good things happen — like playing with friends or getting a hug!</p></div><div class="feeling-card sad"><div class="feeling-emoji">😢</div><h3>Sad</h3><p>You feel sad when something doesn''t go well — like losing a toy or saying goodbye.</p></div></div><p>Both feelings are perfectly normal!</p>',
 'illust-feelings', 'When you''re sad, talking to someone you trust can help you feel better.'),

(9, 3, 'Angry & Scared',
 '<h2>Angry 😠 and Scared 😨</h2><div class="feeling-cards"><div class="feeling-card angry"><div class="feeling-emoji">😠</div><h3>Angry</h3><p>You feel angry when things seem unfair or when someone hurts your feelings.</p></div><div class="feeling-card scared"><div class="feeling-emoji">😨</div><h3>Scared</h3><p>You feel scared when something seems dangerous or unknown.</p></div></div><p>It''s okay to feel these. The important thing is what you <strong>do</strong> with the feeling.</p>',
 'illust-feelings', 'Taking 3 deep breaths can help when you feel angry or scared.'),

(9, 4, 'Feelings Check-In',
 '<h2>How Are You Feeling Right Now? 🌈</h2><p>Take a moment to check in with yourself.</p><div class="feelings-wheel"><div class="fw-item">😊 Happy</div><div class="fw-item">😢 Sad</div><div class="fw-item">😠 Angry</div><div class="fw-item">😨 Scared</div><div class="fw-item">😌 Calm</div><div class="fw-item">🤔 Confused</div><div class="fw-item">🥰 Loved</div><div class="fw-item">😤 Frustrated</div></div><p>There''s no wrong answer! Knowing how you feel is the first step.</p>',
 'illust-rainbow', 'You can do a feelings check-in anytime during the day!');

-- English: Alphabet Adventure (lesson_id = 16, assuming Math 1-8, Self Care 9-15)
INSERT INTO lesson_content (lesson_id, page_order, title, content_html, illustration, tip_text) VALUES
(16, 1, 'The Alphabet Awaits!',
 '<h2>Welcome to the Alphabet! 🔤</h2><p>The alphabet has <strong>26 letters</strong>. Each letter has a special sound and shape.</p><p>Let''s meet them together!</p><div class="alphabet-preview">A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</div>',
 'illust-abc', 'The word "alphabet" comes from the first two Greek letters: Alpha and Beta!'),

(16, 2, 'Letters A to F',
 '<h2>Meet A, B, C, D, E, F ✨</h2><div class="letter-cards"><div class="letter-card"><span class="big-letter">A</span><span class="letter-word">🍎 Apple</span></div><div class="letter-card"><span class="big-letter">B</span><span class="letter-word">🐻 Bear</span></div><div class="letter-card"><span class="big-letter">C</span><span class="letter-word">🐱 Cat</span></div><div class="letter-card"><span class="big-letter">D</span><span class="letter-word">🐕 Dog</span></div><div class="letter-card"><span class="big-letter">E</span><span class="letter-word">🐘 Elephant</span></div><div class="letter-card"><span class="big-letter">F</span><span class="letter-word">🐸 Frog</span></div></div>',
 'illust-letters', 'Can you think of another word that starts with each letter?'),

(16, 3, 'Letters G to L',
 '<h2>Meet G, H, I, J, K, L ✨</h2><div class="letter-cards"><div class="letter-card"><span class="big-letter">G</span><span class="letter-word">🍇 Grapes</span></div><div class="letter-card"><span class="big-letter">H</span><span class="letter-word">🏠 House</span></div><div class="letter-card"><span class="big-letter">I</span><span class="letter-word">🍦 Ice Cream</span></div><div class="letter-card"><span class="big-letter">J</span><span class="letter-word">🧃 Juice</span></div><div class="letter-card"><span class="big-letter">K</span><span class="letter-word">🪁 Kite</span></div><div class="letter-card"><span class="big-letter">L</span><span class="letter-word">🦁 Lion</span></div></div>',
 'illust-letters', 'The letter "L" looks like a foot! Can you see it?'),

(16, 4, 'The Rest of the Alphabet',
 '<h2>M all the way to Z! 🎉</h2><p>You''re doing amazing! Here are the rest of our letter friends.</p><div class="alphabet-grid-full"><span>M 🌙</span><span>N 🥜</span><span>O 🐙</span><span>P 🐧</span><span>Q 👑</span><span>R 🌈</span><span>S ⭐</span><span>T 🐢</span><span>U ☂️</span><span>V 🎻</span><span>W 🐋</span><span>X 🎸</span><span>Y 💛</span><span>Z 🦓</span></div>',
 'illust-alphabet', 'The most used letter in English is "E"!');

-- ============================================================
-- SEED DATA: Quizzes for first lessons
-- ============================================================

-- Quiz for Math: Counting Fun
INSERT INTO quizzes (lesson_id, title, description, pass_percentage, xp_reward, max_attempts) VALUES
(1, 'Counting Quiz', 'Test your counting skills!', 70, 40, 3);

-- Quiz questions (quiz_id = 1)
INSERT INTO quiz_questions (quiz_id, question_order, question_text, question_type, explanation, points) VALUES
(1, 1, 'How many apples are here? 🍎🍎🍎', 'multiple_choice', 'Count each apple: 1, 2, 3. There are 3 apples!', 1),
(1, 2, 'What number comes after 7?', 'multiple_choice', 'When counting: 6, 7, 8. The answer is 8!', 1),
(1, 3, 'How many stars? ⭐⭐⭐⭐⭐', 'multiple_choice', 'Count: 1, 2, 3, 4, 5. There are 5 stars!', 1),
(1, 4, 'What number comes before 10?', 'multiple_choice', 'Counting: 8, 9, 10. The number before 10 is 9!', 1),
(1, 5, 'True or False: 15 comes after 14', 'true_false', '14, 15, 16 — yes, 15 comes right after 14!', 1);

-- Answers for counting quiz
INSERT INTO quiz_answers (question_id, answer_text, is_correct, answer_order) VALUES
-- Q1: How many apples (3)
(1, '2', 0, 1), (1, '3', 1, 2), (1, '4', 0, 3), (1, '5', 0, 4),
-- Q2: After 7 (8)
(2, '6', 0, 1), (2, '7', 0, 2), (2, '8', 1, 3), (2, '9', 0, 4),
-- Q3: How many stars (5)
(3, '3', 0, 1), (3, '4', 0, 2), (3, '5', 1, 3), (3, '6', 0, 4),
-- Q4: Before 10 (9)
(4, '8', 0, 1), (4, '9', 1, 2), (4, '10', 0, 3), (4, '11', 0, 4),
-- Q5: True/False 15 after 14
(5, 'True', 1, 1), (5, 'False', 0, 2);

-- Quiz for Self Care: My Feelings
INSERT INTO quizzes (lesson_id, title, description, pass_percentage, xp_reward, max_attempts) VALUES
(9, 'Feelings Quiz', 'Show what you know about feelings!', 70, 40, 3);

-- Quiz questions (quiz_id = 2)
INSERT INTO quiz_questions (quiz_id, question_order, question_text, question_type, explanation, points) VALUES
(2, 1, 'Which emoji shows a HAPPY feeling?', 'multiple_choice', 'The smiling face 😊 shows happiness!', 1),
(2, 2, 'What can help when you feel angry?', 'multiple_choice', 'Taking deep breaths helps you calm down when angry.', 1),
(2, 3, 'True or False: It''s okay to feel sad sometimes', 'true_false', 'Yes! All feelings are normal and okay.', 1),
(2, 4, 'When your friend is sad, what should you do?', 'multiple_choice', 'Being kind and listening to your friend helps them feel better.', 1);

INSERT INTO quiz_answers (question_id, answer_text, is_correct, answer_order) VALUES
-- Q1: Happy emoji
(6, '😢', 0, 1), (6, '😊', 1, 2), (6, '😠', 0, 3), (6, '😨', 0, 4),
-- Q2: Help when angry
(7, 'Yell louder', 0, 1), (7, 'Take deep breaths', 1, 2), (7, 'Hit something', 0, 3), (7, 'Run away', 0, 4),
-- Q3: T/F sad is okay
(8, 'True', 1, 1), (8, 'False', 0, 2),
-- Q4: Friend is sad
(9, 'Laugh at them', 0, 1), (9, 'Ignore them', 0, 2), (9, 'Be kind and listen', 1, 3), (9, 'Walk away', 0, 4);

-- Quiz for English: Alphabet Adventure
INSERT INTO quizzes (lesson_id, title, description, pass_percentage, xp_reward, max_attempts) VALUES
(16, 'Alphabet Quiz', 'Test your letter knowledge!', 70, 40, 3);

-- Quiz questions (quiz_id = 3)
INSERT INTO quiz_questions (quiz_id, question_order, question_text, question_type, explanation, points) VALUES
(3, 1, 'What letter does APPLE start with?', 'multiple_choice', 'Apple starts with the letter A!', 1),
(3, 2, 'How many letters are in the alphabet?', 'multiple_choice', 'The English alphabet has 26 letters, from A to Z!', 1),
(3, 3, 'Which letter comes after B?', 'multiple_choice', 'A, B, C — the letter C comes after B!', 1),
(3, 4, 'True or False: The letter Z is the last letter', 'true_false', 'Yes! Z is the 26th and last letter of the alphabet.', 1);

INSERT INTO quiz_answers (question_id, answer_text, is_correct, answer_order) VALUES
-- Q1: Apple starts with
(10, 'B', 0, 1), (10, 'A', 1, 2), (10, 'P', 0, 3), (10, 'E', 0, 4),
-- Q2: How many letters
(11, '24', 0, 1), (11, '25', 0, 2), (11, '26', 1, 3), (11, '30', 0, 4),
-- Q3: After B
(12, 'A', 0, 1), (12, 'D', 0, 2), (12, 'C', 1, 3), (12, 'E', 0, 4),
-- Q4: Z is last
(13, 'True', 1, 1), (13, 'False', 0, 2);
