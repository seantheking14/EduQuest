/**
 * pet-companion.js
 * Pet (bottom-left, animal noises) + Wizard guide (bottom-right, motivational messages).
 *
 * Configure BEFORE including this script:
 *   window.PetCompanionConfig = { context: 'learn' | 'game' | 'quiz' };
 *
 * Trigger reactions via custom event:
 *   window.dispatchEvent(new CustomEvent('petReact', { detail: { type: 'correct' } }));
 *
 * Or via public API:
 *   window.PetCompanion.react('correct');
 */
(function () {
    'use strict';

    // ── Configuration ────────────────────────────────────────
    var cfg        = window.PetCompanionConfig || {};
    var CONTEXT    = cfg.context || 'learn';
    var SHOW_WIZARD = cfg.wizard !== false; // set wizard:false to hide the wizard

    var API_PROFILE = '../../EDUQUEST/api/gamification/profile.php';
    var PETS_BASE   = '../../EDUQUEST/assets/pets/';

    var STAGE_NAMES = ['egg', 'baby', 'young', 'adult'];
    var PET_NAMES   = {
        fire:  ['Fire Egg',  'Iggy',    'Blazeback', 'Thornflare'],
        water: ['Water Egg', 'Bubbles', 'Shellby',   'Tidalback'],
        grass: ['Grass Egg', 'Sprout',  'Twigster',  'Vinespark'],
    };

    // ── Pet animal noise pools (per team, per reaction type) ─
    var PET_NOISES = {
        fire: {
            idle:     ['Rawr! \uD83D\uDD25', 'Grrrr~', '*snorts fire*', 'Hissss!', 'ROARR!', '*growls*'],
            correct:  ['ROARR!! \uD83D\uDD25', 'Rawrr! \u2B50', 'Grr GRR! \u26A1'],
            wrong:    ['Mrrf?', 'Hrrm\u2026', '*whimpers*'],
            streak:   ['RAWRRAWRRAWR!! \uD83D\uDD25', 'ROARRR!! \u26A1'],
            complete: ['*victory roar* \uD83C\uDFC6', 'ROOAAARR!! \uD83C\uDF89'],
            encourage:['*nudge nudge*', 'Grr\u2026 grr?', '*paws you*'],
        },
        water: {
            idle:     ['Splashhh~', 'Blurb blurb~', '*bubbles*', 'Whoooosh~', 'Gurgle~', '*splashes*'],
            correct:  ['Splashhh!! \uD83D\uDCA6', 'WHOOSH! \u2B50', '*happy splash* \u26A1'],
            wrong:    ['Blurb?', '*sad bubble*', 'Gurgle\u2026'],
            streak:   ['WHOOOOSH!! \uD83D\uDCA6', 'SPLAAAASH!! \u26A1'],
            complete: ['*tidal wave* \uD83C\uDFC6', 'SPLAAASH!! \uD83C\uDF89'],
            encourage:['*gentle wave*', 'Blurb blurb?', '*nudge splash*'],
        },
        grass: {
            idle:     ['Rustle~', '*leaf rustle*', 'Chirp! Chirp!', 'Wheee~', '*munches leaf*', 'Hrrm hrrm~'],
            correct:  ['WHEEE!! \uD83C\uDF3F', 'Chirp chirp! \u2B50', '*spins* \u26A1'],
            wrong:    ['Hrrm?', '*droops*', 'Rustle\u2026'],
            streak:   ['WHEEEEE!! \uD83C\uDF3F', 'CHIRPCHIRPCHIRP!! \u26A1'],
            complete: ['*victory rustle* \uD83C\uDFC6', 'WHEEEEE!! \uD83C\uDF89'],
            encourage:['*gentle rustle*', 'Hrrm hrrmm?', '*pokes with leaf*'],
        },
    };

    // ── Wizard message pools (per context) ───────────────────
    var MESSAGES = {
        learn: [
            'Let\u2019s learn something new! \uD83D\uDCDA',
            'Knowledge is your superpower! \u2728',
            'Keep exploring! \uD83D\uDD0D',
            'You\u2019re doing great! \uD83C\uDF1F',
            'Every page you read makes you stronger! \uD83D\uDCAA',
            'I\u2019ll be right here with you! \uD83E\uDD29',
        ],
        game: [
            'You\u2019ve got this! \uD83D\uDCAA',
            'Focus \u2026 you can do it! \uD83C\uDFAF',
            'Keep that streak going! \uD83D\uDD25',
            'I believe in you! \u2B50',
            'Unscramble those letters! \uD83D\uDD24',
            'We\u2019re a team! \uD83D\uDC4F',
        ],
        quiz: [
            'Think carefully! \uD83E\uDD14',
            'Read each question twice! \uD83D\uDC40',
            'Trust yourself! \u2B50',
            'Almost there, keep going! \uD83D\uDCE3',
            'I\u2019m cheering for you! \uD83C\uDF89',
            'You\u2019ve prepared well! \uD83D\uDCDD',
        ],
    };

    // ── Wizard reaction message pools ────────────────────────
    var REACTIONS = {
        correct:  ['\uD83C\uDF1F Correct!', '\u26A1 Nice one!', '\u2B50 Yes!',
                   '\uD83D\uDD25 Keep going!', '\uD83E\uDD29 Great job!'],
        wrong:    ['\uD83D\uDCAA Try again!', '\uD83D\uDC4D Almost!',
                   '\uD83C\uDFAF You\u2019ll get it!', '\uD83D\uDEE1 Don\u2019t give up!'],
        streak:   ['\uD83D\uDD25 STREAK!', '\uD83D\uDD25\uD83D\uDD25 On fire!',
                   '\u26A1 Unstoppable!', '\uD83C\uDF89 Amazing streak!'],
        complete: ['\uD83C\uDF89 You did it!', '\uD83C\uDFC6 Incredible!',
                   '\uD83D\uDCAA Well done!', '\u2B50 Outstanding!'],
        encourage:['\uD83E\uDD29 So close!', '\uD83D\uDCDA Keep going!',
                   '\u2B50 Almost done!', '\uD83D\uDCAA You can do it!'],
    };

    // ── State ────────────────────────────────────────────────
    var petTeam      = 'fire';
    var petMinimized = false;
    var wizMinimized = false;
    var noiseIndex   = 0;
    var msgIndex     = 0;
    var noiseTimer   = null;
    var msgTimer     = null;
    var resumeTimer  = null;

    // ── Helpers ──────────────────────────────────────────────
    function legacyToDisplay(s) {
        s = parseInt(s, 10) || 1;
        if (s <= 2) return 0;
        if (s === 3) return 1;
        if (s === 4) return 2;
        return 3;
    }

    function validTeam(t) {
        return (['fire', 'water', 'grass'].indexOf(t) !== -1) ? t : 'fire';
    }

    function rnd(arr) {
        return arr[Math.floor(Math.random() * arr.length)];
    }

    // ── Build pet widget (bottom-left) ───────────────────────
    function buildPetWidget(team, displayStage, petName) {
        var noises   = PET_NOISES[team] || PET_NOISES.fire;
        var stageSrc = PETS_BASE + team + '/' + STAGE_NAMES[displayStage] + '.svg';

        var wrap = document.createElement('div');
        wrap.className = 'pc-pet-wrap';
        wrap.id        = 'pcPetWrap';

        // Noise bubble
        var noise = document.createElement('div');
        noise.className   = 'pc-noise';
        noise.id          = 'pcNoise';
        noise.textContent = noises.idle[0];

        // Body row
        var body = document.createElement('div');
        body.className = 'pc-pet-body';

        // Avatar
        var avatar = document.createElement('div');
        avatar.className        = 'pc-avatar';
        avatar.id               = 'pcAvatar';
        avatar.dataset.team     = team;
        avatar.dataset.stage    = String(displayStage);
        avatar.title            = petName;
        avatar.setAttribute('aria-label', petName + ' companion');

        var img = document.createElement('img');
        img.src       = stageSrc;
        img.alt       = petName;
        img.width     = 68;
        img.height    = 68;
        img.draggable = false;
        avatar.appendChild(img);

        // Click: expand if minimised, else show a random noise
        avatar.addEventListener('click', function () {
            if (petMinimized) {
                expandPet();
            } else {
                showNoise(rnd(noises.idle), '');
            }
        });

        // Minimize button
        var toggle = document.createElement('button');
        toggle.className = 'pc-toggle';
        toggle.id        = 'pcPetToggle';
        toggle.title     = 'Hide pet';
        toggle.setAttribute('aria-label', 'Hide pet');
        toggle.textContent = '\u2212';
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            minimizePet();
        });

        body.appendChild(avatar);
        body.appendChild(toggle);
        wrap.appendChild(noise);
        wrap.appendChild(body);
        document.body.appendChild(wrap);

        startNoiseCycle(noises.idle);
    }

    // ── Build wizard widget (bottom-right) ───────────────────
    function buildWizardWidget() {
        var msgs = MESSAGES[CONTEXT] || MESSAGES.learn;

        var wrap = document.createElement('div');
        wrap.className = 'pc-wiz-wrap';
        wrap.id        = 'pcWizWrap';

        // Speech bubble
        var bubble = document.createElement('div');
        bubble.className   = 'pc-bubble';
        bubble.id          = 'pcBubble';
        bubble.textContent = msgs[0];

        // Body row: [toggle] [wizard]
        var body = document.createElement('div');
        body.className = 'pc-wiz-body';

        // Minimize button (left of wizard)
        var toggle = document.createElement('button');
        toggle.className = 'pc-toggle';
        toggle.id        = 'pcWizToggle';
        toggle.title     = 'Hide guide';
        toggle.setAttribute('aria-label', 'Hide guide');
        toggle.textContent = '\u2212';
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            minimizeWiz();
        });

        // Wizard avatar (emoji circle)
        var wizAvatar = document.createElement('div');
        wizAvatar.className = 'pc-wiz-avatar';
        wizAvatar.id        = 'pcWizAvatar';
        wizAvatar.title     = 'Your guide';
        wizAvatar.setAttribute('aria-label', 'Wizard guide');
        wizAvatar.textContent = '\uD83E\uDDD9'; // 🧙

        // Click: expand if minimised, else show a random message
        wizAvatar.addEventListener('click', function () {
            if (wizMinimized) {
                expandWiz();
            } else {
                showBubble(rnd(msgs), '');
            }
        });

        body.appendChild(toggle);
        body.appendChild(wizAvatar);
        wrap.appendChild(bubble);
        wrap.appendChild(body);
        document.body.appendChild(wrap);

        startMsgCycle(msgs);
    }

    // ── Pet noise bubble ─────────────────────────────────────
    function showNoise(text, extraClass) {
        var noise = document.getElementById('pcNoise');
        if (!noise) return;
        noise.className       = 'pc-noise' + (extraClass ? ' ' + extraClass : '');
        noise.style.animation = 'none';
        void noise.offsetWidth; // force reflow to replay animation
        noise.style.animation = '';
        noise.textContent     = text;
    }

    function startNoiseCycle(idleNoises) {
        clearInterval(noiseTimer);
        noiseIndex = 0;
        noiseTimer = setInterval(function () {
            if (petMinimized) return;
            if (resumeTimer)  return; // don't interrupt a reaction
            noiseIndex = (noiseIndex + 1) % idleNoises.length;
            showNoise(idleNoises[noiseIndex], '');
        }, 5000);
    }

    // ── Wizard speech bubble ─────────────────────────────────
    function showBubble(text, extraClass) {
        var bubble = document.getElementById('pcBubble');
        if (!bubble) return;
        bubble.className       = 'pc-bubble' + (extraClass ? ' ' + extraClass : '');
        bubble.style.animation = 'none';
        void bubble.offsetWidth;
        bubble.style.animation = '';
        bubble.textContent     = text;
    }

    function startMsgCycle(msgs) {
        clearInterval(msgTimer);
        msgIndex = 0;
        msgTimer = setInterval(function () {
            if (wizMinimized) return;
            if (resumeTimer)  return;
            msgIndex = (msgIndex + 1) % msgs.length;
            showBubble(msgs[msgIndex], '');
        }, 6000);
    }

    // ── Reactions: pet makes noise + animates, wizard speaks ─
    function react(type) {
        var avatar    = document.getElementById('pcAvatar');
        var wizAvatar = document.getElementById('pcWizAvatar');
        var noises    = PET_NOISES[petTeam] || PET_NOISES.fire;

        var noisePool = noises[type] || noises.correct;
        var msgPool   = REACTIONS[type] || REACTIONS.correct;
        var tintCls   = type === 'correct'  ? 'pc-b-correct'
                      : type === 'wrong'    ? 'pc-b-wrong'
                      : (type === 'streak' || type === 'complete') ? 'pc-b-streak'
                      : '';

        // Pet: animal noise + jump/shake animation
        showNoise(rnd(noisePool), tintCls);
        if (avatar) {
            var reactCls = 'pc-react-' + type;
            avatar.classList.add('pc-react', reactCls);
            setTimeout(function () {
                avatar.classList.remove('pc-react', reactCls);
            }, 700);
        }

        // Wizard: message text + spin animation
        showBubble(rnd(msgPool), tintCls);
        if (wizAvatar) {
            wizAvatar.classList.add('pc-wiz-react');
            setTimeout(function () {
                wizAvatar.classList.remove('pc-wiz-react');
            }, 600);
        }

        // If minimised, briefly pop open then re-minimise
        if (petMinimized) { expandPet(); setTimeout(minimizePet, 3800); }
        if (wizMinimized) { expandWiz(); setTimeout(minimizeWiz, 3800); }

        // Resume idle after 3.5 s
        clearTimeout(resumeTimer);
        resumeTimer = setTimeout(function () {
            resumeTimer = null;
            var idleNoises = (PET_NOISES[petTeam] || PET_NOISES.fire).idle;
            var msgs       = MESSAGES[CONTEXT] || MESSAGES.learn;
            showNoise(idleNoises[noiseIndex], '');
            showBubble(msgs[msgIndex], '');
        }, 3500);
    }

    // ── Minimize / Expand ────────────────────────────────────
    function minimizePet() {
        petMinimized = true;
        var wrap = document.getElementById('pcPetWrap');
        if (wrap) wrap.classList.add('pc-min');
    }

    function expandPet() {
        petMinimized = false;
        var wrap = document.getElementById('pcPetWrap');
        if (wrap) wrap.classList.remove('pc-min');
        var idleNoises = (PET_NOISES[petTeam] || PET_NOISES.fire).idle;
        showNoise(idleNoises[noiseIndex], '');
    }

    function minimizeWiz() {
        wizMinimized = true;
        var wrap = document.getElementById('pcWizWrap');
        if (wrap) wrap.classList.add('pc-min');
    }

    function expandWiz() {
        wizMinimized = false;
        var wrap = document.getElementById('pcWizWrap');
        if (wrap) wrap.classList.remove('pc-min');
        var msgs = MESSAGES[CONTEXT] || MESSAGES.learn;
        showBubble(msgs[msgIndex], '');
    }

    // ── Init: fetch profile then build both widgets ───────────
    function init() {
        var token = localStorage.getItem('eq_token');
        if (!token) return;

        fetch(API_PROFILE, {
            headers: {
                'Content-Type':  'application/json',
                'Authorization': 'Bearer ' + token,
            },
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (!json.success) return;
            var p            = json.data.profile;
            var team         = validTeam(p.team || 'fire');
            var displayStage = legacyToDisplay(p.eggStage);
            var petName      = PET_NAMES[team][displayStage];
            petTeam          = team;
            buildPetWidget(team, displayStage, petName);
            if (SHOW_WIZARD) buildWizardWidget();
        })
        .catch(function () { /* companion is optional — fail silently */ });
    }

    // ── Listen for custom 'petReact' events ──────────────────
    window.addEventListener('petReact', function (e) {
        if (e.detail && e.detail.type) react(e.detail.type);
    });

    // ── Public API ───────────────────────────────────────────
    window.PetCompanion = {
        react:       react,
        minimizePet: minimizePet,
        expandPet:   expandPet,
        minimizeWiz: minimizeWiz,
        expandWiz:   expandWiz,
        // legacy aliases
        minimize:    minimizePet,
        expand:      expandPet,
    };

    // ── Boot ─────────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
