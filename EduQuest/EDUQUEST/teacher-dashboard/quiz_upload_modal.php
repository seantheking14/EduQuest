<?php
/**
 * quiz_upload_modal.php — Self-contained "Upload Quiz File" modal.
 * Included via require_once from quiz-builder.php.
 *
 * Emits:
 *  1. A <style> block with all scoped .qu-* CSS.
 *  2. The modal HTML (hidden by default).
 *  3. A <script> block with all vanilla JS logic.
 *
 * Depends on: window.EQ.authFetchHeaders()  (from auth-guard.js)
 *             window.QB.injectQuestion()     (from quiz-builder.js public API)
 *             window.QB.finalizeInjection()  (from quiz-builder.js public API)
 */
?>

<!-- ═══════════════════════════════════════════════════════════════
     QUIZ UPLOAD MODAL — CSS
     ═══════════════════════════════════════════════════════════════ -->
<style>
/* ── Upload button in the builder toolbar ── */
.qu-upload-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 16px;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  border: 2px solid #7c3aed;
  color: #7c3aed;
  background: #f5f3ff;
  transition: background 0.15s, color 0.15s;
  white-space: nowrap;
}
.qu-upload-btn:hover {
  background: #7c3aed;
  color: #fff;
}
.qu-upload-btn:focus-visible {
  outline: 3px solid #7c3aed;
  outline-offset: 2px;
}

/* ── Backdrop ── */
.qu-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(3px);
  -webkit-backdrop-filter: blur(3px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99000;
  padding: 16px;
  overflow-y: auto;
}
.qu-backdrop.hidden { display: none; }

/* ── Modal card ── */
.qu-modal {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.22);
  animation: qu-pop-in 0.28s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
@keyframes qu-pop-in {
  from { transform: scale(0.9) translateY(12px); opacity: 0; }
  to   { transform: scale(1)   translateY(0);    opacity: 1; }
}

/* ── Header ── */
.qu-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px 14px;
  border-bottom: 1px solid #e2e8f0;
  flex-shrink: 0;
}
.qu-modal-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
}
.qu-close {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.1rem;
  color: #64748b;
  padding: 4px 8px;
  border-radius: 6px;
  line-height: 1;
}
.qu-close:hover { background: #f1f5f9; color: #1e293b; }
.qu-close:focus-visible { outline: 2px solid #7c3aed; }

/* ── Body ── */
.qu-modal-body {
  padding: 22px;
  flex: 1;
  overflow-y: auto;
}

/* ── Footer ── */
.qu-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 22px;
  border-top: 1px solid #e2e8f0;
  flex-shrink: 0;
}

/* ── Drop zone ── */
.qu-dropzone {
  border: 2px dashed #c4b5fd;
  border-radius: 12px;
  background: #faf5ff;
  padding: 32px 20px;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
  position: relative;
}
.qu-dropzone.qu-drag-over,
.qu-dropzone:focus-within {
  border-color: #7c3aed;
  background: #f0e8ff;
}
.qu-dropzone-icon {
  font-size: 2.5rem;
  margin-bottom: 10px;
  line-height: 1;
}
.qu-dropzone-label {
  margin: 0 0 12px;
  color: #475569;
  font-size: 0.95rem;
}
.qu-dropzone-hint {
  margin: 10px 0 0;
  font-size: 0.8rem;
  color: #94a3b8;
}
.qu-file-label {
  display: inline-block;
  cursor: pointer;
  background: #7c3aed;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 18px;
  font-size: 0.85rem;
  font-weight: 600;
  transition: background 0.14s;
}
.qu-file-label:hover { background: #6d28d9; }
.qu-file-input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}
.qu-selected-name {
  margin: 10px 0 0;
  font-size: 0.82rem;
  color: #7c3aed;
  font-weight: 600;
  word-break: break-all;
}

/* ── Error / status ── */
.qu-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #dc2626;
  padding: 10px 14px;
  font-size: 0.875rem;
  margin-top: 14px;
}
.qu-error.hidden { display: none; }

