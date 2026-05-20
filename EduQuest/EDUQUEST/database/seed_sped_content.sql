-- ============================================================
-- SPED-ALIGNED LESSON CONTENT
-- Based on actual SPED teacher lesson plans.
-- Run AFTER schema_learning_modules.sql
-- This replaces placeholder seed data with real curriculum.
-- ============================================================

USE eduquest;

-- ============================================================
-- CLEAR OLD SEED DATA (preserve tables, remove old rows)
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE student_quiz_attempts;
TRUNCATE TABLE student_lesson_progress;
TRUNCATE TABLE student_subject_progress;
TRUNCATE TABLE quiz_answers;
TRUNCATE TABLE quiz_questions;
TRUNCATE TABLE quizzes;
TRUNCATE TABLE lesson_content;
TRUNCATE TABLE lessons;
TRUNCATE TABLE subjects;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SUBJECTS
-- ============================================================
INSERT INTO subjects (id, slug, title, description, icon, color, bg_color, sort_order) VALUES
(1, 'math',      'Math Adventures',   'Learn about numbers, ordering, comparing, ordinals, and money!', '🔢', '#ef4444', '#fef2f2', 1),
(2, 'self_care', 'Self Care & Science', 'Discover living things, weather, animals, and healthy food!',   '🌱', '#10b981', '#ecfdf5', 2),
(3, 'english',   'English Quest',      'Read, write, and build short CVC /Ii/ words!',                  '📖', '#3b82f6', '#eff6ff', 3);

-- ============================================================
-- MATH LESSONS (subject_id = 1)
-- Based on SPED teacher activities: ordering, comparing,
-- number words, ordinals, coins & bills
-- ============================================================
INSERT INTO lessons (id, subject_id, title, description, lesson_order, difficulty, xp_reward, estimated_minutes, icon, content_type) VALUES
(1,  1, 'Arrange Numbers (up to 100)',      'Learn to arrange numbers from smallest to biggest and biggest to smallest!', 1, 'easy',   30, 10, '🔢', 'interactive'),
(2,  1, 'Comparing Numbers',                'Learn about less than (<), greater than (>), and equal to (=)!',             2, 'easy',   30, 10, '⚖️', 'interactive'),
(3,  1, 'Number Words (up to 500)',          'Read and write numbers in words up to 500!',                                3, 'medium', 35, 12, '📝', 'mixed'),
(4,  1, 'Arrange Numbers (up to 500)',       'Arrange bigger numbers in increasing and decreasing order!',                4, 'medium', 35, 12, '🔢', 'interactive'),
(5,  1, 'Ordinal Numbers',                  'Learn 1st, 2nd, 3rd and beyond!',                                           5, 'easy',   30, 10, '🏅', 'mixed'),
(6,  1, 'Coins and Peso Bills',             'Recognize and count Philippine coins and peso bills!',                       6, 'medium', 40, 15, '💰', 'interactive');

-- ============================================================
-- SELF CARE / SCIENCE LESSONS (subject_id = 2)
-- Based on SPED teacher activities: living/non-living, weather,
-- animals, food, eating habits, food safety
-- ============================================================
INSERT INTO lessons (id, subject_id, title, description, lesson_order, difficulty, xp_reward, estimated_minutes, icon, content_type) VALUES
(7,  2, 'Living Things',                    'Identify and learn about living things around us!',                          1, 'easy',   25, 8,  '🌱', 'mixed'),
(8,  2, 'Non-Living Things',                'Identify and learn about non-living things!',                                2, 'easy',   25, 8,  '🪨', 'mixed'),
(9,  2, 'Kinds of Weather',                 'Discover the different kinds of weather!',                                   3, 'easy',   30, 10, '🌤️', 'mixed'),
(10, 2, 'Weather Activities & Clothes',     'Learn what activities to do and clothes to wear in different weather!',       4, 'easy',   30, 10, '👕', 'mixed'),
(11, 2, 'Animals: Pet, Farm & Zoo',         'Learn about pets, farm animals, and zoo animals!',                           5, 'medium', 35, 12, '🐾', 'interactive'),
(12, 2, 'Healthy and Unhealthy Food',       'Identify which foods are healthy and which are unhealthy!',                  6, 'easy',   30, 10, '🍎', 'mixed'),
(13, 2, 'Effects of Eating',               'Learn what happens when we eat healthy vs unhealthy food!',                   7, 'medium', 30, 10, '💪', 'mixed'),
(14, 2, 'Good Eating Habits',              'Discover good eating habits that keep you healthy!',                          8, 'easy',   25, 8,  '🥗', 'mixed'),
(15, 2, 'Keeping Food Clean and Safe',     'Learn how to keep food clean and safe to eat!',                               9, 'easy',   25, 8,  '🧼', 'mixed'),
(16, 2, 'Ready-to-Eat vs Raw Food',        'Learn which food is ready to eat and which needs cooking!',                  10, 'medium', 30, 10, '🍳', 'interactive');

