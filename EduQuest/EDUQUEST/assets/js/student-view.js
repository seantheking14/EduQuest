/**
 * student-view.js
 * Renders the full read-only student profile page and note management.
 */
'use strict';

const ADHD_LABELS = {
  predominantly_inattentive: 'Predominantly Inattentive',
  predominantly_hyperactive_impulsive: 'Predominantly Hyperactive-Impulsive',
  combined_presentation: 'Combined Presentation',
  other_specified: 'Other Specified',
  unspecified: 'Unspecified',
};

const ACCOM_LABELS = {
  instructional: 'Instructional', assessment: 'Assessment',
  environmental: 'Environmental', behavioral: 'Behavioral',
  technology: 'Technology', social_emotional: 'Social-Emotional', other: 'Other',
};

let studentId = null;

document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  studentId = parseInt(params.get('id'), 10);
  if (!studentId) { document.getElementById('profileContent').innerHTML = '<div class="alert alert-error">No student ID provided.</div>'; return; }

  loadProfile();

  document.getElementById('cancelNoteBtn').addEventListener('click', () => document.getElementById('noteModal').classList.add('hidden'));
  document.getElementById('saveNoteBtn').addEventListener('click', saveNote);
  // Set default date for note
  document.getElementById('noteDate').value = new Date().toISOString().slice(0, 10);
});

async function loadProfile() {
  try {
    const [studRes, planRes] = await Promise.all([
      fetch(`../api/students/get.php?id=${studentId}`,            { headers: EQ.authHeaders() }),
      fetch(`../api/students/plans.php?student_id=${studentId}`,  { headers: EQ.authHeaders() }),
    ]);
    const studData = await studRes.json();
    const planData = await planRes.json();
    if (!studData.success) { showMainError(studData.message); return; }
    renderProfile(studData.data, planData.success ? planData.data : null);
  } catch {
    showMainError('Failed to load profile.');
  }
}

function renderProfile(d, plans) {
  if (d.student.import_source === 'document') {
    renderDocumentProfile(d);
  } else {
    renderManualProfile(d, plans);
  }
}

/* ─────────────────────────────────────────────────────────
   Manual / CSV profile — full detail grid
   ───────────────────────────────────────────────────────── */
