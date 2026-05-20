/**
 * courses.js — Course list page logic
 */
'use strict';

let currentPage   = 1;
let totalPages    = 1;
let searchTimer   = null;

const PRESET_COLORS = ['#6366f1','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#64748b'];

function esc(s) {
  const d = document.createElement('div');
  d.textContent = String(s ?? '');
  return d.innerHTML;
}

/* ─────────────────────────────── boot ─────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  loadCourses();

  document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { currentPage = 1; loadCourses(); }, 350);
  });

  document.getElementById('newCourseBtn').addEventListener('click',      () => openModal());
  document.getElementById('newCourseEmptyBtn').addEventListener('click', () => openModal());

  // Modal dismiss
  document.getElementById('closeModalBtn').addEventListener('click', closeModal);
  document.getElementById('cancelModalBtn').addEventListener('click', closeModal);
  document.getElementById('courseModal').addEventListener('click', e => {
    if (e.target === document.getElementById('courseModal')) closeModal();
  });

  // Color picker ↔ swatches sync
  document.getElementById('courseColor').addEventListener('input', e => {
    syncSwatches(e.target.value);
  });
  document.querySelectorAll('#courseForm .color-swatch').forEach(btn => {
    btn.addEventListener('click', () => {
      const c = btn.dataset.color;
      document.getElementById('courseColor').value = c;
      syncSwatches(c);
    });
  });

  document.getElementById('courseForm').addEventListener('submit', saveCourse);
});

/* ─────────────────────────────── load ─────────────────────────────── */
async function loadCourses() {
  const search = document.getElementById('searchInput').value.trim();
  const params = new URLSearchParams({ page: currentPage, per_page: 12, search });

  document.getElementById('coursesLoading').style.display = '';
  document.getElementById('coursesGrid').classList.add('hidden');
  document.getElementById('coursesEmpty').classList.add('hidden');
  document.getElementById('paginationArea').classList.add('hidden');

  try {
    const res  = await fetch(`../api/courses/list.php?${params}`, { headers: EQ.authHeaders() });
    const data = await res.json();
    document.getElementById('coursesLoading').style.display = 'none';

    if (!data.success) { return; }
    const { courses, total_pages } = data.data;
    totalPages = total_pages || 1;

    if (!courses.length) {
      document.getElementById('coursesEmpty').classList.remove('hidden');
    } else {
      renderGrid(courses);
      document.getElementById('coursesGrid').classList.remove('hidden');
      renderPagination();
    }
  } catch {
    document.getElementById('coursesLoading').style.display = 'none';
  }
}

/* ─────────────────────────────── render ─────────────────────────────── */
function renderGrid(courses) {
  document.getElementById('coursesGrid').innerHTML = courses.map(c => `
    <div class="course-card" onclick="goToCourse(${c.id})">
      <div class="course-card-banner" style="background:${esc(c.cover_color || '#6366f1')}"></div>
      <div class="course-card-body">
        <div class="course-card-meta">
          ${c.subject     ? `<span class="course-badge">${esc(c.subject)}</span>` : ''}
          ${c.grade_level ? `<span class="course-badge">${esc(c.grade_level)}</span>` : ''}
          ${c.school_year ? `<span class="course-badge">${esc(c.school_year)}</span>` : ''}
        </div>
        <p class="course-card-title">${esc(c.title)}</p>
        ${c.description ? `<p class="course-card-desc">${esc(c.description)}</p>` : ''}
      </div>
      <div class="course-card-footer">
        <div class="course-card-stats">
          <span>&#128218; ${esc(c.module_count || 0)} module${c.module_count == 1 ? '' : 's'}</span>
          <span>&#128101; ${esc(c.student_count || 0)} student${c.student_count == 1 ? '' : 's'}</span>
        </div>
        <div class="course-card-actions">
          <button class="btn-icon" title="Edit"   onclick="openEdit(event,${c.id})">&#9998;</button>
          <button class="btn-icon danger" title="Delete" onclick="removeCourse(event,${c.id},'${esc(c.title)}')">&#128465;</button>
        </div>
      </div>
    </div>`).join('');
}