-- ============================================================
-- ENGLISH LESSONS (subject_id = 3)
-- Based on SPED teacher activities: CVC /Ii/ words (reading,
-- writing, phrases, sentences)
-- ============================================================
INSERT INTO lessons (id, subject_id, title, description, lesson_order, difficulty, xp_reward, estimated_minutes, icon, content_type) VALUES
(17, 3, 'Short /Ii/ Words — Reading',      'Read short CVC words with the /Ii/ sound!',                                  1, 'easy',   25, 8,  '📖', 'interactive'),
(18, 3, 'Short /Ii/ Words — Writing',      'Practice writing short /Ii/ words!',                                          2, 'easy',   30, 10, '✏️', 'interactive'),
(19, 3, 'Short /Ii/ Phrases',              'Read phrases with short /Ii/ words!',                                         3, 'medium', 30, 10, '📄', 'mixed'),
(20, 3, 'Short /Ii/ Sentences',            'Read and write sentences with short /Ii/ words!',                             4, 'medium', 35, 12, '📝', 'mixed');


-- ============================================================
-- LESSON CONTENT PAGES
-- ============================================================

-- ── MATH 1: Arrange Numbers (up to 100) ───────────────────
INSERT INTO lesson_content (lesson_id, page_order, title, content_html, illustration, tip_text) VALUES
(1, 1, 'What is Number Order?',
'<h2>Let''s Learn About Number Order! 🔢</h2>
<p>Numbers have a special order. We can arrange them from <strong>smallest to biggest</strong> (increasing order) or from <strong>biggest to smallest</strong> (decreasing order).</p>
<div style="text-align:center;font-size:28px;margin:16px 0">2, 5, 8, 12, 20</div>
<p>⬆️ This is <strong>increasing order</strong> — the numbers get bigger!</p>
<div style="text-align:center;font-size:28px;margin:16px 0">20, 12, 8, 5, 2</div>
<p>⬇️ This is <strong>decreasing order</strong> — the numbers get smaller!</p>',
'illust-numbers', 'Think of climbing stairs: increasing goes UP, decreasing goes DOWN!'),

(1, 2, 'Increasing Order',
'<h2>Increasing Order (Smallest → Biggest) ⬆️</h2>
<p>To arrange numbers in <strong>increasing order</strong>, start with the smallest number and go to the biggest.</p>
<div style="background:#f0fdf4;padding:16px;border-radius:12px;margin:12px 0">
<p><strong>Example:</strong> Arrange 7, 3, 9, 1, 5</p>
<p>Step 1: Find the smallest → <strong>1</strong></p>
<p>Step 2: Find the next smallest → <strong>3</strong></p>
<p>Step 3: Keep going → <strong>5, 7, 9</strong></p>
<p>✅ Answer: <strong>1, 3, 5, 7, 9</strong></p>
</div>',
'illust-asc', 'Try this: arrange 4, 2, 6. The answer is 2, 4, 6!'),

(1, 3, 'Decreasing Order',
'<h2>Decreasing Order (Biggest → Smallest) ⬇️</h2>
<p>To arrange numbers in <strong>decreasing order</strong>, start with the biggest number and go to the smallest.</p>
<div style="background:#fef2f2;padding:16px;border-radius:12px;margin:12px 0">
<p><strong>Example:</strong> Arrange 15, 8, 22, 4, 11</p>
<p>Step 1: Find the biggest → <strong>22</strong></p>
<p>Step 2: Find the next biggest → <strong>15</strong></p>
<p>Step 3: Keep going → <strong>11, 8, 4</strong></p>
<p>✅ Answer: <strong>22, 15, 11, 8, 4</strong></p>
</div>',
'illust-desc', 'Remember: decreasing means going DOWN like a slide! 🛝'),

