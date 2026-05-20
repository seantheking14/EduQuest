/* =====================================================================
   pet_animations.js — EduQuest Pet Card JS Utilities
   ===================================================================== */

(function (window) {
    'use strict';

    // ------------------------------------------------------------------
    // Constants
    // ------------------------------------------------------------------

    const PET_ASSETS_BASE = '../../EDUQUEST/assets/pets/';

    const STAGE_NAMES = ['egg', 'baby', 'young', 'adult'];

    const PET_NAMES = {
        fire:  ['Fire Egg', 'Iggy',    'Blazeback', 'Thornflare'],
        water: ['Water Egg','Bubbles', 'Shellby',   'Tidalback'],
        grass: ['Grass Egg','Sprout',  'Twigster',  'Vinespark'],
    };

    const TEAM_EMOJIS = { fire: '🔥', water: '💧', grass: '🌿' };

    // ------------------------------------------------------------------
    // Helper: convert legacy 5-stage system (1-5) → display stage (0-3)
    // ------------------------------------------------------------------

    function legacyToDisplayStage(s) {
        s = parseInt(s, 10) || 1;
        if (s <= 2) return 0;
        if (s === 3) return 1;
        if (s === 4) return 2;
        return 3;
    }

    // ------------------------------------------------------------------
    // Helper: clamp stage 0-3, validate team
    // ------------------------------------------------------------------

    function clampStage(stage) {
        return Math.max(0, Math.min(3, parseInt(stage, 10) || 0));
    }

    function validTeam(team) {
        return ['fire', 'water', 'grass'].includes(team) ? team : 'fire';
    }

    // ------------------------------------------------------------------
    // renderPetCard(team, stage, studentName, container)
    // JS equivalent of pet_display.php for static HTML pages.
    //
    // @param {string}          team        'fire' | 'water' | 'grass'
    // @param {number}          stage       0-3  (display stage)
    // @param {string}          studentName Student's display name
    // @param {string|Element}  container   CSS selector or DOM element
    //   to append the card into (or replace if it already contains one)
    // @returns {HTMLElement}   The created .pet-card element
    // ------------------------------------------------------------------

    function renderPetCard(team, stage, studentName, container) {
        team  = validTeam(team);
        stage = clampStage(stage);

        const petName   = PET_NAMES[team][stage];
        const stageName = STAGE_NAMES[stage];
        const emoji     = TEAM_EMOJIS[team];
        const imgSrc    = PET_ASSETS_BASE + team + '/' + stageName + '.svg';
        const tooltip   = studentName
            ? petName + ' — ' + studentName + '\'s companion'
            : petName;

        const card = document.createElement('div');
        card.className   = 'pet-card';
        card.dataset.team  = team;
        card.dataset.stage = stage;
        card.title         = tooltip;

        card.innerHTML = [
            '<div class="pet-img-wrap">',
            '  <div class="pet-egg-effect ' + team + '-' + (stage === 0 ? 'flame' : 'bounce') + '-fx"></div>',
            '  <img class="pet-img"',
            '       src="' + imgSrc + '"',
            '       alt="' + petName.replace(/"/g, '&quot;') + '"',
            '       loading="lazy"',
            '       draggable="false">',
            '</div>',
            '<div class="pet-name-row">',
            '  <span class="pet-team-badge">' + emoji + '</span>',
            '  <span class="pet-name">' + escapeHtml(petName) + '</span>',
            '</div>',
            '<span class="pet-selected-badge">✓</span>',
        ].join('\n');

        if (container) {
            const parent = typeof container === 'string'
                ? document.querySelector(container)
                : container;
            if (parent) {
                const existing = parent.querySelector('.pet-card');
                if (existing) parent.removeChild(existing);
                parent.appendChild(card);
            }
        }

        return card;
    }

    // ------------------------------------------------------------------
    // triggerHatchAnimation(team, containerSelector)
    // 6-step visual sequence when the egg hatches.
    //
    // @param {string} team               'fire' | 'water' | 'grass'
    // @param {string} containerSelector  CSS selector for the .pet-card
    //   or its parent (must contain a .pet-card[data-stage="0"])
    // ------------------------------------------------------------------

    function triggerHatchAnimation(team, containerSelector) {
        team = validTeam(team);
        const parent = typeof containerSelector === 'string'
            ? document.querySelector(containerSelector)
            : containerSelector;
        if (!parent) return;

        const card = parent.classList.contains('pet-card')
            ? parent
            : parent.querySelector('.pet-card');
        if (!card) return;

        const img = card.querySelector('.pet-img');
        if (!img) return;

        // Step 1 — rapid wobble (400 ms)
        card.classList.add('pet-hatch-wobble');
        setTimeout(function () {

            // Step 2 — crack glow (200 ms)
            card.classList.remove('pet-hatch-wobble');
            card.classList.add('pet-hatch-crack');
            setTimeout(function () {

                // Step 3 — shake (300 ms)
                card.classList.remove('pet-hatch-crack');
                card.classList.add('pet-hatch-shake');
                setTimeout(function () {

                    // Step 4 — scale + fade out egg (400 ms)
                    card.classList.remove('pet-hatch-shake');
                    card.classList.add('pet-hatch-fadeout');
                    setTimeout(function () {

                        // Step 5 — swap image to baby, fade in
                        card.classList.remove('pet-hatch-fadeout');
                        img.src = PET_ASSETS_BASE + team + '/baby.svg';
                        img.alt = PET_NAMES[team][1];
                        card.dataset.stage = '1';
                        card.classList.add('pet-hatch-fadein');

                        // Update name row
                        var nameEl = card.querySelector('.pet-name');
                        if (nameEl) nameEl.textContent = PET_NAMES[team][1];

                        setTimeout(function () {
                            card.classList.remove('pet-hatch-fadein');

                            // Step 6 — popup
                            if (typeof window.showGamePopup === 'function') {
                                window.showGamePopup({
                                    type: 'evolution',
                                    title: 'Your Egg Hatched!',
                                    message: TEAM_EMOJIS[team] + ' <strong>' + PET_NAMES[team][1] + '</strong> has emerged!',
                                    icon: TEAM_EMOJIS[team],
                                    confetti: true,
                                    autoClose: 4000,
                                });
                            }
                        }, 500);

                    }, 400);
                }, 300);
            }, 200);
        }, 400);
    }

    // ------------------------------------------------------------------
    // triggerEvolveAnimation(team, newStage, containerSelector)
    // 5-step visual sequence when the pet evolves to a new stage.
    //
    // @param {string} team               'fire' | 'water' | 'grass'
    // @param {number} newStage           Target display stage (1-3)
    // @param {string} containerSelector  CSS selector for the .pet-card
    //   or its parent
    // ------------------------------------------------------------------

    function triggerEvolveAnimation(team, newStage, containerSelector) {
        team     = validTeam(team);
        newStage = clampStage(newStage);
        if (newStage === 0) return; // can't evolve to egg

        const parent = typeof containerSelector === 'string'
            ? document.querySelector(containerSelector)
            : containerSelector;
        if (!parent) return;

        const card = parent.classList.contains('pet-card')
            ? parent
            : parent.querySelector('.pet-card');
        if (!card) return;

        const img = card.querySelector('.pet-img');
        if (!img) return;

        // Step 1 — flash white
        card.classList.add('pet-evolve-flash');
        setTimeout(function () {
            card.classList.remove('pet-evolve-flash');

            // Step 2 — radial burst
            card.classList.add('pet-evolve-burst');
            setTimeout(function () {
                card.classList.remove('pet-evolve-burst');

                // Step 3 — fade out current
                card.classList.add('pet-evolve-fadeout');
                setTimeout(function () {
                    card.classList.remove('pet-evolve-fadeout');

                    // Step 4 — swap + fade in new form
                    img.src = PET_ASSETS_BASE + team + '/' + STAGE_NAMES[newStage] + '.svg';
                    img.alt = PET_NAMES[team][newStage];
                    card.dataset.stage = String(newStage);
                    card.classList.add('pet-evolve-fadein');

                    var nameEl = card.querySelector('.pet-name');
                    if (nameEl) nameEl.textContent = PET_NAMES[team][newStage];

                    setTimeout(function () {
                        card.classList.remove('pet-evolve-fadein');

                        // Step 5 — popup
                        if (typeof window.showGamePopup === 'function') {
                            window.showGamePopup({
                                type: 'evolution',
                                title: 'Your Pet Evolved!',
                                message: TEAM_EMOJIS[team] + ' <strong>' + PET_NAMES[team][newStage] + '</strong> has evolved!',
                                icon: TEAM_EMOJIS[team],
                                confetti: true,
                                autoClose: 4500,
                            });
                        }
                    }, 450);

                }, 350);
            }, 500);
        }, 300);
    }

    // ------------------------------------------------------------------
    // Egg selection screen interactions
    // Attaches hover/click behaviours to a list of .pet-card elements.
    //
    // Call: window.initEggSelection('.egg-choice-container')
    // ------------------------------------------------------------------

    function initEggSelection(containerSelector) {
        const container = typeof containerSelector === 'string'
            ? document.querySelector(containerSelector)
            : containerSelector;
        if (!container) return;

        const cards = Array.from(container.querySelectorAll('.pet-card'));
        if (!cards.length) return;

        cards.forEach(function (card) {
            // Hover: speed up wobble
            card.addEventListener('mouseenter', function () {
                var img = card.querySelector('.pet-img');
                if (img && card.dataset.stage === '0') {
                    img.style.animationDuration = '0.5s';
                }
            });

            card.addEventListener('mouseleave', function () {
                var img = card.querySelector('.pet-img');
                if (img) img.style.animationDuration = '';
            });

            // Click: select this card
            card.addEventListener('click', function () {
                cards.forEach(function (c) {
                    c.classList.remove('selected');
                    c.classList.add('dimmed');
                });
                card.classList.remove('dimmed');
                card.classList.add('selected');

                // Dispatch a custom event so the page can react
                container.dispatchEvent(new CustomEvent('petSelected', {
                    bubbles: true,
                    detail: { team: card.dataset.team, stage: card.dataset.stage },
                }));
            });
        });
    }

    // ------------------------------------------------------------------
    // Simple HTML-escape utility
    // ------------------------------------------------------------------

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    window.PetAnimations = {
        legacyToDisplayStage: legacyToDisplayStage,
        renderPetCard:         renderPetCard,
        triggerHatchAnimation: triggerHatchAnimation,
        triggerEvolveAnimation: triggerEvolveAnimation,
        initEggSelection:      initEggSelection,
    };

    // Legacy aliases used by inline calls throughout the codebase
    window.renderPetCard          = renderPetCard;
    window.triggerHatchAnimation  = triggerHatchAnimation;
    window.triggerEvolveAnimation = triggerEvolveAnimation;
    window.legacyToDisplayStage   = legacyToDisplayStage;

}(window));
