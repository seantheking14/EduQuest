/**
 * student-form.js
 * 4-step multi-step form for creating / editing a full student profile.
 */
'use strict';

// ── Preset accommodations teachers can quickly add ──
const ACCOMMODATION_PRESETS = [
  { category: 'assessment',    title: 'Extended time on tests (1.5×)' },
  { category: 'assessment',    title: 'Tests administered in a quiet room' },
  { category: 'assessment',    title: 'Allow oral responses instead of written' },
  { category: 'assessment',    title: 'Simplified test instructions' },
  { category: 'assessment',    title: 'Calculator allowed' },
  { category: 'instructional', title: 'Break tasks into smaller steps' },
  { category: 'instructional', title: 'Provide written and verbal instructions' },
  { category: 'instructional', title: 'Use of graphic organizers' },
  { category: 'instructional', title: 'Frequent check-ins during work time' },
  { category: 'instructional', title: 'Peer tutoring / buddy system' },
  { category: 'instructional', title: 'Visual schedule / task board' },
  { category: 'environmental', title: 'Preferential seating near teacher' },
  { category: 'environmental', title: 'Reduce visual distractions on desk' },
  { category: 'environmental', title: 'Allow fidget tools' },
  { category: 'environmental', title: 'Quiet workspace / study carrel' },
  { category: 'environmental', title: 'Noise-cancelling headphones' },
  { category: 'behavioral',    title: 'Positive reinforcement system' },
  { category: 'behavioral',    title: 'Scheduled movement breaks' },
  { category: 'behavioral',    title: 'Behavior intervention plan (BIP)' },
  { category: 'behavioral',    title: 'Cool-down / calm corner access' },
  { category: 'behavioral',    title: 'Daily check-in/check-out with counselor' },
  { category: 'technology',    title: 'Use of text-to-speech software' },
  { category: 'technology',    title: 'Use of speech-to-text for writing tasks' },
  { category: 'technology',    title: 'Tablet or laptop for written work' },
  { category: 'technology',    title: 'Audio recordings of lessons' },
];

let currentStep     = 1;
const TOTAL_STEPS   = 4;
let editStudentId   = null;
let pendingDocFiles = [];
let pendingPlanFiles = { iep: null, itp: null, sip: null };

// ── Init ──
document.addEventListener('DOMContentLoaded', () => {
  renderPresetBadges();
  initCollapsibleToggles();
  initRangeSliders();
  initPlanCardUploads();
  setupDocDropZone();

  // Check if editing an existing student
  const urlParams = new URLSearchParams(window.location.search);
  editStudentId = urlParams.get('id') ? parseInt(urlParams.get('id'), 10) : null;
  if (editStudentId) {
    document.getElementById('formTitle').textContent = 'Edit Student Profile';
    document.getElementById('studentId').value = editStudentId;
    document.getElementById('manualSection').classList.remove('hidden');
    loadStudentData(editStudentId);
  } else {
    document.getElementById('methodChooser').classList.remove('hidden');

    document.getElementById('chooseManual').addEventListener('click', () => {
      document.getElementById('methodChooser').classList.add('hidden');
      document.getElementById('manualSection').classList.remove('hidden');
      document.getElementById('backToChooserManual').classList.remove('hidden');
      document.getElementById('formTitle').textContent = 'Add New Student Profile';
    });

    document.getElementById('chooseImport').addEventListener('click', () => {
      document.getElementById('methodChooser').classList.add('hidden');
      document.getElementById('importSection').classList.remove('hidden');
      document.getElementById('formTitle').textContent = 'Import Student Profiles';
    });

    document.getElementById('backToChooserManualBtn').addEventListener('click', () => {
      document.getElementById('manualSection').classList.add('hidden');
      document.getElementById('backToChooserManual').classList.add('hidden');
      document.getElementById('methodChooser').classList.remove('hidden');
      document.getElementById('formTitle').textContent = 'Add New Student Profile';
    });

    document.getElementById('backToChooserImportBtn').addEventListener('click', () => {
      document.getElementById('importSection').classList.add('hidden');
      document.getElementById('methodChooser').classList.remove('hidden');
      document.getElementById('formTitle').textContent = 'Add New Student Profile';
    });
  }

  // Step navigation
  document.getElementById('nextBtn').addEventListener('click', () => goToStep(currentStep + 1));
  document.getElementById('prevBtn').addEventListener('click', () => goToStep(currentStep - 1));

  // Clickable step indicators
  document.querySelectorAll('.sf-step[data-step]').forEach(el => {
    el.addEventListener('click', () => goToStep(parseInt(el.dataset.step, 10)));
  });

  // Dynamic list buttons
  document.getElementById('addConditionBtn').addEventListener('click', () => addConditionRow());
  document.getElementById('addMedBtn').addEventListener('click', () => addMedicationRow());
  document.getElementById('addAccomBtn').addEventListener('click', () => addAccommodationRow());

  // Photo preview
  document.getElementById('photoInput').addEventListener('change', previewPhoto);

  // Form submit
  document.getElementById('studentForm').addEventListener('submit', submitProfile);
});