(1, 4, 'Practice Time!',
'<h2>Your Turn! 🎮</h2>
<p>You''ve learned about increasing and decreasing order. Great job!</p>
<p>Now try the <strong>Arrange Numbers</strong> game in the Learning Games section!</p>
<div style="background:#eff6ff;padding:16px;border-radius:12px;margin:16px 0;text-align:center">
<p>🎮 <strong>Play the Arrange Numbers game</strong> to practice!</p>
<p>📝 Then take the <strong>quiz</strong> to test your skills!</p>
</div>',
'illust-celebrate', 'Practice makes perfect! You can play the game as many times as you want.');

-- ── MATH 2: Comparing Numbers ─────────────────────────────
INSERT INTO lesson_content (lesson_id, page_order, title, content_html, illustration, tip_text) VALUES
(2, 1, 'What is Comparing?',
'<h2>Comparing Numbers ⚖️</h2>
<p>When we compare two numbers, we figure out which one is <strong>bigger</strong>, <strong>smaller</strong>, or if they are <strong>the same</strong>.</p>
<p>We use three special symbols:</p>
<div style="display:flex;justify-content:center;gap:24px;margin:20px 0;flex-wrap:wrap">
<div style="text-align:center;padding:12px;background:#f0fdf4;border-radius:12px"><span style="font-size:36px">&lt;</span><br><strong>Less than</strong></div>
<div style="text-align:center;padding:12px;background:#eff6ff;border-radius:12px"><span style="font-size:36px">=</span><br><strong>Equal to</strong></div>
<div style="text-align:center;padding:12px;background:#fef2f2;border-radius:12px"><span style="font-size:36px">&gt;</span><br><strong>Greater than</strong></div>
</div>',
'illust-compare', 'The "mouth" of < and > always eats the bigger number! 🐊'),

(2, 2, 'Less Than and Greater Than',
'<h2>Less Than &lt; and Greater Than &gt; 🐊</h2>
<p><strong>Less than (&lt;)</strong> means the first number is SMALLER.</p>
<div style="text-align:center;font-size:32px;margin:12px 0">3 <span style="color:#ef4444">&lt;</span> 7</div>
<p>3 is less than 7 (3 is smaller!)</p>
<p><strong>Greater than (&gt;)</strong> means the first number is BIGGER.</p>
<div style="text-align:center;font-size:32px;margin:12px 0">10 <span style="color:#3b82f6">&gt;</span> 4</div>
<p>10 is greater than 4 (10 is bigger!)</p>
<div style="background:#fef9c3;padding:12px;border-radius:12px;margin-top:16px">
💡 <strong>Trick:</strong> The crocodile mouth always opens toward the BIGGER number!
</div>',
'illust-croc', 'The hungry crocodile always wants to eat the bigger number!'),

(2, 3, 'Equal To',
'<h2>Equal To = 🤝</h2>
<p>When two numbers are <strong>the same</strong>, we use the equals sign (=).</p>
<div style="text-align:center;font-size:32px;margin:16px 0">5 <span style="color:#10b981">=</span> 5</div>
<p>5 is equal to 5 — they are the same!</p>
<div style="text-align:center;font-size:32px;margin:16px 0">8 <span style="color:#10b981">=</span> 8</div>
<p>8 is equal to 8 — also the same!</p>',
'illust-equal', 'Equal means both sides have the same value — like a balanced seesaw!'),

(2, 4, 'Practice Time!',
'<h2>Let''s Practice! 🎮</h2>
<p>Now you know <strong>&lt;</strong>, <strong>&gt;</strong>, and <strong>=</strong>!</p>
<p>Try the <strong>Compare Numbers</strong> game in Learning Games!</p>
<div style="background:#eff6ff;padding:16px;border-radius:12px;margin:16px 0;text-align:center">
<p>🎮 Play the <strong>Compare Numbers</strong> game!</p>
<p>📝 Then take the quiz!</p>
</div>',
'illust-celebrate', 'Remember: the crocodile always eats the bigger number! 🐊');