function renderManualProfile(d, plans) {
  const s  = d.student;
  const ap = d.adhd_profile;
  document.title = `EduQuest – ${s.first_name} ${s.last_name}`;
  document.body.classList.remove('dv-page-active');

  document.getElementById('profileContent').innerHTML = `
    <!-- Header -->
    <header class="page-header">
      <div class="student-profile-header">
        <img src="${s.profile_photo ? '../uploads/photos/' + s.profile_photo : '../assets/img/default-avatar.php'}"
             class="profile-photo-lg" alt="Photo" />
        <div>
          <a href="students.php" class="link-muted">&larr; Back to Students</a>
          <h2>${esc(s.first_name)} ${esc(s.last_name)}</h2>
          <p class="muted">${esc(s.grade_level) || ''} · ${esc(s.school_name) || ''}</p>
          ${ap ? `<span class="badge badge-info">${ADHD_LABELS[ap.adhd_type] || ap.adhd_type}</span>
                  <span class="badge ${ap.severity === 'severe' ? 'badge-danger' : ap.severity === 'moderate' ? 'badge-warning' : 'badge-success'}">${cap(ap.severity)}</span>` : ''}
        </div>
      </div>
      <a href="student-form.php?id=${s.id}" class="btn btn-primary">&#9998; Edit Profile</a>
      <a href="student-pov.php?id=${s.id}" class="btn btn-secondary" style="margin-left:.5rem">👁️ View Dashboard</a>
    </header>

    <div class="profile-grid">

      <!-- Basic Info -->
      <div class="card">
        <div class="card-header"><h3>Basic Information</h3></div>
        <div class="card-body">
          ${infoRow('Date of Birth', s.date_of_birth || '–')}
          ${infoRow('Gender', cap(s.gender?.replace('_',' ') || '') || '–')}
          ${infoRow('Student ID', s.student_id_number || '–')}
          ${infoRow('Parent / Guardian', s.parent_guardian_name || '–')}
          ${infoRow('Guardian Email', s.parent_guardian_email || '–')}
          ${infoRow('Guardian Phone', s.parent_guardian_phone || '–')}
          ${infoRow('Emergency Contact', s.emergency_contact || '–')}
          ${infoRow('Emergency Phone', s.emergency_phone || '–')}
          ${s.notes ? `<div class="info-note mt-2"><strong>Notes:</strong><br/>${esc(s.notes)}</div>` : ''}
        </div>
      </div>

      <!-- ADHD Profile -->
      <div class="card">
        <div class="card-header"><h3>ADHD Profile</h3></div>
        <div class="card-body">
          ${ap ? `
            ${infoRow('Presentation', ADHD_LABELS[ap.adhd_type] || ap.adhd_type)}
            ${infoRow('Severity', cap(ap.severity))}
            ${infoRow('Diagnosis Date', ap.diagnosis_date || '–')}
            ${infoRow('Diagnosed By', ap.diagnosing_professional || '–')}
            ${infoRow('IEP in Place', ap.iep_in_place ? '✅ Yes' : '❌ No')}
            ${infoRow('504 Plan', ap.section_504_in_place ? '✅ Yes' : '❌ No')}
            <hr/>
            <div class="rating-bars">
              ${ratingBar('Inattention', ap.inattention_rating)}
              ${ratingBar('Hyperactivity', ap.hyperactivity_rating)}
              ${ratingBar('Impulsivity', ap.impulsivity_rating)}
            </div>
            <hr/>
            <h4>Specific Challenges</h4>
            <div class="challenge-tags">
              ${challengeTag('Reading', ap.has_reading_difficulty)}
              ${challengeTag('Writing', ap.has_writing_difficulty)}
              ${challengeTag('Math', ap.has_math_difficulty)}
              ${challengeTag('Focus', ap.has_focus_difficulty)}
              ${challengeTag('Organization', ap.has_organization_difficulty)}
              ${challengeTag('Time Management', ap.has_time_management_difficulty)}
              ${challengeTag('Working Memory', ap.has_working_memory_issues)}
              ${challengeTag('Emotional Regulation', ap.has_emotional_regulation_issues)}
            </div>
            ${ap.additional_notes ? `<p class="mt-2 muted"><em>${esc(ap.additional_notes)}</em></p>` : ''}
          ` : '<p class="muted">No ADHD profile recorded yet.</p>'}
        </div>
      </div>

      <!-- Comorbid Conditions -->
      <div class="card">
        <div class="card-header"><h3>Comorbid Conditions</h3></div>
        <div class="card-body">
          ${d.comorbid_conditions.length ? d.comorbid_conditions.map(c => `
            <div class="list-item">
              <strong>${esc(c.condition_name)}</strong>
              ${c.severity ? `<span class="badge badge-warning">${cap(c.severity)}</span>` : ''}
              <div class="sub-text">${cap(c.condition_category?.replace('_',' ') || '')}${c.diagnosed_by ? ' · Diagnosed by: ' + esc(c.diagnosed_by) : ''}</div>
              ${c.notes ? `<p class="muted mt-1">${esc(c.notes)}</p>` : ''}
            </div>`).join('') : '<p class="muted">No comorbid conditions recorded.</p>'}
        </div>
      </div>

      <!-- Medications -->
      <div class="card">
        <div class="card-header"><h3>Medications</h3></div>
        <div class="card-body">
          ${d.medications.length ? d.medications.map(m => `
            <div class="list-item">
              <strong>${esc(m.medication_name)}</strong>
              ${m.is_current ? '<span class="badge badge-success">Current</span>' : '<span class="badge">Historical</span>'}
              ${m.dosage ? `<span class="muted">${esc(m.dosage)}</span>` : ''}
              <div class="sub-text">${esc(m.frequency) || ''}${m.prescribing_doctor ? ' · Dr. ' + esc(m.prescribing_doctor) : ''}</div>
              ${m.side_effects_notes ? `<p class="muted mt-1">Side effects: ${esc(m.side_effects_notes)}</p>` : ''}
            </div>`).join('') : '<p class="muted">No medications recorded.</p>'}
        </div>
      </div>

      <!-- Accommodations -->
      <div class="card card-wide">
        <div class="card-header"><h3>Accommodations &amp; Strategies</h3></div>
        <div class="card-body">
          ${d.accommodations.length ? (() => {
            const grouped = {};
            d.accommodations.forEach(a => { (grouped[a.category] = grouped[a.category] || []).push(a); });
            return Object.entries(grouped).map(([cat, items]) => `
              <div class="accom-group">
                <h4 class="accom-group-title">${ACCOM_LABELS[cat] || cap(cat)}</h4>
                ${items.map(a => `
                  <div class="accom-item">
                    <strong>${esc(a.title)}</strong>
                    ${a.description ? `<p class="muted">${esc(a.description)}</p>` : ''}
                  </div>`).join('')}
              </div>`).join('');
          })() : '<p class="muted">No accommodations recorded yet.</p>'}
        </div>
      </div>

      <!-- Teacher Notes -->
      <div class="card card-wide">
        <div class="card-header">
          <h3>Teacher Notes &amp; Observations</h3>
          <button class="btn btn-secondary btn-sm" onclick="document.getElementById('noteModal').classList.remove('hidden')">&#43; Add Note</button>
        </div>
        <div class="card-body" id="notesList">
          ${renderNotesList(d.recent_notes)}
        </div>
      </div>

      <!-- Documents -->
      <div class="card card-wide">
        <div class="card-header">
          <h3>Supporting Documents</h3>
          <a href="student-form.php?id=${s.id}" class="btn btn-secondary btn-sm">&#8593; Upload</a>
        </div>
        <div class="card-body">
          ${d.documents.length ? d.documents.map(doc => `
            <div class="doc-item" id="viewDoc-${doc.id}">
              <span class="doc-icon">&#128196;</span>
              <div class="doc-item-info">
                <strong>${esc(doc.title)}</strong>
                <span class="badge">${esc(doc.document_type?.replace(/_/g,' '))}</span>
                <div class="sub-text">${esc(doc.original_filename)} &middot; ${doc.file_size ? (doc.file_size/1024).toFixed(1) + ' KB' : ''} &middot; ${esc(doc.uploaded_at)}</div>
              </div>
              <a href="../api/upload/download.php?doc_id=${doc.id}" class="btn btn-outline btn-sm" target="_blank">View / Download</a>
              <button class="btn btn-danger btn-sm" onclick="deleteViewDoc(${doc.id})">Delete</button>
            </div>`).join('') : '<p class="muted">No documents uploaded yet.</p>'}
        </div>
      </div>

      <!-- SPED Plans -->
      <div class="card card-wide">
        <div class="card-header">
          <h3>&#128221; SPED Plans</h3>
          <a href="student-form.php?id=${s.id}" class="btn btn-secondary btn-sm">&#9998; Edit Plans</a>
        </div>
        <div class="card-body">
          ${renderPlansSection(plans)}
        </div>
      </div>

      <!-- Gamification Progress -->
      <div class="card card-wide" id="gamificationCard">
        <div class="card-header"><h3>&#127918; Gamification Progress</h3></div>
        <div class="card-body" id="gamificationBody">
          <p class="muted">Loading gamification data…</p>
        </div>
      </div>

      <!-- Assignment & Attempt History -->
      <div class="card card-wide" id="attemptHistoryCard">
        <div class="card-header"><h3>&#128203; Assignment &amp; Attempt History</h3></div>
        <div class="card-body" id="attemptHistoryBody">
          <p class="muted">Loading history…</p>
        </div>
      </div>

    </div><!-- /profile-grid -->
  `;

  // Load gamification data for this student
  loadStudentGamification(s.id);
  loadStudentAttemptHistory(s.id);
}