// ═══════════════════════════════════════════════════════════
// STEP NAVIGATION (4 steps)
// ═══════════════════════════════════════════════════════════
function goToStep(step) {
  if (step < 1 || step > TOTAL_STEPS) return;

  // Validate required fields on step 1 before moving forward
  if (step > currentStep && currentStep === 1) {
    const fn = document.getElementById('firstName').value.trim();
    const ln = document.getElementById('lastName').value.trim();
    if (!fn || !ln) {
      showAlert('error', 'Please enter the student\'s first and last name.');
      return;
    }
  }

  document.getElementById(`step${currentStep}`).classList.add('hidden');
  document.getElementById(`step${currentStep}`).classList.remove('active');
  document.querySelector(`.sf-step[data-step="${currentStep}"]`).classList.remove('active');

  currentStep = step;

  document.getElementById(`step${currentStep}`).classList.remove('hidden');
  document.getElementById(`step${currentStep}`).classList.add('active');
  document.querySelector(`.sf-step[data-step="${currentStep}"]`).classList.add('active');

  document.getElementById('prevBtn').disabled = (currentStep === 1);
  document.getElementById('nextBtn').classList.toggle('hidden', currentStep === TOTAL_STEPS);
  document.getElementById('submitBtn').classList.toggle('hidden', currentStep !== TOTAL_STEPS);

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ═══════════════════════════════════════════════════════════
// COLLAPSIBLE SECTIONS
// ═══════════════════════════════════════════════════════════
function initCollapsibleToggles() {
  document.querySelectorAll('.sf-collapse-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.target;
      const body = document.getElementById(targetId);
      if (!body) return;
      const isOpen = body.classList.toggle('open');
      btn.classList.toggle('open', isOpen);
    });
  });
}

function openCollapsible(sectionId) {
  const body = document.getElementById(sectionId);
  if (!body || body.classList.contains('open')) return;
  body.classList.add('open');
  const btn = document.querySelector(`.sf-collapse-toggle[data-target="${sectionId}"]`);
  if (btn) btn.classList.add('open');
}

// ═══════════════════════════════════════════════════════════
// RANGE SLIDER LABELS
// ═══════════════════════════════════════════════════════════
function initRangeSliders() {
  ['inattentionRating', 'hyperactivityRating', 'impulsivityRating'].forEach(id => {
    const input = document.getElementById(id);
    if (!input) return;
    input.addEventListener('input', () => updateRatingLabel(input));
  });
}

function updateRatingLabel(input) {
  const labelId = input.id.replace('Rating', 'Label');
  const el = document.getElementById(labelId);
  if (el) el.textContent = input.value;
}

// ═══════════════════════════════════════════════════════════
// PRESET ACCOMMODATION BADGES (by category grids)
// ═══════════════════════════════════════════════════════════
function renderPresetBadges() {
  const gridMap = {
    assessment:       document.getElementById('presetAssessment'),
    instructional:    document.getElementById('presetInstructional'),
    environmental:    document.getElementById('presetEnvironmental'),
    behavioral:       document.getElementById('presetBehavioral'),
    technology:       document.getElementById('presetTechnology'),
  };

  ACCOMMODATION_PRESETS.forEach(p => {
    const container = gridMap[p.category];
    if (!container) return;
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'sf-preset-badge';
    btn.textContent = p.title;
    btn.addEventListener('click', () => {
      btn.classList.toggle('active');
      if (btn.classList.contains('active')) {
        addAccommodationRow({ title: p.title, category: p.category, isPreset: true });
        showAccomToast('Added: ' + p.title);
      } else {
        removeAccommodationByTitle(p.title);
        showAccomToast('Removed: ' + p.title, 'remove');
      }
      updateNoAccomMsg();
    });
    container.appendChild(btn);
  });
}

function removeAccommodationByTitle(title) {
  const rows = document.getElementById('accommodationsList').querySelectorAll('.sf-dyn-row');
  for (const row of rows) {
    const titleInput = row.querySelector('.ac-title');
    if (titleInput && titleInput.value === title) {
      row.remove();
      updateAccomCount();
      return;
    }
  }
}

function updateNoAccomMsg() {
  const msg = document.getElementById('noAccomMsg');
  const rows = document.getElementById('accommodationsList').querySelectorAll('.sf-dyn-row');
  if (msg) msg.style.display = rows.length ? 'none' : '';
  updateAccomCount();
}

function updateAccomCount() {
  const count = document.getElementById('accommodationsList').querySelectorAll('.sf-dyn-row').length;
  const badge = document.getElementById('accomCount');
  if (!badge) return;
  badge.textContent = count;
  badge.style.display = count ? '' : 'none';
}

let _accomToastTimer = null;
function showAccomToast(msg, type) {
  const el = document.getElementById('accomToast');
  if (!el) return;
  el.textContent = (type === 'remove' ? '✕  ' : '✓  ') + msg;
  el.className = 'sf-accom-toast' + (type === 'remove' ? ' sf-toast-remove' : '');
  clearTimeout(_accomToastTimer);
  requestAnimationFrame(() => el.classList.add('show'));
  _accomToastTimer = setTimeout(() => el.classList.remove('show'), 2200);
}

// ═══════════════════════════════════════════════════════════
// PLAN CARD UPLOADS (IEP / ITP / SIP clickable cards)
// ═══════════════════════════════════════════════════════════
function initPlanCardUploads() {
  [
    { cardId: 'planCardIep', inputId: 'iepFileInput', nameId: 'iepFileName', key: 'iep' },
    { cardId: 'planCardItp', inputId: 'itpFileInput', nameId: 'itpFileName', key: 'itp' },
    { cardId: 'planCardSip', inputId: 'sipFileInput', nameId: 'sipFileName', key: 'sip' },
  ].forEach(({ cardId, inputId, nameId, key }) => {
    const card  = document.getElementById(cardId);
    const input = document.getElementById(inputId);
    const nameEl = document.getElementById(nameId);
    if (!card || !input) return;

    card.addEventListener('click', e => {
      if (e.target !== input) input.click();
    });
    input.addEventListener('change', () => {
      const file = input.files[0];
      if (!file) return;
      pendingPlanFiles[key] = file;
      card.classList.add('has-file');
      nameEl.textContent = file.name;
    });
  });
}

