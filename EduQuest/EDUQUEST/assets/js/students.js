/**
 * students.js
 * Student listing page – card grid, search, filter, and delete.
 */
'use strict';

const ADHD_LABELS = {
  predominantly_inattentive: 'Predominantly Inattentive',
  predominantly_hyperactive_impulsive: 'Hyperactive-Impulsive',
  combined_presentation: 'Combined Presentation',
  other_specified: 'Other Specified',
  unspecified: 'Unspecified',
};
const SEV_CLASS = { mild: 'badge-success', moderate: 'badge-warning', severe: 'badge-danger' };

let currentPage = 1;
let searchTimer = null;
let pendingDeleteId = null;
let pendingLinkId   = null;
let pendingLinkName = '';
let suggestPage     = 1;
let suggestTimer    = null;

document.addEventListener('DOMContentLoaded', () => {
  loadStudents();
  loadSuggestions();

  document.getElementById('searchInput').addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { currentPage = 1; loadStudents(); }, 350);
  });

  document.getElementById('filterAdhd').addEventListener('change', () => {
    currentPage = 1; loadStudents();
  });

  document.getElementById('cancelDeleteBtn').addEventListener('click', closeDeleteModal);
  document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDelete);

  // Suggestions
  document.getElementById('suggestSearch').addEventListener('input', function () {
    clearTimeout(suggestTimer);
    suggestTimer = setTimeout(() => { suggestPage = 1; loadSuggestions(); }, 350);
  });
  document.getElementById('cancelLinkBtn').addEventListener('click', closeLinkModal);
  document.getElementById('confirmLinkBtn').addEventListener('click', confirmLink);
});

async function loadStudents() {
  const search = document.getElementById('searchInput').value.trim();
  const params = new URLSearchParams({ page: currentPage, per_page: 12, search });
  try {
    const res  = await fetch(`../api/students/list.php?${params}`, { headers: EQ.authHeaders() });
    const data = await res.json();
    if (!data.success) { renderError(data.message); return; }
    renderGrid(data.data.students);
    renderPagination(data.data.pagination);
  } catch {
    renderError('Failed to load students.');
  }
}

function renderGrid(students) {
  const grid = document.getElementById('studentGrid');
  if (!students.length) {
    grid.innerHTML = '<div class="empty-state"><p>No students found. <a href="student-form.php">Add your first student.</a></p></div>';
    return;
  }
  grid.innerHTML = students.map(s => `
    <div class="student-card">
      <div class="student-card-header">
        <img src="${s.profile_photo ? '../uploads/photos/' + esc(s.profile_photo) : '../assets/img/default-avatar.php'}"
             alt="Photo" class="student-avatar" />
        <div>
          <div class="student-name">${esc(s.first_name)} ${esc(s.last_name)}</div>
          <div class="sub-text">${esc(s.grade_level) || 'Grade N/A'} · ${esc(s.school_name) || ''}</div>
        </div>
      </div>
      <div class="student-card-body">
        ${s.adhd_type ? `<span class="badge badge-info">${ADHD_LABELS[s.adhd_type] || s.adhd_type}</span>` : ''}
        ${s.adhd_severity ? `<span class="badge ${SEV_CLASS[s.adhd_severity] || ''}">${cap(s.adhd_severity)}</span>` : ''}
      </div>
      <div class="student-card-footer">
        <a href="student-view.php?id=${s.id}" class="btn btn-secondary btn-sm">View Profile</a>
        <a href="student-pov.php?id=${s.id}" class="btn btn-primary btn-sm" title="See the student's full dashboard">👁️ Dashboard</a>
        <a href="student-form.php?id=${s.id}" class="btn btn-outline btn-sm">Edit</a>
        <button class="btn btn-danger btn-sm" onclick="openDeleteModal(${s.id}, '${esc(s.first_name)} ${esc(s.last_name)}')">Archive</button>
      </div>
    </div>`).join('');
}

function renderPagination(pg) {
  const el = document.getElementById('pagination');
  if (pg.pages <= 1) { el.innerHTML = ''; return; }
  let html = `<button class="page-btn" ${pg.page === 1 ? 'disabled' : ''} data-page="${pg.page - 1}">&laquo;</button>`;
  for (let i = 1; i <= pg.pages; i++) {
    html += `<button class="page-btn${i === pg.page ? ' active' : ''}" data-page="${i}">${i}</button>`;
  }
  html += `<button class="page-btn" ${pg.page === pg.pages ? 'disabled' : ''} data-page="${pg.page + 1}">&raquo;</button>`;
  el.innerHTML = html;
  el.querySelectorAll('.page-btn:not([disabled])').forEach(btn => {
    btn.addEventListener('click', () => { currentPage = +btn.dataset.page; loadStudents(); });
  });
}

