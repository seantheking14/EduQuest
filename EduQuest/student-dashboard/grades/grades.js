/**
 * grades.js – Student Grade Analytics Page
 */
(function () {
  'use strict';

  const LETTER_COLORS = { A: '#16a34a', B: '#3b82f6', C: '#d97706', D: '#ea580c', F: '#dc2626' };
  const TYPE_COLORS   = {
    quiz:          '#8b5cf6',
    exam:          '#dc2626',
    assignment:    '#3b82f6',
    project:       '#0ea5e9',
    participation: '#16a34a',
    final:         '#b91c1c',
  };
  const MONTH_NAMES = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  // ── Auth check ─────────────────────────────────────────────
  const token = localStorage.getItem('eq_token');
  const user  = JSON.parse(localStorage.getItem('eduquest_user') || '{}');

  if (!token || !user.email || user.role !== 'student') {
    window.location.href = '../../auth/login/login.html?role=student';
    return;
  }

  // Load student progress for navbar stats
  function loadNavStats() {
    const progress = JSON.parse(localStorage.getItem('student_progress') || '{}');
    const xpEl = document.getElementById('xpPoints');
    const streakEl = document.getElementById('streakDays');
    if (xpEl && progress.xp)     xpEl.textContent = progress.xp.toLocaleString() + ' XP';
    if (streakEl && progress.streak) streakEl.textContent = progress.streak + ' days';
  }

  // ── Main data loader ──────────────────────────────────────
  async function loadGradeAnalytics() {
    try {
      const res  = await fetch('../../EDUQUEST/api/analytics/student-grades.php', {
        headers: {
          'Content-Type':  'application/json',
          'Authorization': 'Bearer ' + token,
        }
      });
      const json = await res.json();

      if (!json.success) { showError(json.message); return; }
      const d = json.data;

      // ── Top stats ──────────────────────────────────────────
      const s = d.summary;
      setText('statTotal', s.total_grades);
      setText('statAvg',   s.class_average ? s.class_average + '%' : '–');
      setText('statHigh',  s.highest_pct   ? s.highest_pct + '%'  : '–');
      setText('statLow',   s.lowest_pct    ? s.lowest_pct + '%'   : '–');

      // ── Grade distribution ─────────────────────────────────
      renderBarChart('distributionChart', d.distribution.map(r => ({
        label: 'Grade ' + r.letter,
        value: parseInt(r.cnt),
        color: LETTER_COLORS[r.letter] || '#64748b',
      })));

      // ── By assessment type ─────────────────────────────────
      renderBarChart('typeChart', d.byType.map(r => ({
        label: capitalize(r.assessment_type),
        value: parseFloat(r.avg_pct),
        suffix: '%',
        color: TYPE_COLORS[r.assessment_type] || '#3b82f6',
      })));

      // ── Monthly trend ──────────────────────────────────────
      renderTrend('trendChart', d.trend);

      // ── Grades table ───────────────────────────────────────
      renderGradesTable('gradesTableBody', d.recentGrades);

    } catch (err) {
      console.error('Grade analytics error:', err);
      showError('Failed to load grades. Please refresh.');
    }
  }

  // ── Bar chart renderer ─────────────────────────────────────
  function renderBarChart(id, items) {
    const el = document.getElementById(id);
    if (!items.length) { el.innerHTML = emptyState('No data available yet.'); return; }

    const max = Math.max(...items.map(i => i.value), 1);
    el.innerHTML = items.map(({ label, value, color, suffix }) => {
      const pct = Math.round((value / max) * 100);
      const display = suffix ? value + suffix : value;
      return `
        <div class="bar-row">
          <div class="bar-label" title="${esc(label)}">${esc(label)}</div>
          <div class="bar-outer">
            <div class="bar-inner" style="width:${pct}%;background:${color}"></div>
          </div>
          <div class="bar-count">${display}</div>
        </div>`;
    }).join('');
  }

  // ── Trend chart renderer ───────────────────────────────────
  function renderTrend(id, data) {
    const el = document.getElementById(id);
    if (!data.length) { el.innerHTML = emptyState('No trend data yet.'); return; }

    el.innerHTML = data.map(r => {
      const pct = parseFloat(r.avg_pct);
      const h   = Math.max(Math.round((pct / 100) * 130), 6);
      const parts = r.month.split('-');
      const label = MONTH_NAMES[parseInt(parts[1]) - 1] + ' ' + parts[0].slice(2);
      const color = pct >= 80 ? '#16a34a' : pct >= 60 ? '#d97706' : '#dc2626';
      return `
        <div class="trend-bar-wrap">
          <div class="trend-val">${pct}%</div>
          <div class="trend-bar" style="height:${h}px;background:${color}"></div>
          <div class="trend-label">${label}</div>
        </div>`;
    }).join('');
  }

  // ── Grades table ───────────────────────────────────────────
  function renderGradesTable(id, grades) {
    const el = document.getElementById(id);
    if (!grades.length) {
      el.innerHTML = '<tr><td colspan="6" class="text-center muted">No grades recorded yet. Your teacher will add grades here.</td></tr>';
      return;
    }

    el.innerHTML = grades.map(g => {
      const pct = parseFloat(g.pct);
      const letter = pct >= 90 ? 'A' : pct >= 80 ? 'B' : pct >= 70 ? 'C' : pct >= 60 ? 'D' : 'F';
      return `
        <tr>
          <td><strong>${esc(g.assessment_name)}</strong></td>
          <td><span class="badge-type" style="background:${TYPE_COLORS[g.assessment_type] || '#64748b'}22;color:${TYPE_COLORS[g.assessment_type] || '#64748b'}">${capitalize(g.assessment_type)}</span></td>
          <td>${g.score} / ${g.max_score}</td>
          <td><span class="badge-type badge-${letter.toLowerCase()}">${pct}% (${letter})</span></td>
          <td>${formatDate(g.graded_at)}</td>
          <td><span class="remarks-text" title="${esc(g.remarks || '')}">${esc(g.remarks || '–')}</span></td>
        </tr>`;
    }).join('');
  }

  // ── Helpers ────────────────────────────────────────────────
  function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val ?? '–';
  }
  function capitalize(str) { return str ? str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ') : ''; }
  function formatDate(iso) { return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); }
  function esc(str) { return str ? String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : ''; }
  function emptyState(msg) { return `<div class="empty-state"><div class="empty-icon">📊</div><p>${msg}</p></div>`; }

  function showError(msg) {
    const targets = document.querySelectorAll('.chart-card, .recent-section');
    targets.forEach(el => {
      const inner = el.querySelector('.bar-chart, .trend-chart, #gradesTableBody');
      if (inner) inner.innerHTML = `<div class="empty-state"><div class="empty-icon">⚠️</div><p>${msg}</p></div>`;
    });
  }

  // ── Logout handler ────────────────────────────────────────
  window.handleLogout = async function () {
    await fetch('../../EDUQUEST/api/auth/logout.php', {
      method: 'POST',
      headers: { Authorization: 'Bearer ' + token },
      credentials: 'include',
    }).catch(() => {});
    ['eq_token', 'eq_teacher', 'eq_student', 'eduquest_user', 'student_progress'].forEach(k =>
      localStorage.removeItem(k)
    );
    window.location.href = '../../auth/login/login.html?role=student';
  };

  // ── Nav toggle ────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    loadNavStats();
    loadGradeAnalytics();

    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) refreshBtn.addEventListener('click', loadGradeAnalytics);

    // Mobile nav toggle
    const navToggle = document.getElementById('navToggle');
    const navMenu   = document.getElementById('navMenu');
    if (navToggle && navMenu) {
      navToggle.addEventListener('click', () => {
        navMenu.classList.toggle('show');
        navToggle.classList.toggle('active');
      });
    }

    // Profile dropdown
    const profileMenu = document.getElementById('profileMenu');
    if (profileMenu) {
      profileMenu.addEventListener('click', (e) => {
        profileMenu.classList.toggle('active');
        e.stopPropagation();
      });
      document.addEventListener('click', () => profileMenu.classList.remove('active'));
    }
  });
})();