/* ── Format guide ── */
.qu-guide {
  margin-top: 18px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
}
.qu-guide summary.qu-guide-toggle {
  list-style: none;
  cursor: pointer;
  padding: 11px 16px;
  font-size: 0.88rem;
  font-weight: 600;
  color: #475569;
  background: #f8fafc;
  user-select: none;
}
.qu-guide summary.qu-guide-toggle::-webkit-details-marker { display: none; }
.qu-guide summary.qu-guide-toggle::before {
  content: '▶ ';
  font-size: 0.7em;
  color: #94a3b8;
  transition: transform 0.15s;
}
.qu-guide[open] summary.qu-guide-toggle::before { content: '▼ '; }
.qu-guide-body {
  padding: 16px;
  font-size: 0.83rem;
  color: #475569;
  line-height: 1.6;
}
.qu-guide-body h4 {
  margin: 14px 0 6px;
  color: #1e293b;
  font-size: 0.9rem;
}
.qu-guide-body h4:first-child { margin-top: 0; }
.qu-guide-body pre {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 10px 12px;
  font-size: 0.78rem;
  line-height: 1.55;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-all;
  margin: 4px 0 0;
}

/* ── Spinner (uploading state) ── */
.qu-pane-uploading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  gap: 16px;
  color: #475569;
  font-size: 0.95rem;
}
.qu-pane-uploading.hidden { display: none; }
.qu-spinner {
  width: 44px;
  height: 44px;
  border: 4px solid #e2e8f0;
  border-top-color: #7c3aed;
  border-radius: 50%;
  animation: qu-spin 0.75s linear infinite;
}
@keyframes qu-spin { to { transform: rotate(360deg); } }

/* ── Preview state ── */
.qu-pane-preview.hidden { display: none; }
.qu-preview-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 14px;
}
.qu-preview-count {
  font-size: 0.9rem;
  font-weight: 700;
  color: #16a34a;
}
.qu-skipped-info {
  font-size: 0.82rem;
  color: #b45309;
  background: #fef3c7;
  border-radius: 6px;
  padding: 4px 10px;
}
.qu-skipped-info.hidden { display: none; }
.qu-preview-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 400px;
  overflow-y: auto;
  padding-right: 2px;
}
.qu-q-card {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px 14px;
  background: #fafafa;
}
.qu-q-num {
  font-size: 0.72rem;
  font-weight: 700;
  color: #7c3aed;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 4px;
}
.qu-q-text {
  font-weight: 600;
  font-size: 0.9rem;
  color: #1e293b;
  margin-bottom: 8px;
  line-height: 1.45;
}
.qu-q-options {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.qu-q-opt {
  display: flex;
  align-items: baseline;
  gap: 7px;
  font-size: 0.83rem;
  color: #475569;
  padding: 3px 8px;
  border-radius: 6px;
}
.qu-q-opt.qu-correct {
  background: #dcfce7;
  color: #15803d;
  font-weight: 700;
}
.qu-q-opt-letter {
  font-weight: 700;
  min-width: 16px;
}

/* ── Idle pane ── */
.qu-pane-idle.hidden { display: none; }

/* ── Load button ── */
#quLoadBtn {
  background: #16a34a;
  border-color: #16a34a;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 18px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.13s;
}
#quLoadBtn:hover { background: #15803d; }
#quLoadBtn:focus-visible { outline: 3px solid #16a34a; outline-offset: 2px; }
#quLoadBtn.hidden { display: none; }
</style>

<!-- ═══════════════════════════════════════════════════════════════
     QUIZ UPLOAD MODAL — HTML
     ═══════════════════════════════════════════════════════════════ -->
<div id="quUploadModal" class="qu-backdrop hidden"
     role="dialog" aria-modal="true" aria-labelledby="quModalTitle">
  <div class="qu-modal">

    <!-- Header -->
    <div class="qu-modal-header">
      <h3 id="quModalTitle">&#128228; Upload Quiz File</h3>
      <button class="qu-close" id="quClose" aria-label="Close upload modal">&#10005;</button>
    </div>

    <!-- Body -->
    <div class="qu-modal-body">

      <!-- ── State: idle ── -->
      <div class="qu-pane qu-pane-idle" id="quPaneIdle">

        <div class="qu-dropzone" id="quDropzone" tabindex="0" role="button"
             aria-label="Drop your quiz file here or click to browse">
          <div class="qu-dropzone-icon">&#128193;</div>
          <p class="qu-dropzone-label">Drag &amp; drop your file here, or</p>
          <label class="qu-file-label">
            Browse&hellip;
            <input type="file" id="quFileInput" class="qu-file-input"
                   accept=".csv,.txt,.docx" aria-label="Choose quiz file" />
          </label>
          <p class="qu-dropzone-hint">.csv &nbsp;&#183;&nbsp; .txt &nbsp;&#183;&nbsp; .docx &nbsp;&nbsp;&#8226;&nbsp;&nbsp; Max 5 MB</p>
          <p class="qu-selected-name hidden" id="quSelectedName"></p>
        </div>

        <div class="qu-error hidden" id="quError" role="alert" aria-live="polite"></div>

        <!-- Format guide (native collapsible) -->
        <details class="qu-guide">
          <summary class="qu-guide-toggle">&#128203; File Format Guide — click to expand</summary>
          <div class="qu-guide-body">

            <h4>CSV (.csv)</h4>
            <p>One question per row. The first row is a header and will be skipped.
               Columns: <code>question_text, option_a, option_b, option_c, option_d, correct_answer</code>.
               <code>correct_answer</code> must be A, B, C, or D.</p>
            <pre>question_text,option_a,option_b,option_c,option_d,correct_answer
