/**
 * grade-analytics.js – Teacher Grade Analytics Page
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

  async function loadGradeAnalytics() {
    try {
      const res  = await fetch('../api/analytics/grades.php', { headers: window.EQ.authHeaders() });
      const json = await res.json();

      if (!json.success) { showError(json.message); return; }
      const d = json.data;

      // ── Top stats ──────────────────────────────────────────
      const s = d.summary;
      setText('statTotal',    s.total_grades);
      setText('statStudents', s.students_graded);
      setText('statAvg',      s.class_average ? s.class_average + '%' : '–');
      setText('statHigh',     s.highest_pct   ? s.highest_pct + '%'  : '–');
      setText('statLow',      s.lowest_pct    ? s.lowest_pct + '%'   : '–');

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

      // ── Student rankings ───────────────────────────────────
      renderRankings('rankingPanel', d.studentAverages);

      // ── Recent grades table ────────────────────────────────
      renderRecent('recentGrades', d.recentGrades);

    } catch (err) {
      console.error('Grade analytics error:', err);
      showError('Failed to load grade analytics. Please refresh.');
    }
  }

  // ── Bar chart renderer ─────────────────────────────────────
  function renderBarChart(id, items) {
    const el = document.getElementById(id);
    if (!items.length) { el.innerHTML = emptyState('No data available.'); return; }

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

    const maxPct = Math.max(...data.map(r => parseFloat(r.avg_pct)), 1);
    el.innerHTML = data.map(r => {
      const pct = parseFloat(r.avg_pct);
      const h   = Math.max(Math.round((pct / 100) * 120), 6);
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

  // ── Student rankings ───────────────────────────────────────
  function renderRankings(id, students) {
    const el = document.getElementById(id);
    if (!students.length) { el.innerHTML = emptyState('No student grades yet.'); return; }

    const rows = students.map((s, i) => {
      const pct   = parseFloat(s.avg_pct);
      const color = pct >= 90 ? '#16a34a' : pct >= 80 ? '#3b82f6' : pct >= 70 ? '#d97706' : pct >= 60 ? '#ea580c' : '#dc2626';
      return `
        <tr>
          <td>${i + 1}</td>
          <td><a href="student-view.php?id=${s.id}" style="color:#3b82f6;text-decoration:none">${esc(s.first_name)} ${esc(s.last_name)}</a></td>
          <td>${s.grade_level || '–'}</td>
          <td>${s.assessments}</td>
          <td>
            <span class="pct-bar" style="width:${Math.round(pct * 0.6)}px;background:${color}"></span>
            <strong>${pct}%</strong>
          </td>
        </tr>`;
    }).join('');

    el.innerHTML = `
      <table class="ranking-table">
        <thead><tr><th>#</th><th>Student</th><th>Grade</th><th>Assessed</th><th>Avg %</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>`;
  }

  // ── Recent grades table ────────────────────────────────────
  function renderRecent(id, grades) {
    const el = document.getElementById(id);
    if (!grades.length) { el.innerHTML = '<tr><td colspan="7" class="text-center muted">No grades recorded yet.</td></tr>'; return; }

    el.innerHTML = grades.map(g => {
      const pct = parseFloat(g.pct);
      const letter = pct >= 90 ? 'A' : pct >= 80 ? 'B' : pct >= 70 ? 'C' : pct >= 60 ? 'D' : 'F';
      return `
        <tr>
          <td>${esc(g.first_name)} ${esc(g.last_name)}</td>
          <td>${esc(g.assessment_name)}</td>
          <td><span class="badge-type" style="background:${TYPE_COLORS[g.assessment_type] || '#64748b'}22;color:${TYPE_COLORS[g.assessment_type] || '#64748b'}">${capitalize(g.assessment_type)}</span></td>
          <td>${g.score} / ${g.max_score}</td>
          <td><span class="badge-type badge-${letter.toLowerCase()}">${pct}% (${letter})</span></td>
          <td>${formatDate(g.graded_at)}</td>
          <td><button class="btn-delete" data-grade-id="${g.id}" title="Delete">&#128465;</button></td>
        </tr>`;
    }).join('');

    // Attach delete handlers
    el.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', () => deleteGrade(btn.dataset.gradeId));
    });
  }

  // ── Helpers ────────────────────────────────────────────────
  function setText(id, val) { document.getElementById(id).textContent = val ?? '–'; }
  function capitalize(str) { return str ? str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ') : ''; }
  function formatDate(iso) { return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); }
  function esc(str) { return str ? String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : ''; }
  function emptyState(msg) { return `<div class="empty-state"><div class="empty-icon">&#128202;</div><p>${msg}</p></div>`; }

  function showError(msg) {
    document.querySelectorAll('.chart-card, .recent-section').forEach(el => {
      el.innerHTML = `<div class="empty-state"><div class="empty-icon">&#9888;&#65039;</div><p>${msg}</p></div>`;
    });
  }

  // ── Modal management ──────────────────────────────────────
  function openModal() {
    document.getElementById('gradeModal').style.display = 'flex';
    document.getElementById('gradeDate').value = new Date().toISOString().slice(0, 10);
    document.getElementById('gradeFormError').style.display = 'none';
    loadStudentOptions();
  }

  function closeModal() {
    document.getElementById('gradeModal').style.display = 'none';
    document.getElementById('gradeForm').reset();
  }

  async function loadStudentOptions() {
    const sel = document.getElementById('gradeStudent');
    if (sel.options.length > 1) return; // already loaded
    try {
      const res  = await fetch('../api/students/list.php?per_page=200', { headers: window.EQ.authHeaders() });
      const json = await res.json();
      if (json.success && json.data && json.data.students) {
        json.data.students.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s.id;
          opt.textContent = s.first_name + ' ' + s.last_name + (s.grade_level ? ' (' + s.grade_level + ')' : '');
          sel.appendChild(opt);
        });
      }
    } catch (e) {
      console.error('Failed to load students for dropdown', e);
    }
  }

  async function submitGrade(e) {
    e.preventDefault();
    const errEl = document.getElementById('gradeFormError');
    errEl.style.display = 'none';

    const payload = {
      student_id:      document.getElementById('gradeStudent').value,
      assessment_name: document.getElementById('gradeName').value.trim(),
      assessment_type: document.getElementById('gradeType').value,
      score:           parseFloat(document.getElementById('gradeScore').value),
      max_score:       parseFloat(document.getElementById('gradeMax').value),
      graded_at:       document.getElementById('gradeDate').value,
      remarks:         document.getElementById('gradeRemarks').value.trim(),
    };

    const btn = document.getElementById('saveGradeBtn');
    btn.disabled = true;
    btn.textContent = 'Saving…';

    try {
      const res  = await fetch('../api/students/grades.php', {
        method: 'POST',
        headers: window.EQ.authHeaders(),
        body: JSON.stringify(payload),
      });
      const json = await res.json();

      if (!json.success) {
        errEl.textContent = json.message;
        errEl.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Save Grade';
        return;
      }

      closeModal();
      loadGradeAnalytics(); // refresh charts
    } catch (err) {
      errEl.textContent = 'Network error: ' + err.message;
      errEl.style.display = 'block';
    } finally {
      btn.disabled = false;
      btn.textContent = 'Save Grade';
    }
  }

  async function deleteGrade(gradeId) {
    if (!confirm('Delete this grade?')) return;
    try {
      const res = await fetch('../api/students/grades.php?id=' + gradeId, {
        method: 'DELETE',
        headers: window.EQ.authHeaders(),
      });
      const json = await res.json();
      if (json.success) loadGradeAnalytics();
    } catch (e) {
      console.error('Delete failed', e);
    }
  }

  // ── Init ──────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    loadGradeAnalytics();
    document.getElementById('refreshBtn').addEventListener('click', loadGradeAnalytics);

    // Modal controls
    document.getElementById('addGradeBtn').addEventListener('click', openModal);
    document.getElementById('closeModal').addEventListener('click', closeModal);
    document.getElementById('cancelModal').addEventListener('click', closeModal);
    document.getElementById('gradeModal').addEventListener('click', (e) => {
      if (e.target === e.currentTarget) closeModal();
    });
    document.getElementById('gradeForm').addEventListener('submit', submitGrade);
  });
})();