function openDeleteModal(id, name) {
  pendingDeleteId = id;
  document.getElementById('deleteStudentName').textContent = name;
  document.getElementById('deleteModal').classList.remove('hidden');
}
function closeDeleteModal() {
  pendingDeleteId = null;
  document.getElementById('deleteModal').classList.add('hidden');
}
async function confirmDelete() {
  if (!pendingDeleteId) return;
  try {
    const res  = await fetch(`../api/students/delete.php?id=${pendingDeleteId}`, {
      method: 'DELETE', headers: EQ.authHeaders(),
    });
    const data = await res.json();
    if (data.success) { closeDeleteModal(); loadStudents(); loadSuggestions(); }
    else alert(data.message);
  } catch { alert('Failed to archive student.'); }
}
function renderError(msg) { document.getElementById('studentGrid').innerHTML = `<div class="alert alert-error">${esc(msg)}</div>`; }
function esc(str) { const d = document.createElement('div'); d.textContent = str || ''; return d.innerHTML; }
function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

/* ═══════════════════════════════════════════════════════
   REGISTERED STUDENT SUGGESTIONS
   ═══════════════════════════════════════════════════════ */
async function loadSuggestions() {
  const search = document.getElementById('suggestSearch').value.trim();
  const params = new URLSearchParams({ page: suggestPage, per_page: 12, search });
  try {
    const res  = await fetch(`../api/students/suggestions.php?${params}`, { headers: EQ.authHeaders() });
    const data = await res.json();
    if (!data.success) return;

    const section = document.getElementById('suggestionsSection');
    if (data.data.students.length || search) {
      section.classList.remove('hidden');
    } else {
      section.classList.add('hidden');
      return;
    }
    renderSuggestions(data.data.students);
    renderSuggestPagination(data.data.pagination);
  } catch {
    // Silently fail — suggestions are supplementary
  }
}

function renderSuggestions(students) {
  const grid = document.getElementById('suggestionsGrid');
  if (!students.length) {
    grid.innerHTML = '<p class="muted" style="padding:1rem 0;font-size:0.88rem">No registered students found.</p>';
    return;
  }
  grid.innerHTML = students.map(s => {
    const initials = ((s.first_name || '').charAt(0) + (s.last_name || '').charAt(0)).toUpperCase();
    const registered = s.registered_at ? new Date(s.registered_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '';
    return `
    <div class="suggest-card" id="suggest-${s.id}">
      <div class="suggest-avatar">${initials}</div>
      <div class="suggest-info">
        <div class="suggest-name">${esc(s.first_name)} ${esc(s.last_name)}</div>
        <div class="suggest-meta">${esc(s.email)}${registered ? ' · Registered ' + registered : ''}</div>
      </div>
      <div class="suggest-actions">
        <button class="btn btn-primary btn-sm" onclick="openLinkModal(${s.id}, '${esc(s.first_name)} ${esc(s.last_name)}')">+ Add</button>
      </div>
    </div>`;
  }).join('');
}

function renderSuggestPagination(pg) {
  const el = document.getElementById('suggestPagination');
  if (pg.pages <= 1) { el.innerHTML = ''; return; }
  let html = `<button class="page-btn" ${pg.page === 1 ? 'disabled' : ''} data-page="${pg.page - 1}">&laquo;</button>`;
  for (let i = 1; i <= pg.pages; i++) {
    html += `<button class="page-btn${i === pg.page ? ' active' : ''}" data-page="${i}">${i}</button>`;
  }
  html += `<button class="page-btn" ${pg.page === pg.pages ? 'disabled' : ''} data-page="${pg.page + 1}">&raquo;</button>`;
  el.innerHTML = html;
  el.querySelectorAll('.page-btn:not([disabled])').forEach(btn => {
    btn.addEventListener('click', () => { suggestPage = +btn.dataset.page; loadSuggestions(); });
  });
}

function openLinkModal(id, name) {
  pendingLinkId   = id;
  pendingLinkName = name;
  document.getElementById('linkStudentName').textContent = name;
  document.getElementById('linkModal').classList.remove('hidden');
}
function closeLinkModal() {
  pendingLinkId = null;
  document.getElementById('linkModal').classList.add('hidden');
}
async function confirmLink() {
  if (!pendingLinkId) return;
  const btn = document.getElementById('confirmLinkBtn');
  btn.disabled = true; btn.textContent = 'Adding…';
  try {
    const res = await fetch('../api/students/link.php', {
      method: 'POST',
      headers: EQ.authHeaders(),
      body: JSON.stringify({ student_id: pendingLinkId }),
    });
    const data = await res.json();
    if (data.success) {
      closeLinkModal();
      loadStudents();
      loadSuggestions();
    } else {
      alert(data.message || 'Could not add student.');
    }
  } catch {
    alert('Connection error. Please try again.');
  } finally {
    btn.disabled = false; btn.textContent = 'Add to My Students';
  }
}