// ═══════════════════════════════════════════════════════════
// PHOTO PREVIEW
// ═══════════════════════════════════════════════════════════
function previewPhoto(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => { document.getElementById('photoPreview').src = ev.target.result; };
  reader.readAsDataURL(file);
}

// ═══════════════════════════════════════════════════════════
// DYNAMIC ROWS: Comorbid Conditions
// ═══════════════════════════════════════════════════════════
function addConditionRow(data = {}) {
  const id  = Date.now();
  const div = document.createElement('div');
  div.className = 'sf-dyn-row';
  div.id = `cond_${id}`;
  div.innerHTML = `
    <div class="form-row">
      <div class="form-group" style="flex:2">
        <label>Condition Name *</label>
        <input type="text" class="cond-name" value="${esc(data.condition_name || '')}" placeholder="e.g. Anxiety Disorder, Dyslexia, ASD" />
      </div>
      <div class="form-group">
        <label>Category</label>
        <select class="cond-cat">${categoryOptions('condition', data.condition_category)}</select>
      </div>
      <div class="form-group">
        <label>Severity</label>
        <select class="cond-sev">
          <option value="">–</option>
          <option value="mild" ${data.severity==='mild'?'selected':''}>Mild</option>
          <option value="moderate" ${data.severity==='moderate'?'selected':''}>Moderate</option>
          <option value="severe" ${data.severity==='severe'?'selected':''}>Severe</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Diagnosed By</label><input type="text" class="cond-diag" value="${esc(data.diagnosed_by || '')}" placeholder="Professional name" /></div>
      <div class="form-group"><label>Diagnosis Date</label><input type="date" class="cond-date" value="${esc(data.diagnosis_date || '')}" /></div>
      <div class="form-group"><label>Current?</label>
        <select class="cond-current">
          <option value="1" ${data.is_current!==0?'selected':''}>Yes</option>
          <option value="0" ${data.is_current===0?'selected':''}>No</option>
        </select>
      </div>
    </div>
    <div class="form-group"><label>Notes</label><input type="text" class="cond-notes" value="${esc(data.notes || '')}" placeholder="Optional" /></div>
    <button type="button" class="btn btn-danger btn-xs remove-row-btn" onclick="removeRow('cond_${id}')">Remove</button>`;
  document.getElementById('conditionsList').appendChild(div);
}

// ═══════════════════════════════════════════════════════════
// DYNAMIC ROWS: Medications
// ═══════════════════════════════════════════════════════════
function addMedicationRow(data = {}) {
  const id  = Date.now();
  const div = document.createElement('div');
  div.className = 'sf-dyn-row';
  div.id = `med_${id}`;
  div.innerHTML = `
    <div class="form-row">
      <div class="form-group" style="flex:2"><label>Medication Name *</label><input type="text" class="med-name" value="${esc(data.medication_name || '')}" placeholder="e.g. Methylphenidate, Adderall" /></div>
      <div class="form-group"><label>Dosage</label><input type="text" class="med-dosage" value="${esc(data.dosage || '')}" placeholder="e.g. 10mg" /></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Frequency</label><input type="text" class="med-freq" value="${esc(data.frequency || '')}" placeholder="e.g. Once daily" /></div>
      <div class="form-group"><label>Prescribing Doctor</label><input type="text" class="med-doc" value="${esc(data.prescribing_doctor || '')}" /></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Start Date</label><input type="date" class="med-start" value="${esc(data.start_date || '')}" /></div>
      <div class="form-group"><label>Still Taking?</label>
        <select class="med-current">
          <option value="1" ${data.is_current!==0?'selected':''}>Yes</option>
          <option value="0" ${data.is_current===0?'selected':''}>No</option>
        </select>
      </div>
    </div>
    <div class="form-group"><label>Side Effects / Notes</label><input type="text" class="med-side" value="${esc(data.side_effects_notes || '')}" placeholder="Optional" /></div>
    <button type="button" class="btn btn-danger btn-xs remove-row-btn" onclick="removeRow('med_${id}')">Remove</button>`;
  document.getElementById('medicationsList').appendChild(div);
}

// ═══════════════════════════════════════════════════════════
// DYNAMIC ROWS: Accommodations
// ═══════════════════════════════════════════════════════════
function addAccommodationRow(data = {}) {
  const id  = Date.now() + Math.random();

  if (data.isPreset) {
    // Compact chip — no form fields shown, hidden inputs for data collection
    const chip = document.createElement('div');
    chip.className = 'sf-accom-chip sf-dyn-row';
    chip.id = `ac_${id}`;
    chip.innerHTML = `
      <input type="hidden" class="ac-title" value="${esc(data.title || '')}">
      <input type="hidden" class="ac-cat"   value="${esc(data.category || 'assessment')}">
      <textarea class="ac-desc" style="display:none"></textarea>
      <span class="sf-chip-label">${esc(data.title || '')}</span>
      <button type="button" class="sf-chip-remove" title="Remove" onclick="removeAccomRow('${id}')">&#215;</button>`;
    const chipsArea = document.getElementById('accomChipsArea');
    if (chipsArea) chipsArea.appendChild(chip);
    else document.getElementById('accommodationsList').appendChild(chip);
  } else {
    // Full form row — for custom accommodations
    const div = document.createElement('div');
    div.className = 'sf-dyn-row';
    div.id = `ac_${id}`;
    div.innerHTML = `
      <div class="form-row">
        <div class="form-group" style="flex:2">
          <label>Accommodation *</label>
          <input type="text" class="ac-title" value="${esc(data.title || '')}" placeholder="Describe the accommodation" />
        </div>
        <div class="form-group">
          <label>Category</label>
          <select class="ac-cat">${acCategoryOptions(data.category)}</select>
        </div>
      </div>
      <div class="form-group">
        <label>Notes</label>
        <textarea class="ac-desc" rows="2" placeholder="Implementation notes…">${esc(data.description || '')}</textarea>
      </div>
      <button type="button" class="btn btn-danger btn-xs remove-row-btn" onclick="removeAccomRow('${id}')">Remove</button>`;
    document.getElementById('accommodationsList').appendChild(div);
  }
  updateNoAccomMsg();
}