"What color is the sky?","Red","Blue","Green","Yellow","B"
"How many sides does a triangle have?","2","3","4","5","B"</pre>

            <h4>Plain Text (.txt)</h4>
            <p>One question per block. Separate blocks with a blank line.
               Each line starts with a label followed by a colon.</p>
            <pre>Q: What color is the sky?
A: Red
B: Blue
C: Green
D: Yellow
Answer: B

Q: How many sides does a triangle have?
A: 2
B: 3
C: 4
D: 5
Answer: B</pre>

            <h4>Word Document (.docx)</h4>
            <p>Use the same Q: / A: / B: / C: / D: / Answer: format as the plain text
               example above, typed directly into a Word document.
               Options C and D are optional if the question only needs two choices.</p>

          </div>
        </details>

      </div><!-- /qu-pane-idle -->

      <!-- ── State: uploading ── -->
      <div class="qu-pane qu-pane-uploading hidden" id="quPaneUploading" aria-live="polite">
        <div class="qu-spinner" aria-hidden="true"></div>
        <p>Uploading and parsing your file&hellip;</p>
      </div>

      <!-- ── State: preview ── -->
      <div class="qu-pane qu-pane-preview hidden" id="quPanePreview">
        <div class="qu-preview-header">
          <span class="qu-preview-count" id="quPreviewCount"></span>
          <span class="qu-skipped-info hidden" id="quSkippedInfo"></span>
        </div>
        <div class="qu-preview-list" id="quPreviewList" aria-label="Parsed questions preview"></div>
      </div>

    </div><!-- /qu-modal-body -->

    <!-- Footer -->
    <div class="qu-modal-footer">
      <button class="btn btn-outline btn-sm" id="quCancel">Cancel</button>
      <button id="quLoadBtn" class="hidden">&#10003; Load into Quiz Builder</button>
    </div>

  </div><!-- /qu-modal -->
</div><!-- /qu-backdrop -->

<!-- ═══════════════════════════════════════════════════════════════
     QUIZ UPLOAD MODAL — JavaScript
     ═══════════════════════════════════════════════════════════════ -->
