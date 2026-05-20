/**
 * analytics.js – Teacher Analytics Page
 */
(function () {
  'use strict';

  const ADHD_COLORS = {
    combined:                 { color: '#7c3aed', label: 'Combined Presentation' },
    predominantly_inattentive:{ color: '#b45309', label: 'Predominantly Inattentive' },
    hyperactive_impulsive:    { color: '#b91c1c', label: 'Hyperactive-Impulsive' },
    unspecified:              { color: '#64748b', label: 'Unspecified' },
  };

  const SEVERITY_COLORS = { mild: '#16a34a', moderate: '#d97706', severe: '#dc2626' };

  const ACCOM_LABELS = {
    instructional:    'Instructional',
    assessment:       'Assessment',
    environmental:    'Environmental',
    behavioral:       'Behavioral',
    technology:       'Technology',
    social_emotional: 'Social-Emotional',
    other:            'Other',
  };

  async function loadAnalytics() {
    try {
      const res  = await fetch('../api/analytics/summary.php', { headers: window.EQ.authHeaders() });
      const data = await res.json();

      if (!data.success) { showError(data.message); return; }

      const d = data.data;

      // ── Top stats ──────────────────────────────────────────
      document.getElementById('statTotal').textContent       = d.students.total;
      document.getElementById('statCourses').textContent     = d.courses.total;
      document.getElementById('statMeds').textContent        = d.students.onMedication;
      document.getElementById('statEnrollments').textContent = d.courses.enrollments;

      // ── ADHD breakdown ────────────────────────────────────
      const adhdEl = document.getElementById('adhdBreakdown');
      const total  = d.students.total || 1;
      const types  = [
        { key: 'combined',   count: d.students.combined },
        { key: 'predominantly_inattentive', count: d.students.inattentive },
        { key: 'hyperactive_impulsive',     count: d.students.hyperactive },
        { key: 'unspecified', count: d.students.unspecified },
      ];

      if (total === 0) {
        adhdEl.innerHTML = emptyState('No student data yet.');
      } else {
        adhdEl.innerHTML = types.map(t => {
          const info = ADHD_COLORS[t.key];
          const pct  = total > 0 ? Math.round((t.count / total) * 100) : 0;
          return `
            <div class="adhd-type-row">
              <div class="adhd-dot" style="background:${info.color}"></div>
              <div class="adhd-name">${info.label}</div>
              <div class="adhd-bar-outer">
                <div class="adhd-bar-inner" style="width:${pct}%;background:${info.color}"></div>
              </div>
              <div class="adhd-pct">${t.count} <span style="color:#94a3b8;font-weight:400">(${pct}%)</span></div>
            </div>`;
        }).join('');
      }

      // ── Severity distribution ─────────────────────────────
      renderBarChart('severityChart', Object.entries(d.severity).map(([k, v]) => ({
        label: capitalize(k), value: v, color: SEVERITY_COLORS[k] || '#3b82f6',
      })));

      // ── Comorbid conditions ───────────────────────────────
      renderBarChart('comorbidChart', d.comorbidities.map(r => ({
        label: r.condition_name, value: parseInt(r.cnt), color: '#3b82f6',
      })));

      // ── Accommodations ────────────────────────────────────
      renderBarChart('accommodationChart', d.accommodations.map(r => ({
        label: ACCOM_LABELS[r.category] || capitalize(r.category),
        value: parseInt(r.cnt),
        color: '#0ea5e9',
      })));

      // ── Grade levels ──────────────────────────────────────
      renderBarChart('gradeChart', d.gradeLevels.map(r => ({
        label: 'Grade ' + r.grade_level, value: parseInt(r.cnt), color: '#8b5cf6',
      })));

      // ── Course stats (simple list) ────────────────────────
      const csEl = document.getElementById('courseStats');
      csEl.innerHTML = [
        { label: 'Total Courses',     value: d.courses.total,       color: '#3b82f6' },
        { label: 'Total Modules',     value: d.courses.modules,     color: '#0ea5e9' },
        { label: 'Total Materials',   value: d.courses.materials,   color: '#8b5cf6' },
        { label: 'Total Enrollments', value: d.courses.enrollments, color: '#16a34a' },
      ].map(({ label, value, color }) => `
        <div class="bar-row">
          <div class="bar-label">${label}</div>
          <div class="bar-count">${value}</div>
        </div>`).join('');

      // ── Recent students ───────────────────────────────────
      const tbody = document.getElementById('recentStudents');
      if (!d.recentStudents.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center muted">No students yet.</td></tr>';
      } else {
        tbody.innerHTML = d.recentStudents.map(s => `
          <tr>
            <td>${escHtml(s.first_name)} ${escHtml(s.last_name)}</td>
            <td>${s.grade_level || '–'}</td>
            <td>${adhdBadge(s.adhd_type)}</td>
            <td>${severityBadge(s.severity)}</td>
            <td>${formatDate(s.created_at)}</td>
            <td><a href="student-view.php?id=${s.id}" class="link-muted">View →</a></td>
          </tr>`).join('');
      }

    } catch (err) {
      showError('Failed to load analytics. Please refresh.');
    }
  }

  // ── Bar chart renderer ─────────────────────────────────────
  function renderBarChart(containerId, items) {
    const el = document.getElementById(containerId);
    if (!items.length) { el.innerHTML = emptyState('No data available.'); return; }

    const max = Math.max(...items.map(i => i.value), 1);
    el.innerHTML = items.map(({ label, value, color }) => {
      const pct = Math.round((value / max) * 100);
      return `
        <div class="bar-row">
          <div class="bar-label" title="${escHtml(label)}">${escHtml(label)}</div>
          <div class="bar-outer">
            <div class="bar-inner" style="width:${pct}%;background:${color}"></div>
          </div>
          <div class="bar-count">${value}</div>
        </div>`;
    }).join('');
  }

  // ── Helpers ───────────────────────────────────────────────
  function adhdBadge(type) {
    const map = {
      combined:                  ['badge-combined',    'Combined'],
      predominantly_inattentive: ['badge-inattentive', 'Inattentive'],
      hyperactive_impulsive:     ['badge-hyperactive', 'Hyperactive'],
    };
    const [cls, label] = map[type] || ['badge-unspecified', 'Unspecified'];
    return `<span class="badge-type ${cls}">${label}</span>`;
  }

  function severityBadge(sev) {
    if (!sev) return '–';
    const cls = { mild: 'badge-mild', moderate: 'badge-moderate', severe: 'badge-severe' }[sev] || '';
    return `<span class="badge-type ${cls}">${capitalize(sev)}</span>`;
  }

  function capitalize(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ') : '';
  }

  function formatDate(iso) {
    return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function emptyState(msg) {
    return `<div class="empty-state"><div class="empty-icon">&#128202;</div><p>${msg}</p></div>`;
  }

  function showError(msg) {
    document.querySelectorAll('.chart-card, .recent-section').forEach(el => {
      el.innerHTML = `<div class="empty-state"><div class="empty-icon">&#9888;&#65039;</div><p>${msg}</p></div>`;
    });
  }

  // ── Init ──────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    loadAnalytics();
    document.getElementById('refreshBtn').addEventListener('click', loadAnalytics);
  });
})();