function removeAccomRow(id) {
  const el = document.getElementById(`ac_${id}`);
  if (!el) return;
  // Un-toggle any matching preset badge
  const titleInput = el.querySelector('.ac-title');
  if (titleInput) {
    const title = titleInput.value;
    document.querySelectorAll('.sf-preset-badge.active').forEach(b => {
      if (b.textContent === title) b.classList.remove('active');
    });
    showAccomToast('Removed: ' + title, 'remove');
  }
  el.remove();
  updateNoAccomMsg();
}

function removeRow(id) { document.getElementById(id)?.remove(); }

// ═══════════════════════════════════════════════════════════
// DOCUMENT UPLOAD ZONE (Step 4 — Other Documents)
// ═══════════════════════════════════════════════════════════
function setupDocDropZone() {
  const zone  = document.getElementById('docUploadZone');
  const input = document.getElementById('docFileInput');
  if (!zone || !input) return;

  zone.addEventListener('click', e => { if (e.target !== input) input.click(); });
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drag-over');
    addDocFiles(e.dataTransfer.files);
  });
  input.addEventListener('change', () => addDocFiles(input.files));
}

function addDocFiles(files) {
  Array.from(files).forEach(file => {
    pendingDocFiles.push(file);
    renderDocItem(file);
  });
}

function renderDocItem(file) {
  const list = document.getElementById('docUploadList');
  const row  = document.createElement('div');
  row.className = 'doc-item';
  row.innerHTML = `
    <div class="doc-item-info">
      <span class="doc-icon">&#128196;</span>
      <span class="doc-name">${esc(file.name)}</span>
      <span class="doc-size muted">(${(file.size / 1024).toFixed(1)} KB)</span>
    </div>
    <select class="doc-type-sel">
      <option value="other">Other</option>
      <option value="iep">IEP</option>
      <option value="itp">ITP</option>
      <option value="individual_profile">Individual Profile</option>
      <option value="medical_report">Medical Report</option>
      <option value="psychological_evaluation">Psych. Evaluation</option>
      <option value="progress_report">Progress Report</option>
      <option value="504_plan">504 Plan</option>
      <option value="parent_consent">Parent Consent</option>
    </select>
    <button type="button" class="btn btn-danger btn-xs remove-pending-doc">&times;</button>`;
  row.querySelector('.remove-pending-doc').addEventListener('click', () => {
    const i = pendingDocFiles.indexOf(file);
    if (i !== -1) pendingDocFiles.splice(i, 1);
    row.remove();
  });
  list.appendChild(row);
}