-- ── SELF CARE 1: Living Things ────────────────────────────
INSERT INTO lesson_content (lesson_id, page_order, title, content_html, illustration, tip_text) VALUES
(7, 1, 'What are Living Things?',
'<h2>Living Things 🌱</h2>
<p>Living things are things that are <strong>alive</strong>! They can:</p>
<ul style="font-size:16px;line-height:2">
<li>🌱 <strong>Grow</strong> — they get bigger over time</li>
<li>🍽️ <strong>Eat</strong> — they need food or nutrients</li>
<li>🫁 <strong>Breathe</strong> — they need air</li>
<li>🏃 <strong>Move</strong> — they can move around (or grow toward light)</li>
<li>👶 <strong>Reproduce</strong> — they can make babies or seeds</li>
</ul>',
'illust-living', 'You are a living thing too! You grow, eat, breathe, and move!'),

(7, 2, 'Examples of Living Things',
'<h2>Can You Name These Living Things? 🌍</h2>
<div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin:16px 0">
<div style="text-align:center;padding:16px;background:#f0fdf4;border-radius:14px;min-width:100px"><span style="font-size:40px">🐕</span><br><strong>Dog</strong></div>
<div style="text-align:center;padding:16px;background:#f0fdf4;border-radius:14px;min-width:100px"><span style="font-size:40px">🌳</span><br><strong>Tree</strong></div>
<div style="text-align:center;padding:16px;background:#f0fdf4;border-radius:14px;min-width:100px"><span style="font-size:40px">🐟</span><br><strong>Fish</strong></div>
<div style="text-align:center;padding:16px;background:#f0fdf4;border-radius:14px;min-width:100px"><span style="font-size:40px">🌻</span><br><strong>Flower</strong></div>
<div style="text-align:center;padding:16px;background:#f0fdf4;border-radius:14px;min-width:100px"><span style="font-size:40px">🐛</span><br><strong>Insect</strong></div>
<div style="text-align:center;padding:16px;background:#f0fdf4;border-radius:14px;min-width:100px"><span style="font-size:40px">👦</span><br><strong>Person</strong></div>
</div>
<p>Animals, plants, insects, and people are all <strong>living things</strong>!</p>',
'illust-nature', 'Plants are living things even though they don''t walk — they grow toward sunlight!'),

(7, 3, 'Practice Time!',
'<h2>Let''s Practice! 🎮</h2>
<p>Now play the <strong>Living or Non-Living?</strong> game!</p>
<p>Can you sort which things are living and which are non-living?</p>
<div style="background:#ecfdf5;padding:16px;border-radius:12px;margin:16px 0;text-align:center">
<p>🎮 Play the <strong>Living or Non-Living?</strong> game!</p>
<p>📝 Then take the quiz!</p>
</div>',
'illust-celebrate', 'Living things need food, water, and air to survive!');

-- ── ENGLISH 1: Short /Ii/ Words — Reading ─────────────────
INSERT INTO lesson_content (lesson_id, page_order, title, content_html, illustration, tip_text) VALUES
(17, 1, 'The Short /Ii/ Sound',
'<h2>The Short /Ii/ Sound 🔤</h2>
<p>The letter <strong>I</strong> makes a short sound like in these words:</p>
<div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin:20px 0">
<div style="text-align:center;padding:14px 20px;background:#eff6ff;border-radius:14px"><span style="font-size:36px">🪑</span><br><strong style="font-size:24px">S<span style="color:#3b82f6">I</span>T</strong></div>
<div style="text-align:center;padding:14px 20px;background:#eff6ff;border-radius:14px"><span style="font-size:36px">📌</span><br><strong style="font-size:24px">P<span style="color:#3b82f6">I</span>N</strong></div>
<div style="text-align:center;padding:14px 20px;background:#eff6ff;border-radius:14px"><span style="font-size:24px">⛏️</span><br><strong style="font-size:24px">D<span style="color:#3b82f6">I</span>G</strong></div>
<div style="text-align:center;padding:14px 20px;background:#eff6ff;border-radius:14px"><span style="font-size:36px">🐷</span><br><strong style="font-size:24px">P<span style="color:#3b82f6">I</span>G</strong></div>
<div style="text-align:center;padding:14px 20px;background:#eff6ff;border-radius:14px"><span style="font-size:36px">6️⃣</span><br><strong style="font-size:24px">S<span style="color:#3b82f6">I</span>X</strong></div>
</div>
<p>These are <strong>CVC words</strong> — Consonant, Vowel, Consonant!</p>',
'illust-abc', 'Say the /i/ sound: it sounds like "ih" — short and quick!'),