<script>
(function () {
  'use strict';

  var UPLOAD_API = '../api/upload/quiz_upload.php';
  var ALLOWED_EXTS = ['csv', 'txt', 'docx'];
  var MAX_BYTES = 5 * 1024 * 1024; // 5 MB

  // Cached DOM refs (populated on DOMContentLoaded)
  var elBackdrop, elClose, elCancel, elLoadBtn,
      elDropzone, elFileInput, elSelectedName,
      elError, elPaneIdle, elPaneUploading, elPanePreview,
      elPreviewCount, elSkippedInfo, elPreviewList;

  // Parsed questions returned from API
  var _parsedQuestions = [];

  /* ── Boot ────────────────────────────────────────────────── */

  document.addEventListener('DOMContentLoaded', function () {
    elBackdrop      = document.getElementById('quUploadModal');
    elClose         = document.getElementById('quClose');
    elCancel        = document.getElementById('quCancel');
    elLoadBtn       = document.getElementById('quLoadBtn');
    elDropzone      = document.getElementById('quDropzone');
    elFileInput     = document.getElementById('quFileInput');
    elSelectedName  = document.getElementById('quSelectedName');
    elError         = document.getElementById('quError');
    elPaneIdle      = document.getElementById('quPaneIdle');
    elPaneUploading = document.getElementById('quPaneUploading');
    elPanePreview   = document.getElementById('quPanePreview');
    elPreviewCount  = document.getElementById('quPreviewCount');
    elSkippedInfo   = document.getElementById('quSkippedInfo');
    elPreviewList   = document.getElementById('quPreviewList');

    // Open button (injected into quiz-builder.php)
    var btnOpen = document.getElementById('btnUploadQuiz');
    if (btnOpen) { btnOpen.addEventListener('click', openModal); }

    elClose.addEventListener('click', closeModal);
    elCancel.addEventListener('click', onCancel);
    elLoadBtn.addEventListener('click', loadIntoBuilder);

    // File input change
    elFileInput.addEventListener('change', function () {
      if (elFileInput.files && elFileInput.files[0]) {
        handleFile(elFileInput.files[0]);
      }
    });

    // Keyboard: Enter/Space activates drop zone
    elDropzone.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); elFileInput.click(); }
    });

    // Drag-and-drop
    elDropzone.addEventListener('dragover', function (e) {
      e.preventDefault();
      elDropzone.classList.add('qu-drag-over');
    });
    elDropzone.addEventListener('dragleave', function (e) {
      if (!elDropzone.contains(e.relatedTarget)) {
        elDropzone.classList.remove('qu-drag-over');
      }
    });
    elDropzone.addEventListener('drop', function (e) {
      e.preventDefault();
      elDropzone.classList.remove('qu-drag-over');
      var files = e.dataTransfer && e.dataTransfer.files;
      if (files && files[0]) { handleFile(files[0]); }
    });
    // Click on the zone (but not the label itself) triggers file picker
    elDropzone.addEventListener('click', function (e) {
      if (e.target !== elFileInput && !e.target.closest('.qu-file-label')) {
        elFileInput.click();
      }
    });

    // Backdrop click outside modal closes it
    elBackdrop.addEventListener('click', function (e) {
      if (e.target === elBackdrop) { closeModal(); }
    });

    // Escape key closes
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !elBackdrop.classList.contains('hidden')) {
        closeModal();
      }
    });
  });

  /* ── Open / Close ────────────────────────────────────────── */

  function openModal() {
    resetModal();
    elBackdrop.classList.remove('hidden');
    setTimeout(function () { elDropzone.focus(); }, 80);
  }

  function closeModal() {
    elBackdrop.classList.add('hidden');
    resetModal();
  }

  function onCancel() {
    // In preview state, go back to idle; otherwise close
    if (!elPanePreview.classList.contains('hidden')) {
      showPane('idle');
      elLoadBtn.classList.add('hidden');
      elCancel.textContent = 'Cancel';
    } else {
      closeModal();
    }
  }

  function resetModal() {
    _parsedQuestions = [];
    elFileInput.value = '';
    elSelectedName.textContent = '';
    elSelectedName.classList.add('hidden');
    elDropzone.classList.remove('qu-drag-over');
    elError.textContent = '';
    elError.classList.add('hidden');
    elLoadBtn.classList.add('hidden');
    elCancel.textContent = 'Cancel';
    elPreviewList.innerHTML = '';
    showPane('idle');
  }

  /* ── State machine ───────────────────────────────────────── */

  function showPane(state) {
    elPaneIdle.classList.toggle('hidden',      state !== 'idle');
    elPaneUploading.classList.toggle('hidden', state !== 'uploading');
    elPanePreview.classList.toggle('hidden',   state !== 'preview');
  }

  function showError(msg) {
    elError.textContent = msg;
    elError.classList.remove('hidden');
    showPane('idle');
    elLoadBtn.classList.add('hidden');
  }

  /* ── File handling ───────────────────────────────────────── */

  function getExt(name) {
    var parts = name.split('.');
    return parts.length > 1 ? parts[parts.length - 1].toLowerCase() : '';
  }

  function handleFile(file) {
    // Client-side validation
    var ext = getExt(file.name);
    if (ALLOWED_EXTS.indexOf(ext) === -1) {
      showError('Unsupported file type ".' + esc(ext) + '". Please choose a .csv, .txt, or .docx file.');
      return;
    }
    if (file.size > MAX_BYTES) {
      showError('This file is ' + formatBytes(file.size) + ', which exceeds the 5 MB limit. Please use a smaller file.');
      return;
    }

    // Show selected filename
    elSelectedName.textContent = '\uD83D\uDCC4 ' + file.name;
    elSelectedName.classList.remove('hidden');
    elError.classList.add('hidden');

    uploadFile(file);
  }

  /* ── Upload ──────────────────────────────────────────────── */

  function uploadFile(file) {
    showPane('uploading');
    elLoadBtn.classList.add('hidden');

    var formData = new FormData();
    formData.append('quiz_file', file);

    var headers = (window.EQ && window.EQ.authFetchHeaders)
      ? window.EQ.authFetchHeaders()
      : {};

    fetch(UPLOAD_API, { method: 'POST', headers: headers, body: formData })
      .then(function (res) {
        return res.json().then(function (json) {
          return { ok: res.ok, status: res.status, json: json };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.json.success) {
          var msg = (result.json && result.json.message)
            ? result.json.message
            : 'Upload failed (HTTP ' + result.status + '). Please try again.';
          showError(msg);
          return;
        }
        _parsedQuestions = result.json.questions || [];
        renderPreview(result.json);
      })
      .catch(function (err) {
        showError('Network error: ' + (err.message || 'Could not reach the server.'));
      });
  }

  /* ── Preview ─────────────────────────────────────────────── */

  function renderPreview(data) {
    var count   = data.question_count || 0;
    var skipped = data.skipped || 0;

    elPreviewCount.textContent = count + ' question' + (count === 1 ? '' : 's') + ' ready to load';

    if (skipped > 0) {
      elSkippedInfo.textContent = skipped + ' skipped (bad format)';
      elSkippedInfo.classList.remove('hidden');
    } else {
      elSkippedInfo.classList.add('hidden');
    }

    elPreviewList.innerHTML = '';
    _parsedQuestions.forEach(function (q, i) {
      var card = document.createElement('div');
      card.className = 'qu-q-card';

      var numEl = document.createElement('div');
      numEl.className = 'qu-q-num';
      numEl.textContent = 'Q' + (i + 1);
      card.appendChild(numEl);

      var textEl = document.createElement('div');
      textEl.className = 'qu-q-text';
      textEl.textContent = q.question;
      card.appendChild(textEl);

      var optsWrap = document.createElement('div');
      optsWrap.className = 'qu-q-options';

      ['A', 'B', 'C', 'D'].forEach(function (letter) {
        var optText = q.options[letter];
        if (!optText) return;
        var optEl = document.createElement('div');
        optEl.className = 'qu-q-opt' + (letter === q.answer ? ' qu-correct' : '');

        var letterEl = document.createElement('span');
        letterEl.className = 'qu-q-opt-letter';
        letterEl.textContent = letter + '.';
        optEl.appendChild(letterEl);

        var optTextEl = document.createElement('span');
        optTextEl.textContent = optText;
        if (letter === q.answer) {
          var checkEl = document.createElement('span');
          checkEl.textContent = ' \u2713';
          optTextEl.appendChild(checkEl);
        }
        optEl.appendChild(optTextEl);
        optsWrap.appendChild(optEl);
      });

      card.appendChild(optsWrap);
      elPreviewList.appendChild(card);
    });

    showPane('preview');
    elLoadBtn.classList.remove('hidden');
    elCancel.textContent = 'Back';

    // Announce for screen readers
    elPreviewCount.setAttribute('tabindex', '-1');
    elPreviewCount.focus();
  }

  /* ── Load into Builder ───────────────────────────────────── */

  function loadIntoBuilder() {
    if (!_parsedQuestions.length) {
      showError('No questions to load. Please upload a file first.');
      return;
    }
    if (!window.QB || typeof window.QB.injectQuestion !== 'function') {
      showError('Quiz builder is not ready. Please make sure a quiz is open in the builder, then try again.');
      return;
    }

    _parsedQuestions.forEach(function (q) {
      window.QB.injectQuestion(q);
    });

    window.QB.finalizeInjection();
    closeModal();
  }

  /* ── Helpers ─────────────────────────────────────────────── */

  function esc(str) {
    var d = document.createElement('span');
    d.textContent = String(str || '');
    return d.innerHTML;
  }

  function formatBytes(bytes) {
    if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    if (bytes >= 1024)        return (bytes / 1024).toFixed(0) + ' KB';
    return bytes + ' B';
  }

})();
</script>