// ═══════════════════════════════════════════════════════════
// LOAD STUDENT DATA (edit mode)
// ═══════════════════════════════════════════════════════════
async function loadStudentData(id) {
  try {
    const res  = await fetch(`../api/students/get.php?id=${id}`, { headers: EQ.authHeaders() });
    const data = await res.json();
    if (!data.success) { showAlert('error', data.message); return; }

    const d = data.data;
    const s = d.student;

    // Step 1 — Basic Details
    document.getElementById('firstName').value    = s.first_name || '';
    document.getElementById('lastName').value     = s.last_name || '';
    document.getElementById('dob').value          = s.date_of_birth || '';
    document.getElementById('gender').value       = s.gender || '';
    document.getElementById('gradeLevel').value   = s.grade_level || '';
    document.getElementById('studentIdNum').value = s.student_id_number || '';
    document.getElementById('schoolName').value   = s.school_name || '';
    document.getElementById('parentName').value   = s.parent_guardian_name || '';
    document.getElementById('parentEmail').value  = s.parent_guardian_email || '';
    document.getElementById('parentPhone').value  = s.parent_guardian_phone || '';
    document.getElementById('emergContact').value = s.emergency_contact || '';
    document.getElementById('emergPhone').value   = s.emergency_phone || '';
    document.getElementById('generalNotes').value = s.notes || '';
    if (s.student_email) {
      document.getElementById('studentEmail').value = s.student_email;
      document.getElementById('studentEmail').disabled = true;
      document.getElementById('emailStatus').textContent = '✓ Account exists';
      document.getElementById('emailStatus').style.color = '#22c55e';
    }
    if (s.profile_photo) document.getElementById('photoPreview').src = `../uploads/photos/${s.profile_photo}`;

    // Open guardian section if any guardian data exists
    if (s.parent_guardian_name || s.parent_guardian_email || s.parent_guardian_phone || s.emergency_contact) {
      openCollapsible('guardianSection');
    }

    // Step 2 — ADHD / disability profile
    if (d.adhd_profile) {
      const ap = d.adhd_profile;
      document.getElementById('adhdType').value       = ap.adhd_type || 'unspecified';
      document.getElementById('adhdSeverity').value   = ap.severity || 'moderate';
      document.getElementById('diagnosisDate').value  = ap.diagnosis_date || '';
      document.getElementById('diagnosingProf').value = ap.diagnosing_professional || '';
      document.getElementById('inattentionRating').value   = ap.inattention_rating || 3;
      document.getElementById('hyperactivityRating').value = ap.hyperactivity_rating || 3;
      document.getElementById('impulsivityRating').value   = ap.impulsivity_rating || 3;
      updateRatingLabel(document.getElementById('inattentionRating'));
      updateRatingLabel(document.getElementById('hyperactivityRating'));
      updateRatingLabel(document.getElementById('impulsivityRating'));
      document.getElementById('chkReading').checked  = !!ap.has_reading_difficulty;
      document.getElementById('chkWriting').checked  = !!ap.has_writing_difficulty;
      document.getElementById('chkMath').checked     = !!ap.has_math_difficulty;
      document.getElementById('chkFocus').checked    = !!ap.has_focus_difficulty;
      document.getElementById('chkOrg').checked      = !!ap.has_organization_difficulty;
      document.getElementById('chkTime').checked     = !!ap.has_time_management_difficulty;
      document.getElementById('chkMemory').checked   = !!ap.has_working_memory_issues;
      document.getElementById('chkEmotion').checked  = !!ap.has_emotional_regulation_issues;
      document.getElementById('chkIEP').checked      = !!ap.iep_in_place;
      document.getElementById('chk504').checked      = !!ap.section_504_in_place;
      document.getElementById('adhdNotes').value     = ap.additional_notes || '';

      // Open diagnosis details if has data
      if (ap.diagnosis_date || ap.diagnosing_professional) {
        openCollapsible('diagnosisDetails');
      }
    }

    // Comorbid conditions (Step 2 – diagnosis details)
    (d.comorbid_conditions || []).forEach(c => addConditionRow(c));
    if ((d.comorbid_conditions || []).length) openCollapsible('diagnosisDetails');

    // Medications (Step 3 – medications section)
    (d.medications || []).forEach(m => addMedicationRow(m));
    if ((d.medications || []).length) openCollapsible('medicationsSection');

    // Accommodations (Step 3)
    (d.accommodations || []).forEach(a => {
      const isPreset = ACCOMMODATION_PRESETS.some(p => p.title === a.title);
      addAccommodationRow({ ...a, isPreset });
      // Activate matching preset badges
      document.querySelectorAll('.sf-preset-badge').forEach(b => {
        if (b.textContent === a.title) b.classList.add('active');
      });
    });
    updateNoAccomMsg();

    // Existing documents (Step 4)
    renderExistingDocs(d.documents || []);

    // Plans (Step 4)
    loadPlansData(id);

  } catch {
    showAlert('error', 'Failed to load student data.');
  }
}

function renderExistingDocs(docs) {
  const el = document.getElementById('existingDocsList');
  if (!docs.length) return;
  el.innerHTML = '<h4 style="margin-bottom:.5rem">Existing Documents</h4>' + docs.map(doc => `
    <div class="doc-item" id="existDoc${doc.id}">
      <span class="doc-icon">&#128196;</span>
      <span class="doc-name">${esc(doc.title)}</span>
      <span class="muted">${esc(doc.document_type)} · ${esc(doc.original_filename)}</span>
      <a href="../api/upload/download.php?doc_id=${doc.id}" class="btn btn-outline btn-xs" target="_blank">Download</a>
      <button type="button" class="btn btn-danger btn-xs" onclick="deleteExistingDoc(${doc.id})">✕</button>
    </div>`).join('');
}

async function deleteExistingDoc(docId) {
  if (!confirm('Remove this document? This cannot be undone.')) return;
  try {
    const res = await fetch(`../api/upload/delete-document.php?doc_id=${docId}`, {
      method: 'DELETE', headers: EQ.authHeaders(),
    });
    const data = await res.json();
    if (data.success) {
      const row = document.getElementById('existDoc' + docId);
      if (row) row.remove();
    } else {
      showAlert('error', data.message || 'Failed to delete document.');
    }
  } catch {
    showAlert('error', 'Failed to delete document.');
  }
}

