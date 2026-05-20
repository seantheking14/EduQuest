/**
 * subject.js
 * Subject detail page — loads lessons roadmap for a given subject.
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

    /* Get subjectId from URL */
    const params = new URLSearchParams(window.location.search);
    const subjectId = params.get('id');

    if (!subjectId) {
        window.location.href = 'learning.html';
        return;
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadLessons();
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
        } catch (_) { /* silent */ }
    }

    /* ── Load Lessons ── */
    async function loadLessons() {
        try {
            const res = await fetch(API_BASE + '/lessons.php?subjectId=' + encodeURIComponent(subjectId), {
                headers: authHeaders(),
            });
            const json = await res.json();

            if (!json.success) {
                renderError(json.message || 'Failed to load lessons');
                return;
            }

            renderBanner(json.data.subject);
            renderRoadmap(json.data.lessons);
        } catch (err) {
            renderError('Could not connect to the server.');
        }
    }

    /* ── Render Subject Banner ── */
    function renderBanner(subject) {
        const breadcrumb = document.getElementById('breadcrumbSubject');
        if (breadcrumb) breadcrumb.textContent = subject.name;

        document.title = subject.name + ' — EduQuest';

        const icons = { math: '🔢', self_care: '🌿', english: '📖' };
        const icon = icons[subject.slug] || '📘';

        const completed = subject.completedLessons || 0;
        const total = subject.totalLessons || 0;
        const pct = total > 0 ? Math.round((completed / total) * 100) : 0;

        document.getElementById('subjectHeader').innerHTML = `
            <div class="subject-banner ${subject.slug || ''}">
                <div class="banner-decoration">${icon}</div>
                <div class="banner-content">
                    <h1 class="banner-title">${icon} ${escapeHtml(subject.name)}</h1>
                    <p class="banner-desc">${escapeHtml(subject.description || '')}</p>
                    <div class="banner-stats">
                        <div class="banner-stat">📖 ${completed}/${total} lessons</div>
                        <div class="banner-stat">⚡ ${subject.totalXpEarned || 0} XP earned</div>
                        <div class="banner-stat">📊 ${pct}% complete</div>
                    </div>
                </div>
            </div>
        `;
    }

    /* ── Render Lesson Roadmap ── */
    function renderRoadmap(lessons) {
        const container = document.getElementById('lessonRoadmap');
        if (!lessons || lessons.length === 0) {
            container.innerHTML = '<p style="text-align:center;color:#9ca3af;padding:40px 0;">No lessons yet — check back soon!</p>';
            return;
        }

        let html = '<div class="roadmap-path">';

        lessons.forEach((lesson, idx) => {
            const status = lesson.status || 'locked';
            const nodeLabel = status === 'completed' ? '✓' : (idx + 1);
            const cardClass = status === 'locked' ? 'locked' : (status === 'completed' ? 'completed' : '');

            html += `
            <div class="lesson-road-card" data-id="${lesson.id}" data-status="${status}">
                <div class="road-node ${status}">${nodeLabel}</div>
                <div class="road-card-body ${cardClass}" onclick="handleLessonClick(${lesson.id}, '${status}')">
                    <div class="road-card-header">
                        <h3 class="road-card-title">
                            <span class="lesson-num">Lesson ${lesson.orderNum}</span>
                            ${escapeHtml(lesson.title)}
                        </h3>
                        <div class="road-card-badges">
                            <span class="difficulty-pill ${lesson.difficulty || 'easy'}">${lesson.difficulty || 'easy'}</span>
                            <span class="xp-pill">⚡ ${lesson.xpReward || 0} XP</span>
                        </div>
                    </div>
                    <p class="road-card-desc">${escapeHtml(lesson.description || '')}</p>
                    <div class="road-card-meta">
                        <div class="meta-left">
                            <span>📄 ${lesson.totalPages || 0} pages</span>
                            ${lesson.estimatedMinutes ? '<span>⏱ ' + lesson.estimatedMinutes + ' min</span>' : ''}
                            ${status === 'in_progress' ? '<span>📖 Page ' + (lesson.currentPage || 1) + '/' + (lesson.totalPages || 1) + '</span>' : ''}
                        </div>
                        ${renderStatusLabel(status)}
                    </div>
                    ${renderQuizIndicator(lesson)}
                </div>
            </div>`;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    /* ── Status Label ── */
    function renderStatusLabel(status) {
        const labels = {
            completed: '<span class="road-card-status completed">✅ Completed</span>',
            in_progress: '<span class="road-card-status in-progress">📖 In Progress</span>',
            available: '<span class="road-card-status available">🟡 Ready to Start</span>',
            locked: '<span class="road-card-status locked-text">🔒 Locked</span>',
        };
        return labels[status] || labels.locked;
    }

    /* ── Quiz Indicator ── */
    function renderQuizIndicator(lesson) {
        if (!lesson.quiz) return '';

        const q = lesson.quiz;
        let statusHtml = '';
        if (q.bestScore !== null && q.bestScore !== undefined) {
            const passed = q.passed ? 'passed' : 'not-passed';
            statusHtml = `<span class="quiz-status ${passed}">${q.passed ? '✅ Passed' : '❌ ' + q.bestScore + '%'} (${q.attempts} attempt${q.attempts !== 1 ? 's' : ''})</span>`;
        } else {
            statusHtml = '<span class="quiz-status">Not attempted</span>';
        }

        return `
            <div class="quiz-indicator">
                <span>📝 Quiz: ${escapeHtml(q.title)}</span>
                ${statusHtml}
            </div>
        `;
    }

    /* ── Lesson Click Handler ── */
    window.handleLessonClick = function (lessonId, status) {
        if (status === 'locked') return;
        window.location.href = 'lesson.html?id=' + lessonId + '&subjectId=' + subjectId;
    };

    /* ── Error ── */
    function renderError(msg) {
        document.getElementById('subjectHeader').innerHTML = `
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
