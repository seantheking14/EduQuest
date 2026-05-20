/**
 * onboarding.js — First-Time Student Onboarding Modal
 * Steps: Team Selection → Pet Naming → Success
 * Non-dismissable, ADHD-friendly UI with NPC guide
 */
(function () {
    'use strict';

    /* ── Constants ── */
    const TEAMS = {
        fire:  { label: 'Fire Team',  emoji: '🔥', eggLabel: 'Fire Egg',  eggDesc: 'Bold and fierce. For students who never give up!',      petName: 'Thornflare', petColor: '#c0392b' },
        water: { label: 'Water Team', emoji: '💧', eggLabel: 'Water Egg', eggDesc: 'Calm and steady. For students who go with the flow!',    petName: 'Tidalback',  petColor: '#1abc9c' },
        grass: { label: 'Grass Team', emoji: '🌿', eggLabel: 'Grass Egg', eggDesc: 'Playful and clever. For students who love to explore!', petName: 'Vinespark',  petColor: '#27ae60' },
    };

    /** Get SVG sprite or fallback emoji */
    function petSvg(team, stage) {
        return typeof PetSprites !== 'undefined' ? PetSprites.get(team, stage) : '';
    }
    function petMini(team, stage) {
        return typeof PetSprites !== 'undefined' ? PetSprites.getMini(team, stage) : '';
    }

    /* ── State ── */
    let selectedTeam    = null;
    let selectedPetName = null;
    let overlay         = null;

    /* ── Public: Show onboarding ── */
    window.showOnboardingModal = function () {
        if (document.getElementById('onboardingOverlay')) return;

        selectedTeam    = null;
        selectedPetName = null;

        overlay = document.createElement('div');
        overlay.id = 'onboardingOverlay';
        overlay.className = 'onboard-overlay';
        overlay.innerHTML = buildHTML();
        document.body.appendChild(overlay);

        requestAnimationFrame(() => overlay.classList.add('visible'));

        bindEvents();
    };

    /* ── Build outer dialog ── */
    function buildHTML() {
        return `<div class="onboard-dialog">${buildTeamStepHTML()}</div>`;
    }

    /* ── Team-selection step inner HTML ── */
    function buildTeamStepHTML() {
        return `
            <div class="onboard-step active" id="onboardTeamStep">
                <div class="onboard-guide">
                    <div class="guide-avatar">🧙</div>
                    <div class="guide-speech">Welcome to EduQuest! Choose your team — you'll receive a matching elemental egg that grows with you!</div>
                </div>
                <h2 class="onboard-title">Choose Your Team</h2>
                <p class="onboard-subtitle">Pick your team to start your adventure. Your starter egg will match your team element!</p>
                <div class="onboard-cards" id="teamCards">
                    ${Object.entries(TEAMS).map(([key, t]) => `
                        <button class="onboard-card team-card${selectedTeam === key ? ' selected' : selectedTeam ? ' dimmed' : ''}" data-team="${esc(key)}"
                                style="--team-color:${t.petColor};border-color:${t.petColor}20;">
                            <span class="team-card-check">✓</span>
                            <span class="card-emoji">${t.emoji}</span>
                            <span class="card-label">${esc(t.label)}</span>
                            <div class="card-egg-preview">
                                <img class="card-egg-img egg-wobble" src="../../EDUQUEST/assets/pets/${esc(key)}/egg.svg"
                                     alt="${esc(t.eggLabel)}" draggable="false"${selectedTeam === key ? ' style="animation-duration:0.5s"' : ''}>
                            </div>
                            <span class="card-pet"><span class="pet-icon" style="color:${t.petColor}">→</span> ${esc(t.petName)}</span>
                            <span class="card-desc">${esc(t.eggDesc)}</span>
                        </button>
                    `).join('')}
                </div>
                <div class="onboard-actions">
                    <button class="onboard-btn btn-confirm" id="btnConfirm"${selectedTeam ? '' : ' disabled'}>Confirm ✓</button>
                </div>
            </div>`;
    }

    /* ── Bind events ── */
    function bindEvents() {
        // Team card clicks
        overlay.querySelectorAll('.team-card').forEach(card => {
            card.addEventListener('click', () => {
                selectedTeam = card.dataset.team;
                overlay.querySelectorAll('.team-card').forEach(c => {
                    c.classList.remove('selected');
                    c.classList.add('dimmed');
                    const img = c.querySelector('.card-egg-img');
                    if (img) img.style.animationDuration = '';
                });
                card.classList.remove('dimmed');
                card.classList.add('selected');
                // Speed up wobble on selected egg
                const selImg = card.querySelector('.card-egg-img');
                if (selImg) selImg.style.animationDuration = '0.5s';
                overlay.querySelector('#btnConfirm').disabled = false;
            });
        });

        // Confirm button — go to naming step
        overlay.querySelector('#btnConfirm').addEventListener('click', () => {
            if (!selectedTeam) return;
            showNamingStep();
        });
    }

    /* ── Naming step ── */
    function showNamingStep() {
        const t       = TEAMS[selectedTeam];
        const dialog  = overlay.querySelector('.onboard-dialog');
        const defName = selectedPetName || t.petName;
        const eggSrc  = '../../EDUQUEST/assets/pets/' + selectedTeam + '/egg.svg';

        dialog.innerHTML = `
            <div class="onboard-step active" id="onboardNameStep">
                <div class="onboard-guide">
                    <div class="guide-avatar">🧙</div>
                    <div class="guide-speech">Give your egg a name — it will carry that name throughout your entire quest!</div>
                </div>
                <h2 class="onboard-title">Name Your ${esc(t.eggLabel)} ${t.emoji}</h2>
                <p class="onboard-subtitle">Keep the default or choose something unique. You can't change it later!</p>
                <div class="pet-naming-preview">
                    <img class="card-egg-img" src="${eggSrc}" alt="${esc(t.eggLabel)}" draggable="false">
                    <div class="pet-naming-badge" id="petNamingBadge"
                         style="background:${t.petColor}20;border-color:${t.petColor};color:${t.petColor};">
                        ${esc(defName)}
                    </div>
                </div>
                <div class="pet-name-input-wrap">
                    <input type="text" id="petNameInput" class="pet-name-input"
                           value="${esc(defName)}"
                           maxlength="24"
                           placeholder="Enter a name…"
                           autocomplete="off"
                           spellcheck="false"
                           style="--team-color:${t.petColor};">
                    <span class="pet-name-counter">
                        <span id="petNameLen">${defName.length}</span><span class="pet-name-counter-max">/24</span>
                    </span>
                </div>
                <p class="pet-name-hint">Letters, numbers, spaces, hyphens and apostrophes only.</p>
                <div class="onboard-actions">
                    <button class="onboard-btn btn-back" id="btnNameBack">← Back</button>
                    <button class="onboard-btn btn-confirm" id="btnNameIt">Name It! ✓</button>
                </div>
            </div>`;

        const input   = dialog.querySelector('#petNameInput');
        const badge   = dialog.querySelector('#petNamingBadge');
        const lenEl   = dialog.querySelector('#petNameLen');
        const nameBtn = dialog.querySelector('#btnNameIt');

        function isValidName(val) {
            return /^[A-Za-z0-9 '\-]{1,24}$/.test(val.trim());
        }

        input.addEventListener('input', () => {
            input.value    = input.value.replace(/[^A-Za-z0-9 '\-]/g, '');
            const val      = input.value;
            lenEl.textContent  = val.length;
            badge.textContent  = val.trim() || t.petName;
            nameBtn.disabled   = !isValidName(val);
        });

        input.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !nameBtn.disabled) nameBtn.click();
        });

        dialog.querySelector('#btnNameBack').addEventListener('click', () => {
            dialog.innerHTML = buildTeamStepHTML();
            bindEvents();
        });

        nameBtn.addEventListener('click', () => {
            const name = input.value.trim();
            if (!isValidName(name)) return;
            selectedPetName = name;
            submitOnboarding();
        });

        input.focus();
        input.select();
    }

    /* ── Submit to API ── */
    async function submitOnboarding() {
        // Works from either the team step (#btnConfirm) or naming step (#btnNameIt)
        const confirmBtn = overlay.querySelector('#btnNameIt') || overlay.querySelector('#btnConfirm');
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Saving…';

        const token = localStorage.getItem('eq_token');
        try {
            const res = await fetch('../../EDUQUEST/api/gamification/onboarding.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token,
                },
                body: JSON.stringify({ team: selectedTeam, egg: selectedTeam, pet_name: selectedPetName }),
            });
            const json = await res.json();

            if (json.success) {
                showOnboardSuccess(json.data);
            } else {
                confirmBtn.disabled = false;
                confirmBtn.textContent = confirmBtn.id === 'btnNameIt' ? 'Name It! ✓' : 'Confirm ✓';
                showToast(json.message || 'Something went wrong. Try again!', 'error');
            }
        } catch (_) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = confirmBtn.id === 'btnNameIt' ? 'Name It! ✓' : 'Confirm ✓';
            showToast('Network error — please check your connection.', 'error');
        }
    }

    /* ── Success state ── */
    function showOnboardSuccess(data) {
        const dialog = overlay.querySelector('.onboard-dialog');
        const t = TEAMS[selectedTeam] || TEAMS.fire;

        // File-based egg + adult preview
        const eggImgSrc   = '../../EDUQUEST/assets/pets/' + selectedTeam + '/egg.svg';
        const adultImgSrc = '../../EDUQUEST/assets/pets/' + selectedTeam + '/adult.svg';

        dialog.innerHTML = `
            <div class="onboard-step active onboard-success">
                <div class="onboard-guide">
                    <div class="guide-avatar">🧙</div>
                    <div class="guide-speech">You're all set, Adventurer! Let's begin your quest!</div>
                </div>
                <div class="success-content">
                    <div class="success-sprites">
                        <span class="success-sprite"><img src="${eggImgSrc}" alt="Egg" style="width:72px;height:72px;object-fit:contain;"></span>
                        <span class="success-arrow">→</span>
                        <span class="success-sprite success-sprite-guardian"><img src="${adultImgSrc}" alt="${esc(t.petName)}" style="width:72px;height:72px;object-fit:contain;"></span>
                    </div>
                    <h2 class="onboard-title">Adventure Awaits!</h2>
                    <p class="onboard-subtitle">
                        You joined <strong>${esc(t.label)}</strong> and received a <strong>${esc(t.eggLabel)}</strong>!<br>
                        Your companion is named <strong style="color:${t.petColor}">${esc(selectedPetName || t.petName)}</strong>.<br>
                        Train hard to evolve it into the mighty <strong>${esc(t.petName)}</strong>!
                    </p>
                    ${data && data.achievementUnlocked ? `
                        <div class="success-achievement">
                            🏆 Achievement Unlocked: <strong>${esc(data.achievementUnlocked)}</strong>
                        </div>` : ''}
                </div>
                <div class="onboard-actions">
                    <button class="onboard-btn btn-confirm" id="btnStart">Start My Quest! 🚀</button>
                </div>
            </div>`;

        dialog.querySelector('#btnStart').addEventListener('click', () => {
            overlay.classList.remove('visible');
            setTimeout(() => {
                overlay.remove();
                overlay = null;
                if (typeof loadProfile === 'function') loadProfile();
            }, 350);
        });
    }

    /* ── Toast notification ── */
    function showToast(msg, type) {
        const el = document.createElement('div');
        el.style.cssText = `
            position:fixed;top:80px;right:20px;padding:12px 20px;
            background:${type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : '#3b82f6'};
            color:#fff;border-radius:10px;font-size:14px;font-weight:600;
            z-index:10100;animation:slideUp .3s ease;max-width:280px;
        `;
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 300); }, 3000);
    }

    /* ── Escape HTML ── */
    function esc(str) {
        const el = document.createElement('span');
        el.textContent = str;
        return el.innerHTML;
    }

})();