// ═══════════════════════════════════════════════════════════
// BUILD PAYLOAD & SUBMIT
// ═══════════════════════════════════════════════════════════
async function submitProfile(e) {
  e.preventDefault();

  const submitBtn = document.getElementById('submitBtn');
  submitBtn.disabled = true;
  submitBtn.querySelector('.btn-text').textContent = 'Saving…';
  submitBtn.querySelector('.btn-spinner').classList.remove('hidden');

  const payload = buildPayload();
  const isEdit  = !!editStudentId;
  const url     = isEdit
    ? `../api/students/update.php?id=${editStudentId}`
    : '../api/students/create.php';
  const method  = isEdit ? 'PUT' : 'POST';

  try {
    const res  = await fetch(url, { method, headers: EQ.authHeaders(), body: JSON.stringify(payload) });
    const data = await res.json();

    if (!data.success) {
      showAlert('error', data.message);
      submitBtn.disabled = false;
      submitBtn.querySelector('.btn-text').textContent = 'Save Student Profile';
      submitBtn.querySelector('.btn-spinner').classList.add('hidden');
      return;
    }

    const studentId = isEdit ? editStudentId : data.data.student_id;

    // Upload photo if selected
    const photoFile = document.getElementById('photoInput').files[0];
    if (photoFile) await uploadPhoto(studentId, photoFile);

    // Upload pending documents
    for (const [idx, file] of pendingDocFiles.entries()) {
      const docTypeSel = document.querySelectorAll('.doc-type-sel')[idx];
      const docType    = docTypeSel ? docTypeSel.value : 'other';
      await uploadDocument(studentId, file, docType);
    }

    // Upload plan files
    for (const [panelKey, file] of Object.entries(pendingPlanFiles)) {
      if (file) {
        const docType = panelKey === 'sip' ? 'individual_profile' : panelKey;
        await uploadDocument(studentId, file, docType);
      }
    }

    // Save structured plan data
    await savePlans(studentId);

    let msg = isEdit ? 'Student profile updated!' : 'Student profile created!';
    if (data.data && data.data.account_created) {
      msg += ` A login account was created for ${data.data.account_email} with the default password.`;
    }
    if (data.data && data.data.account_error) {
      msg += ` Note: ${data.data.account_error}`;
    }
    showAlert('success', msg);
    setTimeout(() => { window.location.href = `student-view.php?id=${studentId}`; }, 2500);

  } catch {
    showAlert('error', 'Unexpected error. Please try again.');
    submitBtn.disabled = false;
    submitBtn.querySelector('.btn-text').textContent = 'Save Student Profile';
    submitBtn.querySelector('.btn-spinner').classList.add('hidden');
  }
}

function buildPayload() {
  return {
    first_name:            document.getElementById('firstName').value.trim(),
    last_name:             document.getElementById('lastName').value.trim(),
    student_email:         document.getElementById('studentEmail').value.trim(),
    date_of_birth:         document.getElementById('dob').value,
    gender:                document.getElementById('gender').value,
    grade_level:           document.getElementById('gradeLevel').value.trim(),
    student_id_number:     document.getElementById('studentIdNum').value.trim(),
    school_name:           document.getElementById('schoolName').value.trim(),
    parent_guardian_name:  document.getElementById('parentName').value.trim(),
    parent_guardian_email: document.getElementById('parentEmail').value.trim(),
    parent_guardian_phone: document.getElementById('parentPhone').value.trim(),
    emergency_contact:     document.getElementById('emergContact').value.trim(),
    emergency_phone:       document.getElementById('emergPhone').value.trim(),
    notes:                 document.getElementById('generalNotes').value.trim(),
    adhd_profile: {
      adhd_type:                       document.getElementById('adhdType').value,
      severity:                        document.getElementById('adhdSeverity').value,
      diagnosis_date:                  document.getElementById('diagnosisDate').value,
      diagnosing_professional:         document.getElementById('diagnosingProf').value.trim(),
      inattention_rating:              parseInt(document.getElementById('inattentionRating').value, 10),
      hyperactivity_rating:            parseInt(document.getElementById('hyperactivityRating').value, 10),
      impulsivity_rating:              parseInt(document.getElementById('impulsivityRating').value, 10),
      has_reading_difficulty:          document.getElementById('chkReading').checked,
      has_writing_difficulty:          document.getElementById('chkWriting').checked,
      has_math_difficulty:             document.getElementById('chkMath').checked,
      has_focus_difficulty:            document.getElementById('chkFocus').checked,
      has_organization_difficulty:     document.getElementById('chkOrg').checked,
      has_time_management_difficulty:  document.getElementById('chkTime').checked,
      has_working_memory_issues:       document.getElementById('chkMemory').checked,
      has_emotional_regulation_issues: document.getElementById('chkEmotion').checked,
      iep_in_place:                    document.getElementById('chkIEP').checked,
      section_504_in_place:            document.getElementById('chk504').checked,
      additional_notes:                document.getElementById('adhdNotes').value.trim(),
    },
    comorbid_conditions: collectRows('conditionsList', row => ({
      condition_name:     row.querySelector('.cond-name').value.trim(),
      condition_category: row.querySelector('.cond-cat').value,
      severity:           row.querySelector('.cond-sev').value,
      diagnosed_by:       row.querySelector('.cond-diag').value.trim(),
      diagnosis_date:     row.querySelector('.cond-date').value,
      is_current:         parseInt(row.querySelector('.cond-current').value, 10),
      notes:              row.querySelector('.cond-notes').value.trim(),
    })).filter(c => c.condition_name),
    medications: collectRows('medicationsList', row => ({
      medication_name:    row.querySelector('.med-name').value.trim(),
      dosage:             row.querySelector('.med-dosage').value.trim(),
      frequency:          row.querySelector('.med-freq').value.trim(),
      prescribing_doctor: row.querySelector('.med-doc').value.trim(),
      start_date:         row.querySelector('.med-start').value,
      is_current:         parseInt(row.querySelector('.med-current').value, 10),
      side_effects_notes: row.querySelector('.med-side').value.trim(),
    })).filter(m => m.medication_name),
    accommodations: collectRows('accommodationsList', row => ({
      title:       row.querySelector('.ac-title').value.trim(),
      category:    row.querySelector('.ac-cat').value,
      description: row.querySelector('.ac-desc').value.trim(),
      is_active:   true,
    })).filter(a => a.title),
  };
}

function collectRows(containerId, mapper) {
  return Array.from(document.getElementById(containerId).querySelectorAll('.sf-dyn-row')).map(mapper);
}

