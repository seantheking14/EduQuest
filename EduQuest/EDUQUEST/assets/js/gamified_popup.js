/**
 * gamified_popup.js — EduQuest Gamified Popup Engine
 *
 * Exposes: window.showGamePopup(config)
 *
 * config: {
 *   type        : 'success'|'levelup'|'badge'|'streak'|'reminder'|'welcome'|'encouragement'
 *   title       : string
 *   message     : string
 *   icon        : string (emoji)
 *   confetti    : boolean  (default false)
 *   autoClose   : number   (ms, 0 = no auto-close)
 *   buttonText  : string   (default 'Awesome!')
 *   onClose     : function (called after popup dismisses)
 * }
 *
 * Calls are queued — multiple showGamePopup() calls display one at a time.
 * Full keyboard accessibility: Tab-trap, Escape to dismiss.
 */

(function () {
  'use strict';

  /* ── Palette ───────────────────────────────────────────────── */

  var PALETTE = {
    success:     { main: '#22c55e', light: '#dcfce7', text: '#15803d', glow: 'rgba(34,197,94,0.45)'   },
    levelup:     { main: '#f59e0b', light: '#fef3c7', text: '#b45309', glow: 'rgba(245,158,11,0.45)'  },
    badge:       { main: '#a855f7', light: '#f3e8ff', text: '#7e22ce', glow: 'rgba(168,85,247,0.45)'  },
    streak:      { main: '#f97316', light: '#ffedd5', text: '#c2410c', glow: 'rgba(249,115,22,0.45)'  },
    reminder:    { main: '#3b82f6', light: '#dbeafe', text: '#1d4ed8', glow: 'rgba(59,130,246,0.45)'  },
    welcome:     { main: '#14b8a6', light: '#ccfbf1', text: '#0f766e', glow: 'rgba(20,184,166,0.45)'  },
    encouragement:{ main: '#ec4899', light: '#fce7f3', text: '#be185d', glow: 'rgba(236,72,153,0.45)' }
  };

  var CONFETTI_COLORS = [
    '#f43f5e','#fb923c','#facc15',
    '#4ade80','#60a5fa','#c084fc',
    '#f472b6','#34d399','#fbbf24'
  ];

  /* ── State ─────────────────────────────────────────────────── */

  var _queue      = [];
  var _isOpen     = false;
  var _prevFocus  = null;

  /* ── Public API ────────────────────────────────────────────── */

  window.showGamePopup = function (config) {
    if (!config || typeof config !== 'object') return;
    _queue.push(config);
    if (!_isOpen) { _processQueue(); }
  };

  /* ── Queue ─────────────────────────────────────────────────── */

  function _processQueue() {
    if (_queue.length === 0) { _isOpen = false; return; }
    _isOpen = true;
    _render(_queue.shift());
  }

  /* ── Render ────────────────────────────────────────────────── */

  function _render(cfg) {
    var type       = cfg.type || 'success';
    var colors     = PALETTE[type] || PALETTE.success;
    var btnText    = cfg.buttonText || 'Awesome!';
    var useGlow    = (type === 'levelup' || type === 'badge');

    /* Save current focus for restoration */
    _prevFocus = document.activeElement;

    /* ── Overlay ── */
    var overlay = document.createElement('div');
    overlay.className = 'gp-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-labelledby', 'gp-title');

    /* ── Card ── */
    var card = document.createElement('div');
    card.className = 'gp-card gp-type-' + type;
    card.style.setProperty('--gp-main',  colors.main);
    card.style.setProperty('--gp-light', colors.light);
    card.style.setProperty('--gp-text',  colors.text);
    card.style.setProperty('--gp-glow',  colors.glow);

    /* Prevent overlay click from bubbling through the card */
    card.addEventListener('click', function (e) { e.stopPropagation(); });

    /* ── Stars decoration ── */
    var stars = document.createElement('div');
    stars.className = 'gp-stars';
    stars.setAttribute('aria-hidden', 'true');
    stars.innerHTML = '<span class="gp-star">&#11088;</span>' +
                      '<span class="gp-star">&#10024;</span>' +
                      '<span class="gp-star">&#11088;</span>';
    card.appendChild(stars);

    /* ── Icon ── */
    var iconWrap = document.createElement('div');
    iconWrap.className = 'gp-icon-wrap' + (useGlow ? ' gp-icon-glow' : '');
    iconWrap.setAttribute('aria-hidden', 'true');

    var iconEl = document.createElement('div');
    iconEl.className = 'gp-icon';
    iconEl.textContent = cfg.icon || '\u2B50';
    iconWrap.appendChild(iconEl);
    card.appendChild(iconWrap);

    /* ── Title ── */
    var titleEl = document.createElement('h2');
    titleEl.id = 'gp-title';
    titleEl.className = 'gp-title';
    titleEl.textContent = cfg.title || '';
    card.appendChild(titleEl);

    /* ── Message ── */
    var msgEl = document.createElement('p');
    msgEl.className = 'gp-message';
    msgEl.textContent = cfg.message || '';
    card.appendChild(msgEl);

    /* ── Button ── */
    var btn = document.createElement('button');
    btn.className = 'gp-btn';
    btn.textContent = btnText;
    btn.setAttribute('type', 'button');
    card.appendChild(btn);

    overlay.appendChild(card);

    /* ── Confetti ── */
    if (cfg.confetti) { _spawnConfetti(overlay); }

    document.body.appendChild(overlay);

    /* Focus the button after the entrance animation starts */
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { btn.focus(); });
    });

    /* ── Dismiss handler ── */
    function close() { _destroyPopup(overlay, cfg.onClose); }

    btn.addEventListener('click', close);

    /* Close on overlay background click */
    overlay.addEventListener('click', close);

    /* Keyboard: Escape closes, Tab stays trapped */
    overlay._gpKeyHandler = function (e) {
      if (e.key === 'Escape') { close(); return; }
      if (e.key === 'Tab')    { _trapFocus(e, overlay); }
    };
    document.addEventListener('keydown', overlay._gpKeyHandler);

    /* Auto-close */
    if (cfg.autoClose && cfg.autoClose > 0) {
      overlay._gpAutoTimer = setTimeout(close, cfg.autoClose);
    }
  }

  /* ── Focus trap ────────────────────────────────────────────── */

  function _trapFocus(e, overlay) {
    var focusable = overlay.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    if (focusable.length === 0) return;
    var first = focusable[0];
    var last  = focusable[focusable.length - 1];
    if (e.shiftKey) {
      if (document.activeElement === first) { e.preventDefault(); last.focus(); }
    } else {
      if (document.activeElement === last)  { e.preventDefault(); first.focus(); }
    }
  }

  /* ── Destroy popup ─────────────────────────────────────────── */

  function _destroyPopup(overlay, onClose) {
    if (overlay._gpAutoTimer) { clearTimeout(overlay._gpAutoTimer); }
    document.removeEventListener('keydown', overlay._gpKeyHandler);

    overlay.classList.add('gp-overlay-out');

    setTimeout(function () {
      if (overlay.parentNode) { overlay.parentNode.removeChild(overlay); }
      /* Restore focus */
      try { if (_prevFocus && typeof _prevFocus.focus === 'function') { _prevFocus.focus(); } } catch (ignore) {}
      _isOpen = false;
      if (typeof onClose === 'function') { onClose(); }
      _processQueue();
    }, 320);
  }

  /* ── Confetti ──────────────────────────────────────────────── */

  function _spawnConfetti(container) {
    for (var i = 0; i < 24; i++) {
      (function (index) {
        var el       = document.createElement('div');
        el.className = 'gp-confetti-piece';

        var x      = Math.random() * 100;                              /* % left  */
        var delay  = Math.random() * 0.55;                             /* seconds */
        var dur    = 1.3 + Math.random() * 0.9;                       /* seconds */
        var size   = 7 + Math.floor(Math.random() * 9);               /* px      */
        var color  = CONFETTI_COLORS[index % CONFETTI_COLORS.length];
        var driftX = (Math.random() - 0.5) * 240;                     /* px      */
        var rotate = Math.floor(Math.random() * 720);                  /* degrees */
        var round  = Math.random() > 0.45 ? '50%' : '2px';

        el.style.cssText =
          'left:'             + x      + '%;'  +
          'width:'            + size   + 'px;' +
          'height:'           + size   + 'px;' +
          'background:'       + color  + ';'   +
          'border-radius:'    + round  + ';'   +
          '--gp-drift:'       + driftX + 'px;' +
          '--gp-rotate:'      + rotate + 'deg;'+
          'animation: gp-confetti-fall ' + dur + 's ' + delay + 's ease-out forwards;';

        container.appendChild(el);

        setTimeout(function () {
          if (el.parentNode) { el.parentNode.removeChild(el); }
        }, (dur + delay + 0.15) * 1000);
      })(i);
    }
  }

})();