/* ─────────────────────────────────────────────────────────
   Document-sourced profile — pure file viewer (Blackboard-style)
   No data cards. The uploaded file IS the profile.
   ───────────────────────────────────────────────────────── */
let _docBlobUrl = null;

function renderDocumentProfile(d) {
  const s = d.student;
  document.title = `EduQuest – ${s.first_name} ${s.last_name}`;
  document.body.classList.add('dv-page-active');

  /* Tab strip — only shown when student has more than one source doc */
  const tabStrip = d.documents.length > 1
    ? `<div class="dv-tab-strip">${d.documents.map((doc, i) => `
        <button class="dv-tab${i === 0 ? ' active' : ''}" data-idx="${i}">
          ${docMimeIcon(doc.mime_type)}&nbsp;${esc(doc.title || doc.original_filename)}
        </button>`).join('')}</div>`
    : (d.documents.length === 1
        ? `<div class="dv-single-title">${docMimeIcon(d.documents[0].mime_type)}&ensp;${esc(d.documents[0].title || d.documents[0].original_filename)}</div>`
        : '');

  document.getElementById('profileContent').innerHTML = `
    <!-- ── Slim toolbar ── -->
    <div class="dv-toolbar">
      <div class="dv-toolbar-left">
        <a href="students.php" class="dv-back-link">&#8592;&nbsp;Students</a>
        <div class="dv-toolbar-student">
          <img src="${s.profile_photo ? '../uploads/photos/' + s.profile_photo : '../assets/img/default-avatar.php'}"
               class="dv-photo" alt="${esc(s.first_name)}" />
          <div>
            <span class="dv-student-name">${esc(s.first_name)} ${esc(s.last_name)}</span>
            <span class="dv-student-meta">${[s.grade_level, s.school_name].filter(Boolean).map(v => esc(v)).join(' · ')}</span>
          </div>
        </div>
      </div>
      <div class="dv-toolbar-right">
        <span class="badge badge-info">From Document</span>
        <button class="btn btn-secondary btn-sm"
                onclick="document.getElementById('noteModal').classList.remove('hidden')">&#43;&nbsp;Add Note</button>
        <a href="student-form.php?id=${s.id}" class="btn btn-primary btn-sm">&#9998;&nbsp;Edit Profile</a>
      </div>
    </div>

    <!-- ── Document viewer ── -->
    ${d.documents.length ? `
    <div class="dv-panel">
      ${tabStrip ? `<div class="dv-panel-bar">${tabStrip}</div>` : ''}
      <div id="docProfileViewer" class="dv-viewer">
        <div class="dv-loading">Loading document&hellip;</div>
      </div>
    </div>` : `
    <div class="dv-empty">
      <div class="dv-empty-icon">&#128196;</div>
      <p>No source document found for this profile.</p>
      <a href="student-form.php?id=${s.id}#step6" class="btn btn-secondary">&#8593;&nbsp;Upload Document</a>
    </div>`}
  `;

  /* Wire tab switching */
  document.querySelectorAll('.dv-tab').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.dv-tab').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      loadDocViewer(d.documents[parseInt(btn.dataset.idx, 10)]);
    });
  });

  if (d.documents.length) loadDocViewer(d.documents[0]);
}