(17, 2, 'More /Ii/ Words',
'<h2>More Words with Short /Ii/ 📖</h2>
<div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin:16px 0">
<div style="padding:10px 18px;background:#dbeafe;border-radius:10px;font-size:20px;font-weight:700">BIG</div>
<div style="padding:10px 18px;background:#dbeafe;border-radius:10px;font-size:20px;font-weight:700">HIT</div>
<div style="padding:10px 18px;background:#dbeafe;border-radius:10px;font-size:20px;font-weight:700">LIP</div>
<div style="padding:10px 18px;background:#dbeafe;border-radius:10px;font-size:20px;font-weight:700">MIX</div>
<div style="padding:10px 18px;background:#dbeafe;border-radius:10px;font-size:20px;font-weight:700">WIG</div>
<div style="padding:10px 18px;background:#dbeafe;border-radius:10px;font-size:20px;font-weight:700">FIT</div>
<div style="padding:10px 18px;background:#dbeafe;border-radius:10px;font-size:20px;font-weight:700">RIP</div>
<div style="padding:10px 18px;background:#dbeafe;border-radius:10px;font-size:20px;font-weight:700">KID</div>
<div style="padding:10px 18px;background:#dbeafe;border-radius:10px;font-size:20px;font-weight:700">TIP</div>
<div style="padding:10px 18px;background:#dbeafe;border-radius:10px;font-size:20px;font-weight:700">FIN</div>
</div>
<p>Practice reading each word out loud. They all have the short /Ii/ sound!</p>',
'illust-letters', 'Try pointing at each word and saying it slowly: b-i-g, h-i-t, l-i-p...'),

(17, 3, 'Practice Time!',
'<h2>Let''s Build Words! 🎮</h2>
<p>Now try the <strong>Build CVC Words</strong> game!</p>
<p>You''ll see a picture and tap letters to build the word.</p>
<div style="background:#eff6ff;padding:16px;border-radius:12px;margin:16px 0;text-align:center">
<p>🔤 Play <strong>Build CVC Words</strong></p>
<p>📖 Play <strong>Read /Ii/ Words</strong></p>
<p>📝 Then take the quiz!</p>
</div>',
'illust-celebrate', 'Great job learning the short /Ii/ sound!');


-- ============================================================
-- QUIZZES
-- ============================================================

-- Quiz: Math 1 - Arrange Numbers
INSERT INTO quizzes (id, lesson_id, title, description, pass_percentage, xp_reward, max_attempts) VALUES
(1, 1, 'Number Ordering Quiz', 'Test your knowledge of increasing and decreasing order!', 60, 40, 5);

INSERT INTO quiz_questions (id, quiz_id, question_order, question_text, question_type, explanation, points) VALUES
(1, 1, 1, 'Which is in INCREASING order?', 'multiple_choice', 'Increasing order means smallest to biggest: 2, 5, 8, 12!', 1),
(2, 1, 2, 'Arrange from smallest to biggest: 9, 3, 7', 'multiple_choice', 'Smallest first: 3, 7, 9!', 1),
(3, 1, 3, 'Which is in DECREASING order?', 'multiple_choice', 'Decreasing means biggest to smallest: 15, 10, 6, 2!', 1),
(4, 1, 4, 'True or False: 1, 5, 3, 8 is in increasing order.', 'true_false', 'No! 1, 3, 5, 8 would be increasing, but 5 comes before 3 here.', 1);

INSERT INTO quiz_answers (question_id, answer_text, is_correct, answer_order) VALUES
(1, '2, 5, 8, 12', 1, 1), (1, '12, 8, 5, 2', 0, 2), (1, '5, 2, 12, 8', 0, 3), (1, '8, 12, 2, 5', 0, 4),
(2, '9, 7, 3', 0, 1), (2, '3, 7, 9', 1, 2), (2, '7, 3, 9', 0, 3), (2, '3, 9, 7', 0, 4),
(3, '15, 10, 6, 2', 1, 1), (3, '2, 6, 10, 15', 0, 2), (3, '10, 15, 2, 6', 0, 3), (3, '6, 2, 15, 10', 0, 4),
(4, 'True', 0, 1), (4, 'False', 1, 2);

-- Quiz: Math 2 - Comparing Numbers
INSERT INTO quizzes (id, lesson_id, title, description, pass_percentage, xp_reward, max_attempts) VALUES
(2, 2, 'Comparing Numbers Quiz', 'Show what you know about <, >, and =!', 60, 40, 5);

