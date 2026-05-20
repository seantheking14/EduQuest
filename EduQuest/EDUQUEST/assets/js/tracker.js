/**
 * tracker.js — Passive student interaction tracker for EduQuest
 *
 * Reads identity from localStorage (bearer-token auth stack):
 *   eduquest_user  → { id, role, ... }  (users.id)
 *   eq_token       → Bearer JWT
 *
 * Each page that wants tracking must set two globals BEFORE this script runs:
 *   window.EDUQUEST_PAGE_NAME  = 'dashboard' | 'learning' | 'quiz' | ...
 *
 * The student_id sent to every endpoint is the users.id from localStorage;
 * the server resolves students.id and validates ownership.
 *
 * Usage (add to any student HTML, just before </body>):
 *   <script>window.EDUQUEST_PAGE_NAME = 'dashboard';</script>
 *   <script src="../../EDUQUEST/assets/js/tracker.js"></script>
 */
(function () {
    'use strict';

    // ── Identity ───────────────────────────────────────────────────────────────
    const _user  = (() => { try { return JSON.parse(localStorage.getItem('eduquest_user') || 'null'); } catch (_) { return null; } })();
    const _token = localStorage.getItem('eq_token') || '';
    const _page  = (typeof window.EDUQUEST_PAGE_NAME === 'string' && window.EDUQUEST_PAGE_NAME.trim())
                    ? window.EDUQUEST_PAGE_NAME.trim()
                    : 'unknown';

    // Only run for authenticated students
    if (!_user || !_token || _user.role !== 'student') return;

    const _userId = _user.id;   // users.id — server resolves students.id

    const _headers = () => ({
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + _token,
    });

    // Resolve API base from the script's own location so it works regardless
    // of where the HTML file lives in the directory tree.
    const _scriptSrc = (function () {
        const scripts = document.querySelectorAll('script[src*="tracker.js"]');
        if (scripts.length) return scripts[scripts.length - 1].src;
        return location.href;
    })();
    const _apiBase = _scriptSrc.replace(/\/assets\/js\/tracker\.js.*$/, '/api');

    // ── Page Time Tracking ─────────────────────────────────────────────────────
    let _pageStart = null;

    document.addEventListener('DOMContentLoaded', function () {
        _pageStart = Date.now();
    });

    // Fallback: if DOMContentLoaded already fired
    if (document.readyState === 'interactive' || document.readyState === 'complete') {
        _pageStart = _pageStart || Date.now();
    }

    function _flushPageSession() {
        if (!_pageStart) return;
        const duration = Math.round((Date.now() - _pageStart) / 1000);
        if (duration < 1) return;

        const payload = JSON.stringify({
            user_id:          _userId,
            page_name:        _page,
            duration_seconds: duration,
        });

        const url = _apiBase + '/track_page_session.php';
        fetch(url, {
            method: 'POST',
            headers: _headers(),
            body: payload,
            keepalive: true,
        }).catch(function () {});
        _pageStart = null; // prevent double-flush
    }

    window.addEventListener('beforeunload', function () {
        _flushClickBatch();
        _flushHoverBatch();
        _flushPageSession();
    });

    // ── Question Timer ─────────────────────────────────────────────────────────
    let _qTimer      = null; // { start, quizId, questionId }

    /**
     * Call when a question renders.
     * @param {number|string} quizId
     * @param {number|string} questionId
     */
    window.startQuestionTimer = function (quizId, questionId) {
        _qTimer = { start: Date.now(), quizId: quizId, questionId: questionId };
    };

    /**
     * Call when the student submits an answer.
     * @param {boolean|null} answeredCorrectly
     * @param {number}       attemptNumber
     */
    window.stopQuestionTimer = function (answeredCorrectly, attemptNumber) {
        if (!_qTimer) return;
        const timeSpent = Math.round((Date.now() - _qTimer.start) / 1000);
        const body = {
            user_id:            _userId,
            quiz_id:            _qTimer.quizId,
            question_id:        _qTimer.questionId,
            time_spent_seconds: timeSpent,
            attempt_number:     attemptNumber || 1,
            answered_correctly: answeredCorrectly === true ? 1 : (answeredCorrectly === false ? 0 : null),
        };
        fetch(_apiBase + '/track_question_time.php', {
            method: 'POST',
            headers: _headers(),
            body:    JSON.stringify(body),
            keepalive: true,
        }).catch(function () {});
        _qTimer = null;
    };

    // ── Click Batch ────────────────────────────────────────────────────────────
    // { element_label: count }
    const _clickBatch = {};

    function _resolveLabel(el) {
        if (!el) return null;
        // Walk up to find the nearest element with data-track
        let node = el;
        while (node && node !== document.body) {
            if (node.dataset && node.dataset.track) return node.dataset.track.trim().slice(0, 80);
            node = node.parentElement;
        }
        // Fallback: aria-label or innerText
        const raw = el.getAttribute('aria-label') || el.innerText || '';
        const label = raw.trim().replace(/\s+/g, ' ').slice(0, 80);
        return label || null;
    }

    document.addEventListener('click', function (e) {
        const label = _resolveLabel(e.target);
        if (!label) return;
        _clickBatch[label] = (_clickBatch[label] || 0) + 1;
    }, true);

    function _flushClickBatch() {
        const labels = Object.keys(_clickBatch);
        if (!labels.length) return;
        const clicks = labels.map(function (l) { return { element_label: l, count: _clickBatch[l] }; });
        labels.forEach(function (l) { delete _clickBatch[l]; });

        const payload = JSON.stringify({
            user_id:   _userId,
            page_name: _page,
            clicks:    clicks,
        });
        const url = _apiBase + '/track_clicks.php';
        fetch(url, { method: 'POST', headers: _headers(), body: payload, keepalive: true }).catch(function () {});
    }

    setInterval(_flushClickBatch, 30000);

    // ── Hover Batch ────────────────────────────────────────────────────────────
    // { element_label: total_ms }
    const _hoverBatch  = {};
    const _hoverActive = {}; // { element_label: start_time }

    document.addEventListener('mouseenter', function (e) {
        const el = e.target;
        if (!el || !el.dataset || !el.dataset.track) return;
        const label = el.dataset.track.trim().slice(0, 80);
        if (!label) return;
        _hoverActive[label] = Date.now();
    }, true);

    document.addEventListener('mouseleave', function (e) {
        const el = e.target;
        if (!el || !el.dataset || !el.dataset.track) return;
        const label = el.dataset.track.trim().slice(0, 80);
        if (!label || !_hoverActive[label]) return;
        const ms = Date.now() - _hoverActive[label];
        delete _hoverActive[label];
        _hoverBatch[label] = (_hoverBatch[label] || 0) + ms;
    }, true);

    function _flushHoverBatch() {
        const labels = Object.keys(_hoverBatch);
        if (!labels.length) return;
        const hovers = labels.map(function (l) { return { element_label: l, total_hover_ms: _hoverBatch[l] }; });
        labels.forEach(function (l) { delete _hoverBatch[l]; });

        const payload = JSON.stringify({
            user_id:   _userId,
            page_name: _page,
            hovers:    hovers,
        });
        const url = _apiBase + '/track_hovers.php';
        fetch(url, { method: 'POST', headers: _headers(), body: payload, keepalive: true }).catch(function () {});
    }

    setInterval(_flushHoverBatch, 30000);

})();