async function loadDocViewer(doc) {
  const container = document.getElementById('docProfileViewer');
  if (!container) return;
  if (_docBlobUrl) { URL.revokeObjectURL(_docBlobUrl); _docBlobUrl = null; }

  const mime  = doc.mime_type || '';
  const fname = (doc.original_filename || '').toLowerCase();
  const isPdf    = mime === 'application/pdf';
  const isImage  = /^image\//.test(mime);
  const isDocx   = mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                   || fname.endsWith('.docx');
  const isDoc    = (mime === 'application/msword' || fname.endsWith('.doc')) && !isDocx;
  const isExcel  = /spreadsheet|excel/i.test(mime)
                   || fname.endsWith('.xlsx') || fname.endsWith('.xls');
  const isOffice = isDoc || isDocx || isExcel || /officedocument|powerpoint/i.test(mime)
                   || /\.(pptx?)$/.test(fname);
  const isLocal  = /^(localhost|127\.|0\.0\.0\.|\[::1\])/.test(window.location.hostname);

  container.innerHTML = '<div class="dv-loading">Loading document&hellip;</div>';

  try {
    /* ── PDF ── */
    if (isPdf) {
      const resp = await fetch(`../api/upload/download.php?doc_id=${doc.id}`, { headers: EQ.authFetchHeaders() });
      if (!resp.ok) throw new Error('Server returned ' + resp.status);
      _docBlobUrl = URL.createObjectURL(await resp.blob());
      container.innerHTML = `<iframe src="${_docBlobUrl}" class="dv-iframe" title="${esc(doc.title || doc.original_filename)}"></iframe>`;
      return;
    }

    /* ── Image ── */
    if (isImage) {
      const resp = await fetch(`../api/upload/download.php?doc_id=${doc.id}`, { headers: EQ.authFetchHeaders() });
      if (!resp.ok) throw new Error('Server returned ' + resp.status);
      _docBlobUrl = URL.createObjectURL(await resp.blob());
      container.innerHTML = `<img src="${_docBlobUrl}" class="dv-img" alt="${esc(doc.original_filename)}" />`;
      return;
    }

    /* ── DOCX → HTML (mammoth.js) ── */
    if (isDocx && typeof mammoth !== 'undefined') {
      const resp = await fetch(`../api/upload/download.php?doc_id=${doc.id}`, { headers: EQ.authFetchHeaders() });
      if (!resp.ok) throw new Error('Server returned ' + resp.status);
      const arrayBuf = await (await resp.blob()).arrayBuffer();
      const result = await mammoth.convertToHtml({ arrayBuffer: arrayBuf });
      container.innerHTML = `
        <div class="dv-html-wrap">
          <div class="dv-html-toolbar">
            <span>${docMimeIcon(mime)}&nbsp;${esc(doc.title || doc.original_filename)}</span>
            <a href="../api/upload/download.php?doc_id=${doc.id}" class="btn btn-outline btn-sm" target="_blank">&#8595;&nbsp;Download</a>
          </div>
          <div class="dv-html-content">${result.value}</div>
        </div>`;
      return;
    }

    /* ── Office docs on a PUBLIC server → Office Online Viewer ── */
    if (isOffice && !isLocal) {
      const tokenResp = await fetch(`../api/upload/preview-token.php?doc_id=${doc.id}`, { headers: EQ.authFetchHeaders() });
      if (!tokenResp.ok) throw new Error('Could not generate preview token');
      const tokenData = await tokenResp.json();
      const officeUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(tokenData.preview_url);
      container.innerHTML = `
        <div class="dv-html-wrap">
          <div class="dv-html-toolbar">
            <span>${docMimeIcon(mime)}&nbsp;${esc(doc.title || doc.original_filename)}</span>
            <a href="../api/upload/download.php?doc_id=${doc.id}" class="btn btn-outline btn-sm" target="_blank">&#8595;&nbsp;Download</a>
          </div>
          <iframe src="${officeUrl}" class="dv-iframe" title="${esc(doc.title || doc.original_filename)}" sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>
        </div>`;
      return;
    }

    /* ── Excel → SheetJS (works everywhere including localhost) ── */
    if (isExcel && typeof XLSX !== 'undefined') {
      const resp = await fetch(`../api/upload/download.php?doc_id=${doc.id}`, { headers: EQ.authFetchHeaders() });
      if (!resp.ok) throw new Error('Server returned ' + resp.status);
      const arrayBuf = await (await resp.blob()).arrayBuffer();
      const workbook = XLSX.read(arrayBuf, { type: 'array' });
      let tabsHtml = '';
      let sheetsHtml = '';
      workbook.SheetNames.forEach((name, i) => {
        const ws = workbook.Sheets[name];
        const html = XLSX.utils.sheet_to_html(ws, { editable: false });
        tabsHtml += `<button class="dv-sheet-tab${i === 0 ? ' active' : ''}" data-sheet="${i}">${esc(name)}</button>`;
        sheetsHtml += `<div class="dv-sheet-pane${i === 0 ? ' active' : ''}" data-sheet="${i}">${html}</div>`;
      });
      container.innerHTML = `
        <div class="dv-html-wrap">
          <div class="dv-html-toolbar">
            <span>${docMimeIcon(mime)}&nbsp;${esc(doc.title || doc.original_filename)}</span>
            <div class="dv-sheet-tabs">${tabsHtml}</div>
            <a href="../api/upload/download.php?doc_id=${doc.id}" class="btn btn-outline btn-sm" target="_blank">&#8595;&nbsp;Download</a>
          </div>
          <div class="dv-html-content dv-excel-content">${sheetsHtml}</div>
        </div>`;
      container.querySelectorAll('.dv-sheet-tab').forEach(btn => {
        btn.addEventListener('click', () => {
          container.querySelectorAll('.dv-sheet-tab').forEach(b => b.classList.remove('active'));
          container.querySelectorAll('.dv-sheet-pane').forEach(p => p.classList.remove('active'));
          btn.classList.add('active');
          container.querySelector(`.dv-sheet-pane[data-sheet="${btn.dataset.sheet}"]`).classList.add('active');
        });
      });
      return;
    }

    /* ── Fallback: download card ── */
    const hint = (isOffice && isLocal)
      ? 'This file type cannot be previewed locally. Deploy to a public server for Office Online preview.'
      : 'This file type cannot be previewed in the browser.';
    container.innerHTML = `
      <div class="dv-no-preview">
        <div class="dv-no-preview-icon">${docMimeIcon(mime)}</div>
        <p class="dv-no-preview-name">${esc(doc.title || doc.original_filename)}</p>
        <p class="dv-no-preview-meta">${esc(doc.original_filename)}&ensp;&middot;&ensp;${doc.file_size ? (doc.file_size / 1024).toFixed(1) + ' KB' : ''}</p>
        <p class="dv-no-preview-hint">${hint}</p>
        <a href="../api/upload/download.php?doc_id=${doc.id}" class="btn btn-primary" target="_blank">&#8595;&nbsp;Download to View</a>
      </div>`;

  } catch (err) {
    container.innerHTML = `
      <div class="dv-no-preview">
        <p class="dv-no-preview-hint">Could not load preview: ${esc(err.message)}</p>
        <a href="../api/upload/download.php?doc_id=${doc.id}" class="btn btn-outline" target="_blank">&#8595;&nbsp;Download instead</a>
      </div>`;
  }
}

