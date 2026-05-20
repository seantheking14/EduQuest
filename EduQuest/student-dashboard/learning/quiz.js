/**
 * quiz.js
 * Interactive quiz — one question at a time with:
 *   • 3-attempt system with progressive hints
 *   • Countdown timer per question
 *   • Guide character companion
 *   • Correct-answer reveal after exhausting attempts
 */
(function () {
    'use strict';

    const API_BASE = '../../EDUQUEST/api/learning';
    const token = localStorage.getItem('eq_token');
    const user = JSON.parse(localStorage.getItem('eduquest_user') || 'null');

    if (!token || !user) {
        window.location.href = '../../auth/login/login.html';
        return;
    }

    const authHeaders = () => ({
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token,
    });

    const params = new URLSearchParams(window.location.search);
    const quizId = params.get('id');
    const lessonId = params.get('lessonId');
    const subjectId = params.get('subjectId');

    if (!quizId) {
        window.location.href = 'learning.html';
        return;
    }

    /* ── State ── */
    let quizData = null;
    let questions = [];
    let currentQ = 0;
    let answers = {};          // { questionId: answerId }  — final locked answer
    let submitted = false;
    let results = null;

    /* Attempt system — per-question tracking */
    const MAX_ATTEMPTS = 3;
    let TIMER_SECONDS = 30;   // default; overridden by teacher settings / quiz config
    let attemptTrackers = {};  // { questionId: AttemptTracker }
    let questionStates = {};   // { questionId: { locked, correct, revealedAnswer, hintShown, wrongIds:[] } }
    let timer = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadQuiz();
        loadNavStats();
    });

    /* ── Nav Stats ── */
    async function loadNavStats() {
        try {
            const res = await fetch('../../EDUQUEST/api/gamification/profile.php', { headers: authHeaders() });
            const json = await res.json();
            if (json.success) {
                const p = json.data.profile;
                const navXp = document.getElementById('navXp');
                const navStreak = document.getElementById('navStreak');
                const navLevel = document.getElementById('navLevel');
                if (navXp) navXp.textContent = formatNum(p.totalXp) + ' XP';
                if (navStreak) navStreak.textContent = p.streakDays + ' days';
                if (navLevel) navLevel.textContent = 'Lv ' + p.level;
            }
        } catch (_) {}
    }

    /* ── Load Quiz ── */
    async function loadQuiz() {
        try {
            // Fetch quiz data and teacher settings in parallel
            const [quizRes, profileRes] = await Promise.all([
                fetch(API_BASE + '/quiz.php?quizId=' + encodeURIComponent(quizId), { headers: authHeaders() }),
                fetch('../../EDUQUEST/api/gamification/profile.php', { headers: authHeaders() }).catch(() => null),
            ]);
            const json = await quizRes.json();

            if (!json.success) {
                renderError(json.message || 'Failed to load quiz');
                return;
            }

            quizData = json.data.quiz;
            questions = json.data.questions || [];

            // Determine timer duration: quiz-level > teacher setting > default 30
            let teacherTimer = 30;
            try {
                const profileJson = profileRes ? await profileRes.json() : null;
                if (profileJson && profileJson.success && profileJson.data && profileJson.data.settings) {
                    teacherTimer = profileJson.data.settings.quizTimerSeconds ?? 30;
                }
            } catch (_) {}
            TIMER_SECONDS = quizData.timeLimit > 0 ? quizData.timeLimit : teacherTimer;

            // Initialise per-question attempt trackers
            questions.forEach(q => {
                attemptTrackers[q.id] = GameHelpers.createAttemptTracker(MAX_ATTEMPTS);
                questionStates[q.id] = {
                    locked: false,
                    correct: false,
                    revealedAnswer: null,
                    hintShown: null,
                    wrongIds: [],
                };
            });

            updateBreadcrumb();
            renderQuiz();

            // Show guide with greeting
            if (window.GuideCharacter) {
                GuideCharacter.show('neutral');
            }
        } catch (err) {
            renderError('Could not connect to the server.');
        }
    }

    /* ── Breadcrumb ── */
    function updateBreadcrumb() {
        const subjectLink = document.getElementById('breadcrumbSubject');
        const quizSpan = document.getElementById('breadcrumbQuiz');

        if (subjectLink && subjectId) {
            subjectLink.href = 'subject.html?id=' + subjectId;
            subjectLink.textContent = quizData.subjectName || 'Subject';
        }
        if (quizSpan) quizSpan.textContent = quizData.title || 'Quiz';
        document.title = (quizData.title || 'Quiz') + ' — EduQuest';
    }

    /* ═══════════════════════════════════════════
       TIMER — per question
       ═══════════════════════════════════════════ */

    function startTimer() {
        if (timer) timer.stop();
        if (TIMER_SECONDS <= 0) return;  // timer disabled by teacher

        timer = GameHelpers.createTimer({
            duration: TIMER_SECONDS,
            onTick(state) {
                // Update the timer bar in-place (no full re-render)
                const track = document.getElementById('timerTrack');
                const text = document.getElementById('timerText');
                if (track) {
                    track.style.width = state.percentLeft + '%';
                    track.className = 'timer-fill';
                    if (state.percentLeft <= 25) track.classList.add('timer-red');
                    else if (state.percentLeft <= 50) track.classList.add('timer-yellow');
                    else track.classList.add('timer-green');
                }
                if (text) text.textContent = state.timeLeft + 's';

                // Guide warns at 25%
                if (state.percentLeft === 25 && window.GuideCharacter) {
                    GuideCharacter.say('timeWarning');
                }
            },
            onTimeout() {
                handleTimeout();
            },
        });

        timer.start();
    }

    function handleTimeout() {
        const q = questions[currentQ];
        if (!q) return;
        const state = questionStates[q.id];
        if (state.locked) return;

        // Lock the question and reveal the correct answer
        state.locked = true;
        const correctAns = q.answers.find(a => a.isCorrect);
        state.revealedAnswer = correctAns;
        state.correct = false;
        // Record the correct answer so it counts for scoring
        answers[q.id] = null; // no answer selected

        if (window.GuideCharacter) {
            GuideCharacter.say('comforting');
        }

        renderQuiz();
    }

    /* ═══════════════════════════════════════════
       RENDER QUIZ
       ═══════════════════════════════════════════ */

    function renderQuiz() {
        const area = document.getElementById('quizArea');
        const slug = quizData.subjectSlug || '';

        let html = '';

        // Header
        html += `
        <div class="quiz-header ${slug}">
            <div class="decor">📝</div>
            <div class="quiz-header-content">
                <h1>${escapeHtml(quizData.title)}</h1>
                <p>${escapeHtml(quizData.description || '')}</p>
                <div class="quiz-meta">
                    <span>❓ ${questions.length} questions</span>
                    <span>🎯 Pass: ${quizData.passingScore || 70}%</span>
                    <span>⚡ ${quizData.xpReward || 0} XP</span>
                </div>
            </div>
        </div>`;

        // Progress dots
        html += '<div class="quiz-progress"><div class="q-progress-dots">';
        questions.forEach((q, i) => {
            let cls = '';
            const qs = questionStates[q.id];
            if (i === currentQ) cls = 'active';
            else if (qs && qs.locked && qs.correct) cls = 'answered correct-dot';
            else if (qs && qs.locked && !qs.correct) cls = 'answered wrong-dot';
            else if (answers[q.id] !== undefined) cls = 'answered';
            html += `<div class="q-dot ${cls}" onclick="jumpToQ(${i})">${i + 1}</div>`;
        });
        html += `</div><span class="q-counter">${currentQ + 1}/${questions.length}</span></div>`;

        // Question card
        if (questions.length > 0) {
            html += renderQuestion(questions[currentQ]);
            html += renderQuizNav();
        }

        area.innerHTML = html;

        // Start timer for current question if not locked
        const curQ = questions[currentQ];
        if (curQ && !questionStates[curQ.id].locked) {
            startTimer();
            // Interaction tracking: start timing this question
            if (window.startQuestionTimer) window.startQuestionTimer(quizData.id, curQ.id);
        } else if (timer) {
            timer.stop();
        }
    }

    /* ── Render Single Question with Timer + Attempts ── */
    function renderQuestion(q) {
        const letters = 'ABCDEFGHIJ';
        const qs = questionStates[q.id];
        const tracker = attemptTrackers[q.id];
        const trackerState = tracker.getState();

        let html = `<div class="question-card">`;

        // Timer bar
        if (!qs.locked && TIMER_SECONDS > 0) {
            html += `
            <div class="quiz-timer">
                <div class="timer-track">
                    <div class="timer-fill timer-green" id="timerTrack" style="width:100%"></div>
                </div>
                <span class="timer-text" id="timerText">${TIMER_SECONDS}s</span>
            </div>`;
        }

        // Attempt stars
        html += GameHelpers.renderAttemptStars(trackerState);

        html += `<span class="question-type-badge">${q.questionType === 'true_false' ? 'True / False' : 'Multiple Choice'}</span>`;
        html += `<p class="question-text">${escapeHtml(q.questionText)}</p>`;

        // Hint bubble (if showing)
        if (qs.hintShown) {
            html += GameHelpers.renderHintBubble(qs.hintShown);
        }

        // Answer options
        html += `<div class="answer-options">`;
        q.answers.forEach((ans, i) => {
            const isLocked = qs.locked;
            const isWrong = qs.wrongIds.includes(ans.id);
            const isSelected = answers[q.id] === ans.id;
            const isRevealed = qs.revealedAnswer && qs.revealedAnswer.id === ans.id;

            let extraCls = '';
            if (isSelected && qs.correct) extraCls = 'correct-pop';
            else if (isRevealed) extraCls = 'revealed-correct';
            else if (isWrong) extraCls = 'wrong-shake disabled-option';
            if (isLocked && !isSelected && !isRevealed) extraCls += ' disabled-option';

            const clickable = !isLocked && !isWrong;

            html += `
            <div class="answer-option ${isSelected ? 'selected' : ''} ${extraCls}" 
                 ${clickable ? `onclick="selectAnswer(${q.id}, ${ans.id})"` : ''}>
                <span class="answer-letter">${letters[i]}</span>
                <span class="answer-text">${escapeHtml(ans.answerText)}</span>
                ${isSelected && qs.correct ? '<span class="answer-icon">✅</span>' : ''}
                ${isRevealed && !isSelected ? '<span class="answer-icon">✅</span>' : ''}
                ${isWrong ? '<span class="answer-icon">❌</span>' : ''}
            </div>`;
        });
        html += '</div>';

        // Answer reveal (after last attempt or timeout)
        if (qs.locked && !qs.correct && qs.revealedAnswer) {
            const explanation = q.answers.find(a => a.isCorrect);
            html += GameHelpers.renderAnswerReveal(
                escapeHtml(qs.revealedAnswer.answerText),
                explanation && explanation.explanation ? escapeHtml(explanation.explanation) : ''
            );
        }

        html += '</div>';
        return html;
    }

    /* ── Navigation ── */
    function renderQuizNav() {
        const isFirst = currentQ === 0;
        const isLast = currentQ === questions.length - 1;
        const allLocked = questions.every(q => questionStates[q.id].locked);

        let html = '<div class="quiz-nav">';

        html += `<button class="quiz-nav-btn qbtn-prev ${isFirst ? 'qbtn-disabled' : ''}" data-track="Previous Question" onclick="navQ(-1)" ${isFirst ? 'disabled' : ''}>← Previous</button>`;

        if (isLast) {
            html += `<button class="quiz-nav-btn qbtn-submit ${allLocked ? '' : 'qbtn-disabled'}" data-track="Submit Quiz" onclick="submitQuiz()" ${allLocked ? '' : 'disabled'}>✅ Submit Quiz</button>`;
        }

        html += '</div>';
        return html;
    }

    /* ═══════════════════════════════════════════
       SELECT ANSWER — attempt-based
       ═══════════════════════════════════════════ */

    window.selectAnswer = function (qId, aId) {
        if (submitted) return;
        const q = questions.find(x => x.id === qId);
        if (!q) return;
        const qs = questionStates[qId];
        if (qs.locked) return;

        const tracker = attemptTrackers[qId];
        const attemptResult = tracker.use();

        // Check if correct
        const selectedAns = q.answers.find(a => a.id === aId);
        const isCorrect = selectedAns && selectedAns.isCorrect;

        if (isCorrect) {
            // Correct! Lock question, stop timer
            qs.locked = true;
            qs.correct = true;
            answers[qId] = aId;
            if (timer) timer.stop();
            if (window.stopQuestionTimer) window.stopQuestionTimer(true, attemptResult.attemptsUsed);

            if (window.GuideCharacter) {
                GuideCharacter.say('celebrating');
            }
            if (window.GameHelpers) GameHelpers.sparkleEffect();

            renderQuiz();

            // Auto-advance on correct answer; auto-submit if this is the last question.
            setTimeout(() => {
                if (submitted) return;
                if (currentQ >= questions.length - 1) {
                    submitQuiz();
                } else {
                    navQ(1);
                }
            }, 520);
            return;
        }

        // Wrong answer
        qs.wrongIds.push(aId);

        if (attemptResult.isLastAttempt) {
            // Last attempt used — reveal correct answer
            qs.locked = true;
            qs.correct = false;
            const correctAns = q.answers.find(a => a.isCorrect);
            qs.revealedAnswer = correctAns;
            answers[qId] = aId; // record the wrong answer
            if (timer) timer.stop();
            if (window.stopQuestionTimer) window.stopQuestionTimer(false, attemptResult.attemptsUsed);

            if (window.GuideCharacter) {
                GuideCharacter.say('lastAttempt');
            }
        } else {
            // Show hint for next attempt
            const hints = buildHints(q);
            const hint = GameHelpers.shouldShowHint(attemptResult.attemptsUsed, hints);
            qs.hintShown = hint;

            if (window.GuideCharacter) {
                GuideCharacter.say('hinting');
            }
        }

        renderQuiz();
    };

    /**
     * Build hint strings from question data.
     * Hint 1: Eliminate one wrong answer (general hint)
     * Hint 2: More specific hint
     */
    function buildHints(q) {
        const correctAns = q.answers.find(a => a.isCorrect);
        const wrongAnswers = q.answers.filter(a => !a.isCorrect);
        const hints = [];

        // Hint 1 — general encouragement + eliminate
        if (wrongAnswers.length > 1) {
            hints.push("Try eliminating answers you know aren't right! 🔍");
        } else {
            hints.push("Think carefully about each option! 🔍");
        }

        // Hint 2 — more direct
        if (correctAns && correctAns.explanation) {
            hints.push(correctAns.explanation);
        } else if (correctAns) {
            const firstLetter = correctAns.answerText.charAt(0).toUpperCase();
            hints.push("The answer starts with '" + firstLetter + "' — you've got this! 💡");
        }

        return hints;
    }

    /* ── Navigate Questions ── */
    window.navQ = function (dir) {
        const target = currentQ + dir;
        if (target < 0 || target >= questions.length) return;
        currentQ = target;
        renderQuiz();

        // Guide encouragement when moving to new question
        const qs = questionStates[questions[currentQ].id];
        if (!qs.locked && window.GuideCharacter) {
            GuideCharacter.say('encouraging');
        }
    };

    window.jumpToQ = function (index) {
        if (index < 0 || index >= questions.length) return;
        currentQ = index;
        renderQuiz();
    };

    /* ═══════════════════════════════════════════
       SUBMIT QUIZ
       ═══════════════════════════════════════════ */

    window.submitQuiz = async function () {
        if (submitted) return;
        const allLocked = questions.every(q => questionStates[q.id].locked);
        if (!allLocked) return;

        if (timer) timer.stop();

        // Build submission — use the locked-in answers
        const submission = questions.map(q => ({
            questionId: q.id,
            answerId: answers[q.id] || null,
        }));

        try {
            const res = await fetch(API_BASE + '/quiz.php', {
                method: 'POST',
                headers: authHeaders(),
                body: JSON.stringify({ quizId: parseInt(quizId), answers: submission }),
            });
            const json = await res.json();

            if (!json.success) {
                alert(json.message || 'Failed to submit quiz.');
                return;
            }

            submitted = true;
            results = json.data;
            renderResults();

            if (window.GuideCharacter) {
                if (results.passed) {
                    GuideCharacter.say('celebrating', 'You passed! I\'m so proud of you! 🎉🏆');
                } else {
                    GuideCharacter.say('comforting', 'You did your best! Keep practicing and you\'ll get it! 💛');
                }
            }
        } catch (err) {
            alert('Network error — please try again.');
        }
    };

    /* ── Render Results ── */
    function renderResults() {
        const area = document.getElementById('quizArea');
        const passed = results.passed;
        const score = results.score;

        let html = `
        <div class="results-card ${passed ? 'passed' : 'failed'}">
            <span class="results-emoji">${passed ? '🎉' : '💪'}</span>
            <h2>${passed ? 'Quiz Passed!' : 'Keep Practicing!'}</h2>
            <div class="results-score">${score}%</div>
            <p class="results-detail">${results.correctCount}/${results.totalQuestions} correct · Passing: ${quizData.passingScore}%</p>
            ${results.xpAwarded ? '<p class="results-xp">⚡ +' + results.xpAwarded + ' XP earned!</p>' : '<p class="results-xp" style="color:#9ca3af;">No XP (quiz already passed)</p>'}
            <div class="results-actions">
                ${subjectId ? '<button class="quiz-nav-btn qbtn-prev" onclick="goBackToSubject()">← Back to Lessons</button>' : ''}
                <button class="quiz-nav-btn qbtn-prev" onclick="goBackToLearning()">📚 All Subjects</button>
            </div>

            <!-- Review each question -->
            <div class="review-section">
                <h3>📋 Review Answers</h3>
                ${renderReviewItems()}
            </div>
        </div>`;

        area.innerHTML = html;
        window.scrollTo({ top: 0, behavior: 'smooth' });

        if (passed && window.GameHelpers) GameHelpers.sparkleEffect();
    }

    /* ── Review Items ── */
    function renderReviewItems() {
        if (!results.results) return '';

        return results.results.map((r, i) => {
            const cls = r.correct ? 'correct' : 'wrong';
            return `
            <div class="review-item ${cls}">
                <p class="review-q">${i + 1}. ${escapeHtml(r.questionText)}</p>
                <p class="review-answer">
                    <span class="label">Your answer:</span> ${escapeHtml(r.selectedAnswer || 'No answer')} ${r.correct ? '✅' : '❌'}
                </p>
                ${!r.correct ? '<p class="review-answer"><span class="label">Correct:</span> ' + escapeHtml(r.correctAnswer) + '</p>' : ''}
                ${r.explanation ? '<p class="review-answer" style="color:#6b7280;font-style:italic;">💡 ' + escapeHtml(r.explanation) + '</p>' : ''}
            </div>`;
        }).join('');
    }

    /* ── Navigation Helpers ── */
    window.goBackToSubject = function () {
        window.location.href = 'subject.html?id=' + subjectId;
    };

    window.goBackToLearning = function () {
        window.location.href = 'learning.html';
    };

    /* ── Error ── */
    function renderError(msg) {
        document.getElementById('quizArea').innerHTML = `
            <div style="text-align:center;padding:40px;background:#fef2f2;border-radius:16px;color:#dc2626;">
                <p style="font-size:18px;font-weight:600;">⚠️ ${escapeHtml(msg)}</p>
                <a href="learning.html" style="color:#8b5cf6;margin-top:12px;display:inline-block;">← Back to subjects</a>
            </div>`;
    }

    /* ── Helpers ── */
    function escapeHtml(str) {
        const el = document.createElement('span');
        el.textContent = str;
        return el.innerHTML;
    }

    function formatNum(n) {
        return Number(n || 0).toLocaleString();
    }
})();