INSERT INTO quiz_questions (id, quiz_id, question_order, question_text, question_type, explanation, points) VALUES
(5, 2, 1, '5 ___ 8. What symbol goes in the blank?', 'multiple_choice', '5 is less than 8, so the answer is < !', 1),
(6, 2, 2, '12 ___ 12. What symbol goes in the blank?', 'multiple_choice', '12 and 12 are the same, so the answer is = !', 1),
(7, 2, 3, '20 ___ 15. What symbol goes in the blank?', 'multiple_choice', '20 is greater than 15, so the answer is > !', 1),
(8, 2, 4, 'True or False: 7 > 3 means 7 is greater than 3', 'true_false', 'Yes! The > symbol means greater than.', 1);

INSERT INTO quiz_answers (question_id, answer_text, is_correct, answer_order) VALUES
(5, '<', 1, 1), (5, '>', 0, 2), (5, '=', 0, 3),
(6, '<', 0, 1), (6, '>', 0, 2), (6, '=', 1, 3),
(7, '<', 0, 1), (7, '>', 1, 2), (7, '=', 0, 3),
(8, 'True', 1, 1), (8, 'False', 0, 2);

-- Quiz: Self Care 1 - Living Things
INSERT INTO quizzes (id, lesson_id, title, description, pass_percentage, xp_reward, max_attempts) VALUES
(3, 7, 'Living Things Quiz', 'Can you tell what is living?', 60, 40, 5);

INSERT INTO quiz_questions (id, quiz_id, question_order, question_text, question_type, explanation, points) VALUES
(9,  3, 1, 'Which of these is a LIVING thing?', 'multiple_choice', 'A dog is alive — it breathes, eats, and grows!', 1),
(10, 3, 2, 'True or False: A rock is a living thing.', 'true_false', 'A rock is NOT alive. It does not grow, eat, or breathe.', 1),
(11, 3, 3, 'Living things can ___.', 'multiple_choice', 'Living things can grow, eat, breathe, and move!', 1),
(12, 3, 4, 'Which is NOT a living thing?', 'multiple_choice', 'A chair is not alive — it was made by people.', 1);

INSERT INTO quiz_answers (question_id, answer_text, is_correct, answer_order) VALUES
(9, '🐕 Dog', 1, 1), (9, '🪨 Rock', 0, 2), (9, '🪑 Chair', 0, 3), (9, '📱 Phone', 0, 4),
(10, 'True', 0, 1), (10, 'False', 1, 2),
(11, 'Grow and eat', 1, 1), (11, 'Stay the same forever', 0, 2), (11, 'Only change color', 0, 3), (11, 'Turn into a rock', 0, 4),
(12, '🐱 Cat', 0, 1), (12, '🌻 Flower', 0, 2), (12, '🪑 Chair', 1, 3), (12, '🐛 Bug', 0, 4);

-- Quiz: English 1 - Short /Ii/ Words
INSERT INTO quizzes (id, lesson_id, title, description, pass_percentage, xp_reward, max_attempts) VALUES
(4, 17, 'Short /Ii/ Words Quiz', 'Test your knowledge of CVC /Ii/ words!', 60, 40, 5);

INSERT INTO quiz_questions (id, quiz_id, question_order, question_text, question_type, explanation, points) VALUES
(13, 4, 1, 'What is the short /Ii/ word for 🪑?', 'multiple_choice', 'You SIT on a chair!', 1),
(14, 4, 2, 'Which word has the short /Ii/ sound?', 'multiple_choice', 'PIG has the short /i/ sound — p-i-g!', 1),
(15, 4, 3, 'Fill in the blank: The cat did d_g a hole.', 'multiple_choice', 'D-I-G = dig! The cat did dig a hole.', 1),
(16, 4, 4, 'True or False: BIG has the short /Ii/ sound.', 'true_false', 'Yes! B-I-G has the short /i/ sound.', 1);

INSERT INTO quiz_answers (question_id, answer_text, is_correct, answer_order) VALUES
(13, 'sat', 0, 1), (13, 'sit', 1, 2), (13, 'set', 0, 3), (13, 'sot', 0, 4),
(14, 'cup', 0, 1), (14, 'cat', 0, 2), (14, 'pig', 1, 3), (14, 'dog', 0, 4),
(15, 'a', 0, 1), (15, 'u', 0, 2), (15, 'i', 1, 3), (15, 'o', 0, 4),
(16, 'True', 1, 1), (16, 'False', 0, 2);