function docMimeIcon(mimeType) {
  if (mimeType === 'application/pdf')        return '&#128209;';
  if (/^image\//.test(mimeType))            return '&#128247;';
  if (/word|msword/.test(mimeType))         return '&#128196;';
  if (/excel|spreadsheet/.test(mimeType))  return '&#128202;';
  return '&#128196;';
}

function renderNotesList(notes) {
  if (!notes.length) return '<p class="muted" id="noNotesMsg">No notes yet. Add your first observation above.</p>';
  return notes.map(n => `
    <div class="note-item">
      <div class="note-meta">
        <strong>${esc(n.note_date)}</strong>
        <span class="badge">${cap(n.note_type)}</span>
        ${n.subject_area ? `<span class="muted">${esc(n.subject_area)}</span>` : ''}
        <span class="muted ml-auto">by ${esc(n.teacher_name)}</span>
      </div>
      <p>${esc(n.content)}</p>
    </div>`).join('');
}

async function deleteViewDoc(docId) {
  if (!confirm('Are you sure you want to delete this document?')) return;
  try {
    const res = await fetch(`../api/upload/delete-document.php?doc_id=${docId}`, {
      method: 'DELETE', headers: EQ.authHeaders(),
    });
    const data = await res.json();
    if (data.success) {
      const row = document.getElementById(`viewDoc-${docId}`);
      if (row) row.remove();
    } else {
      alert(data.message || 'Failed to delete document.');
    }
  } catch { alert('Failed to delete document.'); }
}

async function saveNote() {
  const content = document.getElementById('noteContent').value.trim();
  if (!content) { alert('Please enter a note.'); return; }

  const payload = {
    note_date:    document.getElementById('noteDate').value,
    note_type:    document.getElementById('noteType').value,
    subject_area: document.getElementById('noteSubject').value.trim(),
    content,
    is_private:   document.getElementById('notePrivate').checked,
  };

  try {
    const res  = await fetch(`../api/students/notes.php?student_id=${studentId}`, {
      method: 'POST', headers: EQ.authHeaders(), body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.success) {
      document.getElementById('noteModal').classList.add('hidden');
      document.getElementById('noteContent').value = '';
      document.getElementById('noteSubject').value = '';
      document.getElementById('notePrivate').checked = false;
      // Reload notes section
      loadProfile();
    } else {
      alert(data.message);
    }
  } catch { alert('Failed to save note.'); }
}

function infoRow(label, value) {
  return `<div class="info-row"><span class="info-label">${label}</span><span class="info-value">${esc(String(value))}</span></div>`;
}
function ratingBar(label, value) {
  const pct = value ? Math.round((value / 5) * 100) : 0;
  return `<div class="rating-row"><span>${label}</span><div class="rating-bar-bg"><div class="rating-bar-fill" style="width:${pct}%"></div></div><span>${value || '–'}/5</span></div>`;
}
function challengeTag(label, active) {
  return `<span class="challenge-tag ${active ? 'challenge-active' : 'challenge-inactive'}">${label}</span>`;
}
function showMainError(msg) { document.getElementById('profileContent').innerHTML = `<div class="alert alert-error">${esc(msg)}</div>`; }
function esc(str) { const d = document.createElement('div'); d.textContent = str || ''; return d.innerHTML; }
function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

/* ─── SPED Plans renderer ─────────────────────────────────────────────── */
function renderPlansSection(plans) {
  if (!plans) return '<p class="muted">No plan data available.</p>';
  const { iep, itp, profile } = plans;
  const hasIep = iep && Object.values(iep).some(v => v);
  const hasItp = itp && Object.values(itp).some(v => v);
  const hasSip = profile && Object.values(profile).some(v => v);
  if (!hasIep && !hasItp && !hasSip) return '<p class="muted">No SPED plans entered yet.</p>';

  const row = (label, val) => val
    ? `<div class="plan-view-row"><span class="plan-view-label">${esc(label)}</span><span class="plan-view-val">${esc(val)}</span></div>` : '';
  const sec = (heading, html) => html.trim()
    ? `<div class="plan-view-section"><h5 class="plan-section-heading">${heading}</h5>${html}</div>` : '';

  let html = '<div class="plan-view-tabs">';

  /* ── IEP ── */
  if (hasIep && iep) {
    html += `<details class="plan-view-group" open>
      <summary><strong>IEP – Individualized Education Program</strong></summary>
      ${sec('IEP Information',
        row('Effective Date', iep.effective_date) +
        row('Review Date', iep.review_date) +
        row('Meeting Date', iep.meeting_date) +
        row('Disability Classification', iep.disability_classification) +
        row('SPED Category', iep.sped_category) +
        row('IEP Team', iep.iep_team))}
      ${sec('Present Level of Educational Performance (PLEP)',
        row('Academic Performance', iep.plep_academic) +
        row('Functional Performance', iep.plep_functional) +
        row('Social/Emotional Performance', iep.plep_social))}
      ${sec('Annual Goals & Objectives',
        row('Annual Goals', iep.annual_goals) +
        row('Short-Term Objectives', iep.short_term_objectives))}
      ${sec('Services',
        row('Special Education Services', iep.sped_services) +
        row('Related Services', iep.related_services))}
      ${sec('Accommodations & Placement',
        row('Accommodations', iep.accommodations_notes) +
        row('Modifications', iep.modifications_notes) +
        row('Regular Ed Participation %', iep.regular_ed_percentage != null ? iep.regular_ed_percentage + '%' : null) +
        row('Assessment Accommodations', iep.assessment_accommodations))}
      ${sec('Transition & Notes',
        row('Transition Services', iep.transition_services) +
        row('Additional Notes', iep.additional_notes))}
    </details>`;
  }

  /* ── ITP ── */
  if (hasItp && itp) {
    html += `<details class="plan-view-group">
      <summary><strong>ITP – Individualized Transition Plan</strong></summary>
      ${sec('ITP Information',
        row('Effective Date', itp.effective_date) +
        row('Anticipated Graduation Date', itp.graduation_date) +
        row('Disability Category', itp.disability_category))}
      ${sec('Present Level of Performance',
        row('Career Interests', itp.career_interests) +
        row('Assessed Strengths', itp.assessed_strengths) +
        row('Work Experiences', itp.work_experiences) +
        row('Community Experiences', itp.community_experiences) +
        row('Daily Living Skills', itp.daily_living_skills))}
      ${sec('Post-Secondary Goals',
        row('Education / Training', itp.goal_postsecondary_education) +
        row('Employment', itp.goal_employment) +
        row('Independent Living', itp.goal_independent_living) +
        row('Community Participation', itp.goal_community))}
      ${sec('Transition Services',
        row('Instruction', itp.services_instruction) +
        row('Community Experiences', itp.services_community) +
        row('Employment / Post-School', itp.services_employment) +
        row('Adult Living / Daily Living', itp.services_adult_living))}
      ${sec('Course of Study & Linkages',
        row('Course of Study', itp.course_of_study) +
        row('Agency Linkages', itp.agency_linkages))}
      ${sec('Annual Transition Goals & Notes',
        row('Annual Transition Goals', itp.annual_goals_transition) +
        row('Additional Notes', itp.additional_notes))}
    </details>`;
  }

  /* ── Individual Profile ── */
  if (hasSip && profile) {
    html += `<details class="plan-view-group">
      <summary><strong>Individual Student Profile</strong></summary>
      ${sec('Student Classification',
        row('Disability Classification', profile.disability_classification) +
        row('SPED Category', profile.sped_category) +
        row('Years in SPED', profile.years_in_sped) +
        row('Preferred Name', profile.preferred_name) +
        row('Pronouns', profile.preferred_pronouns) +
        row('Primary Language', profile.primary_language))}
      ${sec('Strengths & Challenges',
        row('Academic Strengths', profile.academic_strengths) +
        row('Academic Challenges', profile.academic_challenges) +
        row('Behavioral Strengths', profile.behavioral_strengths) +
        row('Behavioral Challenges', profile.behavioral_challenges) +
        row('Social Strengths', profile.social_strengths) +
        row('Social Challenges', profile.social_challenges))}
      ${sec('Learning Profile',
        row('Learning Style', profile.learning_style) +
        row('Attention Span', profile.attention_span) +
        row('Learning Style Notes', profile.learning_style_notes))}
      ${sec('Communication & Behavior',
        row('Communication Profile', profile.communication_profile) +
        row('Motivators', profile.motivators) +
        row('Triggers', profile.triggers) +
        row('Calming Strategies', profile.calming_strategies) +
        row('Reinforcement Strategies', profile.reinforcement_strategies))}
      ${sec('Support Network & Observations',
        row('Family Support Level', profile.family_support_level) +
        row('Outside Services', profile.outside_services) +
        row('Student Voice', profile.student_voice) +
        row('Teacher Observations', profile.teacher_observations))}
    </details>`;
  }

  html += '</div>';
  return html;
}

/* ─────────────────────────────────────────────────────────
   Gamification panel on teacher student-view
   ───────────────────────────────────────────────────────── */
async function loadStudentGamification(sid) {
  const body = document.getElementById('gamificationBody');
  if (!body) return;

  try {
    const res = await fetch(`../api/gamification/student-progress.php?studentId=${sid}`, {
      headers: EQ.authHeaders(),
    });
    const json = await res.json();

    if (!json.success || !json.data) {
      body.innerHTML = '<p class="muted">No gamification data available for this student.</p>';
      return;
    }

    const g = json.data;
    const teamColors = { fire: '#ef4444', water: '#3b82f6', grass: '#22c55e' };
    const teamEmojis = { fire: '🔥', water: '💧', grass: '🌿' };

    // Use PetSprites if available, else fall back to emojis
    const hasPetSprites = typeof PetSprites !== 'undefined';
    const companionVisual = hasPetSprites
        ? `<div style="width:56px;height:70px;margin:0 auto;">${PetSprites.get(g.team || 'fire', g.eggStage || 1)}</div>`
        : `<div style="font-size:2rem;">${({ 1: '🥚', 2: '🐣', 3: '🐥', 4: '🦅', 5: '🐉' })[g.eggStage] || '🥚'}</div>`;
    const companionName = hasPetSprites
        ? PetSprites.stageName(g.team || 'fire', g.eggStage || 1)
        : ({ 1: 'Egg', 2: 'Cracking Egg', 3: 'Hatchling', 4: 'Young Creature', 5: 'Mythical Guardian' })[g.eggStage] || 'Egg';

    body.innerHTML = `
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem;">
        <div style="text-align:center;padding:1rem;background:#f8fafc;border-radius:12px;">
          <div style="font-size:2rem;">⚡</div>
          <div style="font-size:1.4rem;font-weight:700;">${(g.totalXp || 0).toLocaleString()}</div>
          <div style="font-size:0.8rem;color:#64748b;">Total XP</div>
        </div>
        <div style="text-align:center;padding:1rem;background:#f8fafc;border-radius:12px;">
          <div style="font-size:2rem;">🏅</div>
          <div style="font-size:1.4rem;font-weight:700;">Level ${g.level || 1}</div>
          <div style="font-size:0.8rem;color:#64748b;">Current Level</div>
        </div>
        <div style="text-align:center;padding:1rem;background:#f8fafc;border-radius:12px;">
          ${companionVisual}
          <div style="font-size:1.4rem;font-weight:700;">${companionName}</div>
          <div style="font-size:0.8rem;color:#64748b;">Companion</div>
        </div>
        <div style="text-align:center;padding:1rem;background:#f8fafc;border-radius:12px;">
          <div style="font-size:2rem;">🔥</div>
          <div style="font-size:1.4rem;font-weight:700;">${g.streakDays || 0} days</div>
          <div style="font-size:0.8rem;color:#64748b;">Current Streak</div>
        </div>
        <div style="text-align:center;padding:1rem;background:#f8fafc;border-radius:12px;" id="teamAssignCard">
          <div style="font-size:2rem;" id="teamCardEmoji">${g.team ? teamEmojis[g.team] : '❓'}</div>
          <div style="font-size:1.4rem;font-weight:700;" id="teamCardLabel">${g.team ? g.team.charAt(0).toUpperCase() + g.team.slice(1) : 'None'}</div>
          <div style="font-size:0.8rem;color:#64748b;margin-bottom:0.4rem;">Team</div>
          <select id="teamAssignSelect" style="font-size:0.8rem;padding:0.25rem 0.4rem;border-radius:6px;border:1px solid #cbd5e1;cursor:pointer;">
            <option value="fire" ${g.team === 'fire' ? 'selected' : ''}>🔥 Fire</option>
            <option value="water" ${g.team === 'water' ? 'selected' : ''}>💧 Water</option>
            <option value="grass" ${g.team === 'grass' ? 'selected' : ''}>🌿 Grass</option>
          </select>
        </div>
      </div>

      ${g.recentActivity && g.recentActivity.length ? (() => {
        const typeColors = {
          quiz:       { bg: '#eff6ff', border: '#bfdbfe', text: '#1d4ed8', icon: '📝' },
          assignment: { bg: '#f0fdf4', border: '#bbf7d0', text: '#15803d', icon: '📋' },
          activity:   { bg: '#fdf4ff', border: '#e9d5ff', text: '#7e22ce', icon: '🎮' },
          game:       { bg: '#fdf4ff', border: '#e9d5ff', text: '#7e22ce', icon: '🎮' },
          module:     { bg: '#fff7ed', border: '#fed7aa', text: '#c2410c', icon: '📚' },
          lesson:     { bg: '#fff7ed', border: '#fed7aa', text: '#c2410c', icon: '📚' },
          achievement:{ bg: '#fefce8', border: '#fde68a', text: '#b45309', icon: '🏅' },
        };
        const PREVIEW = 5;
        const total   = g.recentActivity.length;

        // Group by date
        const groups = {};
        g.recentActivity.forEach(a => {
          const raw = a.created_at || a.completed_at || '';
          const key = raw ? new Date(raw).toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'}) : 'Unknown date';
          if (!groups[key]) groups[key] = [];
          groups[key].push(a);
        });

        let allCards = [];
        Object.entries(groups).forEach(([dateLabel, items]) => {
          allCards.push({ type: 'date', label: dateLabel });
          items.forEach(a => allCards.push({ type: 'item', a }));
        });

        const renderCard = (a, hidden) => {
          const typeKey  = (a.activity_type || a.source_type || '').toLowerCase();
          const c        = typeColors[typeKey] || { bg: '#f8fafc', border: '#e2e8f0', text: '#475569', icon: '⚡' };
          const typeLabel= (a.activity_type || a.source_type || 'Activity').replace(/_/g,' ');
          const score    = a.score != null ? a.score + (a.max_score ? '/' + a.max_score : '%') : null;
          return `
          <div class="ra-card${hidden ? ' ra-hidden' : ''}" style="display:${hidden ? 'none' : 'flex'};align-items:center;gap:1rem;
               padding:0.85rem 1rem;background:#fff;border:1px solid #e8edf3;border-radius:10px;
               box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <div style="width:38px;height:38px;border-radius:9px;background:${c.bg};border:1px solid ${c.border};
                        display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex-shrink:0;">
              ${c.icon}
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-weight:600;font-size:0.875rem;color:#1e293b;
                          white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                ${esc(a.title)}
              </div>
              <span style="font-size:0.73rem;padding:0.12rem 0.5rem;border-radius:20px;
                           background:${c.bg};color:${c.text};border:1px solid ${c.border};
                           text-transform:capitalize;font-weight:500;margin-top:0.2rem;display:inline-block;">
                ${typeLabel}
              </span>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.2rem;flex-shrink:0;">
              ${score ? `<span style="font-size:0.85rem;font-weight:700;color:#1e293b;">${score}</span>` : ''}
              <span style="font-size:0.75rem;font-weight:600;color:#6366f1;
                           background:#eef2ff;padding:0.12rem 0.5rem;border-radius:20px;">
                +${a.xp_amount || 0} XP
              </span>
            </div>
          </div>`;
        };

        let itemCount = 0;
        const body = allCards.map(entry => {
          if (entry.type === 'date') {
            const hidden = itemCount >= PREVIEW;
            return `<div class="ra-date-sep${hidden ? ' ra-hidden' : ''}" style="display:${hidden ? 'none' : 'flex'};
                    align-items:center;gap:0.6rem;margin:0.75rem 0 0.35rem;">
              <span style="font-size:0.73rem;font-weight:600;color:#64748b;white-space:nowrap;">${entry.label}</span>
              <span style="flex:1;height:1px;background:#e2e8f0;"></span>
            </div>`;
          } else {
            const hidden = itemCount >= PREVIEW;
            itemCount++;
            return renderCard(entry.a, hidden);
          }
        }).join('');

        const hiddenCount = Math.max(0, total - PREVIEW);
        const showMoreBtn = hiddenCount > 0 ? `
          <button id="raToggleBtn" onclick="(function(){
            const hidden = document.querySelectorAll('.ra-hidden');
            const btn    = document.getElementById('raToggleBtn');
            const expanded = btn.dataset.expanded === '1';
            hidden.forEach(el => el.style.display = expanded ? 'none' : (el.classList.contains('ra-card') ? 'flex' : 'flex'));
            btn.dataset.expanded = expanded ? '0' : '1';
            btn.innerHTML = expanded
              ? '&#9660; Show ${hiddenCount} more activit${hiddenCount === 1 ? 'y' : 'ies'}'
              : '&#9650; Show less';
          })()" data-expanded="0"
            style="margin-top:0.65rem;width:100%;padding:0.55rem;border:1px dashed #c7d2fe;border-radius:8px;
                   background:#f8f7ff;color:#6366f1;font-size:0.82rem;font-weight:600;cursor:pointer;">
            &#9660; Show ${hiddenCount} more activit${hiddenCount === 1 ? 'y' : 'ies'}
          </button>` : '';

        return `
        <div style="margin-top:1.5rem;">
          <button id="raCollapseBtn" onclick="(function(){
            const list = document.getElementById('raList');
            const btn  = document.getElementById('raCollapseBtn');
            const open = btn.dataset.open === '1';
            list.style.display = open ? 'none' : 'block';
            btn.dataset.open   = open ? '0' : '1';
            document.getElementById('raChevron').style.transform = open ? 'rotate(-90deg)' : 'rotate(0deg)';
          })()" data-open="1"
            style="display:flex;align-items:center;gap:0.5rem;background:none;border:none;
                   padding:0;cursor:pointer;margin-bottom:0.75rem;width:100%;">
            <span style="width:4px;height:18px;background:#6366f1;border-radius:2px;flex-shrink:0;"></span>
            <span style="font-size:1rem;font-weight:600;color:#1e293b;flex:1;text-align:left;">
              Recent Activity <span style="font-size:0.8rem;color:#64748b;font-weight:400;">(${total})</span>
            </span>
            <span id="raChevron" style="color:#94a3b8;font-size:0.8rem;transition:transform 0.2s;">&#9660;</span>
          </button>
          <div id="raList">
            <div style="display:flex;flex-direction:column;gap:0.5rem;">${body}</div>
            ${showMoreBtn}
          </div>
        </div>`;
      })() : ''}
    `;

    // Bind team change dropdown
    const teamSelect = document.getElementById('teamAssignSelect');
    if (teamSelect) {
      teamSelect.addEventListener('change', async function() {
        const newTeam = this.value;
        try {
          const r = await fetch('../api/gamification/assign-team.php', {
            method: 'POST',
            headers: EQ.authHeaders(),
            body: JSON.stringify({ student_id: sid, team: newTeam }),
          });
          const j = await r.json();
          if (j.success) {
            document.getElementById('teamCardEmoji').textContent = teamEmojis[newTeam];
            document.getElementById('teamCardLabel').textContent = newTeam.charAt(0).toUpperCase() + newTeam.slice(1);
          } else {
            alert(j.message || 'Failed to change team.');
            this.value = g.team || 'fire';
          }
        } catch (_) {
          alert('Network error.');
          this.value = g.team || 'fire';
        }
      });
    }
  } catch (e) {
    body.innerHTML = '<p class="muted">Failed to load gamification data.</p>';
  }
}

