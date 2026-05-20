/* =========================================================
   Word Scramble — Game Engine
   ========================================================= */
(() => {
    'use strict';

    /* ── Word Banks ── */
    const WORDS = {
        english: [
            { word: 'APPLE', hint: 'A round fruit that keeps the doctor away' },
            { word: 'BRAVE', hint: 'Having courage to face danger' },
            { word: 'CLOUD', hint: 'White fluffy thing in the sky' },
            { word: 'DANCE', hint: 'Moving your body to music' },
            { word: 'EAGLE', hint: 'A large powerful bird' },
            { word: 'FLAME', hint: 'The glowing part of a fire' },
            { word: 'GRAPE', hint: 'Small fruit used to make wine' },
            { word: 'HOUSE', hint: 'A building where people live' },
            { word: 'IMAGE', hint: 'A picture or visual representation' },
            { word: 'JUICE', hint: 'A drink made from fruit' },
            { word: 'KNIFE', hint: 'A tool used for cutting' },
            { word: 'LEMON', hint: 'A sour yellow citrus fruit' },
            { word: 'MOUSE', hint: 'A small rodent or computer device' },
            { word: 'OCEAN', hint: 'A vast body of salt water' },
            { word: 'PIANO', hint: 'A musical instrument with keys' },
            { word: 'QUEEN', hint: 'A female ruler of a country' },
            { word: 'SMILE', hint: 'An expression of happiness' },
            { word: 'TIGER', hint: 'A striped big cat' },
            { word: 'VOICE', hint: 'Sound produced when speaking' },
            { word: 'WORLD', hint: 'The planet we live on' },
            { word: 'BLANKET', hint: 'A warm covering for your bed' },
            { word: 'CHAPTER', hint: 'A section of a book' },
            { word: 'DOLPHIN', hint: 'A smart ocean mammal' },
            { word: 'EPISODE', hint: 'One part of a TV series' },
            { word: 'FEATHER', hint: 'Light covering on a bird' },
            { word: 'KITCHEN', hint: 'Room where food is prepared' },
            { word: 'MORNING', hint: 'The early part of the day' },
            { word: 'PICTURE', hint: 'An image or photograph' },
            { word: 'RAINBOW', hint: 'Colorful arc in the sky after rain' },
            { word: 'CHICKEN', hint: 'A common farm bird' },
            { word: 'UMBRELLA', hint: 'Used for protection from rain' },
            { word: 'MOUNTAIN', hint: 'A very high natural landform' },
            { word: 'BIRTHDAY', hint: 'The anniversary of being born' },
            { word: 'SANDWICH', hint: 'Filling between two slices of bread' },
            { word: 'TREASURE', hint: 'Valuable hidden riches' },
            { word: 'ALPHABET', hint: 'The set of letters A to Z' },
            { word: 'ELEPHANT', hint: 'The largest land animal' },
            { word: 'NOTEBOOK', hint: 'A book for writing notes' },
            { word: 'TRIANGLE', hint: 'A shape with three sides' },
            { word: 'PINEAPPLE', hint: 'A tropical fruit with spiky skin' }
        ],
        math: [
            { word: 'ANGLE', hint: 'Space between two intersecting lines' },
            { word: 'DIGIT', hint: 'A single number symbol (0-9)' },
            { word: 'GRAPH', hint: 'Visual representation of data' },
            { word: 'MINUS', hint: 'Subtraction operator' },
            { word: 'PRIME', hint: 'Divisible only by 1 and itself' },
            { word: 'RATIO', hint: 'A comparison of two numbers' },
            { word: 'VALUE', hint: 'The worth or amount of something' },
            { word: 'WIDTH', hint: 'Measurement from side to side' },
            { word: 'EQUAL', hint: 'The same in value' },
            { word: 'SLOPE', hint: 'Steepness of a line' },
            { word: 'MEDIAN', hint: 'The middle value in a data set' },
            { word: 'FACTOR', hint: 'A number that divides evenly' },
            { word: 'SQUARE', hint: 'Shape with 4 equal sides or to multiply by itself' },
            { word: 'VOLUME', hint: 'Amount of space inside a 3D object' },
            { word: 'CIRCLE', hint: 'A perfectly round shape' },
            { word: 'RADIUS', hint: 'Distance from center to edge of a circle' },
            { word: 'DIVIDE', hint: 'To split into equal parts' },
            { word: 'WEIGHT', hint: 'How heavy something is' },
            { word: 'FORMULA', hint: 'A math rule written with symbols' },
            { word: 'PERCENT', hint: 'Parts per hundred' },
            { word: 'AVERAGE', hint: 'Sum divided by count' },
            { word: 'DECIMAL', hint: 'A number with a dot (like 3.14)' },
            { word: 'PRODUCT', hint: 'Result of multiplication' },
            { word: 'POLYGON', hint: 'A closed shape with straight sides' },
            { word: 'ALGEBRA', hint: 'Math with letters and symbols' },
            { word: 'FRACTION', hint: 'A part of a whole (like 1/2)' },
            { word: 'MULTIPLY', hint: 'Repeated addition shortcut' },
            { word: 'NEGATIVE', hint: 'A number less than zero' },
            { word: 'EQUATION', hint: 'A statement that two things are equal' },
            { word: 'DISTANCE', hint: 'How far apart two points are' },
            { word: 'GEOMETRY', hint: 'Study of shapes and spaces' },
            { word: 'ABSOLUTE', hint: 'Distance from zero on a number line' },
            { word: 'SYMMETRY', hint: 'When both halves are mirror images' },
            { word: 'DIAMETER', hint: 'Distance across a circle through its center' },
            { word: 'QUOTIENT', hint: 'Result of division' }
        ],
        selfcare: [
            { word: 'SLEEP', hint: 'Rest your body and mind at night' },
            { word: 'SMILE', hint: 'A happy facial expression' },
            { word: 'PEACE', hint: 'A state of calm and quiet' },
            { word: 'WATER', hint: 'Essential drink for hydration' },
            { word: 'RELAX', hint: 'To rest and reduce tension' },
            { word: 'HAPPY', hint: 'Feeling good and joyful' },
            { word: 'HEART', hint: 'Organ that pumps blood or symbol of love' },
            { word: 'MUSIC', hint: 'Sounds arranged to be pleasing' },
            { word: 'DREAM', hint: 'Images in your mind while sleeping' },
            { word: 'LIGHT', hint: 'What helps you see in darkness' },
            { word: 'KINDLY', hint: 'In a warm and caring way' },
            { word: 'BREATH', hint: 'Air in and out of your lungs' },
            { word: 'GENTLE', hint: 'Soft and careful in manner' },
            { word: 'FRIEND', hint: 'Someone you trust and enjoy being with' },
            { word: 'NATURE', hint: 'The natural world of plants and animals' },
            { word: 'ENERGY', hint: 'The power to be active and do things' },
            { word: 'JOYFUL', hint: 'Full of happiness and delight' },
            { word: 'HEALTH', hint: 'State of being free from illness' },
            { word: 'SAFETY', hint: 'Being protected from danger' },
            { word: 'GARDEN', hint: 'A place where plants are grown' },
            { word: 'BALANCE', hint: 'Equal distribution; stability in life' },
            { word: 'FEELING', hint: 'An emotional state or sensation' },
            { word: 'HEALTHY', hint: 'In good condition; not sick' },
            { word: 'MINDFUL', hint: 'Being aware of the present moment' },
            { word: 'COMFORT', hint: 'A state of ease and well-being' },
            { word: 'JOURNAL', hint: 'A book for writing your thoughts' },
            { word: 'COURAGE', hint: 'Bravery in the face of fear' },
            { word: 'STRETCH', hint: 'Extend your body to stay flexible' },
            { word: 'SUNRISE', hint: 'When the sun comes up in the morning' },
            { word: 'PATIENCE', hint: 'The ability to wait calmly' },
            { word: 'STRENGTH', hint: 'Physical or mental power' },
            { word: 'EXERCISE', hint: 'Physical activity for fitness' },
            { word: 'LAUGHTER', hint: 'The sound of being amused' },
            { word: 'GRATEFUL', hint: 'Feeling thankful; appreciative' },
            { word: 'POSITIVE', hint: 'Optimistic; focusing on the good' }
        ]
    };

    /* ── Difficulty Config ── */
    const DIFFICULTY = {
        easy:   { maxLen: 5, time: 30, rounds: 8,  scoreBase: 100 },
        medium: { maxLen: 7, time: 25, rounds: 10, scoreBase: 150 },
        hard:   { maxLen: 99, time: 20, rounds: 10, scoreBase: 200 }
    };

    /* ── State ── */
    let diff = 'easy';
    let cat  = 'english';
    let roundWords = [];
    let currentRound = 0;
    let score = 0;
    let streak = 0;
    let bestStreak = 0;
    let timer = 0;
    let timerInterval = null;
    let currentWord = '';
    let answer = [];
    let hintsUsed = 0;
    let correctCount = 0;

    /* ── Attempt tracking state ── */
    let _wsAttemptId = 0;
    let _wsGameStartTime = 0;

    /* ── DOM refs ── */
    const $ = id => document.getElementById(id);

    const startScreen   = $('startScreen');
    const gameScreen    = $('gameScreen');
    const resultsScreen = $('resultsScreen');
    const letterRack    = $('letterRack');
    const answerRack    = $('answerRack');
    const hintText      = $('hintText');
    const hudScore      = $('hudScore');
    const hudRound      = $('hudRound');
    const hudTimer      = $('hudTimer');
    const hudStreak     = $('hudStreak');
    const feedback      = $('feedback');
    const btnSubmit     = $('btnSubmit');
    const btnClear      = $('btnClear');
    const btnHint       = $('btnHint');

    /* ── Init ── */
    document.addEventListener('DOMContentLoaded', () => {
        // Difficulty buttons
        document.querySelectorAll('.diff-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.diff-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                diff = btn.dataset.diff;
            });
        });

        // Category buttons
        document.querySelectorAll('.cat-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                cat = btn.dataset.cat;
            });
        });

        $('btnPlay').addEventListener('click', async () => {
            await wsStartAttempt();
            startGame();
        });

        // Abandon attempt if student leaves mid-game
        window.addEventListener('beforeunload', () => wsAbandonAttempt());
        btnSubmit.addEventListener('click', submitAnswer);
        btnClear.addEventListener('click', clearAnswer);
        btnHint.addEventListener('click', showHint);
        $('btnPlayAgain').addEventListener('click', () => showScreen('start'));
        $('btnDashboard').addEventListener('click', () => {
            window.location.href = '../dashboard/dashboard.html';
        });

        // Show high score
        updateHighScoreDisplay();
        // Load attempt history from server
        fetchWsHistory();

        // Keyboard support
        document.addEventListener('keydown', handleKeyboard);
    });

    /* ── Screen Management ── */
    function showScreen(name) {
        startScreen.classList.add('hidden');
        gameScreen.classList.add('hidden');
        resultsScreen.classList.add('hidden');
        if (name === 'start')   startScreen.classList.remove('hidden');
        if (name === 'game')    gameScreen.classList.remove('hidden');
        if (name === 'results') resultsScreen.classList.remove('hidden');
    }

    /* ── Game Flow ── */
    function startGame() {
        const cfg = DIFFICULTY[diff];
        const pool = WORDS[cat].filter(w => {
            if (diff === 'easy')   return w.word.length <= 5;
            if (diff === 'medium') return w.word.length >= 5 && w.word.length <= 7;
            return w.word.length >= 7;
        });

        if (pool.length < cfg.rounds) {
            // Fall back to full category if filter is too strict
            roundWords = shuffle([...WORDS[cat]]).slice(0, cfg.rounds);
        } else {
            roundWords = shuffle([...pool]).slice(0, cfg.rounds);
        }

        currentRound = 0;
        score = 0;
        streak = 0;
        bestStreak = 0;
        hintsUsed = 0;
        correctCount = 0;

        showScreen('game');
        nextRound();
    }

    function nextRound() {
        if (currentRound >= roundWords.length) {
            endGame();
            return;
        }

        const entry = roundWords[currentRound];
        currentWord = entry.word;
        answer = new Array(currentWord.length).fill(null);

        // HUD
        hudScore.textContent = score;
        hudRound.textContent = `${currentRound + 1}/${roundWords.length}`;
        hudStreak.textContent = streak;
        hintText.textContent = '';

        // Timer
        clearInterval(timerInterval);
        timer = DIFFICULTY[diff].time;
        hudTimer.textContent = timer;
        $('hudTimerItem').classList.remove('warning');
        timerInterval = setInterval(tick, 1000);

        // Build racks
        buildLetterRack(currentWord);
        buildAnswerRack(currentWord.length);
    }

    function tick() {
        timer--;
        hudTimer.textContent = timer;
        if (timer <= 5) $('hudTimerItem').classList.add('warning');
        if (timer <= 0) {
            clearInterval(timerInterval);
            showFeedback(`Time's up! It was "${currentWord}"`, 'wrong');
            streak = 0;
            currentRound++;
            setTimeout(nextRound, 1600);
        }
    }

    /* ── Rack Builders ── */
    function buildLetterRack(word) {
        letterRack.innerHTML = '';
        const letters = shuffle(word.split(''));
        letters.forEach((ch, i) => {
            const tile = document.createElement('div');
            tile.className = 'letter-tile';
            tile.textContent = ch;
            tile.dataset.index = i;
            tile.addEventListener('click', () => placeLetter(tile));
            letterRack.appendChild(tile);
        });
    }

    function buildAnswerRack(len) {
        answerRack.innerHTML = '';
        for (let i = 0; i < len; i++) {
            const slot = document.createElement('div');
            slot.className = 'answer-slot';
            slot.dataset.pos = i;
            slot.addEventListener('click', () => removeLetter(slot, i));
            answerRack.appendChild(slot);
        }
    }

    /* ── Letter Placement ── */
    function placeLetter(tile) {
        if (tile.classList.contains('used')) return;
        const emptyIdx = answer.indexOf(null);
        if (emptyIdx === -1) return;

        answer[emptyIdx] = { letter: tile.textContent, tileIndex: tile.dataset.index };
        tile.classList.add('used');

        const slots = answerRack.querySelectorAll('.answer-slot');
        slots[emptyIdx].textContent = tile.textContent;
        slots[emptyIdx].classList.add('filled');
        slots[emptyIdx].dataset.tileIndex = tile.dataset.index;
    }

    function removeLetter(slot, pos) {
        if (!answer[pos]) return;
        const tileIdx = answer[pos].tileIndex;
        answer[pos] = null;
        slot.textContent = '';
        slot.classList.remove('filled');

        const tile = letterRack.querySelector(`[data-index="${tileIdx}"]`);
        if (tile) tile.classList.remove('used');
    }

    function clearAnswer() {
        answer.fill(null);
        letterRack.querySelectorAll('.letter-tile').forEach(t => t.classList.remove('used'));
        const slots = answerRack.querySelectorAll('.answer-slot');
        slots.forEach(s => { s.textContent = ''; s.classList.remove('filled', 'correct', 'wrong'); });
    }

    /* ── Submit ── */
    function submitAnswer() {
        if (answer.some(a => a === null)) {
            showFeedback('Fill all letters first!', 'info');
            return;
        }

        const guess = answer.map(a => a.letter).join('');
        const slots = answerRack.querySelectorAll('.answer-slot');

        if (guess === currentWord) {
            // Correct
            clearInterval(timerInterval);
            slots.forEach(s => s.classList.add('correct'));

            streak++;
            if (streak > bestStreak) bestStreak = streak;
            correctCount++;

            const timeBonus = Math.round(timer * 3);
            const streakBonus = Math.min(streak - 1, 5) * 20;
            const roundScore = DIFFICULTY[diff].scoreBase + timeBonus + streakBonus;
            score += roundScore;
            hudScore.textContent = score;
            hudStreak.textContent = streak;

            const msg = streak > 1
                ? `Correct! +${roundScore} pts (🔥 ${streak} streak)`
                : `Correct! +${roundScore} pts`;
            showFeedback(msg, 'correct');
            window.dispatchEvent(new CustomEvent('petReact', { detail: { type: streak >= 3 ? 'streak' : 'correct' } }));

            currentRound++;
            setTimeout(nextRound, 1200);
        } else {
            // Wrong
            slots.forEach((s, i) => {
                if (answer[i].letter === currentWord[i]) {
                    s.classList.add('correct');
                } else {
                    s.classList.add('wrong');
                }
            });

            streak = 0;
            hudStreak.textContent = 0;
            showFeedback('Not quite — try again!', 'wrong');
            window.dispatchEvent(new CustomEvent('petReact', { detail: { type: 'wrong' } }));

            setTimeout(() => {
                slots.forEach(s => s.classList.remove('correct', 'wrong'));
            }, 800);
        }
    }

    /* ── Hint ── */
    function showHint() {
        const entry = roundWords[currentRound];
        if (!entry) return;

        hintsUsed++;

        if (hintText.textContent === '') {
            hintText.textContent = `QUESTION HINT: ${entry.hint}`;
        } else {
            // Reveal a random unrevealed letter
            const emptySlots = answer
                .map((a, i) => a === null ? i : -1)
                .filter(i => i !== -1);
            if (emptySlots.length === 0) return;

            const revealIdx = emptySlots[Math.floor(Math.random() * emptySlots.length)];
            const neededLetter = currentWord[revealIdx];

            // Find unused tile with this letter
            const tiles = letterRack.querySelectorAll('.letter-tile:not(.used)');
            for (const tile of tiles) {
                if (tile.textContent === neededLetter) {
                    placeLetter(tile);
                    break;
                }
            }
        }
    }

    /* ── End Game ── */
    function endGame() {
        clearInterval(timerInterval);

        const totalRounds = roundWords.length;
        const accuracy = totalRounds > 0 ? Math.round((correctCount / totalRounds) * 100) : 0;
        const xpEarned = Math.round(score / 10);

        // Save high score
        const key = `ws_high_${diff}_${cat}`;
        const prev = parseInt(localStorage.getItem(key) || '0', 10);
        if (score > prev) localStorage.setItem(key, score);

        // Populate results
        const emoji = accuracy >= 80 ? '🏆' : accuracy >= 50 ? '⭐' : '💪';
        const title = accuracy >= 80 ? 'Amazing!' : accuracy >= 50 ? 'Good Job!' : 'Keep Practicing!';

        $('resultsEmoji').textContent = emoji;
        $('resultsTitle').textContent = title;
        $('resultsScore').textContent = score;
        $('resultsDetail').textContent = `${correctCount}/${totalRounds} correct · Best streak: ${bestStreak} · Hints: ${hintsUsed}`;
        $('resultsXP').textContent = `+${xpEarned} XP earned!`;

        showScreen('results');
        updateHighScoreDisplay();
        window.dispatchEvent(new CustomEvent('petReact', { detail: { type: 'complete' } }));

        // Gamified popup — celebrate completion
        if (window.showGamePopup) {
            const wsMsg = accuracy >= 80
                ? 'Incredible word skills! You\u2019re a Word Scramble champion!'
                : accuracy >= 50
                    ? 'Great job finishing the game! Keep unscrambling those words!'
                    : 'You finished! Practice makes perfect \u2014 give it another go!';
            showGamePopup({
                type:      'success',
                title:     accuracy >= 80 ? 'Word Master! \uD83C\uDF1F' : 'Game Complete! \uD83C\uDFAE',
                icon:      accuracy >= 80 ? '\uD83C\uDFC6' : '\uD83C\uDF1F',
                message:   wsMsg,
                confetti:  accuracy >= 50,
                autoClose: 4000,
            });
        }

        // Award XP via API
        awardXP(xpEarned);
        // Record completed attempt
        wsCompleteAttempt(xpEarned, totalRounds);
    }

    /* ── XP Award ── */
    async function awardXP(xp) {
        if (xp <= 0) return;
        const token = localStorage.getItem('eq_token');
        if (!token) return;

        try {
            await fetch('../../EDUQUEST/api/gamification/track-activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    activityType: 'game',
                    title: `Word Scramble · ${diff} · ${cat} · Score ${score}`
                })
            });
        } catch (e) {
            // Silently fail — game still works offline
        }
    }

    /* ── Attempt Tracking ── */
    async function wsStartAttempt() {
        const token = localStorage.getItem('eq_token');
        if (!token) return;
        _wsGameStartTime = Date.now();
        try {
            const res = await fetch('../../EDUQUEST/api/attempt/game_start.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                body: JSON.stringify({ game_type: 'word_scramble' }),
            });
            const json = await res.json();
            _wsAttemptId = (json.data && json.data.attempt_id) ? json.data.attempt_id : 0;
        } catch (e) { _wsAttemptId = 0; }
    }

    async function wsCompleteAttempt(xpEarned, totalRounds) {
        if (_wsAttemptId <= 0) return;
        const token = localStorage.getItem('eq_token');
        if (!token) return;
        const timeSpent = _wsGameStartTime > 0 ? Math.round((Date.now() - _wsGameStartTime) / 1000) : 0;
        try {
            await fetch('../../EDUQUEST/api/attempt/game_complete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                body: JSON.stringify({
                    attempt_id: _wsAttemptId,
                    score: score,
                    max_score: totalRounds * DIFFICULTY[diff].scoreBase,
                    xp_earned: xpEarned,
                    time_spent_sec: timeSpent,
                }),
            });
        } catch (e) { /* Silently fail */ }
        _wsAttemptId = 0;
    }

    function wsAbandonAttempt() {
        if (_wsAttemptId <= 0) return;
        const token = localStorage.getItem('eq_token');
        if (!token) return;
        // Use sendBeacon for reliable page-leave delivery
        const payload = JSON.stringify({ attempt_id: _wsAttemptId });
        navigator.sendBeacon
            ? navigator.sendBeacon('../../EDUQUEST/api/attempt/game_abandon.php', new Blob([payload], { type: 'application/json' }))
            : fetch('../../EDUQUEST/api/attempt/game_abandon.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }, body: payload, keepalive: true }).catch(() => {});
        _wsAttemptId = 0;
    }

    /* ── Keyboard Support ── */
    function handleKeyboard(e) {
        if (gameScreen.classList.contains('hidden')) return;

        if (e.key === 'Enter') {
            e.preventDefault();
            submitAnswer();
            return;
        }

        if (e.key === 'Backspace') {
            e.preventDefault();
            // Remove last placed letter
            for (let i = answer.length - 1; i >= 0; i--) {
                if (answer[i] !== null) {
                    const slots = answerRack.querySelectorAll('.answer-slot');
                    removeLetter(slots[i], i);
                    break;
                }
            }
            return;
        }

        const key = e.key.toUpperCase();
        if (/^[A-Z]$/.test(key)) {
            // Type a letter — find matching unused tile
            const tiles = letterRack.querySelectorAll('.letter-tile:not(.used)');
            for (const tile of tiles) {
                if (tile.textContent === key) {
                    placeLetter(tile);
                    break;
                }
            }
        }
    }

    /* ── Feedback Toast ── */
    function showFeedback(msg, type) {
        feedback.textContent = msg;
        feedback.className = `feedback ${type}`;
        feedback.classList.remove('hidden');
        setTimeout(() => feedback.classList.add('hidden'), 1400);
    }

    /* ── Server Attempt History ── */
    async function fetchWsHistory() {
        const token = localStorage.getItem('eq_token');
        if (!token) return;
        try {
            const res = await fetch('../../EDUQUEST/api/attempt/my_attempts.php?type=game_list', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            const json = await res.json();
            if (json.success && json.data && json.data.games) {
                const stats = json.data.games['word_scramble'];
                if (stats && stats.total_plays > 0) {
                    const el = $('wsHistoryBox');
                    if (!el) return;
                    const best  = stats.best_score  != null ? Math.round(stats.best_score) + '%' : '—';
                    const last  = stats.last_played ? new Date(stats.last_played).toLocaleDateString() : '—';
                    el.innerHTML = `
                        <div class="ws-hist-stat"><span class="ws-hist-val">${stats.total_plays}</span><span class="ws-hist-lbl">Plays</span></div>
                        <div class="ws-hist-stat"><span class="ws-hist-val">${stats.completed_plays}</span><span class="ws-hist-lbl">Completed</span></div>
                        <div class="ws-hist-stat"><span class="ws-hist-val">${best}</span><span class="ws-hist-lbl">Best Score</span></div>
                        <div class="ws-hist-stat"><span class="ws-hist-val ws-hist-date">${last}</span><span class="ws-hist-lbl">Last Played</span></div>`;
                    el.classList.remove('hidden');
                }
            }
        } catch (e) { /* Silently fail */ }
    }

    /* ── High Score Display ── */
    function updateHighScoreDisplay() {
        const key = `ws_high_${diff}_${cat}`;
        const hs = localStorage.getItem(key) || '0';
        const el = $('highScore');
        if (el) el.textContent = `🏅 High Score: ${hs}`;
    }

    /* ── Helpers ── */
    function shuffle(arr) {
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
        return arr;
    }
})();