async function uploadPhoto(studentId, file) {
  const fd = new FormData();
  fd.append('photo', file);
  await fetch(`../api/upload/photo.php?student_id=${studentId}`, {
    method: 'POST', headers: EQ.authFetchHeaders(), body: fd,
  });
}

async function uploadDocument(studentId, file, docType) {
  const fd = new FormData();
  fd.append('file', file);
  fd.append('document_type', docType);
  fd.append('title', file.name);
  await fetch(`../api/upload/document.php?student_id=${studentId}`, {
    method: 'POST', headers: EQ.authFetchHeaders(), body: fd,
  });
}

// ═══════════════════════════════════════════════════════════
// SPED PLANS — Load / Collect / Save
// ═══════════════════════════════════════════════════════════
async function loadPlansData(studentId) {
  try {
    const res  = await fetch(`../api/students/plans.php?student_id=${studentId}`, { headers: EQ.authHeaders() });
    const data = await res.json();
    if (!data.success) return;
    if (data.data.iep) {
      populateIepForm(data.data.iep);
      openCollapsible('iepManualSection');
    }
    if (data.data.itp) {
      populateItpForm(data.data.itp);
      openCollapsible('itpManualSection');
    }
    if (data.data.profile) {
      populateSipForm(data.data.profile);
      // SIP data lives in Step 2 fields, open behavioral section if has data
      if (data.data.profile.behavioral_strengths || data.data.profile.social_strengths || data.data.profile.motivators) {
        openCollapsible('behaviorSection');
      }
    }
  } catch { /* silent – plans are optional */ }
}

function populateIepForm(p) {
  setVal('iep_effective_date',  p.effective_date);
  setVal('iep_review_date',     p.review_date);
  setVal('iep_meeting_date',    p.meeting_date);
  setVal('iep_disability_class',p.disability_classification);
  setVal('iep_sped_category',   p.sped_category);
  setVal('iep_team',            p.iep_team);
  setVal('iep_plep_academic',   p.plep_academic);
  setVal('iep_plep_functional', p.plep_functional);
  setVal('iep_plep_social',     p.plep_social);
  setVal('iep_annual_goals',    p.annual_goals);
  setVal('iep_objectives',      p.short_term_objectives);
  setVal('iep_sped_services',   p.sped_services);
  setVal('iep_related_services',p.related_services);
  setVal('iep_accommodations',  p.accommodations_notes);
  setVal('iep_modifications',   p.modifications_notes);
  setVal('iep_regular_ed_pct',  p.regular_ed_percentage);
  setVal('iep_assess_accom',    p.assessment_accommodations);
  setVal('iep_transition',      p.transition_services);
  setVal('iep_notes',           p.additional_notes);
}

function populateItpForm(p) {
  setVal('itp_effective_date',      p.effective_date);
  setVal('itp_graduation_date',     p.graduation_date);
  setVal('itp_disability_category', p.disability_category);
  setVal('itp_career_interests',    p.career_interests);
  setVal('itp_assessed_strengths',  p.assessed_strengths);
  setVal('itp_work_experiences',    p.work_experiences);
  setVal('itp_community_experiences', p.community_experiences);
  setVal('itp_daily_living',        p.daily_living_skills);
  setVal('itp_goal_education',      p.goal_postsecondary_education);
  setVal('itp_goal_employment',     p.goal_employment);
  setVal('itp_goal_independent',    p.goal_independent_living);
  setVal('itp_goal_community',      p.goal_community);
  setVal('itp_services_instruction',p.services_instruction);
  setVal('itp_services_community',  p.services_community);
  setVal('itp_services_employment', p.services_employment);
  setVal('itp_services_adult',      p.services_adult_living);
  setVal('itp_course_of_study',     p.course_of_study);
  setVal('itp_agency_linkages',     p.agency_linkages);
  setVal('itp_annual_goals',        p.annual_goals_transition);
  setVal('itp_notes',               p.additional_notes);
}

function populateSipForm(p) {
  setVal('sip_disability_class',     p.disability_classification);
  setVal('sip_sped_category',        p.sped_category);
  setVal('sip_years_in_sped',        p.years_in_sped);
  setVal('sip_preferred_name',       p.preferred_name);
  setVal('sip_pronouns',             p.preferred_pronouns);
  setVal('sip_language',             p.primary_language);
  setVal('sip_academic_strengths',   p.academic_strengths);
  setVal('sip_academic_challenges',  p.academic_challenges);
  setVal('sip_behavioral_strengths', p.behavioral_strengths);
  setVal('sip_behavioral_challenges',p.behavioral_challenges);
  setVal('sip_social_strengths',     p.social_strengths);
  setVal('sip_social_challenges',    p.social_challenges);
  setVal('sip_learning_style',       p.learning_style);
  setVal('sip_learning_style_notes', p.learning_style_notes);
  setVal('sip_attention_span',       p.attention_span);
  setVal('sip_communication',        p.communication_profile);
  setVal('sip_motivators',           p.motivators);
  setVal('sip_triggers',             p.triggers);
  setVal('sip_calming',              p.calming_strategies);
  setVal('sip_reinforcement',        p.reinforcement_strategies);
  setVal('sip_family_support',       p.family_support_level);
  setVal('sip_outside_services',     p.outside_services);
  setVal('sip_student_voice',        p.student_voice);
  setVal('sip_teacher_observations', p.teacher_observations);
}

function setVal(id, val) {
  const el = document.getElementById(id);
  if (el && val !== null && val !== undefined) el.value = val;
}

