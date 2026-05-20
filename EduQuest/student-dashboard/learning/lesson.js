/**
 * lesson.js
 * Multi-page lesson viewer with progress tracking.
 * Includes guide character with idle detection and milestone triggers.
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
    const lessonId = params.get('id');
    const subjectId = params.get('subjectId');

    if (!lessonId) {
        window.location.href = 'learning.html';
        return;
    }

    let lessonData = null;
    let pages = [];
    let currentPage = 0; // 0-indexed
    let completed = false;

    /* ── Guide / Idle detection state ── */
    const IDLE_TRIGGER_MS = 15000;  // 15 seconds of inactivity
    const IDLE_COOLDOWN_MS = 20000; // 20 seconds between idle prompts
    let idleTimer = null;
    let lastIdlePrompt = 0;
    let halfwayTriggered = false;

    document.addEventListener('DOMContentLoaded', () => {
        loadLesson();
        loadNavStats();
        startIdleDetection();
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
        } catch (_) { /* silent */ }
    }

    /* ═══════════════════════════════════════════
       IDLE DETECTION
       ═══════════════════════════════════════════ */

    function startIdleDetection() {
        const events = ['click', 'scroll', 'keydown', 'mousemove', 'touchstart'];
        events.forEach(evt => {
            document.addEventListener(evt, resetIdleTimer, { passive: true });
        });
        resetIdleTimer();
    }

    function resetIdleTimer() {
        if (idleTimer) clearTimeout(idleTimer);
        idleTimer = setTimeout(handleIdle, IDLE_TRIGGER_MS);
    }

    function handleIdle() {
        const now = Date.now();
        if (now - lastIdlePrompt < IDLE_COOLDOWN_MS) return;
        lastIdlePrompt = now;

        if (window.GuideCharacter) {
            GuideCharacter.say('lessonIdle');
        }
    }

    /* ── Load Lesson Content ── */
    async function loadLesson() {
        try {
            const res = await fetch(
                API_BASE + '/lesson-content.php?lessonId=' + encodeURIComponent(lessonId),
                { headers: authHeaders() }
            );
            const json = await res.json();

            if (!json.success) {
                renderError(json.message || 'Failed to load lesson');
                return;
            }

            lessonData = json.data.lesson;
            pages = json.data.pages || [];
            completed = lessonData.status === 'completed';

            // Resume from last page if in progress
            if (lessonData.currentPage && lessonData.currentPage > 1) {
                currentPage = lessonData.currentPage - 1; // convert to 0-indexed
            }

            updateBreadcrumb();
            renderViewer();

            // Guide greets on lesson start
            if (window.GuideCharacter) {
                GuideCharacter.show('lessonStart');
            }
        } catch (err) {
            renderError('Could not connect to the server.');
        }
    }

    /* ── Breadcrumb ── */
    function updateBreadcrumb() {
        const subjectLink = document.getElementById('breadcrumbSubject');
        const lessonSpan = document.getElementById('breadcrumbLesson');

        if (subjectLink && subjectId) {
            subjectLink.href = 'subject.html?id=' + subjectId;
            subjectLink.textContent = lessonData.subjectName || 'Subject';
        }
        if (lessonSpan) {
            lessonSpan.textContent = lessonData.title || 'Lesson';
        }
        document.title = (lessonData.title || 'Lesson') + ' — EduQuest';
    }

    /* ── Render Full Viewer ── */
    function renderViewer() {
        const viewer = document.getElementById('lessonViewer');
        const slug = lessonData.subjectSlug || '';

        const icons = { math: '🔢', self_care: '🌿', english: '📖' };
        const icon = icons[slug] || '📘';

        const pct = pages.length > 0 ? Math.round(((currentPage + 1) / pages.length) * 100) : 0;

        let html = '';

        // Header
        html += `
        <div class="lesson-header ${slug}">
            <div class="decor">${icon}</div>
            <div class="lesson-header-content">
                <h1>${escapeHtml(lessonData.title)}</h1>
                <p>${escapeHtml(lessonData.description || '')}</p>
                <div class="lesson-meta-row">
                    <span>📄 ${pages.length} pages</span>
                    <span>⚡ ${lessonData.xpReward || 0} XP</span>
                    <span>📊 ${lessonData.difficulty || 'easy'}</span>
                    ${lessonData.estimatedMinutes ? '<span>⏱ ~' + lessonData.estimatedMinutes + ' min</span>' : ''}
                </div>
            </div>
        </div>`;

        // Progress bar (no percentage text — just visual)
        html += `
        <div class="page-progress-bar">
            <span class="progress-label">Progress</span>
            <div class="progress-track"><div class="progress-fill" style="width:${pct}%"></div></div>
        </div>`;

        // Current page content
        if (pages.length > 0) {
            const page = pages[currentPage];
            html += renderPageCard(page);
        } else {
            html += '<p style="text-align:center;color:#9ca3af;padding:40px 0">This lesson has no content yet.</p>';
        }

        // Navigation buttons
        if (pages.length > 0) {
            html += renderNavButtons();
        }

        viewer.innerHTML = html;
    }

    /* ── Render Single Page Card ── */
    function renderPageCard(page) {
        let html = '<div class="lesson-page-card">';

        // Illustration area
        if (page.illustration) {
            html += `<div class="page-illustration"><span class="illustration-emoji">${page.illustration}</span></div>`;
        }

        // Content
        html += `<div class="page-content">
            <h2>${escapeHtml(page.title || 'Page ' + (currentPage + 1))}</h2>
            <div class="page-body">${page.contentHtml || escapeHtml(page.contentText || '')}</div>`;

        // Tip box
        if (page.tipText) {
            html += `<div class="lesson-tip">${escapeHtml(page.tipText)}</div>`;
        }

        html += '</div></div>';
        return html;
    }

    /* ── Navigation Buttons ── */
    function renderNavButtons() {
        const isFirst = currentPage === 0;
        const isLast = currentPage === pages.length - 1;

        let html = '<div class="lesson-nav">';

        // PREV
        html += `<button class="lesson-nav-btn btn-prev ${isFirst ? 'btn-disabled' : ''}" onclick="goPage(-1)" ${isFirst ? 'disabled' : ''}>
            ← Previous
        </button>`;

        // Page indicator
        html += `<span class="page-indicator">Page ${currentPage + 1} of ${pages.length}</span>`;

        // NEXT / COMPLETE / QUIZ
        if (isLast) {
            if (completed) {
                if (lessonData.hasQuiz) {
                    html += `<button class="lesson-nav-btn btn-quiz" onclick="goQuiz()">📝 Take Quiz →</button>`;
                } else {
                    html += `<button class="lesson-nav-btn btn-next" onclick="goBack()">← Back to Lessons</button>`;
                }
            } else {
                html += `<button class="lesson-nav-btn btn-complete" onclick="completeLesson()">✅ Complete Lesson</button>`;
            }
        } else {
            html += `<button class="lesson-nav-btn btn-next" onclick="goPage(1)">Next →</button>`;
        }

        html += '</div>';
        return html;
    }

    /* ── Page Navigation ── */
    window.goPage = function (dir) {
        const target = currentPage + dir;
        if (target < 0 || target >= pages.length) return;
        currentPage = target;
        saveProgress();
        renderViewer();
        resetIdleTimer();
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Guide milestone triggers
        triggerGuideMilestone();
    };

    /* ═══════════════════════════════════════════
       GUIDE MILESTONE TRIGGERS
       ═══════════════════════════════════════════ */

    function triggerGuideMilestone() {
        if (!window.GuideCharacter) return;

        const pct = pages.length > 0 ? Math.round(((currentPage + 1) / pages.length) * 100) : 0;

        // 50% milestone
        if (pct >= 50 && !halfwayTriggered) {
            halfwayTriggered = true;
            GuideCharacter.say('lessonHalfway');
            return;
        }

        // Before quiz (last page)
        if (currentPage === pages.length - 1 && lessonData.hasQuiz) {
            GuideCharacter.say('lessonBeforeQuiz');
            return;
        }

        // General page-advance encouragement (every ~3 pages)
        if ((currentPage + 1) % 3 === 0) {
            GuideCharacter.say('lessonProgress');
        }
    }

    /* ── Save Progress ── */
    async function saveProgress() {
        try {
            await fetch(API_BASE + '/lesson-content.php', {
                method: 'POST',
                headers: authHeaders(),
                body: JSON.stringify({
                    lessonId: parseInt(lessonId),
                    currentPage: currentPage + 1, // 1-indexed for API
                }),
            });
        } catch (_) { /* silent */ }
    }

    /* ── Complete Lesson ── */
    window.completeLesson = async function () {
        try {
            const res = await fetch(API_BASE + '/lesson-content.php', {
                method: 'POST',
                headers: authHeaders(),
                body: JSON.stringify({
                    lessonId: parseInt(lessonId),
                    currentPage: pages.length,
                    completed: true,
                }),
            });
            const json = await res.json();

            if (json.success) {
                completed = true;
                showCompletionSplash(json.data);

                // Guide celebrates
                if (window.GuideCharacter) {
                    GuideCharacter.say('lessonEnd');
                }
                if (window.GameHelpers) GameHelpers.sparkleEffect();
            } else {
                alert(json.message || 'Could not complete the lesson.');
            }
        } catch (err) {
            alert('Network error — please try again.');
        }
    };

    /* ── Completion Splash Screen ── */
    function showCompletionSplash(data) {
        const viewer = document.getElementById('lessonViewer');
        const xpEarned = data.xpAwarded || lessonData.xpReward || 0;

        let html = `
        <div class="completion-splash">
            <span class="splash-emoji">🎉</span>
            <h2>Lesson Complete!</h2>
            <p class="xp-gained">+${xpEarned} XP earned!</p>
            <div class="completion-actions">`;

        if (lessonData.hasQuiz) {
            html += `<button class="lesson-nav-btn btn-quiz" onclick="goQuiz()">📝 Take the Quiz</button>`;
        }

        html += `
                <button class="lesson-nav-btn btn-prev" onclick="goBack()">← Back to Lessons</button>
            </div>
        </div>`;

        viewer.innerHTML = html;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /* ── Quiz Navigation ── */
    window.goQuiz = function () {
        if (lessonData.quizId) {
            window.location.href = 'quiz.html?id=' + lessonData.quizId + '&lessonId=' + lessonId + '&subjectId=' + subjectId;
        }
    };

    /* ── Back to Subject ── */
    window.goBack = function () {
        if (subjectId) {
            window.location.href = 'subject.html?id=' + subjectId;
        } else {
            window.location.href = 'learning.html';
        }
    };

    /* ── Error ── */
    function renderError(msg) {
        document.getElementById('lessonViewer').innerHTML = `
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
