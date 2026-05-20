/* ─────────────────────────────────────────────
   EduQuest — student-import.js
   Handles:
     • Document: drag-drop queue, upload, profile results
     • Profile results viewer
   ───────────────────────────────────────────── */

(function () {
  'use strict';

  /* ─── Globals ─────────────────── */
  let docFiles = [];            // FileList staging array

  /* ─── Init ───────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', () => {
    wireDocZone();
  });

  /* ══════════════════════════════════════
     DOCUMENT UPLOAD
     ══════════════════════════════════════ */

  function wireDocZone() {
    const zone      = document.getElementById('docDropZone');
    const input     = document.getElementById('docFilesInput');
    const uploadBtn = document.getElementById('uploadDocsBtn');

    zone.addEventListener('click', () => input.click());
    zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
      e.preventDefault();
      zone.classList.remove('drag-over');
      addDocFiles(Array.from(e.dataTransfer.files));
    });

    input.addEventListener('change', () => {
      addDocFiles(Array.from(input.files));
      input.value = '';
    });

    uploadBtn.addEventListener('click', uploadDocuments);
  }

  const ALLOWED_DOC_EXT = /\.(pdf|doc|docx|xls|xlsx|jpg|jpeg|png|tif|tiff)$/i;

  function addDocFiles(files) {
    const added    = files.filter(f => ALLOWED_DOC_EXT.test(f.name));
    const rejected = files.length - added.length;
    if (rejected > 0) {
      showBanner(`${rejected} file(s) rejected — only PDF, Word, Excel, JPEG, PNG and TIFF are allowed.`, 'warn');
    }
    const remaining = 10 - docFiles.length;
    if (added.length > remaining) {
      showBanner(`Only ${remaining} more file(s) can be added (max 10).`, 'warn');
      added.splice(remaining);
    }
    docFiles.push(...added);
    renderDocQueue();
  }

  function renderDocQueue() {
    const queue = document.getElementById('docFileQueue');
    queue.innerHTML = '';
    docFiles.forEach((f, i) => {
      const item = document.createElement('div');
      item.className = 'doc-queue-item';
      item.innerHTML = `
        <span class="doc-icon">${fileIcon(f.name)}</span>
        <span class="dq-name" title="${esc(f.name)}">${esc(f.name)}</span>
        <span class="dq-size">${formatBytes(f.size)}</span>
        <button type="button" class="dq-remove" data-index="${i}" title="Remove">&#10005;</button>
      `;
      queue.appendChild(item);
    });
    queue.querySelectorAll('.dq-remove').forEach(btn => {
      btn.addEventListener('click', () => {
        docFiles.splice(parseInt(btn.dataset.index), 1);
        renderDocQueue();
        if (!docFiles.length) hide('docUploadOptions');
      });
    });
    if (docFiles.length > 0) show('docUploadOptions');
    else hide('docUploadOptions');
  }

  async function uploadDocuments() {
    if (docFiles.length === 0) return;
    const uploadBtn = document.getElementById('uploadDocsBtn');
    uploadBtn.setAttribute('disabled', 'true');
    uploadBtn.textContent = 'Uploading…';

    const nameHintsRaw = (document.getElementById('nameHints').value || '').trim();
    const nameHints    = nameHintsRaw.split('\n').map(n => n.trim());
    const docType      = document.getElementById('docTypeSelect').value;

    show('docUploadProgress');
    const fill = document.getElementById('docProgressFill');
    const txt  = document.getElementById('docProgressText');

    const fd = new FormData();
    docFiles.forEach((f, i) => {
      fd.append('file[]', f);
      fd.append('suggested_name[]', nameHints[i] || '');
    });
    fd.append('document_type', docType);

    let pct = 5;
    const interval = setInterval(() => {
      pct = Math.min(pct + 8, 85);
      fill.style.width = pct + '%';
      txt.textContent = 'Uploading… ' + pct + '%';
    }, 400);

    try {
      const resp = await fetch('../api/students/import-document.php', {
        method: 'POST',
        headers: window.EQ.authFetchHeaders(),
        body: fd
      });
      clearInterval(interval);
      fill.style.width = '100%';
      txt.textContent = 'Processing…';
      const data = await resp.json();
      hide('docUploadProgress');
      if (!resp.ok) {
        showBanner(data.message || 'Upload failed.', 'error');
      } else {
        renderDocResult(data);
        docFiles = [];
        renderDocQueue();
      }
    } catch (err) {
      clearInterval(interval);
      hide('docUploadProgress');
      showBanner('Network error: ' + err.message, 'error');
    } finally {
      uploadBtn.removeAttribute('disabled');
      uploadBtn.textContent = '⬆ Upload & Create Profiles';
    }
  }

  function renderDocResult(data) {
    const list = document.getElementById('draftProfileList');
    list.innerHTML = '';
    const payload = data.data || {};
    const created = payload.created_profiles || [];
    const errors  = payload.errors           || [];

    created.forEach(item => {
      const div = document.createElement('div');
      div.className = 'draft-item';
      div.innerHTML = `
        <span class="draft-icon">${fileIcon(item.filename || '')}</span>
        <div class="draft-info">
          <div class="draft-name">${esc(item.student_name || 'Unnamed')}</div>
          <div class="draft-file">${esc(item.filename || '')}</div>
        </div>
        <a href="student-view.php?id=${item.student_id}" class="btn btn-primary btn-sm">View Profile &rarr;</a>
      `;
      list.appendChild(div);
    });

    errors.forEach(e => {
      const div = document.createElement('div');
      div.className = 'error-item-block';
      div.innerHTML = `&#10005; ${esc(e.filename || e.file || 'Unknown file')}: ${esc(e.error || 'Upload failed')}`;
      list.appendChild(div);
    });

    show('docUploadResult');
    document.getElementById('docUploadResult').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  /* ─── Utility functions ────────────────────── */

  function show(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('hidden');
  }

  function hide(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('hidden');
  }

  function esc(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatBytes(bytes) {
    if (bytes < 1024)       return bytes + ' B';
    if (bytes < 1048576)    return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
  }

  function fileIcon(name) {
    if (/\.pdf$/i.test(name))           return '&#128196;';
    if (/\.(doc|docx)$/i.test(name))    return '&#128196;';
    if (/\.(jpg|jpeg|png|tiff?)$/i.test(name)) return '&#128247;';
    return '&#128196;';
  }

  function showBanner(msg, type) {
    const existing = document.getElementById('globalBanner');
    if (existing) existing.remove();

    const div = document.createElement('div');
    div.id = 'globalBanner';
    div.style.cssText = `
      position: fixed; top: 1rem; right: 1.5rem; z-index: 9999;
      padding: 0.75rem 1.25rem; border-radius: 8px;
      font-size: 0.88rem; max-width: 380px;
      box-shadow: 0 4px 12px rgba(0,0,0,.15);
      background: ${type === 'error' ? '#fee2e2' : type === 'warn' ? '#fef9c3' : '#dcfce7'};
      color: ${type === 'error' ? '#991b1b' : type === 'warn' ? '#854d0e' : '#166534'};
      border-left: 4px solid ${type === 'error' ? '#ef4444' : type === 'warn' ? '#eab308' : '#22c55e'};
    `;
    div.textContent = msg;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 6000);
  }

})();
