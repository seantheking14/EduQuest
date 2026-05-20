/**
 * game-helpers.js — Timer, attempt tracker, and utility functions
 * for the quiz/game system.
 */
(function () {
    'use strict';

    /* ═══════════════════════════════════════════
       COUNTDOWN TIMER
       ═══════════════════════════════════════════ */

    /**
     * Creates a countdown timer instance.
     * @param {Object} opts
     * @param {number}   opts.duration   — seconds (default 30)
     * @param {Function} opts.onTick     — called every second with { timeLeft, percentLeft }
     * @param {Function} opts.onTimeout  — called when timer hits 0
     * @returns {{ start, stop, reset, getState }}
     */
    function createTimer(opts) {
        const duration = opts.duration || 30;
        let timeLeft = duration;
        let intervalId = null;
        let running = false;

        function tick() {
            timeLeft--;
            const percentLeft = Math.round((timeLeft / duration) * 100);

            if (opts.onTick) {
                opts.onTick({ timeLeft, percentLeft, duration });
            }

            if (timeLeft <= 0) {
                stop();
                if (opts.onTimeout) opts.onTimeout();
            }
        }

        function start() {
            if (running) return;
            running = true;
            intervalId = setInterval(tick, 1000);
        }

        function stop() {
            running = false;
            if (intervalId) { clearInterval(intervalId); intervalId = null; }
        }

        function reset(newDuration) {
            stop();
            timeLeft = newDuration || duration;
            const percentLeft = Math.round((timeLeft / (newDuration || duration)) * 100);
            if (opts.onTick) opts.onTick({ timeLeft, percentLeft, duration: newDuration || duration });
        }

        function getState() {
            return {
                timeLeft,
                percentLeft: Math.round((timeLeft / duration) * 100),
                isRunning: running,
                duration,
            };
        }

        return { start, stop, reset, getState };
    }

    /* ═══════════════════════════════════════════
       ATTEMPT TRACKER
       ═══════════════════════════════════════════ */

    /**
     * Creates an attempt tracker for a question.
     * @param {number} maxAttempts — default 3
     * @returns {{ use, reset, getState }}
     */
    function createAttemptTracker(maxAttempts) {
        maxAttempts = maxAttempts || 3;
        let used = 0;

        return {
            use() {
                if (used < maxAttempts) used++;
                return {
                    attemptsUsed: used,
                    attemptsLeft: maxAttempts - used,
                    isLastAttempt: used >= maxAttempts,
                    maxAttempts,
                };
            },
            reset() {
                used = 0;
            },
            getState() {
                return {
                    attemptsUsed: used,
                    attemptsLeft: maxAttempts - used,
                    isLastAttempt: used >= maxAttempts,
                    maxAttempts,
                };
            },
        };
    }

    /* ═══════════════════════════════════════════
       UTILITY FUNCTIONS
       ═══════════════════════════════════════════ */

    /**
     * Pick a random dialogue line from a mood pool.
     */
    function getRandomDialogue(mood) {
        const pool = (window.GUIDE_DIALOGUE || {})[mood];
        if (!pool || pool.length === 0) return '';
        return pool[Math.floor(Math.random() * pool.length)];
    }

    /**
     * Calculate EXP based on attempts used (never shown to student as reduced).
     * @param {number} attemptsUsed
     * @param {number} baseXP
     * @returns {number}
     */
    function calculateEXP(attemptsUsed, baseXP) {
        baseXP = baseXP || 30;
        if (attemptsUsed <= 1) return baseXP;        // full XP
        if (attemptsUsed === 2) return Math.round(baseXP * 0.7);  // 70%
        return Math.round(baseXP * 0.4);              // 40% for 3+ attempts
    }

    /**
     * Get the appropriate hint based on attempts used.
     * @param {number} attemptsUsed
     * @param {string[]} hints — array of hint strings
     * @returns {string|null}
     */
    function shouldShowHint(attemptsUsed, hints) {
        if (!hints || hints.length === 0) return null;
        if (attemptsUsed <= 0) return null;
        const idx = Math.min(attemptsUsed - 1, hints.length - 1);
        return hints[idx];
    }

    /**
     * Render the timer bar HTML.
     * @param {{ timeLeft: number, percentLeft: number }} state
     * @returns {string}
     */
    function renderTimerBar(state) {
        let colorClass = 'timer-green';
        if (state.percentLeft <= 25) colorClass = 'timer-red';
        else if (state.percentLeft <= 50) colorClass = 'timer-yellow';

        return `
        <div class="quiz-timer">
            <div class="timer-track">
                <div class="timer-fill ${colorClass}" style="width:${state.percentLeft}%"></div>
            </div>
            <span class="timer-text">${state.timeLeft}s</span>
        </div>`;
    }

    /**
     * Render attempt stars HTML.
     * @param {{ attemptsLeft: number, maxAttempts: number }} state
     * @returns {string}
     */
    function renderAttemptStars(state) {
        let html = '<div class="attempt-stars">';
        for (let i = 0; i < state.maxAttempts; i++) {
            const active = i < state.attemptsLeft;
            html += `<span class="attempt-star ${active ? 'active' : 'used'}">❤️</span>`;
        }
        html += '</div>';
        return html;
    }

    /**
     * Render the hint bubble HTML.
     * @param {string} hintText
     * @returns {string}
     */
    function renderHintBubble(hintText) {
        if (!hintText) return '';
        return `
        <div class="hint-bubble">
            <span class="hint-icon">💡</span>
            <span class="hint-text">${hintText}</span>
        </div>`;
    }

    /**
     * Render the correct-answer reveal HTML.
     * @param {string} correctAnswer
     * @param {string} explanation
     * @returns {string}
     */
    function renderAnswerReveal(correctAnswer, explanation) {
        let html = `
        <div class="answer-reveal">
            <div class="answer-reveal-header">✅ The answer is:</div>
            <div class="answer-reveal-text">${correctAnswer}</div>`;
        if (explanation) {
            html += `<div class="answer-reveal-explain">💡 ${explanation}</div>`;
        }
        html += '</div>';
        return html;
    }

    /**
     * Create a lightweight sparkle/confetti burst (non-overwhelming).
     */
    function sparkleEffect() {
        const colors = ['#fbbf24', '#8b5cf6', '#ef4444', '#22c55e', '#3b82f6'];
        const container = document.createElement('div');
        container.className = 'sparkle-container';
        container.setAttribute('aria-hidden', 'true');

        for (let i = 0; i < 12; i++) {
            const dot = document.createElement('span');
            dot.className = 'sparkle-dot';
            dot.style.setProperty('--x', (Math.random() * 200 - 100) + 'px');
            dot.style.setProperty('--y', (Math.random() * -150 - 50) + 'px');
            dot.style.setProperty('--delay', (Math.random() * 0.3) + 's');
            dot.style.background = colors[Math.floor(Math.random() * colors.length)];
            container.appendChild(dot);
        }

        document.body.appendChild(container);
        setTimeout(() => container.remove(), 1200);
    }

    /* ── Expose globally ── */
    window.GameHelpers = {
        createTimer,
        createAttemptTracker,
        getRandomDialogue,
        calculateEXP,
        shouldShowHint,
        renderTimerBar,
        renderAttemptStars,
        renderHintBubble,
        renderAnswerReveal,
        sparkleEffect,
    };

})();
