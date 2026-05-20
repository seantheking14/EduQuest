/**
 * dashboard.js
 * Loads student list and stats for the dashboard page.
 */
'use strict';

const ADHD_LABELS = {
  predominantly_inattentive: 'Inattentive',
  predominantly_hyperactive_impulsive: 'Hyperactive',
  combined_presentation: 'Combined',
  other_specified: 'Other',
  unspecified: 'Unspecified',
};

const SEV_CLASS = { mild: 'badge-success', moderate: 'badge-warning', severe: 'badge-danger' };

let currentPage = 1;
let searchQuery = '';
let searchTimer = null;

document.addEventListener('DOMContentLoaded', () => {
  loadStudents();
  loadAssignmentActivity();

  const searchEl = document.getElementById('searchInput');
  if (searchEl) {
    searchEl.addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        searchQuery = searchEl.value.trim();
        currentPage = 1;
        loadStudents();
      }, 350);
    });
  }
});

async function loadStudents() {
  const params = new URLSearchParams({ page: currentPage, per_page: 8, search: searchQuery });
  try {
    const res  = await fetch(`../api/students/list.php?${params}`, { headers: EQ.authHeaders() });
    const data = await res.json();
    if (!data.success) { showError(data.message); return; }

    renderStats(data.data.students, data.data.pagination, data.data.inactive_count);
    renderTable(data.data.students);
    renderPagination(data.data.pagination);
  } catch {
    showError('Failed to load students.');
  }
}

function renderStats(students, pagination, inactiveCount) {
  const total = (pagination && pagination.total != null) ? pagination.total : students.length;
  document.getElementById('statTotal').textContent = total;
  document.getElementById('statActive').textContent = total;
  document.getElementById('statInactive').textContent = inactiveCount != null ? inactiveCount : 0;
  const countEl = document.getElementById('studentCount');
  if (countEl) countEl.textContent = total + ' total';
}

function renderTable(students) {
  const tbody = document.getElementById('studentTableBody');
  if (!students.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center muted">No students found. <a href="student-form.php">Add one?</a></td></tr>';
    return;
  }
  tbody.innerHTML = students.map(s => `
    <tr>
      <td>
        <strong>${esc(s.first_name)} ${esc(s.last_name)}</strong>
        ${s.student_id_number ? `<div class="sub-text">ID: ${esc(s.student_id_number)}</div>` : ''}
      </td>
      <td>${esc(s.grade_level) || '–'}</td>
      <td>${s.adhd_type ? `<span class="badge badge-info">${ADHD_LABELS[s.adhd_type] || s.adhd_type}</span>` : '–'}</td>
      <td>${s.adhd_severity ? `<span class="badge ${SEV_CLASS[s.adhd_severity] || ''}">${cap(s.adhd_severity)}</span>` : '–'}</td>
      <td>${esc(s.school_name) || '–'}</td>
      <td class="actions-cell">
        <a href="student-form.php?id=${s.id}" class="btn btn-outline btn-xs">Edit</a>
        <a href="student-view.php?id=${s.id}" class="btn btn-secondary btn-xs">View</a>
      </td>
    </tr>`).join('');
}

function renderPagination(pg) {
  const el = document.getElementById('pagination');
  if (pg.pages <= 1) { el.innerHTML = ''; return; }
  let html = '';
  for (let i = 1; i <= pg.pages; i++) {
    html += `<button class="page-btn${i === pg.page ? ' active' : ''}" data-page="${i}">${i}</button>`;
  }
  el.innerHTML = html;
  el.querySelectorAll('.page-btn').forEach(btn => {
    btn.addEventListener('click', () => { currentPage = +btn.dataset.page; loadStudents(); });
  });
}

function showError(msg) {
  console.error(msg);
}

function esc(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}
function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

async function loadAssignmentActivity() {
  const body = document.getElementById('assignmentActivityBody');
  const statEl = document.getElementById('statAssignActive');
  if (!body) return;
  try {
    const res = await fetch('../api/attempt/assignment_activity.php', { headers: EQ.authHeaders() });
    const json = await res.json();
    if (!json.success || !json.data) {
      body.innerHTML = '<p class="muted">No assignment data available.</p>';
      return;
    }
    const d = json.data;
    if (statEl) statEl.textContent = d.total_active;

    let html = `<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1rem;">
      <div style="background:#f0f9ff;border-radius:8px;padding:12px;text-align:center;">
        <div style="font-size:1.6rem;font-weight:700;color:#0369a1;">${d.total_active}</div>
        <div style="font-size:0.82rem;color:#374151;">Active Assignments</div>
      </div>
      <div style="background:#fff7ed;border-radius:8px;padding:12px;text-align:center;">
        <div style="font-size:1.6rem;font-weight:700;color:#c2410c;">${d.due_soon}</div>
        <div style="font-size:0.82rem;color:#374151;">Due Within 3 Days</div>
      </div>
      <div style="background:#f0fdf4;border-radius:8px;padding:12px;text-align:center;">
        <div style="font-size:1.6rem;font-weight:700;color:#15803d;">${d.quiz_active}</div>
        <div style="font-size:0.82rem;color:#374151;">Quiz Assignments</div>
      </div>
      <div style="background:#fdf4ff;border-radius:8px;padding:12px;text-align:center;">
        <div style="font-size:1.6rem;font-weight:700;color:#7e22ce;">${d.game_active}</div>
        <div style="font-size:0.82rem;color:#374151;">Game Assignments</div>
      </div>
    </div>`;

    if (d.recent && d.recent.length > 0) {
      html += `<h4 style="margin:0 0 8px;color:#374151;font-size:0.9rem;">Recent Activity</h4>
      <table style="width:100%;font-size:0.82rem;border-collapse:collapse;">
        <thead><tr style="border-bottom:1px solid #e5e7eb;">
          <th style="text-align:left;padding:5px 8px;">Student</th>
          <th style="text-align:left;padding:5px 8px;">Assignment</th>
          <th style="text-align:left;padding:5px 8px;">Type</th>
          <th style="text-align:left;padding:5px 8px;">Due</th>
          <th style="text-align:left;padding:5px 8px;">Attempts</th>
        </tr></thead>
        <tbody>${d.recent.map(r => `<tr style="border-bottom:1px solid #f3f4f6;">
          <td style="padding:5px 8px;">${esc(r.student_name)}</td>
          <td style="padding:5px 8px;">${esc(r.title)}</td>
          <td style="padding:5px 8px;"><span style="background:#e0e7ff;color:#4338ca;border-radius:4px;padding:1px 6px;">${esc(r.type)}</span></td>
          <td style="padding:5px 8px;">${r.due_date || '—'}</td>
          <td style="padding:5px 8px;">${r.attempts_used}${r.max_attempts > 0 ? '/'+r.max_attempts : ''}</td>
        </tr>`).join('')}</tbody>
      </table>`;
    }

    body.innerHTML = html;
  } catch (e) {
    if (body) body.innerHTML = '<p class="muted">Failed to load assignment activity.</p>';
  }
}

