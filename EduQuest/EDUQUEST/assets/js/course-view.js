/**
 * course-view.js — Full Blackboard-style course management page
 */
'use strict';

/* ─── State ─── */
let courseId         = 0;
let course           = null;
let editingModuleId  = null;   // null = creating new
let matModuleId      = 0;      // module the material modal belongs to
let editMaterialId   = 0;      // 0 = creating new material
let activeType       = 'file'; // selected material type in modal
let enrollTimer      = null;
let _matBlobUrl      = null;   // revocable blob URL for material viewer

/* ─── Helpers ─── */
function esc(s) {
  const d = document.createElement('div'); d.textContent = String(s ?? ''); return d.innerHTML;
}
function fmtDate(str) {
  if (!str) return '';
  const d = new Date(str + 'T00:00:00');
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}
function fmtTs(str) {
  if (!str) return '';
  let normalized = String(str).trim().replace(' ', 'T');
  // Course announcement timestamps come from SQL in UTC without timezone.
  if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/.test(normalized)) normalized += 'Z';
  const d = new Date(normalized);
  if (isNaN(d.getTime())) return String(str);
  return d.toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit'
  });
}
function initials(first, last) {
  return ((first || '').charAt(0) + (last || '').charAt(0)).toUpperCase();
}
function materialIcon(type, mime) {
  if (type === 'link')       return '&#128279;';
  if (type === 'text')       return '&#128221;';
  if (type === 'assignment') return '&#9998;';
  if (!mime) return '&#128196;';
  if (mime.startsWith('image/'))        return '&#128247;';
  if (mime === 'application/pdf')       return '&#128196;';
  if (mime.includes('word'))            return '&#128209;';
  if (mime.includes('spreadsheet') || mime.includes('excel')) return '&#128202;';
  if (mime.includes('presentation') || mime.includes('powerpoint')) return '&#127910;';
  if (mime.startsWith('video/'))        return '&#127916;';
  if (mime.startsWith('audio/'))        return '&#127925;';
  return '&#128196;';
}
function showAlert(msg, type = 'error') {
  const el = document.getElementById('cvAlert');
  el.textContent = msg;
  el.className   = `alert alert-${type}`;
  el.classList.remove('hidden');
  setTimeout(() => el.classList.add('hidden'), 5000);
}
function showSectionAlert(id, msg, type = 'error') {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = msg;
  el.className   = `alert alert-${type}`;
  el.classList.remove('hidden');
  setTimeout(() => el.classList.add('hidden'), 5000);
}
async function apiPOST(url, payload) {
  const res  = await fetch(url, { method: 'POST', headers: EQ.authHeaders(), body: JSON.stringify(payload) });
  return res.json();
}

/* ═══════════════════════════════════════════════════════
   BOOT
   ═══════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  courseId = parseInt(params.get('id')) || 0;
  if (!courseId) { window.location.href = 'courses.php'; return; }

  loadCourse();
  bindTabs();
  bindContent();
  bindAnnouncements();
  bindStudents();
  bindSettings();
  bindMaterialModal();
});

/* ═══════════════════════════════════════════════════════
   LOAD COURSE
   ═══════════════════════════════════════════════════════ */
async function loadCourse() {
  try {
    const res  = await fetch(`../api/courses/get.php?id=${courseId}`, { headers: EQ.authHeaders() });
    const data = await res.json();
    if (!data.success) { window.location.href = 'courses.php'; return; }
    course = data.data.course;
    renderBanner(course);
    renderModules(course.modules || []);
    renderAnnouncements(course.announcements || []);
    renderEnrolled(course.students || []);
    prefillSettings(course);
  } catch {
    showAlert('Failed to load course. Please refresh.', 'error');
  }
}

/* ─── Banner ─── */
function renderBanner(c) {
  document.title = `EduQuest – ${c.title}`;
  document.getElementById('cvBanner').style.background = c.cover_color || '#6366f1';
  document.getElementById('cvTitle').textContent = c.title;

  if (c.description) {
    document.getElementById('cvDescription').textContent = c.description;
  } else {
    document.getElementById('cvDescription').style.display = 'none';
  }
  setBadge('cvSubjectBadge', c.subject);
  setBadge('cvGradeBadge', c.grade_level);
  setBadge('cvYearBadge', c.school_year);
}
function setBadge(id, text) {
  const el = document.getElementById(id);
  if (text && el) { el.textContent = text; el.classList.remove('hidden'); }
}

/* ═══════════════════════════════════════════════════════
   TABS
   ═══════════════════════════════════════════════════════ */