/* ─────────────────────────────────────────────────────────
   Assignment & Attempt History panel on teacher student-view
   ───────────────────────────────────────────────────────── */
async function loadStudentAttemptHistory(sid) {
  const body = document.getElementById('attemptHistoryBody');
  if (!body) return;

  try {
    const res = await fetch(`../api/attempt/student_history.php?student_id=${sid}`, {
      headers: EQ.authHeaders(),
    });
    const json = await res.json();

    if (!json.success || !json.data) {
      body.innerHTML = '<p class="muted">No assignment history found.</p>';
      return;
    }

    const { quiz_assignments, game_assignments } = json.data;

    function renderAttemptRows(attempts) {
      if (!attempts || attempts.length === 0) return '<p class="muted" style="margin:4px 0;">No attempts yet.</p>';
      return `<table style="width:100%;font-size:0.82rem;border-collapse:collapse;">
        <thead><tr style="border-bottom:1px solid #e5e7eb;">
          <th style="text-align:left;padding:4px 6px;">#</th>
          <th style="text-align:left;padding:4px 6px;">Score</th>
          <th style="text-align:left;padding:4px 6px;">%</th>
          <th style="text-align:left;padding:4px 6px;">Status</th>
          <th style="text-align:left;padding:4px 6px;">Date</th>
        </tr></thead>
        <tbody>${attempts.map((a, i) => `<tr style="border-bottom:1px solid #f3f4f6;">
          <td style="padding:4px 6px;">${i + 1}</td>
          <td style="padding:4px 6px;">${a.score ?? '—'}</td>
          <td style="padding:4px 6px;">${a.percentage != null ? a.percentage + '%' : '—'}</td>
          <td style="padding:4px 6px;">${a.is_abandoned ? '❌ Abandoned' : a.completed_at ? '✅ Done' : '⏳ In Progress'}</td>
          <td style="padding:4px 6px;">${a.started_at ? a.started_at.slice(0, 10) : '—'}</td>
        </tr>`).join('')}</tbody>
      </table>`;
    }

    function resetBtn(type, assignmentId) {
      return `<button class="btn btn-outline btn-sm ah-reset-btn" data-type="${esc(type)}" data-id="${assignmentId}" style="font-size:0.78rem;padding:3px 10px;">🔄 Reset Attempts</button>`;
    }

    let html = '';

    if (quiz_assignments && quiz_assignments.length > 0) {
      html += `<h4 style="margin:0 0 10px;color:#374151;">📝 Quiz Assignments</h4>`;
      quiz_assignments.forEach(qa => {
        const dueLabel = qa.due_date ? ` · Due ${qa.due_date}` : '';
        const attLabel = qa.max_attempts > 0 ? ` · ${qa.attempts_used}/${qa.max_attempts} attempts` : ` · ${qa.attempts_used} attempts`;
        html += `<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-bottom:10px;">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
            <strong>${esc(qa.quiz_title)}</strong>
            <span style="color:#6b7280;font-size:0.82rem;">${attLabel}${dueLabel}</span>
            ${resetBtn('quiz', qa.assignment_id)}
          </div>
          ${renderAttemptRows(qa.attempts)}
        </div>`;
      });
    }

    if (game_assignments && game_assignments.length > 0) {
      html += `<h4 style="margin:12px 0 10px;color:#374151;">🎮 Game Assignments</h4>`;
      game_assignments.forEach(ga => {
        const dueLabel = ga.due_date ? ` · Due ${ga.due_date}` : '';
        const attLabel = ga.max_attempts > 0 ? ` · ${ga.attempts_used}/${ga.max_attempts} attempts` : ` · ${ga.attempts_used} attempts`;
        html += `<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-bottom:10px;">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
            <strong>${esc(ga.game_name)}</strong>
            <span style="color:#6b7280;font-size:0.82rem;">${attLabel}${dueLabel}</span>
            ${resetBtn('game', ga.assignment_id)}
          </div>
          ${renderAttemptRows(ga.attempts)}
        </div>`;
      });
    }

    if (!html) {
      html = '<p class="muted">No assignments found for this student.</p>';
    }

    body.innerHTML = html;

    // Wire reset buttons
    body.querySelectorAll('.ah-reset-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Reset all attempts for this assignment? This cannot be undone.')) return;
        const type = btn.dataset.type;
        const assignmentId = parseInt(btn.dataset.id, 10);
        try {
          const url = type === 'quiz' ? '../api/courses/quizzes.php' : '../api/attempt/game_reset.php';
          const body2 = type === 'quiz'
            ? JSON.stringify({ action: 'reset_attempts', assignment_id: assignmentId })
            : JSON.stringify({ assignment_id: assignmentId });
          const r = await fetch(url, { method: 'POST', headers: EQ.authHeaders(), body: body2 });
          const j = await r.json();
          if (j.success) {
            loadStudentAttemptHistory(sid);
          } else {
            alert(j.message || 'Reset failed.');
          }
        } catch (_) { alert('Network error.'); }
      });
    });

  } catch (e) {
    const body2 = document.getElementById('attemptHistoryBody');
    if (body2) body2.innerHTML = '<p class="muted">Failed to load assignment history.</p>';
  }
}