function renderPagination() {
  const el = document.getElementById('paginationArea');
  if (totalPages <= 1) { el.classList.add('hidden'); return; }
  el.classList.remove('hidden');
  el.innerHTML = Array.from({ length: totalPages }, (_, i) => i + 1).map(p =>
    `<button class="page-btn${p === currentPage ? ' active' : ''}" onclick="goPage(${p})">${p}</button>`
  ).join('');
}

function goPage(p) { currentPage = p; loadCourses(); }
function goToCourse(id) { window.location.href = `course-view.php?id=${id}`; }

/* ─────────────────────────────── modal ─────────────────────────────── */
function openModal(course = null) {
  const isEdit = !!course;
  document.getElementById('courseModalTitle').textContent = isEdit ? 'Edit Course' : 'New Course';
  document.getElementById('courseId').value          = isEdit ? course.id    : '';
  document.getElementById('courseTitle').value       = isEdit ? course.title : '';
  document.getElementById('courseSubject').value     = isEdit ? (course.subject    || '') : '';
  document.getElementById('courseGrade').value       = isEdit ? (course.grade_level || '') : '';
  document.getElementById('courseYear').value        = isEdit ? (course.school_year || '') : '';
  document.getElementById('courseDescription').value = isEdit ? (course.description || '') : '';

  const color = isEdit ? (course.cover_color || '#6366f1') : '#6366f1';
  document.getElementById('courseColor').value = color;
  syncSwatches(color);

  const alertEl = document.getElementById('courseFormAlert');
  alertEl.textContent = '';
  alertEl.classList.add('hidden');

  document.getElementById('courseModal').classList.remove('hidden');
  setTimeout(() => document.getElementById('courseTitle').focus(), 50);
}

function openEdit(e, id) {
  e.stopPropagation();
  fetch(`../api/courses/get.php?id=${id}`, { headers: EQ.authHeaders() })
    .then(r => r.json())
    .then(data => { if (data.success) openModal(data.data.course); });
}

function closeModal() {
  document.getElementById('courseModal').classList.add('hidden');
}

function syncSwatches(color) {
  document.querySelectorAll('#courseForm .color-swatch').forEach(b => {
    b.classList.toggle('active', b.dataset.color === color);
  });
}

/* ─────────────────────────────── save ─────────────────────────────── */
async function saveCourse(e) {
  e.preventDefault();
  const btn     = document.getElementById('saveCourseBtnModal');
  const alertEl = document.getElementById('courseFormAlert');
  alertEl.textContent = '';
  alertEl.classList.add('hidden');
  btn.disabled = true;
  btn.querySelector('.btn-text').textContent = 'Saving…';

  const idVal = document.getElementById('courseId').value;
  const payload = {
    title:       document.getElementById('courseTitle').value.trim(),
    subject:     document.getElementById('courseSubject').value.trim(),
    grade_level: document.getElementById('courseGrade').value.trim(),
    school_year: document.getElementById('courseYear').value.trim(),
    cover_color: document.getElementById('courseColor').value,
    description: document.getElementById('courseDescription').value.trim(),
  };
  if (idVal) payload.id = idVal;

  try {
    const res  = await fetch('../api/courses/save.php', {
      method: 'POST', headers: EQ.authHeaders(), body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) {
      closeModal();
      loadCourses();
    } else {
      alertEl.textContent = data.message || 'Could not save course.';
      alertEl.className   = 'alert alert-error';
    }
  } catch {
    alertEl.textContent = 'Connection error, please try again.';
    alertEl.className   = 'alert alert-error';
  } finally {
    btn.disabled = false;
    btn.querySelector('.btn-text').textContent = 'Save Course';
  }
}

/* ─────────────────────────────── delete ─────────────────────────────── */
async function removeCourse(e, id, title) {
  e.stopPropagation();
  if (!confirm(`Delete "${title}"?\n\nAll modules, materials, and enrollments will be removed. This cannot be undone.`)) return;
  try {
    const res  = await fetch('../api/courses/delete.php', {
      method: 'POST', headers: EQ.authHeaders(), body: JSON.stringify({ id }),
    });
    const data = await res.json();
    if (data.success) { loadCourses(); }
    else alert('Could not delete course: ' + (data.message || 'Unknown error'));
  } catch {
    alert('Connection error.');
  }
}
