/* ═══════════════════════════════════════════════════════════
   Quiz Builder — Teacher Dashboard
   Create & manage quizzes with 4 question types:
     multiple_choice, fill_blank, drag_drop, matching
   ═══════════════════════════════════════════════════════════ */
(() => {
  'use strict';

  const API  = '../api/courses/quizzes.php';
  const UPLOAD_API = '../api/upload/quiz-image.php';
  const $ = id => document.getElementById(id);

  const TYPE_LABELS = {
    multiple_choice:  '☑ Multiple Choice',
    fill_blank:       '✏ Fill in the Blank',
    matching:         '🔗 Matching',
    drag_drop:        '🔀 Drag & Drop',
  };
  const TYPE_SHORT = {
    multiple_choice:  'MC',
    fill_blank:       'Fill',
    matching:         'Match',
    drag_drop:        'DD',
  };

  let editingQuizId = null;
  let questions = [];  // in-memory question array for builder
  let courses = [];
  let localQuestionSeed = 1;
  let draggingQuestionIdx = null;
  const collapsedQuestionUids = new Set();
  let currentAssignments = [];  // cached assignments for the open quiz

  /* ══════════════════════════════════════════════════════════
     INIT
     ══════════════════════════════════════════════════════════ */
  document.addEventListener('DOMContentLoaded', async () => {
    await loadCourses();
    loadQuizList();
    bindEvents();
  });

  function bindEvents() {
    $('btnNewQuiz').addEventListener('click', () => openBuilder(null));
    $('btnBackToList').addEventListener('click', (e) => { e.preventDefault(); closeBuilder(); });
    $('btnSaveQuiz').addEventListener('click', saveQuiz);
    $('btnPreview').addEventListener('click', previewQuiz);
    $('quizSearch').addEventListener('input', debounce(loadQuizList, 300));
    $('quizCourseFilter').addEventListener('change', loadQuizList);

    // Detail tabs
    document.querySelectorAll('.qb-detail-tab').forEach(btn => {
      btn.addEventListener('click', () => switchQuizTab(btn.dataset.tab));
    });

    // Add question type dropdown
    $('btnAddQuestion').addEventListener('click', (e) => {
      e.stopPropagation();
      $('typeDropdown').classList.toggle('hidden');
    });
    document.addEventListener('click', () => $('typeDropdown').classList.add('hidden'));
    document.querySelectorAll('.qb-type-opt').forEach(btn => {
      btn.addEventListener('click', () => {
        addQuestion(btn.dataset.type);
        $('typeDropdown').classList.add('hidden');
      });
    });

    // Large-quiz productivity actions
    $('btnBulkAdd').addEventListener('click', () => {
      $('bulkAddAlert').classList.add('hidden');
      $('bulkQuestionCount').value = '10';
      $('bulkAddModal').classList.remove('hidden');
    });
    $('closeBulkAddModal').addEventListener('click', closeBulkAddModal);
    $('cancelBulkAdd').addEventListener('click', closeBulkAddModal);
    $('confirmBulkAdd').addEventListener('click', handleBulkAddQuestions);

    $('btnExpandAll').addEventListener('click', () => {
      collapsedQuestionUids.clear();
      renderQuestions();
    });
    $('btnCollapseAll').addEventListener('click', () => {
      questions.forEach(q => collapsedQuestionUids.add(q.uid));
      renderQuestions();
    });
    $('jumpQuestion').addEventListener('change', (e) => {
      const idx = +e.target.value;
      if (!Number.isInteger(idx) || idx < 0) return;
      openAndScrollToQuestion(idx);
      e.target.value = '';
    });
    $('btnMoveQuestion').addEventListener('click', handleMoveQuestionToPosition);

    // Assign pane
    $('confirmAssign').addEventListener('click', submitAssignment);
    $('confirmAssignStudents').addEventListener('click', submitAssignStudentsInline);

    // Duplicate pane
    $('confirmDuplicate').addEventListener('click', () => {
      if (editingQuizId) duplicateQuiz(editingQuizId);
    });
  }

  function closeBulkAddModal() {
    $('bulkAddModal').classList.add('hidden');
  }

  /* ══════════════════════════════════════════════════════════
     LOAD COURSES for selects
     ══════════════════════════════════════════════════════════ */
  async function loadCourses() {
    try {
      const res = await fetch('../api/courses/list.php', { headers: EQ.authHeaders() });
      const json = await res.json();
      if (json.success) {
        courses = json.data.courses || [];
        const opts = '<option value="">— None —</option>' +
          courses.map(c => `<option value="${c.id}">${esc(c.title)}</option>`).join('');
        $('qCourse').innerHTML = opts;
        $('assignCourse').innerHTML = '<option value="">— Select Course —</option>' +
          courses.map(c => `<option value="${c.id}">${esc(c.title)}</option>`).join('');
        // filter select
        $('quizCourseFilter').innerHTML = '<option value="">All Courses</option>' +
          courses.map(c => `<option value="${c.id}">${esc(c.title)}</option>`).join('');
      }
    } catch (e) { /* ignore */ }
  }

  /* ══════════════════════════════════════════════════════════
     LIST VIEW — load and render quizzes
     ══════════════════════════════════════════════════════════ */
  async function loadQuizList() {
    const search = $('quizSearch').value.trim();
    const courseId = $('quizCourseFilter').value;
    let url = API + '?action=list';
    if (search) url += '&search=' + encodeURIComponent(search);
    if (courseId) url += '&course_id=' + courseId;

    try {
      const res = await fetch(url, { headers: EQ.authHeaders() });
      const json = await res.json();
      if (!json.success) throw new Error(json.message);

      const quizzes = json.data.quizzes || [];
      if (quizzes.length === 0) {
        $('quizList').innerHTML = '';
        $('quizEmpty').classList.remove('hidden');
        return;
      }

      $('quizEmpty').classList.add('hidden');
      $('quizList').innerHTML = quizzes.map(q => {
        const active = +q.is_active;
        return `
        <div class="qb-quiz-card ${active ? 'qb-card-active' : 'qb-card-inactive'}" data-id="${q.id}">
          <div class="qb-card-icon">📝</div>
          <div class="qb-card-info">
            <h3>${esc(q.title)}</h3>
            <div class="qb-card-meta">
              <span>📋 ${q.question_count} questions</span>
              <span>🎯 ${q.pass_percentage}% to pass</span>
              <span>⚡ ${q.xp_reward} XP</span>
              ${q.course_title ? `<span>📚 ${esc(q.course_title)}</span>` : '<span class="qb-no-course">No course</span>'}
              <span>👥 ${q.attempt_count} ${+q.attempt_count === 1 ? 'attempt' : 'attempts'}</span>
              ${+q.show_score === 0 ? '<span class="qb-score-hidden" title="Score hidden from students">🔇 Score hidden</span>' : ''}
            </div>
          </div>
          <div class="qb-card-right">
            <span class="qb-badge ${active ? 'qb-badge-active' : 'qb-badge-inactive'}">${active ? '● Active' : '● Inactive'}</span>
            <div class="qb-card-actions">
              <button class="btn btn-outline btn-sm qb-toggle-btn" title="${active ? 'Deactivate' : 'Activate'}">${active ? '🔒' : '🔓'}</button>
              <button class="btn btn-outline btn-sm qb-del-btn" title="Delete" style="color:#dc2626">🗑️</button>
            </div>
          </div>
        </div>`;
      }).join('');

      // Bind card actions
      $('quizList').querySelectorAll('.qb-quiz-card').forEach(card => {
        const id = +card.dataset.id;
        card.querySelector('.qb-toggle-btn').addEventListener('click', (e) => { e.stopPropagation(); toggleQuiz(id); });
        card.querySelector('.qb-del-btn').addEventListener('click', (e) => { e.stopPropagation(); deleteQuiz(id); });
        card.addEventListener('click', () => openBuilder(id));
      });

    } catch (e) {
      $('quizList').innerHTML = `<p style="color:#ef4444;padding:1rem">${esc(e.message)}</p>`;
    }
  }

  /* ══════════════════════════════════════════════════════════
     BUILDER — open / close
     ══════════════════════════════════════════════════════════ */
  async function openBuilder(quizId) {
    editingQuizId = quizId;
    questions = [];
    collapsedQuestionUids.clear();

    $('listView').classList.add('hidden');
    $('builderView').classList.remove('hidden');
    $('builderTitle').textContent = quizId ? 'Edit Quiz' : 'Create New Quiz';
    $('builderAlert').classList.add('hidden');

    // Show/hide tabs for existing vs new quiz
    if (quizId) {
      $('quizDetailTabs').classList.remove('hidden');
      switchQuizTab('edit');
    } else {
      $('quizDetailTabs').classList.add('hidden');
      // Ensure edit pane visible, header actions visible
      ['edit','results','assign','duplicate'].forEach(t => {
        $('quizPane-' + t).classList.toggle('hidden', t !== 'edit');
      });
      $('builderHeaderActions').classList.remove('hidden');
      document.querySelectorAll('.qb-detail-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === 'edit'));
    }

    // Reset form
    $('qTitle').value = '';
    $('qDescription').value = '';
    $('qInstructions').value = '';
    $('qCourse').value = '';
    $('qPass').value = '70';
    $('qAttempts').value = '0';
    $('qTime').value = '0';
    $('qXP').value = '50';
    $('qShuffleQ').checked = true;
    $('qShuffleA').checked = true;
    $('qShowScore').checked = true;

    if (quizId) {
      try {
        const res = await fetch(API + '?action=get&id=' + quizId, { headers: EQ.authHeaders() });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);

        const q = json.data.quiz;
        $('qTitle').value = q.title || '';
        $('qDescription').value = q.description || '';
        $('qInstructions').value = q.instructions || '';
        $('qCourse').value = q.course_id || '';
        $('qPass').value = q.pass_percentage;
        $('qAttempts').value = q.max_attempts;
        $('qTime').value = q.time_limit_sec;
        $('qXP').value = q.xp_reward;
        $('qShuffleQ').checked = !!+q.shuffle_questions;
        $('qShuffleA').checked = !!+q.shuffle_answers;
        $('qShowScore').checked = q.show_score !== undefined ? !!+q.show_score : true;

        // Populate duplicate pane title
        const dupTitle = $('dupQuizTitle');
        if (dupTitle) dupTitle.textContent = '"' + (q.title || '') + '"';

        // Render assignment indicator panels
        renderAssignmentSummaries(q.assignments || []);

        // Load questions
        questions = (q.questions || []).map(dbQ => ({
          uid: makeQuestionUid(dbQ.id),
          id: dbQ.id,
          type: dbQ.question_type,
          text: dbQ.question_text,
          image: dbQ.question_image || '',
          explanation: dbQ.explanation || '',
          points: +dbQ.points || 1,
          answers: (dbQ.answers || []).map(a => ({
            id: a.id,
            text: a.answer_text,
            image: a.answer_image || '',
            isCorrect: !!+a.is_correct,
            matchTarget: a.match_target || '',
          })),
        }));
      } catch (e) {
        showAlert('builderAlert', e.message, 'error');
      }
    }

    renderQuestions();
  }

  /* Switch between Edit / Results / Assign / Duplicate tabs */
  function switchQuizTab(tab) {
    document.querySelectorAll('.qb-detail-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    ['edit','results','assign','duplicate'].forEach(t => {
      $('quizPane-' + t).classList.toggle('hidden', t !== tab);
    });

    // Show header actions (save/preview/upload) only on Edit tab
    $('builderHeaderActions').classList.toggle('hidden', tab !== 'edit');

    // Lazy-load results when tab first opened
    if (tab === 'results' && editingQuizId) {
      const content = $('resultsContent');
      if (content && content.dataset.loaded !== String(editingQuizId)) {
        content.dataset.loaded = String(editingQuizId);
        viewResults(editingQuizId);
      }
    }

    // Reset duplicate result message when switching to duplicate tab
    if (tab === 'duplicate') {
      const dupRes = $('dupResult');
      if (dupRes) dupRes.classList.add('hidden');
    }

    // Lazy-load student list when assign tab first opened for this quiz
    if (tab === 'assign' && editingQuizId) {
      const list = $('inlineStudentList');
      if (list && list.dataset.loaded !== String(editingQuizId)) {
        list.dataset.loaded = String(editingQuizId);
        loadInlineStudentsList();
      }
    }
  }

  function closeBuilder() {
    $('builderView').classList.add('hidden');
    $('listView').classList.remove('hidden');
    editingQuizId = null;
    questions = [];
    currentAssignments = [];
    collapsedQuestionUids.clear();
    loadQuizList();
  }

  /* ══════════════════════════════════════════════════════════
     ASSIGNMENT INDICATOR PANELS
     ─ Edit tab:   list of individually assigned students
     ─ Assign tab: list of courses this quiz is assigned to
     ══════════════════════════════════════════════════════════ */
  function renderAssignmentSummaries(assignments) {
    currentAssignments = assignments || [];

    // ── Edit pane: Assigned Students panel ──────────────────
    const editPanel = $('editAssignedSummary');
    if (editPanel) {
      const studentRows = currentAssignments.filter(a => a.student_id);
      if (studentRows.length === 0) {
        editPanel.innerHTML = `
          <div class="qb-assum-box qb-assum-empty">
            <span class="qb-assum-icon">&#128100;</span>
            <span>No students individually assigned yet. Use the <strong>Assign</strong> tab to assign students.</span>
          </div>`;
      } else {
        const rows = studentRows.map(a => {
          const name = esc(((a.student_first_name || '') + ' ' + (a.student_last_name || '')).trim()) || 'Unknown';
          const due  = a.due_date ? new Date(a.due_date).toLocaleDateString() : 'No due date';
          return `<div class="qb-assum-row">
            <span class="qb-assum-student">&#128100; ${name}</span>
            <span class="qb-assum-due">&#128197; ${due}</span>
          </div>`;
        }).join('');
        editPanel.innerHTML = `
          <div class="qb-assum-box">
            <span class="qb-assum-title">&#128101; Assigned Students (${studentRows.length})</span>
            <div class="qb-assum-list">${rows}</div>
          </div>`;
      }
      editPanel.style.display = '';
    }

    // ── Assign pane: Assigned Courses panel ─────────────────
    const assignPanel = $('assignedCourseSummary');
    if (assignPanel) {
      const courseRows = currentAssignments.filter(a => a.course_id && !a.student_id);
      if (courseRows.length === 0) {
        assignPanel.innerHTML = `
          <div class="qb-assum-box qb-assum-notice">
            <span class="qb-assum-icon">&#128218;</span>
            <span>Not yet assigned to any course. Select a course below to assign.</span>
          </div>`;
      } else {
        const tags = courseRows.map(a => {
          const due = a.due_date ? ` &bull; &#128197; ${new Date(a.due_date).toLocaleDateString()}` : '';
          return `<div class="qb-assum-course-tag">&#128218; ${esc(a.course_title || 'Unknown')}${due}</div>`;
        }).join('');
        assignPanel.innerHTML = `
          <div class="qb-assum-box qb-assum-courses">
            <span class="qb-assum-title">&#10003; Currently assigned to:</span>
            <div class="qb-assum-course-list">${tags}</div>
          </div>`;
      }
    }
  }

  async function refreshAssignmentSummaries() {
    if (!editingQuizId) return;
    try {
      const res  = await fetch(API + '?action=get&id=' + editingQuizId, { headers: EQ.authHeaders() });
      const json = await res.json();
      if (json.success) renderAssignmentSummaries(json.data.quiz.assignments || []);
    } catch (_) { /* silent */ }
  }

  /* ══════════════════════════════════════════════════════════
     QUESTIONS — add, render, edit, delete
     ══════════════════════════════════════════════════════════ */
  function addQuestion(type, opts = {}) {
    const defaults = {
      multiple_choice:  { answers: [
        { text: '', image: '', isCorrect: true, matchTarget: '' },
        { text: '', image: '', isCorrect: false, matchTarget: '' },
        { text: '', image: '', isCorrect: false, matchTarget: '' },
        { text: '', image: '', isCorrect: false, matchTarget: '' },
      ]},
      fill_blank:       { answers: [{ text: '', image: '', isCorrect: true, matchTarget: '' }] },
      matching:         { answers: [
        { text: '', image: '', isCorrect: false, matchTarget: '' },
        { text: '', image: '', isCorrect: false, matchTarget: '' },
      ]},
      drag_drop:        { answers: [
        { text: '', image: '', isCorrect: false, matchTarget: '' },
        { text: '', image: '', isCorrect: false, matchTarget: '' },
      ]},
    };

    questions.push({
      uid: makeQuestionUid(),
      type,
      text: '',
      image: '',
      explanation: '',
      points: 1,
      answers: defaults[type]?.answers || [],
    });

    const newQuestion = questions[questions.length - 1];
    if (opts.collapse) collapsedQuestionUids.add(newQuestion.uid);

    if (opts.silent) return;
    renderQuestions();
    // scroll to new question
    setTimeout(() => {
      const cards = $('questionsList').querySelectorAll('.qb-question-card');
      const last = cards[cards.length - 1];
      if (last) last.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 50);
  }

  function renderQuestions() {
    const list = $('questionsList');
    $('questionCount').textContent = `(${questions.length})`;
    refreshQuestionNavigator();

    if (questions.length === 0) {
      list.innerHTML = '';
      $('questionsEmpty').classList.remove('hidden');
      return;
    }
    $('questionsEmpty').classList.add('hidden');

    list.innerHTML = questions.map((q, idx) => {
      const typeLabel = TYPE_LABELS[q.type] || q.type;
      const typeShort = TYPE_SHORT[q.type] || 'Q';
      const preview = q.text ? q.text.substring(0, 60) : '(no question text)';
      const isCollapsed = collapsedQuestionUids.has(q.uid);

      return `
      <div class="qb-question-card" data-type="${q.type}" data-idx="${idx}" data-uid="${q.uid}">
        <div class="qb-q-header" draggable="true">
          <span class="qb-q-drag">⠿</span>
          <span class="qb-q-number">${idx + 1}</span>
          <div class="qb-q-header-info">
            <span class="qb-q-type-badge">${esc(typeLabel)}</span>
            <span class="qb-q-preview">${esc(preview)}</span>
          </div>
          <div class="qb-q-actions">
            <button class="qb-expand-q" title="Expand/Collapse" aria-label="Expand or collapse question">${isCollapsed ? '▼ Expand' : '▲ Collapse'}</button>
            <button class="qb-duplicate-q" title="Duplicate" aria-label="Duplicate question">Duplicate</button>
            <button class="qb-delete-q" title="Delete" aria-label="Delete question">Delete</button>
          </div>
        </div>
        <div class="qb-q-body ${isCollapsed ? 'hidden' : ''}" id="qBody_${idx}">
          ${renderQuestionEditor(q, idx)}
        </div>
      </div>`;
    }).join('');

    // Bind events for each question card
    list.querySelectorAll('.qb-question-card').forEach(card => {
      const idx = +card.dataset.idx;
      card.querySelector('.qb-expand-q').addEventListener('click', () => {
        const body = $(`qBody_${idx}`);
        body.classList.toggle('hidden');
        const toggleBtn = card.querySelector('.qb-expand-q');
        if (toggleBtn) toggleBtn.textContent = body.classList.contains('hidden') ? 'Expand' : 'Collapse';
        if (body.classList.contains('hidden')) collapsedQuestionUids.add(questions[idx].uid);
        else collapsedQuestionUids.delete(questions[idx].uid);
      });
      card.querySelector('.qb-duplicate-q').addEventListener('click', () => duplicateQuestion(idx));
      card.querySelector('.qb-delete-q').addEventListener('click', () => deleteQuestion(idx));

      // Bind input changes to update questions array live
      bindQuestionInputs(card, idx);
    });

    bindQuestionDragAndDrop();
  }

  function bindQuestionDragAndDrop() {
    const cards = [...$('questionsList').querySelectorAll('.qb-question-card')];

    cards.forEach(card => {
      const idx = +card.dataset.idx;
      const header = card.querySelector('.qb-q-header');
      if (!header) return;

      header.addEventListener('dragstart', (e) => {
        draggingQuestionIdx = idx;
        card.classList.add('qb-dragging-card');
        if (e.dataTransfer) {
          e.dataTransfer.effectAllowed = 'move';
          e.dataTransfer.setData('text/plain', String(idx));
        }
      });

      header.addEventListener('dragend', () => {
        draggingQuestionIdx = null;
        card.classList.remove('qb-dragging-card');
        cards.forEach(c => c.classList.remove('qb-drop-target'));
      });

      card.addEventListener('dragover', (e) => {
        if (draggingQuestionIdx === null || draggingQuestionIdx === idx) return;
        e.preventDefault();
        card.classList.add('qb-drop-target');
      });

      card.addEventListener('dragleave', () => {
        card.classList.remove('qb-drop-target');
      });

      card.addEventListener('drop', (e) => {
        e.preventDefault();
        card.classList.remove('qb-drop-target');
        if (draggingQuestionIdx === null || draggingQuestionIdx === idx) return;
        const fromIdx = draggingQuestionIdx;
        const toIdx = idx;
        draggingQuestionIdx = null;
        moveQuestionToIndex(fromIdx, toIdx);
      });
    });
  }

  function renderQuestionEditor(q, idx) {
    let answersHTML = '';

    switch (q.type) {
      case 'multiple_choice':
        answersHTML = renderMCAnswers(q, idx);
        break;
      case 'fill_blank':
        answersHTML = renderFillBlankAnswers(q, idx);
        break;
      case 'drag_drop':
        answersHTML = renderDragDropAnswers(q, idx);
        break;
      case 'matching':
        answersHTML = renderMatchingAnswers(q, idx);
        break;
    }

    const typeHint = {
      multiple_choice:  'Students pick one correct answer from the options below.',
      fill_blank:       'Students type their answer. Add multiple accepted spellings.',
      matching:         'Students connect each left item to its matching right item.',
      drag_drop:        'Students drag items into the correct drop zones.',
    }[q.type] || '';

    const typeHintStyle = {
      multiple_choice:  'background:#eff6ff;color:#1d4ed8;',
      fill_blank:       'background:#fef9c3;color:#854d0e;',
      matching:         'background:#fdf4ff;color:#7e22ce;',
      drag_drop:        'background:#ecfdf5;color:#065f46;',
    }[q.type] || 'background:#f8fafc;color:#475569;';

    return `
      <div style="font-size:0.84rem;padding:0.6rem 1rem;border-radius:8px;margin-bottom:1.1rem;
                  font-weight:500;${typeHintStyle}">
        &#8505;&#65039; ${typeHint}
      </div>

      <!-- ① Question Text — required, most prominent -->
      <div class="qb-editor-section qb-esec-question">
        <div class="qb-esec-head">
          <span class="qb-esec-num" style="background:#3b82f6;">1</span>
          <span class="qb-esec-label">Question Text</span>
          <span class="qb-req-badge">&#10033; Required</span>
        </div>
        <div class="qb-esec-body">
          <textarea class="qb-q-text" rows="3"
            placeholder="${q.type === 'fill_blank'
              ? 'Use ___ for the blank — e.g. The capital of Japan is ___.'
              : 'Write your question here…'}">${esc(q.text)}</textarea>
        </div>
      </div>

      <!-- ② Answers -->
      <div class="qb-editor-section qb-esec-answers">
        <div class="qb-esec-head">
          <span class="qb-esec-num" style="background:#10b981;">2</span>
          <span class="qb-esec-label">Answers</span>
          ${q.type === 'multiple_choice' ? '<span class="qb-tip-badge">&#10003; Mark the correct one</span>' : ''}
        </div>
        <div class="qb-esec-body">
          <div class="qb-answers-list">${answersHTML}</div>
        </div>
      </div>

      <!-- ③ Image & Explanation — optional -->
      <div class="qb-editor-section qb-esec-optional">
        <div class="qb-esec-head">
          <span class="qb-esec-num" style="background:#f59e0b;">3</span>
          <span class="qb-esec-label">Image &amp; Explanation</span>
          <span class="qb-opt-badge">Optional</span>
        </div>
        <div class="qb-esec-body">
          <div class="qb-img-upload">
            <div class="qb-img-preview">
              ${q.image ? `<img src="${esc(q.image)}" alt="Question image" />` : '<span class="qb-no-img">&#128444;</span>'}
            </div>
            <div class="qb-img-controls">
              <span class="qb-img-label">&#128247; Question Image</span>
              <div class="qb-img-actions">
                <label class="qb-img-upload-btn">
                  ${q.image ? '&#128260; Replace' : '&#128193; Upload Image'}
                  <input type="file" class="qb-q-img-input" accept="image/*" />
                </label>
                ${q.image ? '<button type="button" class="btn btn-outline btn-xs qb-clear-q-img">&#128465; Remove</button>' : ''}
              </div>
              ${q.image ? '<p class="qb-img-status">&#10003; Image attached</p>' : '<p class="qb-img-status muted">No image attached</p>'}
            </div>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label style="font-size:0.88rem;font-weight:600;color:#374151;margin-bottom:0.35rem;display:block;">
              Explanation <span style="font-weight:400;color:#94a3b8;font-size:0.8rem;">(shown to students after answering)</span>
            </label>
            <input type="text" class="qb-q-explanation" value="${esc(q.explanation)}"
              placeholder="e.g. The answer is Tokyo because it is the capital city of Japan." />
          </div>
        </div>
      </div>

      <!-- Points -->
      <div class="qb-points-row">
        <label>Points:</label>
        <input type="number" class="qb-q-points" min="1" value="${q.points}" />
      </div>
    `;
  }

  /* ── Multiple Choice / Choose from Box answers ── */
  function renderMCAnswers(q, idx) {
    const rows = q.answers.map((a, aIdx) => `
      <div class="qb-answer-row ${a.isCorrect ? 'qb-correct-row' : ''}" data-aidx="${aIdx}">
        <button type="button" class="qb-correct-toggle ${a.isCorrect ? 'active' : ''}" title="Mark correct">✓</button>
        <input type="text" class="qb-answer-text" value="${esc(a.text)}" placeholder="Answer option ${aIdx + 1}" />
        <button type="button" class="qb-remove-answer" title="Remove">✕</button>
      </div>
    `).join('');

    return rows + `<button type="button" class="qb-add-answer-btn" data-qidx="${idx}">+ Add Option</button>`;
  }

  /* ── Fill in the Blank answers ── */
  function renderFillBlankAnswers(q, idx) {
    const rows = q.answers.map((a, aIdx) => `
      <div class="qb-answer-row qb-correct-row" data-aidx="${aIdx}">
        <span style="font-size:0.78rem;color:#16a34a;font-weight:600;flex-shrink:0">✓ Accepted:</span>
        <input type="text" class="qb-answer-text" value="${esc(a.text)}" placeholder="Accepted answer ${aIdx + 1}" />
        ${aIdx > 0 ? '<button type="button" class="qb-remove-answer" title="Remove">✕</button>' : ''}
      </div>
    `).join('');

    return rows + `<button type="button" class="qb-add-answer-btn" data-qidx="${idx}">+ Add Alternative Spelling</button>`;
  }

  /* ── Drag & Drop answers ── */
  function renderDragDropAnswers(q, idx) {
    const rows = q.answers.map((a, aIdx) => `
      <div class="qb-dd-item-row qb-answer-row" data-aidx="${aIdx}">
        <input type="text" class="qb-answer-text" value="${esc(a.text)}" placeholder="Drag item ${aIdx + 1}" />
        <span class="qb-pair-arrow">→</span>
        <input type="text" class="qb-match-target" value="${esc(a.matchTarget)}" placeholder="Drop zone name" />
        <button type="button" class="qb-remove-answer" title="Remove">✕</button>
      </div>
    `).join('');

    return rows + `<button type="button" class="qb-add-answer-btn" data-qidx="${idx}">+ Add Item</button>`;
  }

  /* ── Matching answers ── */
  function renderMatchingAnswers(q, idx) {
    const rows = q.answers.map((a, aIdx) => `
      <div class="qb-pair-row qb-answer-row" data-aidx="${aIdx}">
        <input type="text" class="qb-answer-text" value="${esc(a.text)}" placeholder="Left item ${aIdx + 1}" />
        <span class="qb-pair-arrow">↔</span>
        <input type="text" class="qb-match-target" value="${esc(a.matchTarget)}" placeholder="Right item ${aIdx + 1}" />
        <button type="button" class="qb-remove-answer" title="Remove">✕</button>
      </div>
    `).join('');

    return rows + `<button type="button" class="qb-add-answer-btn" data-qidx="${idx}">+ Add Pair</button>`;
  }

  /* ══════════════════════════════════════════════════════════
     BIND INPUTS — live update questions array from UI
     ══════════════════════════════════════════════════════════ */
  function bindQuestionInputs(card, idx) {
    const q = questions[idx];

    // Question text
    const textEl = card.querySelector('.qb-q-text');
    if (textEl) textEl.addEventListener('input', () => { q.text = textEl.value; });

    // Explanation
    const explEl = card.querySelector('.qb-q-explanation');
    if (explEl) explEl.addEventListener('input', () => { q.explanation = explEl.value; });

    // Points
    const ptsEl = card.querySelector('.qb-q-points');
    if (ptsEl) ptsEl.addEventListener('input', () => { q.points = Math.max(1, +ptsEl.value); });

    // Image upload
    const imgInput = card.querySelector('.qb-q-img-input');
    if (imgInput) {
      imgInput.addEventListener('change', async () => {
        if (!imgInput.files[0]) return;
        const preview = card.querySelector('.qb-img-preview');
        const status  = card.querySelector('.qb-img-status');
        if (preview) preview.innerHTML = '<span class="qb-img-uploading">⏳ Uploading…</span>';
        if (status)  status.textContent = 'Uploading…';
        const url = await uploadImage(imgInput.files[0]);
        if (url) q.image = url;
        renderQuestions();
      });
    }
    const clearImg = card.querySelector('.qb-clear-q-img');
    if (clearImg) clearImg.addEventListener('click', () => { q.image = ''; renderQuestions(); });

    // Answer rows
    card.querySelectorAll('.qb-answer-row').forEach(row => {
      const aIdx = +row.dataset.aidx;
      const a = q.answers[aIdx];
      if (!a) return;

      const textIn = row.querySelector('.qb-answer-text');
      if (textIn) textIn.addEventListener('input', () => { a.text = textIn.value; });

      const matchIn = row.querySelector('.qb-match-target');
      if (matchIn) matchIn.addEventListener('input', () => { a.matchTarget = matchIn.value; });

      const correctBtn = row.querySelector('.qb-correct-toggle');
      if (correctBtn) {
        correctBtn.addEventListener('click', () => {
          // For MC, only one can be correct
          if (q.type === 'multiple_choice') {
            q.answers.forEach(ans => ans.isCorrect = false);
          }
          a.isCorrect = !a.isCorrect;
          renderQuestions();
        });
      }

      const removeBtn = row.querySelector('.qb-remove-answer');
      if (removeBtn) {
        removeBtn.addEventListener('click', () => {
          q.answers.splice(aIdx, 1);
          renderQuestions();
        });
      }
    });

    // Add answer button
    const addBtn = card.querySelector('.qb-add-answer-btn');
    if (addBtn) {
      addBtn.addEventListener('click', () => {
        q.answers.push({ text: '', image: '', isCorrect: q.type === 'fill_blank', matchTarget: '' });
        renderQuestions();
      });
    }
  }

  function moveQuestion(idx, dir) {
    const newIdx = idx + dir;
    if (newIdx < 0 || newIdx >= questions.length) return;
    [questions[idx], questions[newIdx]] = [questions[newIdx], questions[idx]];
    renderQuestions();
  }

  function moveQuestionToIndex(fromIdx, toIdx) {
    if (fromIdx === toIdx) return;
    if (fromIdx < 0 || fromIdx >= questions.length) return;
    if (toIdx < 0 || toIdx >= questions.length) return;
    const [picked] = questions.splice(fromIdx, 1);
    questions.splice(toIdx, 0, picked);
    renderQuestions();
    openAndScrollToQuestion(toIdx);
  }

  function duplicateQuestion(idx) {
    const source = questions[idx];
    if (!source) return;
    const copy = {
      ...source,
      uid: makeQuestionUid(),
      id: undefined,
      answers: (source.answers || []).map(a => ({ ...a, id: undefined })),
    };
    questions.splice(idx + 1, 0, copy);
    renderQuestions();
    openAndScrollToQuestion(idx + 1);
  }

  function refreshQuestionNavigator() {
    const select = $('jumpQuestion');
    if (!select) return;
    const options = ['<option value="">Jump to question...</option>'];
    questions.forEach((q, idx) => {
      const shortType = TYPE_SHORT[q.type] || 'Q';
      const preview = q.text ? q.text.substring(0, 40) : '(no text)';
      options.push(`<option value="${idx}">${idx + 1}. [${shortType}] ${esc(preview)}</option>`);
    });
    select.innerHTML = options.join('');

    const max = Math.max(1, questions.length);
    $('moveFrom').max = String(max);
    $('moveTo').max = String(max);
  }

  function openAndScrollToQuestion(idx) {
    const q = questions[idx];
    if (!q) return;
    collapsedQuestionUids.delete(q.uid);
    renderQuestions();
    setTimeout(() => {
      const target = $('questionsList').querySelector(`.qb-question-card[data-idx="${idx}"]`);
      if (!target) return;
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      target.classList.add('qb-highlight-card');
      setTimeout(() => target.classList.remove('qb-highlight-card'), 900);
    }, 50);
  }

  function handleMoveQuestionToPosition() {
    if (questions.length === 0) return;
    const fromVal = +$('moveFrom').value;
    const toVal = +$('moveTo').value;
    if (!Number.isInteger(fromVal) || !Number.isInteger(toVal)) {
      showAlert('builderAlert', 'Enter both From and To question numbers.', 'error');
      return;
    }
    const fromIdx = fromVal - 1;
    const toIdx = toVal - 1;
    if (fromIdx < 0 || fromIdx >= questions.length || toIdx < 0 || toIdx >= questions.length) {
      showAlert('builderAlert', 'Question numbers are out of range.', 'error');
      return;
    }
    moveQuestionToIndex(fromIdx, toIdx);
  }

  function handleBulkAddQuestions() {
    const count = +$('bulkQuestionCount').value;
    const selectedTypes = [...document.querySelectorAll('.bulk-type-check:checked')].map(el => el.value);
    if (!Number.isInteger(count) || count < 1 || count > 200) {
      showAlert('bulkAddAlert', 'Enter a question count between 1 and 200.', 'error');
      return;
    }
    if (selectedTypes.length === 0) {
      showAlert('bulkAddAlert', 'Select at least one question type.', 'error');
      return;
    }

    const startIndex = questions.length;

    for (let i = 0; i < count; i++) {
      const type = selectedTypes[0];
      addQuestion(type, { silent: true });
      const justAdded = questions[questions.length - 1];
      if (justAdded) justAdded.text = `Question ${questions.length}`;
    }

    renderQuestions();
    closeBulkAddModal();
    showAlert('builderAlert', `Added ${count} question${count > 1 ? 's' : ''}.`, 'success');
    if (questions[startIndex]) openAndScrollToQuestion(startIndex);
  }

  function deleteQuestion(idx) {
    if (!confirm('Delete this question?')) return;
    collapsedQuestionUids.delete(questions[idx]?.uid);
    questions.splice(idx, 1);
    renderQuestions();
  }

  /* ══════════════════════════════════════════════════════════
     SAVE QUIZ
     ══════════════════════════════════════════════════════════ */
  async function saveQuiz() {
    const title = $('qTitle').value.trim();
    if (!title) {
      showAlert('builderAlert', 'Quiz title is required.', 'error');
      const el = $('qTitle'); el.focus(); el.classList.add('qb-field-error');
      setTimeout(() => el.classList.remove('qb-field-error'), 500);
      return;
    }
    const courseId = $('qCourse').value;
    if (!courseId) {
      showAlert('builderAlert', 'Please select a course before saving.', 'error');
      const el = $('qCourse'); el.focus(); el.classList.add('qb-field-error');
      setTimeout(() => el.classList.remove('qb-field-error'), 500);
      return;
    }

    if (questions.length === 0) {
      showAlert('builderAlert', 'Add at least one question.', 'error');
      return;
    }

    // Validate questions
    for (let i = 0; i < questions.length; i++) {
      const q = questions[i];
      if (!q.text.trim()) {
        showAlert('builderAlert', `Question ${i + 1} has no text.`, 'error');
        return;
      }
      if (q.type === 'multiple_choice') {
        const hasCorrect = q.answers.some(a => a.isCorrect);
        const hasOptions = q.answers.filter(a => a.text.trim()).length >= 2;
        if (!hasCorrect) {
          showAlert('builderAlert', `Question ${i + 1}: Mark at least one correct answer.`, 'error');
          return;
        }
        if (!hasOptions) {
          showAlert('builderAlert', `Question ${i + 1}: Need at least 2 answer options.`, 'error');
          return;
        }
      }
      if (q.type === 'fill_blank') {
        const hasAnswer = q.answers.some(a => a.text.trim());
        if (!hasAnswer) {
          showAlert('builderAlert', `Question ${i + 1}: Enter at least one accepted answer.`, 'error');
          return;
        }
      }
      if (q.type === 'drag_drop' || q.type === 'matching') {
        const validPairs = q.answers.filter(a => a.text.trim() && a.matchTarget.trim());
        if (validPairs.length < 2) {
          showAlert('builderAlert', `Question ${i + 1}: Need at least 2 complete pairs.`, 'error');
          return;
        }
      }
    }

    const payload = {
      action: editingQuizId ? 'update' : 'create',
      id: editingQuizId || undefined,
      title,
      description: $('qDescription').value.trim(),
      instructions: $('qInstructions').value.trim(),
      course_id: courseId || null,
      pass_percentage: +$('qPass').value || 70,
      max_attempts: +$('qAttempts').value || 0,
      time_limit_sec: +$('qTime').value || 0,
      shuffle_questions: $('qShuffleQ').checked ? 1 : 0,
      shuffle_answers: $('qShuffleA').checked ? 1 : 0,
      show_score: $('qShowScore').checked ? 1 : 0,
      xp_reward: +$('qXP').value || 50,
      questions: questions.map(q => ({
        question_type: q.type,
        question_text: q.text,
        question_image: q.image,
        explanation: q.explanation,
        points: q.points,
        answers: q.answers.filter(a => a.text.trim() || a.matchTarget.trim()).map(a => ({
          answer_text: a.text,
          answer_image: a.image,
          is_correct: a.isCorrect ? 1 : 0,
          match_target: a.matchTarget,
        })),
      })),
    };

    $('btnSaveQuiz').disabled = true;
    $('btnSaveQuiz').textContent = 'Saving…';

    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: EQ.authHeaders(),
        body: JSON.stringify(payload),
      });
      const json = await res.json();
      if (!json.success) throw new Error(json.message);

      showAlert('builderAlert', '✅ Quiz saved successfully!', 'success');
      if (!editingQuizId && json.data.quizId) {
        editingQuizId = json.data.quizId;
        $('builderTitle').textContent = 'Edit Quiz';
        // Trigger 7: Celebrate quiz publication
        if (window.showGamePopup) {
          showGamePopup({
            type:      'success',
            title:     'Activity Published! \u2705',
            icon:      '\u2705',
            message:   'Your students can now see and start this activity. Great work!',
            autoClose: 3500,
          });
        }
      }
    } catch (e) {
      showAlert('builderAlert', '❌ ' + e.message, 'error');
    } finally {
      $('btnSaveQuiz').disabled = false;
      $('btnSaveQuiz').innerHTML = '&#128190; Save Quiz';
    }
  }

  /* ══════════════════════════════════════════════════════════
     IMAGE UPLOAD
     ══════════════════════════════════════════════════════════ */
  async function uploadImage(file) {
    const form = new FormData();
    form.append('file', file);

    try {
      const res = await fetch(UPLOAD_API, {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + EQ.token },
        body: form,
      });
      const json = await res.json();
      if (!json.success) { alert('Upload failed: ' + json.message); return null; }
      return json.data.url;
    } catch (e) {
      alert('Upload error: ' + e.message);
      return null;
    }
  }

  /* ══════════════════════════════════════════════════════════
     ACTIONS — duplicate, toggle, delete, assign
     ══════════════════════════════════════════════════════════ */
  async function duplicateQuiz(id) {
    const btn = $('confirmDuplicate');
    const resultEl = $('dupResult');
    if (btn) { btn.disabled = true; btn.textContent = 'Duplicating…'; }
    if (resultEl) resultEl.classList.add('hidden');
    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: EQ.authHeaders(),
        body: JSON.stringify({ action: 'duplicate', id }),
      });
      const json = await res.json();
      if (!json.success) throw new Error(json.message);
      if (resultEl) {
        resultEl.textContent = '✅ Copy created! You can find it in the quiz list.';
        resultEl.className = 'alert alert-success';
        resultEl.classList.remove('hidden');
      }
    } catch (e) {
      if (resultEl) {
        resultEl.textContent = '❌ ' + e.message;
        resultEl.className = 'alert alert-danger';
        resultEl.classList.remove('hidden');
      }
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = '&#128196; Create a Copy'; }
    }
  }

  async function toggleQuiz(id) {
    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: EQ.authHeaders(),
        body: JSON.stringify({ action: 'toggle', id }),
      });
      const json = await res.json();
      if (!json.success) throw new Error(json.message);
      loadQuizList();
    } catch (e) { alert(e.message); }
  }

  async function deleteQuiz(id) {
    if (!confirm('Delete this quiz permanently? This cannot be undone.')) return;
    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: EQ.authHeaders(),
        body: JSON.stringify({ action: 'delete', id }),
      });
      const json = await res.json();
      if (!json.success) throw new Error(json.message);
      loadQuizList();
    } catch (e) { alert(e.message); }
  }

  /* ── Assign pane ── */
  let assigningQuizId = null;
  function openAssignModal(id) {
    assigningQuizId = id;
    // Reset assign pane form
    $('assignDue').value = '';
    $('assignCourse').value = '';
    $('assignAlert').classList.add('hidden');
    switchQuizTab('assign');
  }

  async function submitAssignment() {
    const courseId = $('assignCourse').value;
    if (!courseId) { showAlert('assignAlert', 'Select a course.', 'error'); return; }
    const qid = assigningQuizId || editingQuizId;
    if (!qid) return;

    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: EQ.authHeaders(),
        body: JSON.stringify({
          action: 'assign',
          quiz_id: qid,
          course_id: +courseId,
          due_date: $('assignDue').value || null,
        }),
      });
      const json = await res.json();
      if (!json.success) throw new Error(json.message);
      showAlert('assignAlert', '✅ Quiz assigned successfully!', 'success');
      refreshAssignmentSummaries();
    } catch (e) {
      showAlert('assignAlert', e.message, 'error');
    }
  }

  /* ── Assign to Students modal ── */
  let _assignStudentsQuizId = null;
  let _allStudents = [];

  /* ── Inline student assignment (Assign tab) ── */
  async function loadInlineStudentsList() {
    const list       = $('inlineStudentList');
    const searchEl   = $('inlineStudentSearch');
    const matchCount = $('inlineMatchCount');
    const selAll     = $('inlineSelectAll');
    if (!list) return;

    if (searchEl) { searchEl.value = ''; searchEl.oninput = null; }
    if (matchCount) matchCount.textContent = '';
    if (selAll) { selAll.checked = false; selAll.onchange = null; }
    $('inlineAssignAlert').classList.add('hidden');
    list.innerHTML = '<div class="loading-msg">Loading students…</div>';

    try {
      const res  = await fetch('../api/students/list.php', { headers: EQ.authHeaders() });
      const json = await res.json();
      if (!json.success) throw new Error(json.message);
      _allStudents = json.data.students || [];

      renderInlineStudents('');

      if (searchEl) {
        searchEl.oninput = () => {
          renderInlineStudents(searchEl.value);
          if (selAll) selAll.checked = false;
        };
      }
      if (selAll) {
        selAll.onchange = () => {
          document.querySelectorAll('.inline-student-check').forEach(cb => cb.checked = selAll.checked);
        };
      }
    } catch (e) {
      list.innerHTML = `<p style="color:#ef4444">${esc(e.message)}</p>`;
    }
  }

  function renderInlineStudents(filter) {
    const list       = $('inlineStudentList');
    const matchCount = $('inlineMatchCount');
    if (!list) return;
    const q = (filter || '').trim().toLowerCase();
    const visible = q
      ? _allStudents.filter(s => (s.first_name + ' ' + s.last_name).toLowerCase().includes(q))
      : _allStudents;
    if (matchCount) matchCount.textContent = q ? `${visible.length} result${visible.length !== 1 ? 's' : ''}` : '';
    if (visible.length === 0) {
      list.innerHTML = '<p class="muted" style="padding:.35rem 0">No matching students.</p>';
      return;
    }
    list.innerHTML = visible.map(s => `
      <label class="checkbox-item" style="padding:.35rem 0;display:flex;align-items:center;gap:.5rem">
        <input type="checkbox" class="inline-student-check" value="${s.id}" />
        <span>${esc(s.first_name)} ${esc(s.last_name)}</span>
      </label>`).join('');
  }

  async function submitAssignStudentsInline() {
    const checked = [...document.querySelectorAll('.inline-student-check:checked')].map(cb => +cb.value);
    if (checked.length === 0) { showAlert('inlineAssignAlert', 'Select at least one student.', 'error'); return; }
    if (!editingQuizId) return;

    const maxAttempts = Math.max(0, +($('inlineMaxAttempts').value) || 0);
    const dueDate     = $('inlineDueDate').value || null;

    const btn = $('confirmAssignStudents');
    if (btn) { btn.disabled = true; btn.textContent = 'Assigning…'; }

    try {
      const res  = await fetch(API, {
        method: 'POST',
        headers: EQ.authHeaders(),
        body: JSON.stringify({
          action: 'assign',
          quiz_id: editingQuizId,
          student_ids: checked,
          max_attempts: maxAttempts,
          due_date: dueDate,
        }),
      });
      const json = await res.json();
      if (!json.success) throw new Error(json.message);
      showAlert('inlineAssignAlert', `✅ Quiz assigned to ${checked.length} student${checked.length > 1 ? 's' : ''}!`, 'success');
      refreshAssignmentSummaries();
      document.querySelectorAll('.inline-student-check').forEach(cb => cb.checked = false);
      const selAll = $('inlineSelectAll');
      if (selAll) selAll.checked = false;
    } catch (e) {
      showAlert('inlineAssignAlert', '❌ ' + e.message, 'error');
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = '👤 Assign to Students'; }
    }
  }

  async function openAssignStudentsModal(quizId) {
    _assignStudentsQuizId = quizId;
    const modal = $('assignStudentsModal');
    if (!modal) return;

    $('asAlert').classList.add('hidden');
    $('asStudentList').innerHTML = '<div class="loading-msg">Loading students…</div>';
    $('asDueDate').value = '';
    $('asMaxAttempts').value = '0';
    const searchEl = $('asStudentSearch');
    if (searchEl) { searchEl.value = ''; }
    const matchCount = $('asMatchCount');
    if (matchCount) matchCount.textContent = '';
    modal.classList.remove('hidden');

    try {
      const res = await fetch('../api/students/list.php', { headers: EQ.authHeaders() });
      const json = await res.json();
      if (!json.success) throw new Error(json.message);
      _allStudents = json.data.students || [];

      if (_allStudents.length === 0) {
        $('asStudentList').innerHTML = '<p class="muted" style="padding:.5rem 0">No students found.</p>';
        return;
      }

      const renderList = (filter) => {
        const q = (filter || '').trim().toLowerCase();
        const visible = q
          ? _allStudents.filter(s => (s.first_name + ' ' + s.last_name).toLowerCase().includes(q))
          : _allStudents;
        const matchCount = $('asMatchCount');
        if (matchCount) matchCount.textContent = q ? `${visible.length} result${visible.length !== 1 ? 's' : ''}` : '';
        if (visible.length === 0) {
          $('asStudentList').innerHTML = '<p class="muted" style="padding:.35rem 0">No matching students.</p>';
          return;
        }
        $('asStudentList').innerHTML = visible.map(s => `
          <label class="checkbox-item" style="padding:.35rem 0;display:flex;align-items:center;gap:.5rem">
            <input type="checkbox" class="as-student-check" value="${s.id}" />
            <span>${esc(s.first_name)} ${esc(s.last_name)}</span>
          </label>`).join('');
      };

      renderList('');

      // Search filter
      const searchEl = $('asStudentSearch');
      if (searchEl) {
        searchEl.oninput = () => {
          renderList(searchEl.value);
          const selAll = $('asSelectAll');
          if (selAll) selAll.checked = false;
        };
      }

      // Select all toggle (only checks visible rows)
      const selAll = $('asSelectAll');
      if (selAll) {
        selAll.checked = false;
        selAll.onchange = () => {
          document.querySelectorAll('.as-student-check').forEach(cb => cb.checked = selAll.checked);
        };
      }
    } catch (e) {
      $('asStudentList').innerHTML = `<p style="color:#ef4444">${esc(e.message)}</p>`;
    }
  }

  function closeAssignStudentsModal() {
    const modal = $('assignStudentsModal');
    if (modal) modal.classList.add('hidden');
    _assignStudentsQuizId = null;
  }

  async function submitAssignStudents() {
    const checked = [...document.querySelectorAll('.as-student-check:checked')].map(cb => +cb.value);
    if (checked.length === 0) { showAlert('asAlert', 'Select at least one student.', 'error'); return; }
    if (!_assignStudentsQuizId) return;

    const maxAttempts = Math.max(0, +($('asMaxAttempts').value) || 0);
    const dueDate = $('asDueDate').value || null;

    const btn = $('asConfirmBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Assigning…'; }

    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: EQ.authHeaders(),
        body: JSON.stringify({
          action: 'assign',
          quiz_id: _assignStudentsQuizId,
          student_ids: checked,
          max_attempts: maxAttempts,
          due_date: dueDate,
        }),
      });
      const json = await res.json();
      if (!json.success) throw new Error(json.message);
      showAlert('asAlert', `✅ Quiz assigned to ${checked.length} student${checked.length > 1 ? 's' : ''}!`, 'success');
      setTimeout(closeAssignStudentsModal, 1800);
    } catch (e) {
      showAlert('asAlert', '❌ ' + e.message, 'error');
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Assign'; }
    }
  }

  // Wire modal buttons on init (called after DOM ready)
  document.addEventListener('DOMContentLoaded', () => {
    const cancelBtn = $('asCancelBtn');
    if (cancelBtn) cancelBtn.addEventListener('click', closeAssignStudentsModal);
    const confirmBtn = $('asConfirmBtn');
    if (confirmBtn) confirmBtn.addEventListener('click', submitAssignStudents);
    const closeBtn = $('asCloseBtn');
    if (closeBtn) closeBtn.addEventListener('click', closeAssignStudentsModal);

    // Grade overlay
    const btnCloseGrade = $('btnCloseGrade');
    if (btnCloseGrade) btnCloseGrade.addEventListener('click', closeGradePanel);
    const gradeOverlay = $('gradeOverlay');
    if (gradeOverlay) gradeOverlay.addEventListener('click', e => { if (e.target === gradeOverlay) closeGradePanel(); });
    const btnSaveGrade = $('btnSaveGrade');
    if (btnSaveGrade) btnSaveGrade.addEventListener('click', saveGradeOverride);
    const gradeScoreInput = $('gradeScoreInput');
    if (gradeScoreInput) gradeScoreInput.addEventListener('input', updateScorePreview);
  });

  async function viewResults(id) {
    const content = $('resultsContent');
    content.dataset.quizId = id;
    content.innerHTML = '<div class="loading-msg">Loading results…</div>';

    try {
      const res = await fetch(API + '?action=results&id=' + id, { headers: EQ.authHeaders() });
      const json = await parseJsonResponse(res, 'Failed to load results.');
      if (!json.success) throw new Error(json.message || 'Failed to load results.');

      const quiz = json.data.quiz || {};
      const summary = json.data.summary || {};
      const attempts = json.data.attempts || [];

      let html = `
        <div style="margin-bottom:1rem;padding:0.9rem 1rem;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc">
          <div style="font-weight:700;color:#1e293b;margin-bottom:0.5rem">${esc(quiz.title || 'Quiz Results')}</div>
          <div style="display:flex;gap:1rem;flex-wrap:wrap;font-size:0.85rem;color:#334155">
            <span>👥 Students: <strong>${summary.studentCount || 0}</strong></span>
            <span>📝 Attempts: <strong>${summary.attemptCount || 0}</strong></span>
            <span>✅ Passed: <strong>${summary.passCount || 0}</strong></span>
            <span>📊 Avg: <strong>${summary.avgScore || 0}%</strong></span>
            <span>🏆 Best: <strong>${summary.bestScore || 0}%</strong></span>
            <span>🎯 Pass Mark: <strong>${quiz.passPercentage || 0}%</strong></span>
          </div>
        </div>`;

      if (!attempts.length) {
        html += '<div class="empty-state" style="padding:1rem 0"><p>No attempts yet for this quiz.</p></div>';
      } else {
        html += `
          <div style="overflow:auto">
            <table style="width:100%;border-collapse:collapse;font-size:0.85rem">
              <thead>
                <tr style="background:#f1f5f9;text-align:left">
                  <th style="padding:0.6rem;border-bottom:1px solid #e2e8f0">Student</th>
                  <th style="padding:0.6rem;border-bottom:1px solid #e2e8f0">Attempt</th>
                  <th style="padding:0.6rem;border-bottom:1px solid #e2e8f0">Score</th>
                  <th style="padding:0.6rem;border-bottom:1px solid #e2e8f0">Percent</th>
                  <th style="padding:0.6rem;border-bottom:1px solid #e2e8f0">Pass</th>
                  <th style="padding:0.6rem;border-bottom:1px solid #e2e8f0">XP</th>
                  <th style="padding:0.6rem;border-bottom:1px solid #e2e8f0">Completed</th>
                  <th style="padding:0.6rem;border-bottom:1px solid #e2e8f0">Actions</th>
                </tr>
              </thead>
              <tbody>
                ${attempts.map(a => `
                  <tr>
                    <td style="padding:0.6rem;border-bottom:1px solid #f1f5f9">${esc((a.first_name || '') + ' ' + (a.last_name || ''))}</td>
                    <td style="padding:0.6rem;border-bottom:1px solid #f1f5f9">#${a.attempt_number}</td>
                    <td style="padding:0.6rem;border-bottom:1px solid #f1f5f9">${a.score}/${a.max_score}</td>
                    <td style="padding:0.6rem;border-bottom:1px solid #f1f5f9">${Math.round(+a.percentage)}%</td>
                    <td style="padding:0.6rem;border-bottom:1px solid #f1f5f9">${+a.passed ? '✅' : '❌'}</td>
                    <td style="padding:0.6rem;border-bottom:1px solid #f1f5f9">${+a.xp_earned || 0}</td>
                    <td style="padding:0.6rem;border-bottom:1px solid #f1f5f9">${esc(a.completed_at || '')}</td>
                    <td style="padding:0.6rem;border-bottom:1px solid #f1f5f9">
                      <button class="qb-grade-btn" data-attempt-id="${a.id}">✏️ Grade</button>
                    </td>
                  </tr>`).join('')}
              </tbody>
            </table>
          </div>`;
      }

      content.innerHTML = html;

      // Bind Grade buttons
      content.querySelectorAll('.qb-grade-btn').forEach(btn => {
        btn.addEventListener('click', () => openGradePanel(+btn.dataset.attemptId));
      });

      // Trigger 8: Encourage teacher when they first open results for a quiz that has attempts
      if (attempts.length > 0 && window.showGamePopup) {
        var viewedKey = 'gp_resultsViewed_' + id;
        if (!sessionStorage.getItem(viewedKey)) {
          sessionStorage.setItem(viewedKey, '1');
          var latestStudent = (attempts[0].first_name || '') + (attempts[0].last_name ? ' ' + attempts[0].last_name : '');
          showGamePopup({
            type:    'encouragement',
            title:   'A Student Finished! \uD83C\uDF89',
            icon:    '\uD83C\uDF89',
            message: latestStudent.trim()
              ? latestStudent.trim() + ' and others have completed this quiz. Check their results below!'
              : 'Students have completed this quiz. Check their results below!',
            buttonText: 'See Results',
          });
        }
      }
    } catch (e) {
      content.innerHTML = `<p style="color:#ef4444;padding:0.5rem 0">${esc(e.message)}</p>`;
    }
  }

  /* ══════════════════════════════════════════════════════════
     MANUAL GRADING — teacher grade override overlay
     ══════════════════════════════════════════════════════════ */

  function closeGradePanel() {
    $('gradeOverlay').classList.add('hidden');
    document.body.style.overflow = '';
  }

  async function openGradePanel(attemptId) {
    $('gradeTitle').textContent = '✏️ Grade Submission';
    $('gradeMetaRow').innerHTML = '';
    $('gradeBody').innerHTML = '<div class="qb-grade-loading">Loading submission…</div>';
    $('gradeFooter').style.display = 'none';
    $('gradeOverlay').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    try {
      const res  = await fetch(API + '?action=attempt_detail&attempt_id=' + attemptId, { headers: EQ.authHeaders() });
      const json = await parseJsonResponse(res, 'Failed to load attempt.');
      if (!json.success) throw new Error(json.message || 'Failed to load attempt.');
      renderGradePanel(json.data.attempt, json.data.questions);
    } catch (e) {
      $('gradeBody').innerHTML = `<p style="color:#ef4444;padding:.5rem">${esc(e.message)}</p>`;
    }
  }

  function renderGradePanel(attempt, questions) {
    // Header metadata
    $('gradeTitle').textContent = '✏️ Grade — ' + esc(attempt.quiz_title);
    const metaRow = $('gradeMetaRow');
    const pctDisplay = Math.round(attempt.percentage);
    const passColor  = attempt.passed ? '#16a34a' : '#dc2626';
    const gradeTag   = attempt.is_teacher_graded
      ? '<span class="qb-grade-badge qb-grade-badge--manual">✏️ Manually Graded</span>'
      : '';
    metaRow.innerHTML = `
      <span class="qb-grade-meta-pill">👤 ${esc(attempt.student_name)}</span>
      <span class="qb-grade-meta-pill">📌 Attempt #${attempt.attempt_number}</span>
      <span class="qb-grade-meta-pill" style="color:${passColor};font-weight:600">
        ${attempt.score}/${attempt.max_score} pts (${pctDisplay}%) ${attempt.passed ? '✅ Pass' : '❌ Fail'}
      </span>
      ${gradeTag}`;

    // Per-question breakdown
    const typeLabel = { multiple_choice:'MC', fill_blank:'Fill', drag_drop:'Drag', matching:'Match', choose_from_box:'Choose' };
    const qCards = questions.map((q, i) => {
      const correct     = q.is_correct;
      const borderColor = correct ? '#86efac' : '#fca5a5';
      const bgColor     = correct ? '#f0fdf4' : '#fff5f5';
      const icon        = correct ? '✅' : '❌';
      const typeLbl     = typeLabel[q.type] || q.type;
      return `
        <div class="qb-grade-q-card" style="border-left:4px solid ${borderColor};background:${bgColor}">
          <div class="qb-grade-q-header">
            <span class="qb-grade-q-num">Q${q.order}</span>
            <span class="qb-grade-q-type">${typeLbl}</span>
            <span class="qb-grade-q-pts">${q.points_awarded}/${q.max_points} pts ${icon}</span>
          </div>
          <div class="qb-grade-q-text">${esc(q.text)}</div>
          <div class="qb-grade-q-answers">
            <div class="qb-grade-q-row qb-grade-q-student">
              <span class="qb-grade-q-label">Student:</span>
              <span>${esc(q.student_answer)}</span>
            </div>
            <div class="qb-grade-q-row qb-grade-q-correct">
              <span class="qb-grade-q-label">Correct:</span>
              <span>${esc(q.correct_answer)}</span>
            </div>
            ${q.explanation ? `<div class="qb-grade-q-row qb-grade-q-explain"><span class="qb-grade-q-label">Note:</span><span>${esc(q.explanation)}</span></div>` : ''}
          </div>
        </div>`;
    }).join('');

    $('gradeBody').innerHTML = qCards || '<p style="color:#94a3b8;padding:.5rem">No questions found.</p>';
    $('gradeBody').dataset.attemptId  = attempt.id;
    $('gradeBody').dataset.maxScore   = attempt.max_score;
    $('gradeBody').dataset.passPct    = attempt.pass_percentage;

    // Populate footer
    const scoreInput = $('gradeScoreInput');
    scoreInput.max   = attempt.max_score;
    scoreInput.value = attempt.is_teacher_graded ? attempt.teacher_score : attempt.score;
    $('gradeMaxScore').textContent = '/ ' + attempt.max_score + ' pts';
    $('gradeNotesInput').value     = attempt.teacher_notes || '';
    $('gradeAlert').classList.add('hidden');
    $('gradeAlert').textContent    = '';
    updateScorePreview();
    $('gradeFooter').style.display = '';
  }

  function updateScorePreview() {
    const scoreInput = $('gradeScoreInput');
    const maxScore   = +(scoreInput.max || $('gradeBody').dataset.maxScore || 0);
    const val        = Math.min(Math.max(0, +(scoreInput.value || 0)), maxScore);
    const pct        = maxScore > 0 ? Math.round(val / maxScore * 100) : 0;
    const passPct    = +($('gradeBody').dataset.passPct || 70);
    const passed     = pct >= passPct;
    $('gradeScorePct').textContent = pct + '%';
    $('gradeScorePct').style.color = passed ? '#16a34a' : '#dc2626';
  }

  async function saveGradeOverride() {
    const attemptId = +$('gradeBody').dataset.attemptId;
    const maxScore  = +$('gradeBody').dataset.maxScore;
    const newScore  = +('' + $('gradeScoreInput').value).trim();
    const notes     = $('gradeNotesInput').value.trim();

    const alertEl = $('gradeAlert');
    alertEl.classList.add('hidden');

    if (!attemptId) { showGradeAlert('Invalid attempt. Please reopen.', 'error'); return; }
    if (isNaN(newScore) || newScore < 0 || newScore > maxScore) {
      showGradeAlert('Score must be between 0 and ' + maxScore + '.', 'error');
      return;
    }

    const btn = $('btnSaveGrade');
    btn.disabled    = true;
    btn.textContent = '⏳ Saving…';

    try {
      const res  = await fetch(API, {
        method: 'POST',
        headers: { ...EQ.authHeaders(), 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'grade_override', attempt_id: attemptId, new_score: newScore, notes }),
      });
      const json = await parseJsonResponse(res, 'Save failed.');
      if (!json.success) throw new Error(json.message || 'Save failed.');

      showGradeAlert('✅ Grade override saved successfully!', 'success');

      // Refresh the results table so the updated score is visible
      const activeQuizId = +($('resultsContent').dataset.quizId || 0);
      if (activeQuizId) viewResults(activeQuizId);

    } catch (e) {
      showGradeAlert(e.message, 'error');
    } finally {
      btn.disabled    = false;
      btn.textContent = '💾 Save Override';
    }
  }

  function showGradeAlert(msg, type) {
    const el = $('gradeAlert');
    el.textContent = msg;
    el.className   = 'qb-grade-alert ' + (type === 'success' ? 'qb-grade-alert--ok' : 'qb-grade-alert--err');
    el.classList.remove('hidden');
  }

  /* ══════════════════════════════════════════════════════════
     PREVIEW — open quiz in new tab for preview
     ══════════════════════════════════════════════════════════ */
  function previewQuiz() {
    if (questions.length === 0) {
      showAlert('builderAlert', 'Add some questions first to preview.', 'error');
      return;
    }

    // Build preview data and store in sessionStorage
    const previewData = {
      title: $('qTitle').value || 'Untitled Quiz',
      description: $('qDescription').value,
      instructions: $('qInstructions').value,
      timeLimitSec: +$('qTime').value || 0,
      questions: questions.map((q, i) => ({
        id: i + 1,
        question_order: i + 1,
        question_type: q.type,
        question_text: q.text,
        question_image: q.image,
        points: q.points,
        answers: q.answers.map((a, ai) => ({
          id: ai + 1,
          text: a.text,
          image: a.image,
        })),
        // For matching/drag_drop preview
        leftItems: q.type === 'matching' ? q.answers.map((a, ai) => ({ id: ai+1, text: a.text })) : undefined,
        rightItems: q.type === 'matching' ? q.answers.map(a => a.matchTarget).sort(() => Math.random() - 0.5) : undefined,
        dragItems: q.type === 'drag_drop' ? q.answers.map((a, ai) => ({ id: ai+1, text: a.text })) : undefined,
        dropZones: q.type === 'drag_drop' ? [...new Set(q.answers.map(a => a.matchTarget).filter(Boolean))] : undefined,
      })),
    };
    sessionStorage.setItem('quiz_preview', JSON.stringify(previewData));
    window.open('../../student-dashboard/quests/take-quiz.html?preview=1', '_blank');
  }

  /* ══════════════════════════════════════════════════════════
     HELPERS
     ══════════════════════════════════════════════════════════ */
  async function parseJsonResponse(response, fallbackMessage) {
    const raw = await response.text();
    let data;

    try {
      data = raw ? JSON.parse(raw) : null;
    } catch (_) {
      throw new Error(raw || fallbackMessage || 'Unexpected server response.');
    }

    if (!response.ok) {
      const msg = (data && data.message) ? data.message : (raw || fallbackMessage || 'Request failed.');
      throw new Error(msg);
    }

    if (!data || typeof data !== 'object') {
      throw new Error(fallbackMessage || 'Unexpected server response.');
    }

    return data;
  }

  function esc(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
  }

  function showAlert(id, msg, type) {
    const el = $(id);
    el.textContent = msg;
    el.className = 'alert alert-' + (type === 'error' ? 'danger' : type);
    el.classList.remove('hidden');
    if (type === 'success') setTimeout(() => el.classList.add('hidden'), 4000);
  }

  function debounce(fn, ms) {
    let timer;
    return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), ms); };
  }

  function makeQuestionUid(existingId) {
    if (existingId) return `db_${existingId}`;
    localQuestionSeed += 1;
    return `tmp_${Date.now()}_${localQuestionSeed}`;
  }

  /* ── Public API — used by quiz_upload_modal.php ───────────────
     Expose just what the upload modal needs; nothing else is changed.
     ──────────────────────────────────────────────────────────── */
  window.QB = window.QB || {};

  /**
   * Inject a pre-parsed multiple-choice question.
   * data: { question: string, options: { A, B, C, D }, answer: "A"|"B"|"C"|"D" }
   * Call finalizeInjection() once after all questions have been injected.
   */
  window.QB.injectQuestion = function (data) {
    addQuestion('multiple_choice', { silent: true });
    var q = questions[questions.length - 1];
    q.text = data.question || '';
    q.answers = [
      { text: data.options.A || '', image: '', isCorrect: data.answer === 'A', matchTarget: '' },
      { text: data.options.B || '', image: '', isCorrect: data.answer === 'B', matchTarget: '' },
      { text: data.options.C || '', image: '', isCorrect: data.answer === 'C', matchTarget: '' },
      { text: data.options.D || '', image: '', isCorrect: data.answer === 'D', matchTarget: '' },
    ];
  };

  /** Re-render the question list and scroll to the last injected question. */
  window.QB.finalizeInjection = function () {
    renderQuestions();
    setTimeout(function () {
      var cards = document.getElementById('questionsList').querySelectorAll('.qb-question-card');
      var first = cards[0];
      if (first) first.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 60);
  };

})();
