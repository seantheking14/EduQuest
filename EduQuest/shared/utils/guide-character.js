/**
 * guide-character.js — Reusable virtual guide companion.
 *
 * Renders a floating guide avatar with a speech bubble in the corner
 * of the screen. Exposes a global GuideCharacter object.
 *
 * Usage:
 *   GuideCharacter.show('neutral');         // show with random neutral line
 *   GuideCharacter.say('celebrating', 'YES! Amazing!');  // specific text
 *   GuideCharacter.hide();
 *   GuideCharacter.setPosition('bottom-left'); // or 'bottom-right'
 */
window.GuideCharacter = (function () {
    'use strict';

    let container = null;
    let currentMood = 'neutral';
    let position = 'bottom-right';
    let speechTimeout = null;
    let isVisible = false;

    /* ── Initialise DOM element (once) ── */
    function ensureDOM() {
        if (container) return;

        container = document.createElement('div');
        container.id = 'guideCharacter';
        container.className = 'guide-char guide-pos-' + position;
        container.innerHTML = `
            <div class="guide-bubble" id="guideBubble"></div>
            <div class="guide-sprite" id="guideSprite">
                <span class="guide-face" id="guideFace">🧙</span>
            </div>
        `;
        document.body.appendChild(container);
    }

    /* ── Pick random line from the dialogue pool ── */
    function getRandomLine(mood) {
        const pool = (window.GUIDE_DIALOGUE || {})[mood];
        if (!pool || pool.length === 0) return '';
        return pool[Math.floor(Math.random() * pool.length)];
    }

    /* ── Public API ── */
    return {
        /**
         * Show guide with a mood. Picks a random line from that mood's pool.
         * @param {string} mood — key from GUIDE_DIALOGUE / GUIDE_EXPRESSIONS
         */
        show(mood) {
            this.say(mood);
        },

        /**
         * Speak a specific line (or random from pool).
         * @param {string} mood
         * @param {string} [text] — override with a specific string
         * @param {number} [duration] — ms before bubble auto-hides (0 = persistent)
         */
        say(mood, text, duration) {
            ensureDOM();
            currentMood = mood || 'neutral';

            const line = text || getRandomLine(currentMood) || '';

            const bubbleEl = document.getElementById('guideBubble');

            if (bubbleEl) {
                bubbleEl.textContent = line;
                bubbleEl.classList.add('visible');
            }

            container.classList.add('visible');
            isVisible = true;

            // Add mood class for CSS styling
            container.className = 'guide-char guide-pos-' + position + ' visible mood-' + currentMood;

            // Bounce animation on mood change
            const sprite = document.getElementById('guideSprite');
            if (sprite) {
                sprite.classList.remove('bounce');
                void sprite.offsetWidth; // reflow
                sprite.classList.add('bounce');
            }

            // Auto-hide speech bubble after duration
            if (speechTimeout) clearTimeout(speechTimeout);
            if (duration !== 0) {
                speechTimeout = setTimeout(() => {
                    if (bubbleEl) bubbleEl.classList.remove('visible');
                }, duration || 5000);
            }
        },

        /**
         * Update just the speech bubble text without changing mood/face.
         */
        updateSpeech(text) {
            ensureDOM();
            const bubbleEl = document.getElementById('guideBubble');
            if (bubbleEl) {
                bubbleEl.textContent = text;
                bubbleEl.classList.add('visible');
            }
            if (speechTimeout) clearTimeout(speechTimeout);
            speechTimeout = setTimeout(() => {
                if (bubbleEl) bubbleEl.classList.remove('visible');
            }, 5000);
        },

        /**
         * Hide the guide completely.
         */
        hide() {
            if (!container) return;
            container.classList.remove('visible');
            isVisible = false;
        },

        /**
         * Set which corner the guide sits in.
         * @param {'bottom-left'|'bottom-right'} pos
         */
        setPosition(pos) {
            position = pos || 'bottom-right';
            if (container) {
                container.className = container.className
                    .replace(/guide-pos-\S+/, 'guide-pos-' + position);
            }
        },

        /** Check if guide is currently shown */
        get visible() { return isVisible; },

        /** Get current mood */
        get mood() { return currentMood; },
    };
})();