function collectIepData() {
  return {
    type: 'iep',
    effective_date:            gv('iep_effective_date'),
    review_date:               gv('iep_review_date'),
    meeting_date:              gv('iep_meeting_date'),
    disability_classification: gv('iep_disability_class'),
    sped_category:             gv('iep_sped_category'),
    iep_team:                  gv('iep_team'),
    plep_academic:             gv('iep_plep_academic'),
    plep_functional:           gv('iep_plep_functional'),
    plep_social:               gv('iep_plep_social'),
    annual_goals:              gv('iep_annual_goals'),
    short_term_objectives:     gv('iep_objectives'),
    sped_services:             gv('iep_sped_services'),
    related_services:          gv('iep_related_services'),
    accommodations_notes:      gv('iep_accommodations'),
    modifications_notes:       gv('iep_modifications'),
    regular_ed_percentage:     gv('iep_regular_ed_pct'),
    assessment_accommodations: gv('iep_assess_accom'),
    transition_services:       gv('iep_transition'),
    additional_notes:          gv('iep_notes'),
  };
}

function collectItpData() {
  return {
    type: 'itp',
    effective_date:               gv('itp_effective_date'),
    graduation_date:              gv('itp_graduation_date'),
    disability_category:          gv('itp_disability_category'),
    career_interests:             gv('itp_career_interests'),
    assessed_strengths:           gv('itp_assessed_strengths'),
    work_experiences:             gv('itp_work_experiences'),
    community_experiences:        gv('itp_community_experiences'),
    daily_living_skills:          gv('itp_daily_living'),
    goal_postsecondary_education: gv('itp_goal_education'),
    goal_employment:              gv('itp_goal_employment'),
    goal_independent_living:      gv('itp_goal_independent'),
    goal_community:               gv('itp_goal_community'),
    services_instruction:         gv('itp_services_instruction'),
    services_community:           gv('itp_services_community'),
    services_employment:          gv('itp_services_employment'),
    services_adult_living:        gv('itp_services_adult'),
    course_of_study:              gv('itp_course_of_study'),
    agency_linkages:              gv('itp_agency_linkages'),
    annual_goals_transition:      gv('itp_annual_goals'),
    additional_notes:             gv('itp_notes'),
  };
}

function collectSipData() {
  return {
    type: 'profile',
    disability_classification:  gv('sip_disability_class'),
    sped_category:              gv('sip_sped_category'),
    years_in_sped:              gv('sip_years_in_sped'),
    preferred_name:             gv('sip_preferred_name'),
    preferred_pronouns:         gv('sip_pronouns'),
    primary_language:           gv('sip_language'),
    academic_strengths:         gv('sip_academic_strengths'),
    academic_challenges:        gv('sip_academic_challenges'),
    behavioral_strengths:       gv('sip_behavioral_strengths'),
    behavioral_challenges:      gv('sip_behavioral_challenges'),
    social_strengths:           gv('sip_social_strengths'),
    social_challenges:          gv('sip_social_challenges'),
    learning_style:             gv('sip_learning_style'),
    learning_style_notes:       gv('sip_learning_style_notes'),
    attention_span:             gv('sip_attention_span'),
    communication_profile:      gv('sip_communication'),
    motivators:                 gv('sip_motivators'),
    triggers:                   gv('sip_triggers'),
    calming_strategies:         gv('sip_calming'),
    reinforcement_strategies:   gv('sip_reinforcement'),
    family_support_level:       gv('sip_family_support'),
    outside_services:           gv('sip_outside_services'),
    student_voice:              gv('sip_student_voice'),
    teacher_observations:       gv('sip_teacher_observations'),
  };
}

function gv(id) {
  const el = document.getElementById(id);
  return el ? el.value.trim() : '';
}

function planHasData(obj) {
  return Object.entries(obj).some(([k, v]) => k !== 'type' && v !== '' && v !== null && v !== undefined);
}

async function savePlans(studentId) {
  const plans = [collectIepData(), collectItpData(), collectSipData()];
  for (const plan of plans) {
    if (!planHasData(plan)) continue;
    try {
      await fetch(`../api/students/plans.php?student_id=${studentId}`, {
        method: 'POST',
        headers: EQ.authHeaders(),
        body: JSON.stringify(plan),
      });
    } catch { /* silent — plans are best-effort */ }
  }
}

// ═══════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════
function showAlert(type, msg) {
  const el = document.getElementById('formAlert');
  el.className = `alert alert-${type}`;
  el.textContent = msg;
  el.classList.remove('hidden');
  el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function categoryOptions(prefix, selected) {
  const opts = [
    ['neurodevelopmental', 'Neurodevelopmental'],
    ['mood_disorder', 'Mood Disorder'],
    ['anxiety_disorder', 'Anxiety Disorder'],
    ['learning_disability', 'Learning Disability'],
    ['behavioral_disorder', 'Behavioral Disorder'],
    ['sleep_disorder', 'Sleep Disorder'],
    ['sensory_processing', 'Sensory Processing'],
    ['other', 'Other'],
  ];
  return opts.map(([v, l]) => `<option value="${v}" ${selected===v?'selected':''}>${l}</option>`).join('');
}

function acCategoryOptions(selected) {
  const opts = [
    ['instructional', 'Instructional'],
    ['assessment', 'Assessment'],
    ['environmental', 'Environmental'],
    ['behavioral', 'Behavioral'],
    ['technology', 'Technology'],
    ['social_emotional', 'Social-Emotional'],
    ['other', 'Other'],
  ];
  return opts.map(([v, l]) => `<option value="${v}" ${selected===v?'selected':''}>${l}</option>`).join('');
}

function esc(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}