function bindTabs() {
  document.querySelectorAll('.cv-tab').forEach(btn => {
    btn.addEventListener('click', () => switchTab(btn.dataset.tab));
  });
}
function switchTab(name) {
  document.querySelectorAll('.cv-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === name));
  document.getElementById('tabContent').classList.toggle('hidden',       name !== 'content');
  document.getElementById('tabAnnouncements').classList.toggle('hidden', name !== 'announcements');
  document.getElementById('tabStudents').classList.toggle('hidden',      name !== 'students');
  document.getElementById('tabSettings').classList.toggle('hidden',      name !== 'settings');
}

/* ═══════════════════════════════════════════════════════
   CONTENT TAB – MODULES
   ═══════════════════════════════════════════════════════ */
function bindContent() {
  document.getElementById('addModuleBtn').addEventListener('click', () => {
    editingModuleId = null;
    resetModuleForm();
    toggleModuleForm(true);
  });
  document.getElementById('cancelNewModuleBtn').addEventListener('click', () => toggleModuleForm(false));
  document.getElementById('saveNewModuleBtn').addEventListener('click', saveModule);
}

function toggleModuleForm(show) {
  const f = document.getElementById('addModuleForm');
  f.classList.toggle('hidden', !show);
  if (show) {
    document.getElementById('newModuleTitle').focus();
    document.getElementById('addModuleBtn').textContent = editingModuleId ? '+ Add Module' : '– Cancel';
  } else {
    resetModuleForm();
    document.getElementById('addModuleBtn').textContent = '+ Add Module';
  }
}

function resetModuleForm() {
  document.getElementById('newModuleTitle').value = '';
  document.getElementById('newModuleDesc').value  = '';
  editingModuleId = null;
  document.getElementById('saveNewModuleBtn').textContent = 'Save Module';
}

async function saveModule() {
  const title = document.getElementById('newModuleTitle').value.trim();
  const desc  = document.getElementById('newModuleDesc').value.trim();
  if (!title) { document.getElementById('newModuleTitle').focus(); return; }

  const btn = document.getElementById('saveNewModuleBtn');
  btn.disabled = true; btn.textContent = 'Saving…';

  try {
    let data;
    if (editingModuleId) {
      data = await apiPOST('../api/courses/modules.php', { action: 'update', id: editingModuleId, title, description: desc });
    } else {
      data = await apiPOST('../api/courses/modules.php', { action: 'create', course_id: courseId, title, description: desc });
    }
    if (data.success) {
      toggleModuleForm(false);
      await reloadCourse();
    } else {
      showAlert(data.message || 'Could not save module.', 'error');
    }
  } catch {
    showAlert('Connection error.', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = editingModuleId ? 'Save Changes' : 'Save Module';
  }
}

function editModule(mod) {
  editingModuleId = mod.id;
  document.getElementById('newModuleTitle').value = mod.title;
  document.getElementById('newModuleDesc').value  = mod.description || '';
  document.getElementById('saveNewModuleBtn').textContent = 'Save Changes';
  toggleModuleForm(true);
  document.getElementById('addModuleForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function deleteModule(id, title) {
  if (!confirm(`Delete module "${title}"?\n\nAll materials inside it will also be deleted.`)) return;
  try {
    const data = await apiPOST('../api/courses/modules.php', { action: 'delete', id });
    if (data.success) { await reloadCourse(); }
    else showAlert(data.message || 'Could not delete module.', 'error');
  } catch {
    showAlert('Connection error.', 'error');
  }
}

/* ─── Module rendering ─── */
function renderModules(modules) {
  const list = document.getElementById('moduleList');
  const empty = document.getElementById('contentEmpty');

  if (!modules.length) {
    list.innerHTML  = '';
    empty.classList.remove('hidden');
    return;
  }
  empty.classList.add('hidden');

  list.innerHTML = modules.map(mod => `
    <div class="cv-module" id="mod-${mod.id}">
      <div class="cv-module-header" onclick="toggleModule(${mod.id})">
        <span class="cv-module-toggle">&#9658;</span>
        <div class="cv-module-header-text">
          <p class="cv-module-title">${esc(mod.title)}</p>
          ${mod.description ? `<p class="cv-module-desc">${esc(mod.description)}</p>` : ''}
        </div>
        <div class="cv-module-actions" onclick="event.stopPropagation()">
          <button class="btn-icon" title="Edit module"   onclick="editModuleById(${mod.id})">&#9998;</button>
          <button class="btn-icon danger" title="Delete module" onclick="deleteModule(${mod.id},'${esc(mod.title)}')">&#128465;</button>
        </div>
      </div>
      <div class="cv-module-body">
        ${renderMaterialList(mod.materials || [], mod.id)}
        <div class="cv-add-content-row">
          <button class="cv-add-content-btn" onclick="openMaterialModal(${mod.id})">&#43; Add Content</button>
        </div>
      </div>
    </div>`).join('');

  // Open all modules by default
  modules.forEach(m => openModuleEl(m.id));
}

function openModuleEl(id) {
  const el = document.getElementById('mod-' + id);
  if (el) el.classList.add('open');
}
function toggleModule(id) {
  const el = document.getElementById('mod-' + id);
  if (el) el.classList.toggle('open');
}
function editModuleById(id) {
  const mod = (course.modules || []).find(m => m.id == id);
  if (mod) editModule(mod);
}

/* ─── Material list inside a module ─── */
function renderMaterialList(materials, moduleId) {
  if (!materials.length) {
    return '<p class="muted" style="font-size:0.82rem;padding:0.5rem 0">No content yet. Add your first item below.</p>';
  }
  return `<ul class="cv-material-list">` +
    materials.map(mat => {
      const icon  = materialIcon(mat.material_type, mat.mime_type);
      const href  = mat.material_type === 'link'
        ? `href="${esc(mat.content)}" target="_blank" rel="noopener noreferrer"`
        : '';
      const clickAttr = mat.material_type === 'file'
        ? `onclick="openMaterialViewer(${mat.id}); return false;" href="#" style="cursor:pointer"`
        : '';
      const titleEl = mat.material_type === 'link' && href
        ? `<a ${href}>${esc(mat.title)}</a>`
        : mat.material_type === 'file'
          ? `<a ${clickAttr}>${esc(mat.title)}</a>`
          : esc(mat.title);
      const sub = [
        mat.material_type === 'assignment' && mat.due_date ? `<span class="badge-due">Due ${fmtDate(mat.due_date)}</span>` : '',
        mat.original_filename ? `<span>${esc(mat.original_filename)}</span>` : '',
        mat.material_type === 'text' || mat.material_type === 'assignment' ? `<span>${cap(mat.material_type)}</span>` : '',
      ].filter(Boolean).join(' · ');

      return `<li class="cv-material-item" id="mat-${mat.id}">
        <span class="cv-material-icon">${icon}</span>
        <div class="cv-material-info">
          <p class="cv-material-title">${titleEl}</p>
          ${sub ? `<p class="cv-material-sub">${sub}</p>` : ''}
        </div>
        <div class="cv-material-actions">
          ${mat.material_type === 'assignment' ? `<button class="btn-icon" title="View Submissions" onclick="openSubmissions(${mat.id},'${esc(mat.title)}')">&#128196;</button>` : ''}
          <button class="btn-icon" title="Edit"   onclick="openEditMaterial(${mat.id})">&#9998;</button>
          <button class="btn-icon danger" title="Delete" onclick="deleteMaterial(${mat.id},'${esc(mat.title)}')">&#128465;</button>
        </div>
      </li>`;
    }).join('') +
    `</ul>`;
}

function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

async function deleteMaterial(id, title) {
  if (!confirm(`Remove "${title}" from this module?`)) return;
  try {
    const data = await apiPOST('../api/courses/materials.php', { action: 'delete', id });
    if (data.success) await reloadCourse();
    else showAlert(data.message || 'Could not delete material.', 'error');
  } catch {
    showAlert('Connection error.', 'error');
  }
}

function openEditMaterial(id) {
  const mod = (course.modules || []).find(m => m.materials && m.materials.some(mat => mat.id == id));
  if (!mod) return;
  const mat = mod.materials.find(m => m.id == id);
  openMaterialModal(mod.id, mat);
}

/* ═══════════════════════════════════════════════════════
   MATERIAL MODAL
   ═══════════════════════════════════════════════════════ */
function openMaterialModal(moduleId, editMat = null) {
  matModuleId    = moduleId;
  editMaterialId = editMat ? editMat.id : 0;
  activeType     = editMat ? editMat.material_type : 'file';

  // Title
  document.getElementById('materialModalTitle').textContent = editMat ? 'Edit Content' : 'Add Content';

  // Type selector visibility
  const typeSelector = document.getElementById('materialTypeSelector');
  typeSelector.style.display = editMat ? 'none' : '';

  // Reset & set type
  setMaterialType(activeType, !editMat);

  // Populate fields if editing
  document.getElementById('matTitle').value = editMat ? editMat.title : '';
  document.getElementById('matDescription').value = editMat ? (editMat.description || '') : '';

  if (editMat) {
    if (editMat.material_type === 'link') {
      document.getElementById('matUrl').value = editMat.content || '';
    } else if (editMat.material_type === 'text') {
      document.getElementById('matTextContent').value = editMat.content || '';
    } else if (editMat.material_type === 'assignment') {
      document.getElementById('matAssignContent').value = editMat.content || '';
      document.getElementById('matDueDate').value = editMat.due_date || '';
    }
  }

  clearMatAlert();
  document.getElementById('saveMaterialBtn').querySelector('.btn-text').textContent =
    editMat ? 'Save Changes' : 'Add Content';

  document.getElementById('materialModal').classList.remove('hidden');
  setTimeout(() => document.getElementById('matTitle').focus(), 50);
}

function bindMaterialModal() {
  document.getElementById('closeMaterialModal').addEventListener('click', closeMaterialModal);
  document.getElementById('cancelMaterialBtn').addEventListener('click', closeMaterialModal);
  document.getElementById('materialModal').addEventListener('click', e => {
    if (e.target === document.getElementById('materialModal')) closeMaterialModal();
  });

  // Type buttons
  document.querySelectorAll('.mat-type-btn').forEach(btn => {
    btn.addEventListener('click', () => setMaterialType(btn.dataset.type, true));
  });

  // File input / drop zone
  document.getElementById('matDropZone').addEventListener('click', () => {
    document.getElementById('matFileInput').click();
  });
  document.getElementById('matFileInput').addEventListener('change', e => {
    const file = e.target.files[0];
    if (file) showSelectedFile(file);
  });
  document.getElementById('matDropZone').addEventListener('dragover', e => {
    e.preventDefault(); e.currentTarget.style.borderColor = '#6366f1';
  });
  document.getElementById('matDropZone').addEventListener('dragleave', e => {
    e.currentTarget.style.borderColor = '';
  });
  document.getElementById('matDropZone').addEventListener('drop', e => {
    e.preventDefault(); e.currentTarget.style.borderColor = '';
    const file = e.dataTransfer.files[0];
    if (file) {
      document.getElementById('matFileInput').files = e.dataTransfer.files;
      showSelectedFile(file);
    }
  });
  document.getElementById('matClearFile').addEventListener('click', clearFileSelection);

  document.getElementById('materialForm').addEventListener('submit', saveMaterial);
}

function setMaterialType(type, updateButtons = true) {
  activeType = type;
  document.getElementById('matType').value = type;

  document.getElementById('matFileArea').classList.toggle('hidden',   type !== 'file');
  document.getElementById('matLinkArea').classList.toggle('hidden',   type !== 'link');
  document.getElementById('matTextArea').classList.toggle('hidden',   type !== 'text');
  document.getElementById('matAssignArea').classList.toggle('hidden', type !== 'assignment');

  if (updateButtons) {
    document.querySelectorAll('.mat-type-btn').forEach(b => {
      b.classList.toggle('active', b.dataset.type === type);
    });
  }
  clearFileSelection();
}

function showSelectedFile(file) {
  document.getElementById('matDropZone').style.display     = 'none';
  document.getElementById('matSelectedFile').classList.remove('hidden');
  document.getElementById('matFileName').textContent       = file.name;
}
function clearFileSelection() {
  document.getElementById('matFileInput').value            = '';
  document.getElementById('matDropZone').style.display     = '';
  document.getElementById('matSelectedFile').classList.add('hidden');
  document.getElementById('matFileName').textContent       = '';
}
function closeMaterialModal() {
  document.getElementById('materialModal').classList.add('hidden');
  clearFileSelection();
}
function clearMatAlert() {
  const a = document.getElementById('materialFormAlert');
  a.textContent = ''; a.classList.add('hidden');
}
function showMatAlert(msg) {
  const a = document.getElementById('materialFormAlert');
  a.textContent = msg; a.className = 'alert alert-error';
}

async function saveMaterial(e) {
  e.preventDefault();
  const btn  = document.getElementById('saveMaterialBtn');
  const type = document.getElementById('matType').value;
  clearMatAlert();
  btn.disabled = true;
  btn.querySelector('.btn-text').textContent = 'Saving…';

  try {
    let data;
    if (type === 'file' && !editMaterialId) {
      // ─── File upload via FormData
      const file = document.getElementById('matFileInput').files[0];
      if (!file) { showMatAlert('Please select a file to upload.'); return; }

      const fd = new FormData();
      fd.append('module_id',   matModuleId);
      fd.append('title',       document.getElementById('matTitle').value.trim());
      fd.append('description', document.getElementById('matDescription').value.trim());
      fd.append('file',        file);

      const res = await fetch('../api/courses/material-upload.php', {
        method: 'POST',
        headers: EQ.authFetchHeaders(),
        body: fd,
      });
      data = await res.json();
    } else {
      // ─── JSON create / update
      const title = document.getElementById('matTitle').value.trim();
      if (!title) { showMatAlert('Title is required.'); return; }

      let content = '';
      let dueDate = null;
      if (type === 'link')       content = document.getElementById('matUrl').value.trim();
      if (type === 'text')       content = document.getElementById('matTextContent').value.trim();
      if (type === 'assignment') {
        content = document.getElementById('matAssignContent').value.trim();
        dueDate = document.getElementById('matDueDate').value || null;
      }

      if (editMaterialId) {
        data = await apiPOST('../api/courses/materials.php', {
          action: 'update', id: editMaterialId, title,
          description: document.getElementById('matDescription').value.trim(),
          content, due_date: dueDate,
        });
      } else {
        data = await apiPOST('../api/courses/materials.php', {
          action: 'create', module_id: matModuleId, title,
          description: document.getElementById('matDescription').value.trim(),
          material_type: type, content, due_date: dueDate,
        });
      }
    }

    if (data.success) {
      closeMaterialModal();
      await reloadCourse();
    } else {
      showMatAlert(data.message || 'Could not save content.');
    }
  } catch {
    showMatAlert('Connection error, please try again.');
  } finally {
    btn.disabled = false;
    btn.querySelector('.btn-text').textContent = editMaterialId ? 'Save Changes' : 'Add Content';
  }
}

/* ═══════════════════════════════════════════════════════
   ANNOUNCEMENTS TAB
   ═══════════════════════════════════════════════════════ */
function bindAnnouncements() {
  document.getElementById('addAnnBtn').addEventListener('click', () => {
    document.getElementById('annEditId').value = '';
    document.getElementById('annTitle').value  = '';
    document.getElementById('annContent').value = '';
    document.getElementById('annPinned').checked = false;
    document.getElementById('saveAnnBtn').textContent = 'Post';
    document.getElementById('annForm').classList.remove('hidden');
    document.getElementById('annTitle').focus();
  });
  document.getElementById('cancelAnnBtn').addEventListener('click', () => {
    document.getElementById('annForm').classList.add('hidden');
  });
  document.getElementById('saveAnnBtn').addEventListener('click', saveAnn);
}

function renderAnnouncements(anns) {
  const list  = document.getElementById('annList');
  const empty = document.getElementById('annEmpty');
  if (!anns.length) {
    list.innerHTML = ''; empty.classList.remove('hidden'); return;
  }
  empty.classList.add('hidden');
  list.innerHTML = anns.map(a => `
    <div class="cv-ann-card${a.is_pinned ? ' pinned' : ''}">
      <div class="cv-ann-header">
        <p class="cv-ann-title">${esc(a.title)}</p>
        ${a.is_pinned ? `<span class="cv-ann-pin">&#128204; Pinned</span>` : ''}
      </div>
      <p class="cv-ann-content">${esc(a.content)}</p>
      <div class="cv-ann-footer">
        <span>${fmtTs(a.created_at)}</span>
        <div class="cv-ann-actions">
          <button class="btn-icon" title="Edit"   onclick="editAnn(${a.id})">&#9998;</button>
          <button class="btn-icon danger" title="Delete" onclick="deleteAnn(${a.id},'${esc(a.title)}')">&#128465;</button>
        </div>
      </div>
    </div>`).join('');
}

async function saveAnn() {
  const annId   = document.getElementById('annEditId').value;
  const title   = document.getElementById('annTitle').value.trim();
  const content = document.getElementById('annContent').value.trim();
  const pinned  = document.getElementById('annPinned').checked ? 1 : 0;
  if (!title || !content) { showAlert('Title and content are required.', 'error'); return; }

  const btn = document.getElementById('saveAnnBtn');
  btn.disabled = true; btn.textContent = 'Saving…';

  try {
    let data;
    if (annId) {
      data = await apiPOST('../api/courses/announcements.php', {
        action: 'update', id: annId, title, content, is_pinned: pinned,
      });
    } else {
      data = await apiPOST('../api/courses/announcements.php', {
        action: 'create', course_id: courseId, title, content, is_pinned: pinned,
      });
    }
    if (data.success) {
      document.getElementById('annForm').classList.add('hidden');
      await reloadCourse();
    } else {
      showAlert(data.message || 'Could not save announcement.', 'error');
    }
  } catch {
    showAlert('Connection error.', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = annId ? 'Save Changes' : 'Post';
  }
}

function editAnn(id) {
  const ann = (course.announcements || []).find(a => a.id == id);
  if (!ann) return;
  document.getElementById('annEditId').value  = ann.id;
  document.getElementById('annTitle').value   = ann.title;
  document.getElementById('annContent').value = ann.content;
  document.getElementById('annPinned').checked = !!ann.is_pinned;
  document.getElementById('saveAnnBtn').textContent = 'Save Changes';
  document.getElementById('annForm').classList.remove('hidden');
  document.getElementById('annTitle').focus();
  document.getElementById('annForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function deleteAnn(id, title) {
  if (!confirm(`Delete announcement "${title}"?`)) return;
  try {
    const data = await apiPOST('../api/courses/announcements.php', { action: 'delete', id });
    if (data.success) await reloadCourse();
    else showAlert(data.message || 'Could not delete.', 'error');
  } catch {
    showAlert('Connection error.', 'error');
  }
}

/* ═══════════════════════════════════════════════════════
   STUDENTS TAB
   ═══════════════════════════════════════════════════════ */
function bindStudents() {
  document.getElementById('enrollBtn').addEventListener('click', loadEnrollPicker);
  document.getElementById('cancelEnrollBtn').addEventListener('click', () => {
    document.getElementById('enrollPicker').classList.add('hidden');
  });
  document.getElementById('confirmEnrollBtn').addEventListener('click', enrollSelected);
  document.getElementById('enrollSearch').addEventListener('input', function () {
    clearTimeout(enrollTimer);
    enrollTimer = setTimeout(() => filterAvailable(this.value), 200);
  });
}

function renderEnrolled(students) {
  const list  = document.getElementById('enrolledList');
  const empty = document.getElementById('studentsEmpty');
  if (!students.length) {
    list.innerHTML = ''; empty.classList.remove('hidden'); return;
  }
  empty.classList.add('hidden');
  list.innerHTML = students.map(s => `
    <div class="cv-student-row">
      <div class="cv-student-avatar">
        ${s.profile_photo
          ? `<img src="../uploads/photos/${esc(s.profile_photo)}" style="width:100%;height:100%;border-radius:50%;object-fit:cover" />`
          : initials(s.first_name, s.last_name)}
      </div>
      <div class="cv-student-info">
        <div class="cv-student-name">${esc(s.first_name)} ${esc(s.last_name)}</div>
        <div class="cv-student-meta">${esc(s.grade_level || '')}${s.school_name ? ' · ' + esc(s.school_name) : ''}</div>
      </div>
      <a href="student-view.php?id=${s.id}" class="btn btn-outline btn-sm" style="flex-shrink:0">View</a>
      <button class="btn-icon danger" title="Unenroll" onclick="unenrollStudent(${s.id},'${esc(s.first_name)} ${esc(s.last_name)}')">&#10005;</button>
    </div>`).join('');
}

async function loadEnrollPicker() {
  document.getElementById('enrollPicker').classList.remove('hidden');
  document.getElementById('availableList').innerHTML = '<div class="loading-msg">Loading…</div>';
  document.getElementById('enrollSearch').value = '';

  try {
    // Load teacher's existing students not yet enrolled
    const res  = await fetch(`../api/courses/enroll.php?action=list&course_id=${courseId}`, { headers: EQ.authHeaders() });
    const data = await res.json();
    if (!data.success) { showAlert(data.message, 'error'); return; }

    const available = data.data.available || [];
    renderAvailableList(available);

    // Also load registered student suggestions (unlinked)
    loadEnrollSuggestions();
  } catch {
    showAlert('Connection error.', 'error');
  }
}

async function loadEnrollSuggestions() {
  const section = document.getElementById('enrollSuggestSection');
  const list    = document.getElementById('enrollSuggestList');
  try {
    const res  = await fetch('../api/students/suggestions.php?per_page=20', { headers: EQ.authHeaders() });
    const data = await res.json();
    if (!data.success || !data.data.students.length) {
      section.classList.add('hidden');
      return;
    }
    section.classList.remove('hidden');
    list.innerHTML = data.data.students.map(s => `
      <label class="cv-avail-item" id="suggest-enroll-${s.id}" data-suggest="1" data-sid="${s.id}">
        <input type="checkbox" value="${s.id}" data-suggest="1" />
        <span>${esc(s.first_name)} ${esc(s.last_name)} <small style="color:#94a3b8">(${esc(s.email)})</small></span>
      </label>`).join('');
  } catch {
    section.classList.add('hidden');
  }
}

function renderAvailableList(students) {
  const list = document.getElementById('availableList');
  if (!students.length) {
    list.innerHTML = '<p class="muted" style="font-size:0.85rem;padding:0.5rem">All your students are already enrolled.</p>';
    return;
  }
  list.innerHTML = students.map(s => `
    <label class="cv-avail-item" id="avail-${s.id}">
      <input type="checkbox" value="${s.id}" />
      <span>${esc(s.first_name)} ${esc(s.last_name)}${s.grade_level ? ' — ' + esc(s.grade_level) : ''}</span>
    </label>`).join('');
}

function filterAvailable(q) {
  const term = q.toLowerCase();
  document.querySelectorAll('#availableList .cv-avail-item, #enrollSuggestList .cv-avail-item').forEach(item => {
    const text = item.textContent.toLowerCase();
    item.classList.toggle('hidden', term && !text.includes(term));
  });
}

async function enrollSelected() {
  const checkedAvail   = [...document.querySelectorAll('#availableList input[type=checkbox]:checked')];
  const checkedSuggest = [...document.querySelectorAll('#enrollSuggestList input[type=checkbox]:checked')];
  if (!checkedAvail.length && !checkedSuggest.length) { showAlert('Select at least one student.', 'error'); return; }

  const btn = document.getElementById('confirmEnrollBtn');
  btn.disabled = true; btn.textContent = 'Enrolling…';

  try {
    // First: link + enroll any suggested (unlinked) students
    for (const cb of checkedSuggest) {
      const sid = parseInt(cb.value);
      await fetch('../api/students/link.php', {
        method: 'POST',
        headers: EQ.authHeaders(),
        body: JSON.stringify({ student_id: sid, course_id: courseId }),
      });
    }

    // Then: enroll already-linked students
    if (checkedAvail.length) {
      const studentIds = checkedAvail.map(c => parseInt(c.value));
      const data = await apiPOST('../api/courses/enroll.php', { action: 'enroll', course_id: courseId, student_ids: studentIds });
      if (!data.success) {
        showAlert(data.message || 'Could not enroll students.', 'error');
        return;
      }
    }

    document.getElementById('enrollPicker').classList.add('hidden');
    await reloadCourse();
  } catch {
    showAlert('Connection error.', 'error');
  } finally {
    btn.disabled = false; btn.textContent = 'Enroll Selected';
  }
}

async function unenrollStudent(studentId, name) {
  if (!confirm(`Remove ${name} from this course?`)) return;
  try {
    const data = await apiPOST('../api/courses/enroll.php', { action: 'unenroll', course_id: courseId, student_id: studentId });
    if (data.success) await reloadCourse();
    else showAlert(data.message || 'Could not unenroll student.', 'error');
  } catch {
    showAlert('Connection error.', 'error');
  }
}

/* ═══════════════════════════════════════════════════════
   SETTINGS TAB
   ═══════════════════════════════════════════════════════ */
function bindSettings() {
  // Cover colour pickers in Settings tab
  document.getElementById('setColor').addEventListener('input', e => {
    syncSettingSwatches(e.target.value);
  });
  document.querySelectorAll('#tabSettings .color-swatch').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('setColor').value = btn.dataset.color;
      syncSettingSwatches(btn.dataset.color);
    });
  });

  document.getElementById('settingsForm').addEventListener('submit', saveSettings);
  document.getElementById('deleteCourseBtn').addEventListener('click', deleteCourse);
}

function prefillSettings(c) {
  document.getElementById('setTitle').value       = c.title       || '';
  document.getElementById('setSubject').value     = c.subject     || '';
  document.getElementById('setGrade').value       = c.grade_level || '';
  document.getElementById('setYear').value        = c.school_year || '';
  document.getElementById('setDescription').value = c.description || '';
  const color = c.cover_color || '#6366f1';
  document.getElementById('setColor').value = color;
  syncSettingSwatches(color);
}

function syncSettingSwatches(color) {
  document.querySelectorAll('#tabSettings .color-swatch').forEach(b => {
    b.classList.toggle('active', b.dataset.color === color);
  });
}

async function saveSettings(e) {
  e.preventDefault();
  const alertEl = document.getElementById('settingsAlert');
  alertEl.classList.add('hidden');

  const payload = {
    id:          courseId,
    title:       document.getElementById('setTitle').value.trim(),
    subject:     document.getElementById('setSubject').value.trim(),
    grade_level: document.getElementById('setGrade').value.trim(),
    school_year: document.getElementById('setYear').value.trim(),
    cover_color: document.getElementById('setColor').value,
    description: document.getElementById('setDescription').value.trim(),
  };
  if (!payload.title) {
    showSectionAlert('settingsAlert', 'Course title is required.', 'error');
    return;
  }
  try {
    const data = await apiPOST('../api/courses/save.php', payload);
    if (data.success) {
      showSectionAlert('settingsAlert', 'Settings saved.', 'success');
      await reloadCourse();
    } else {
      showSectionAlert('settingsAlert', data.message || 'Could not save.', 'error');
    }
  } catch {
    showSectionAlert('settingsAlert', 'Connection error.', 'error');
  }
}

async function deleteCourse() {
  if (!confirm('Delete this course?\n\nAll modules, materials, and enrollment records will be permanently removed.')) return;
  try {
    const data = await apiPOST('../api/courses/delete.php', { id: courseId });
    if (data.success) { window.location.href = 'courses.php'; }
    else showAlert(data.message || 'Could not delete course.', 'error');
  } catch {
    showAlert('Connection error.', 'error');
  }
}

/* ═══════════════════════════════════════════════════════
   RELOAD HELPER
   ═══════════════════════════════════════════════════════ */
async function reloadCourse() {
  try {
    const res  = await fetch(`../api/courses/get.php?id=${courseId}`, { headers: EQ.authHeaders() });
    const data = await res.json();
    if (!data.success) return;
    course = data.data.course;
    renderBanner(course);
    renderModules(course.modules || []);
    renderAnnouncements(course.announcements || []);
    renderEnrolled(course.students || []);
    prefillSettings(course);
  } catch {
    showAlert('Failed to refresh data.', 'error');
  }
}

/* ═══════════════════════════════════════
   MATERIAL VIEWER
   ═══════════════════════════════════════ */

function findMaterial(id) {
  for (const mod of (course.modules || [])) {
    const mat = (mod.materials || []).find(m => m.id == id);
    if (mat) return mat;
  }
  return null;
}

function openMaterialViewer(matId) {
  const mat = findMaterial(matId);
  if (!mat) return;
  const modal = document.getElementById('materialViewerModal');
  modal.classList.remove('hidden');
  document.getElementById('mvTitle').textContent = mat.title || mat.original_filename;
  document.getElementById('mvDownloadBtn').href = `../api/courses/material-download.php?id=${mat.id}`;
  loadMaterialPreview(mat);
}

function closeMaterialViewer() {
  const modal = document.getElementById('materialViewerModal');
  modal.classList.add('hidden');
  modal.querySelector('.mv-modal').classList.remove('mv-maximized');
  if (_matBlobUrl) { URL.revokeObjectURL(_matBlobUrl); _matBlobUrl = null; }
  document.getElementById('mvBody').innerHTML = '';
}

function toggleMaxViewer() {
  const modal = document.querySelector('.mv-modal');
  const btn = document.getElementById('mvMaxBtn');
  modal.classList.toggle('mv-maximized');
  if (modal.classList.contains('mv-maximized')) {
    btn.innerHTML = '&#9635;';
    btn.title = 'Restore';
  } else {
    btn.innerHTML = '&#9634;';
    btn.title = 'Maximize';
  }
}

async function loadMaterialPreview(mat) {
  const body = document.getElementById('mvBody');
  body.innerHTML = '<div class="dv-loading">Loading document&hellip;</div>';

  const mime  = mat.mime_type || '';
  const fname = (mat.original_filename || '').toLowerCase();
  const isPdf   = mime === 'application/pdf';
  const isImage = /^image\//.test(mime);
  const isDocx  = mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                  || fname.endsWith('.docx');
  const isDoc   = (mime === 'application/msword' || fname.endsWith('.doc')) && !isDocx;
  const isExcel = /spreadsheet|excel/i.test(mime) || fname.endsWith('.xlsx') || fname.endsWith('.xls');
  const isVideo = /^video\//.test(mime);
  const isAudio = /^audio\//.test(mime);
  const isText  = /^text\//.test(mime) || fname.endsWith('.csv') || fname.endsWith('.txt');

  try {
    const resp = await fetch(`../api/courses/material-preview.php?id=${mat.id}`, {
      headers: EQ.authFetchHeaders()
    });
    if (!resp.ok) throw new Error('Server returned ' + resp.status);
    const blob = await resp.blob();

    /* PDF */
    if (isPdf) {
      _matBlobUrl = URL.createObjectURL(blob);
      body.innerHTML = `<iframe src="${_matBlobUrl}" class="mv-iframe"></iframe>`;
      return;
    }

    /* Image */
    if (isImage) {
      _matBlobUrl = URL.createObjectURL(blob);
      body.innerHTML = `<img src="${_matBlobUrl}" class="mv-img" alt="${esc(mat.original_filename)}" />`;
      return;
    }

    /* DOCX → HTML (mammoth.js) */
    if (isDocx && typeof mammoth !== 'undefined') {
      const arrayBuf = await blob.arrayBuffer();
      const result = await mammoth.convertToHtml({ arrayBuffer: arrayBuf });
      body.innerHTML = `<div class="mv-html-content">${result.value}</div>`;
      return;
    }

    /* Excel → HTML table (SheetJS) */
    if (isExcel && typeof XLSX !== 'undefined') {
      const arrayBuf = await blob.arrayBuffer();
      const workbook = XLSX.read(arrayBuf, { type: 'array' });
      let tabsHtml = '';
      let sheetsHtml = '';
      workbook.SheetNames.forEach((name, i) => {
        const ws = workbook.Sheets[name];
        const html = XLSX.utils.sheet_to_html(ws, { editable: false });
        tabsHtml += `<button class="mv-sheet-tab${i === 0 ? ' active' : ''}" data-sheet="${i}">${esc(name)}</button>`;
        sheetsHtml += `<div class="mv-sheet-pane${i === 0 ? ' active' : ''}" data-sheet="${i}">${html}</div>`;
      });
      body.innerHTML = `
        <div class="mv-sheet-bar">${tabsHtml}</div>
        <div class="mv-html-content mv-excel">${sheetsHtml}</div>`;
      body.querySelectorAll('.mv-sheet-tab').forEach(btn => {
        btn.addEventListener('click', () => {
          body.querySelectorAll('.mv-sheet-tab').forEach(b => b.classList.remove('active'));
          body.querySelectorAll('.mv-sheet-pane').forEach(p => p.classList.remove('active'));
          btn.classList.add('active');
          body.querySelector(`.mv-sheet-pane[data-sheet="${btn.dataset.sheet}"]`).classList.add('active');
        });
      });
      return;
    }

    /* Video */
    if (isVideo) {
      _matBlobUrl = URL.createObjectURL(blob);
      body.innerHTML = `<video src="${_matBlobUrl}" controls class="mv-video"></video>`;
      return;
    }

    /* Audio */
    if (isAudio) {
      _matBlobUrl = URL.createObjectURL(blob);
      body.innerHTML = `<div class="mv-audio-wrap"><audio src="${_matBlobUrl}" controls></audio></div>`;
      return;
    }

    /* Plain text / CSV */
    if (isText) {
      const text = await blob.text();
      body.innerHTML = `<pre class="mv-text-content">${esc(text)}</pre>`;
      return;
    }

    /* Fallback: cannot preview */
    body.innerHTML = `
      <div class="mv-no-preview">
        <p>${esc(mat.original_filename)}</p>
        <p class="muted">This file type cannot be previewed in the browser.</p>
        <a href="../api/courses/material-download.php?id=${mat.id}" class="btn btn-primary" target="_blank">&#8595; Download to View</a>
      </div>`;

  } catch (err) {
    body.innerHTML = `
      <div class="mv-no-preview">
        <p class="muted">Could not load preview: ${esc(err.message)}</p>
        <a href="../api/courses/material-download.php?id=${mat.id}" class="btn btn-outline" target="_blank">&#8595; Download instead</a>
      </div>`;
  }
}

/* ═════════════════════════════════════════════════════════════
   Assignment Submissions Viewer
   ═════════════════════════════════════════════════════════════ */
let _submissionsModalId = null;

function openSubmissions(materialId, title) {
  _submissionsModalId = materialId;

  // Create modal if not present
  if (!document.getElementById('submissionsModal')) {
    document.body.insertAdjacentHTML('beforeend', `
      <div class="modal-overlay" id="submissionsModal" style="display:none" onclick="if(event.target===this)closeSubmissions()">
        <div class="modal-box" style="max-width:720px;width:95%">
          <div class="modal-header">
            <h3 id="subModalTitle">Submissions</h3>
            <button class="btn-icon" onclick="closeSubmissions()">&times;</button>
          </div>
          <div class="modal-body" id="subModalBody" style="max-height:70vh;overflow-y:auto;padding:1rem">
            Loading...
          </div>
        </div>
      </div>
    `);
  }

  document.getElementById('subModalTitle').textContent = 'Submissions — ' + title;
  document.getElementById('subModalBody').innerHTML = '<p class="muted" style="text-align:center;padding:2rem">Loading submissions&hellip;</p>';
  document.getElementById('submissionsModal').style.display = '';
  document.body.style.overflow = 'hidden';

  fetchSubmissions(materialId);
}

function closeSubmissions() {
  const m = document.getElementById('submissionsModal');
  if (m) m.style.display = 'none';
  document.body.style.overflow = '';
}

async function fetchSubmissions(materialId) {
  const body = document.getElementById('subModalBody');
  try {
    const resp = await fetch(`../api/courses/submissions.php?materialId=${materialId}`, {
      headers: EQ.authFetchHeaders()
    });
    const json = await resp.json();
    if (!json.success) { body.innerHTML = `<p class="text-danger">${esc(json.message)}</p>`; return; }

    const d = json.data;
    const { submissions, enrolledCount, submittedCount } = d;

    let html = `
      <div style="display:flex;gap:1rem;margin-bottom:1rem;font-size:0.85rem;color:#6b7280">
        <span>👥 Enrolled: <strong>${enrolledCount}</strong></span>
        <span>📤 Submitted: <strong>${submittedCount}</strong></span>
      </div>`;

    if (!submissions.length) {
      html += '<p class="muted" style="text-align:center;padding:2rem">No submissions yet.</p>';
    } else {
      html += '<div class="sub-list">';
      submissions.forEach(s => {
        const statusBadge = s.status === 'graded'
          ? '<span class="sub-badge sub-graded">Graded</span>'
          : s.status === 'returned'
            ? '<span class="sub-badge sub-returned">Returned</span>'
            : '<span class="sub-badge sub-submitted">Submitted</span>';

        const gradeDisplay = s.grade !== null && s.grade !== undefined
          ? `<span style="font-weight:700;color:#059669">${s.grade}</span>` : '—';

        html += `
          <div class="sub-card" id="subCard-${s.id}">
            <div class="sub-card-header">
              <div>
                <strong>${esc(s.studentName)}</strong>
                ${statusBadge}
              </div>
              <span style="font-size:0.8rem;color:#9ca3af">${new Date(s.submittedAt).toLocaleString()}</span>
            </div>
            <div class="sub-card-body">
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                <button onclick="downloadSubmission(${s.id},'${esc(s.originalFilename)}')"
                   class="btn btn-sm btn-outline" style="font-size:0.8rem;cursor:pointer">
                  &#8595; ${esc(s.originalFilename)}
                </button>
                <span style="font-size:0.75rem;color:#9ca3af">${formatFileSize(s.fileSize)}</span>
              </div>
              ${s.notes ? `<p style="font-size:0.82rem;color:#4b5563;margin-bottom:8px"><em>Note:</em> ${esc(s.notes)}</p>` : ''}
              <div class="sub-grade-row">
                <label style="font-size:0.82rem;font-weight:600">Grade:</label>
                <input type="number" step="0.01" min="0" max="100" id="gradeInput-${s.id}" value="${s.grade ?? ''}"
                       class="sub-grade-input" placeholder="0-100" />
              </div>
              <div style="margin-top:6px">
                <label style="font-size:0.82rem;font-weight:600">Feedback:</label>
                <textarea id="feedbackInput-${s.id}" class="sub-feedback-input" rows="2" placeholder="Write feedback...">${esc(s.feedback || '')}</textarea>
              </div>
              <div style="margin-top:8px;display:flex;gap:8px;align-items:center">
                <button class="btn btn-primary btn-sm" onclick="gradeSubmission(${s.id})">💾 Save Grade</button>
                <span class="sub-save-status" id="saveStatus-${s.id}" style="font-size:0.8rem"></span>
              </div>
            </div>
          </div>`;
      });
      html += '</div>';
    }

    body.innerHTML = html;
  } catch (err) {
    body.innerHTML = `<p class="text-danger">Error loading submissions: ${esc(err.message)}</p>`;
  }
}

function formatFileSize(bytes) {
  if (!bytes) return '0 B';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1048576).toFixed(1) + ' MB';
}

async function gradeSubmission(subId) {
  const gradeEl    = document.getElementById('gradeInput-' + subId);
  const feedbackEl = document.getElementById('feedbackInput-' + subId);
  const statusEl   = document.getElementById('saveStatus-' + subId);

  const grade    = gradeEl ? gradeEl.value : '';
  const feedback = feedbackEl ? feedbackEl.value : '';

  if (statusEl) { statusEl.textContent = 'Saving...'; statusEl.style.color = '#6b7280'; }

  try {
    const resp = await fetch('../api/courses/submissions.php', {
      method: 'POST',
      headers: EQ.authFetchHeaders(),
      body: JSON.stringify({ action: 'grade', submissionId: subId, grade, feedback })
    });
    const json = await resp.json();
    if (json.success) {
      if (statusEl) { statusEl.textContent = '✅ Saved'; statusEl.style.color = '#059669'; }
      // Update badge
      const card = document.getElementById('subCard-' + subId);
      if (card) {
        const badge = card.querySelector('.sub-badge');
        if (badge) { badge.className = 'sub-badge sub-graded'; badge.textContent = 'Graded'; }
      }
    } else {
      if (statusEl) { statusEl.textContent = '❌ ' + (json.message || 'Error'); statusEl.style.color = '#dc2626'; }
    }
  } catch (err) {
    if (statusEl) { statusEl.textContent = '❌ Network error'; statusEl.style.color = '#dc2626'; }
  }
}

async function downloadSubmission(subId, filename) {
  try {
    const resp = await fetch(`../api/courses/submissions.php?action=download&submissionId=${subId}`, {
      headers: EQ.authFetchHeaders()
    });
    if (!resp.ok) throw new Error('Download failed');
    const blob = await resp.blob();
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  } catch (err) {
    alert('Could not download file: ' + err.message);
  }
}
