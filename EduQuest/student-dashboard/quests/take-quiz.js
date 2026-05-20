/* ═══════════════════════════════════════════════════════════
   Take Quiz — Student Quiz Engine
   Handles all 5 question types:
     multiple_choice, fill_blank, drag_drop, matching, choose_from_box
   ═══════════════════════════════════════════════════════════ */
(() => {
  'use strict';

  const API = '../../EDUQUEST/api/courses/student-quizzes.php';
  const $ = id => document.getElementById(id);

  const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

  let quizMeta = null;       // quiz metadata from list
  let quizData = null;       // { quiz:{}, questions:[] } from GET
  let questionList = [];     // the questions array
  const _historyCache = {};  // quiz attempt history: { [quizId]: attempts[] }
  let currentIdx = 0;
  let studentAnswers = {};   // { questionId: answer }
  let startTime = 0;
  let timerInterval = null;
  let autoAdvanceTimer = null;
  let isPreview = false;

  /* ══════════════════════════════════════
     INIT
     ══════════════════════════════════════ */
  document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(location.search);
    isPreview = params.get('preview') === '1';

    if (isPreview) {
      loadPreview();
    } else {
      if (typeof Auth !== 'undefined' && !Auth.requireAuth('student')) return;
      loadQuizList();
    }

    $('btnBackList').addEventListener('click', showList);
    $('btnStartQuiz').addEventListener('click', startQuiz);
    $('btnPrevQ').addEventListener('click', () => navigateQ(-1));
    $('btnResultBack').addEventListener('click', showList);
    $('btnRetry').addEventListener('click', () => {
      if (quizMeta) openIntro(quizMeta);
    });
    $('btnCloseReview').addEventListener('click', () => {
      $('reviewOverlay').classList.add('hidden');
      document.body.style.overflow = '';
    });
    $('reviewOverlay').addEventListener('click', e => {
      if (e.target === $('reviewOverlay')) {
        $('reviewOverlay').classList.add('hidden');
        document.body.style.overflow = '';
      }
    });

    const nextBtn = $('btnNextQ');
    if (nextBtn) nextBtn.addEventListener('click', () => navigateQ(1));
  });

  /* ══════════════════════════════════════
     PREVIEW MODE (from teacher builder)
     ══════════════════════════════════════ */
  function loadPreview() {
    try {
      const data = JSON.parse(sessionStorage.getItem('quiz_preview'));
      if (!data) { document.body.innerHTML = '<p style="padding:2rem">No preview data found.</p>'; return; }

      quizData = {
        quiz: {
          title: data.title,
          description: data.description,
          instructions: data.instructions,
          timeLimitSec: data.timeLimitSec || 0,
          passPercentage: 70,
          xpReward: 0,
          maxAttempts: 0,
          questionCount: data.questions.length,
        },
        questions: data.questions,
      };
      quizMeta = { title: data.title, description: data.description };
      questionList = data.questions;

      showScreen('quizIntroScreen');
      populateIntro();
    } catch (e) {
      document.body.innerHTML = '<p style="padding:2rem;color:red">Error loading preview.</p>';
    }
  }

  /* ══════════════════════════════════════
     QUIZ LIST
     ══════════════════════════════════════ */
  async function loadQuizList() {
    try {
      const res = await apiFetch(API + '?action=list');
      const json = await res.json();
      if (!json.success) throw new Error(json.message);

      const quizzes = json.data.quizzes || [];
      if (quizzes.length === 0) {
        $('quizListCards').innerHTML = '';
        $('quizListEmpty').classList.remove('hidden');
        return;
      }

      $('quizListEmpty').classList.add('hidden');
      $('quizListCards').innerHTML = quizzes.map(q => {
        const passed = +q.ever_passed;
        const attempts = +q.my_attempts;
        const best = q.best_score != null ? Math.round(+q.best_score) : null;
        const maxAttempts = +q.max_attempts || 0;
        const locked = maxAttempts > 0 && attempts >= maxAttempts;
        const dueDate = q.due_date ? new Date(q.due_date + 'T00:00:00') : null;
        const dueLabel = dueDate ? dueDate.toLocaleDateString() : '';

        const statusLabel = locked ? 'Locked' : passed ? 'Passed' : attempts > 0 ? 'In Progress' : 'New';
        const statusClass = locked ? 'locked' : passed ? 'passed' : attempts > 0 ? 'tried' : 'new';
        const cardClass   = locked ? 'disabled' : passed ? 'passed' : '';
        const icon        = passed ? '✅' : locked ? '🔒' : '📝';
        const ctaText     = locked ? '🔒 View Attempts' : passed ? '▶ Play Again' : attempts > 0 ? '▶ Continue' : '▶ Start Quiz';

        const timeFmt = +q.time_limit_sec > 0 ? (+q.time_limit_sec >= 60 ? `${Math.floor(+q.time_limit_sec / 60)}m` : `${+q.time_limit_sec}s`) : null;
        const attemptPct = maxAttempts > 0 ? Math.min(100, Math.round((attempts / maxAttempts) * 100)) : 0;
        return `
        <div class="tq-quiz-wrap">
          <div class="tq-quiz-card ${cardClass}" data-id="${q.id}">
            <div class="tq-card-banner"></div>
            <span class="tq-card-status ${statusClass}">${statusLabel}</span>
            <div class="tq-card-body">
              <div class="tq-card-header">
                <div class="tq-card-icon-wrap">${icon}</div>
                <div class="tq-card-info">
                  <div class="tq-card-title">${esc(q.title)}</div>
                  ${q.description ? `<div class="tq-card-desc">${esc(q.description)}</div>` : ''}
                </div>
              </div>
              <div class="tq-card-meta">
                <span class="tq-card-pill">📋 ${q.question_count} Q</span>
                <span class="tq-card-pill xp">⚡ ${q.xp_reward} XP</span>
                <span class="tq-card-pill pass">🎯 ${q.pass_percentage}%</span>
                ${timeFmt ? `<span class="tq-card-pill">⏱ ${timeFmt}</span>` : ''}
                ${best != null ? `<span class="tq-card-pill ${passed ? 'done' : ''}">📊 Best: ${best}%</span>` : ''}
                ${dueLabel ? `<span class="tq-card-pill">📅 Due ${dueLabel}</span>` : ''}
              </div>
              ${maxAttempts > 0 ? `<div class="tq-card-progress-wrap">
                <div class="tq-card-progress-bar"><div class="tq-card-progress-fill" style="width:${attemptPct}%"></div></div>
                <span class="tq-card-progress-label">${attempts}/${maxAttempts} tries</span>
              </div>` : ''}
            </div>
            <div class="tq-card-footer">
              <span class="tq-card-cta">${ctaText}</span>
              ${attempts > 0 ? `<button class="tq-history-btn" data-qid="${q.id}">📋 ${attempts} attempt${attempts !== 1 ? 's' : ''}</button>` : ''}
              <span class="tq-card-arrow">›</span>
            </div>
          </div>
          ${attempts > 0 ? `<div class="tq-history-drawer hidden" id="histDrawer_${q.id}"><p class="tq-history-loading">Loading…</p></div>` : ''}
        </div>`;
      }).join('');

      // Bind clicks
      $('quizListCards').querySelectorAll('.tq-quiz-card').forEach(card => {
        const id = +card.dataset.id;
        const q = quizzes.find(x => +x.id === id);
        const maxAttempts = +q.max_attempts || 0;
        const attempts = +q.my_attempts;
        const locked = maxAttempts > 0 && attempts >= maxAttempts;
        card.addEventListener('click', () => {
          if (locked) {
            // For locked quizzes: open the history drawer so student can review attempts
            if (attempts > 0) {
              toggleQuizHistory(+q.id);
            }
            return;
          }
          openIntro(q);
        });
        const histBtn = card.querySelector('.tq-history-btn');
        if (histBtn) {
          histBtn.addEventListener('click', e => {
            e.stopPropagation();
            toggleQuizHistory(+histBtn.dataset.qid);
          });
        }
      });

      // Populate summary pills
      const pills = $('quizSummaryPills');
      if (pills) {
        const total  = quizzes.length;
        const nPassed = quizzes.filter(q => +q.ever_passed).length;
        const nLocked = quizzes.filter(q => { const ma = +q.max_attempts || 0; return ma > 0 && +q.my_attempts >= ma; }).length;
        const nPending = total - nPassed - nLocked;
        pills.innerHTML = [
          `<span class="tq-summary-pill total">📋 ${total} Total</span>`,
          nPassed  > 0 ? `<span class="tq-summary-pill passed">✅ ${nPassed} Passed</span>`  : '',
          nPending > 0 ? `<span class="tq-summary-pill pending">📝 ${nPending} To Do</span>` : '',
          nLocked  > 0 ? `<span class="tq-summary-pill locked">🔒 ${nLocked} Locked</span>` : '',
        ].join('');
      }

    } catch (e) {
      $('quizListCards').innerHTML = `<p style="color:#ef4444;padding:1rem">${esc(e.message)}</p>`;
    }
  }

  function showList() {
    stopTimer();
    quizData = null;
    questionList = [];
    studentAnswers = {};
    showScreen('quizListScreen');
    if (!isPreview) loadQuizList();
  }

  /* ══════════════════════════════════════
     INTRO SCREEN
     ══════════════════════════════════════ */
  function openIntro(meta) {
    quizMeta = meta;
    showScreen('quizIntroScreen');

    if (!isPreview) {
      // Fetch fresh quiz data
      apiFetch(API + '?action=get&id=' + meta.id)
        .then(r => r.json())
        .then(json => {
          if (!json.success) { alert(json.message); showList(); return; }
          quizData = json.data;
          if (quizData && quizData.quiz) {
            quizData.quiz.myAttempts = toInt(
              quizData.quiz.myAttempts,
              quizData.quiz.my_attempts,
              quizData.quiz.attemptsSoFar,
              meta.my_attempts,
              meta.myAttempts,
              0
            );
            quizData.quiz.maxAttempts = toInt(
              quizData.quiz.maxAttempts,
              quizData.quiz.max_attempts,
              meta.max_attempts,
              meta.maxAttempts,
              0
            );
          }
          questionList = quizData.questions;
          populateIntro();
        })
        .catch(e => { alert(e.message); showList(); });
    } else {
      populateIntro();
    }
  }

  function populateIntro() {
    if (!quizData) return;
    const q = quizData.quiz;
    $('introTitle').textContent = q.title;
    $('introDesc').textContent = q.description || '';
    $('metaQuestions').textContent = q.questionCount + ' questions';
    $('metaPass').textContent = q.passPercentage + '% to pass';
    $('metaTime').textContent = q.timeLimitSec > 0 ? formatTime(q.timeLimitSec) : 'No time limit';
    $('metaXP').textContent = q.xpReward + ' XP';
    $('metaAttempts').textContent = q.maxAttempts > 0 ? q.maxAttempts + ' attempts' : 'Unlimited';

    const attemptsInfo = getAttemptInfo(q);
    const startBtn = $('btnStartQuiz');
    const notice = $('introAttemptNotice');
    if (attemptsInfo.exhausted) {
      startBtn.disabled = true;
      startBtn.textContent = '🚫 Attempt Limit Reached';
      notice.textContent = `You already used ${attemptsInfo.attempts} of ${attemptsInfo.maxAttempts} attempts for this quiz.`;
      notice.classList.remove('hidden');
    } else {
      startBtn.disabled = false;
      startBtn.textContent = '▶️ Start Quiz';
      notice.classList.add('hidden');
    }

    if (q.instructions) {
      $('introInstructions').textContent = q.instructions;
      $('introInstructions').classList.remove('hidden');
    } else {
      $('introInstructions').classList.add('hidden');
    }
  }

  /* ══════════════════════════════════════
     START QUIZ
     ══════════════════════════════════════ */
  function startQuiz() {
    if (!quizData || questionList.length === 0) return;

    currentIdx = 0;
    studentAnswers = {};
    startTime = Date.now();

    showScreen('quizPlayScreen');

    // Timer
    const timeLimit = quizData.quiz.timeLimitSec;
    if (timeLimit > 0) {
      $('hudTimerWrap').classList.remove('hidden');
      $('timerBar').classList.remove('hidden');
      startTimer(timeLimit);
    } else {
      $('hudTimerWrap').classList.add('hidden');
      $('timerBar').classList.add('hidden');
    }

    renderQuestion();
    renderDots();
  }

  /* ══════════════════════════════════════
     TIMER
     ══════════════════════════════════════ */
  function startTimer(totalSec) {
    let remaining = totalSec;
    updateTimerDisplay(remaining, totalSec);

    timerInterval = setInterval(() => {
      remaining--;
      updateTimerDisplay(remaining, totalSec);
      if (remaining <= 0) {
        stopTimer();
        submitQuiz();
      }
    }, 1000);
  }

  function updateTimerDisplay(remaining, total) {
    $('hudTimer').textContent = formatTime(remaining);
    $('timerFill').style.width = (remaining / total * 100) + '%';
    if (remaining <= 30) $('hudTimer').style.color = '#ef4444';
  }

  function stopTimer() {
    if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
    $('hudTimer').style.color = '';
  }

  /* ══════════════════════════════════════
     RENDER QUESTION
     ══════════════════════════════════════ */
  function renderQuestion() {
    const q = questionList[currentIdx];
    if (!q) return;

    $('hudQuestion').textContent = `${currentIdx + 1}/${questionList.length}`;
    const answeredCount = Object.keys(studentAnswers).length;
    $('hudAnswered').textContent = answeredCount;
    const qFill = $('qProgressFill');
    if (qFill) qFill.style.width = ((currentIdx + 1) / questionList.length * 100) + '%';

    const area = $('questionArea');
    const qId = q.id;
    const type = q.question_type;

    // Update question type badge
    const badge = $('qTypeBadge');
    if (badge) {
      const TYPE_LABELS = {
        multiple_choice: '🔤 Multiple Choice',
        choose_from_box: '📦 Choose from Box',
        fill_blank:      '✏️ Fill in the Blank',
        drag_drop:       '🔀 Drag & Drop',
        matching:        '🔗 Matching',
      };
      badge.textContent = TYPE_LABELS[type] || type;
    }

    let html = `<div class="tq-q-kicker">QUESTION</div><div class="tq-q-text">${esc(q.question_text)}</div>`;
    if (q.question_image) html += `<img class="tq-q-image" src="${esc(q.question_image)}" alt="Question image" />`;

    switch (type) {
      case 'multiple_choice':
        html += renderMC(q, qId);
        break;
      case 'choose_from_box':
        html += renderChooseBox(q, qId);
        break;
      case 'fill_blank':
        html += renderFillBlank(q, qId);
        break;
      case 'drag_drop':
        html += renderDragDrop(q, qId);
        break;
      case 'matching':
        html += renderMatching(q, qId);
        break;
    }

    area.innerHTML = html;

    // Bind interactions
    switch (type) {
      case 'multiple_choice': bindMC(qId); break;
      case 'choose_from_box': bindChooseBox(qId); break;
      case 'fill_blank': bindFillBlank(qId); break;
      case 'drag_drop': bindDragDrop(q, qId); break;
      case 'matching': bindMatching(q, qId); break;
    }

    // Nav buttons
    $('btnPrevQ').disabled = currentIdx === 0;

    renderDots();
  }

  function scheduleAutoAdvance(delay = 420) {
    if (autoAdvanceTimer) clearTimeout(autoAdvanceTimer);
    autoAdvanceTimer = setTimeout(() => {
      navigateQ(1);
    }, delay);
  }

  function renderDots() {
    const dots = questionList.map((q, i) => {
      let cls = 'tq-q-dot';
      if (i === currentIdx) cls += ' active';
      if (studentAnswers[q.id] !== undefined) cls += ' answered';
      return `<span class="${cls}"></span>`;
    }).join('');
    $('qDots').innerHTML = dots;
  }

  function navigateQ(dir) {
    if (dir === 1 && currentIdx === questionList.length - 1) {
      // Submit
      submitQuiz();
      return;
    }
    currentIdx = Math.max(0, Math.min(questionList.length - 1, currentIdx + dir));
    renderQuestion();
  }

  /* ══════════════════════════════════════
     QUESTION TYPE: Multiple Choice
     ══════════════════════════════════════ */
  function renderMC(q, qId) {
    const selected = studentAnswers[qId];
    return '<div class="tq-mc-options">' +
      q.answers.map((a, i) =>
        `<div class="tq-mc-option ${selected === a.id ? 'selected' : ''}" data-aid="${a.id}">
          <span class="tq-opt-letter">${LETTERS[i]}</span>
          <span>${esc(a.text)}</span>
          ${a.image ? `<img src="${esc(a.image)}" style="max-height:40px;border-radius:6px;margin-left:auto" />` : ''}
        </div>`
      ).join('') + '</div>';
  }

  function bindMC(qId) {
    $('questionArea').querySelectorAll('.tq-mc-option').forEach(opt => {
      opt.addEventListener('click', () => {
        studentAnswers[qId] = +opt.dataset.aid;
        renderQuestion();
        scheduleAutoAdvance();
      });
    });
  }

  /* ══════════════════════════════════════
     QUESTION TYPE: Choose from Box
     ══════════════════════════════════════ */
  function renderChooseBox(q, qId) {
    const selected = studentAnswers[qId];
    return '<div class="tq-box-pool">' +
      q.answers.map(a =>
        `<div class="tq-box-chip ${selected === a.id ? 'selected' : ''}" data-aid="${a.id}">
          ${esc(a.text)}
        </div>`
      ).join('') + '</div>';
  }

  function bindChooseBox(qId) {
    $('questionArea').querySelectorAll('.tq-box-chip').forEach(chip => {
      chip.addEventListener('click', () => {
        studentAnswers[qId] = +chip.dataset.aid;
        renderQuestion();
        scheduleAutoAdvance();
      });
    });
  }

  /* ══════════════════════════════════════
     QUESTION TYPE: Fill in the Blank
     ══════════════════════════════════════ */
  function renderFillBlank(q, qId) {
    const val = studentAnswers[qId] || '';
    return `<input type="text" class="tq-fill-input" id="fillInput" value="${esc(val)}" placeholder="Type your answer…" autocomplete="off" />`;
  }

  function bindFillBlank(qId) {
    const input = $('fillInput');
    if (input) {
      input.focus();
      input.addEventListener('input', () => {
        const val = input.value.trim();
        studentAnswers[qId] = val;
        renderDots();
        if (val.length > 0) scheduleAutoAdvance(800);
      });
      input.addEventListener('keydown', e => {
        if (e.key === 'Enter' && input.value.trim().length > 0) {
          e.preventDefault();
          scheduleAutoAdvance(120);
        }
      });
    }
  }

  /* ══════════════════════════════════════
     QUESTION TYPE: Drag & Drop
     ══════════════════════════════════════ */
  function renderDragDrop(q, qId) {
    const placed = studentAnswers[qId] || {}; // { answerId: zoneName }
    const placedIds = Object.keys(placed).map(Number);

    // Pool: items not yet placed
    const poolItems = (q.dragItems || []).filter(d => !placedIds.includes(d.id));

    let html = '<div class="tq-dd-container">';
    html += '<div class="tq-dd-items"><h4>Drag Items</h4><div class="tq-dd-pool" id="ddPool">';
    html += poolItems.map(d =>
      `<div class="tq-dd-chip" draggable="true" data-did="${d.id}">${esc(d.text)}</div>`
    ).join('');
    html += '</div></div>';

    html += '<div class="tq-dd-zones"><h4>Drop Zones</h4>';
    (q.dropZones || []).forEach(zone => {
      const itemsInZone = Object.entries(placed)
        .filter(([_, z]) => z === zone)
        .map(([aid]) => (q.dragItems || []).find(d => d.id === +aid))
        .filter(Boolean);

      html += `<div class="tq-dd-zone" data-zone="${esc(zone)}">
        <span class="tq-dd-zone-label">${esc(zone)}</span>
        ${itemsInZone.map(d => `<div class="tq-dd-chip" data-did="${d.id}">${esc(d.text)}</div>`).join('')}
      </div>`;
    });
    html += '</div></div>';

    return html;
  }

  function bindDragDrop(q, qId) {
    const pool = $('ddPool');
    if (!pool) return;
    const placed = studentAnswers[qId] || {};

    // Drag events
    $('questionArea').querySelectorAll('.tq-dd-chip[draggable]').forEach(chip => {
      chip.addEventListener('dragstart', e => {
        e.dataTransfer.setData('text/plain', chip.dataset.did);
        chip.classList.add('dragging');
      });
      chip.addEventListener('dragend', () => chip.classList.remove('dragging'));
    });

    // Drop zones
    $('questionArea').querySelectorAll('.tq-dd-zone').forEach(zone => {
      zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
      zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
      zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        const did = e.dataTransfer.getData('text/plain');
        if (!did) return;
        placed[did] = zone.dataset.zone;
        studentAnswers[qId] = { ...placed };
        const totalItems = (q.dragItems || []).length;
        const isComplete = totalItems > 0 && Object.keys(placed).length >= totalItems;
        renderQuestion();
        if (isComplete) scheduleAutoAdvance();
      });
    });

    // Pool drop (to remove from zone)
    pool.addEventListener('dragover', e => e.preventDefault());
    pool.addEventListener('drop', e => {
      e.preventDefault();
      const did = e.dataTransfer.getData('text/plain');
      if (did && placed[did]) {
        delete placed[did];
        studentAnswers[qId] = { ...placed };
        renderQuestion();
      }
    });

    // Click chip in zone to return to pool
    $('questionArea').querySelectorAll('.tq-dd-zone .tq-dd-chip').forEach(chip => {
      chip.addEventListener('click', () => {
        const did = chip.dataset.did;
        if (placed[did]) {
          delete placed[did];
          studentAnswers[qId] = { ...placed };
          renderQuestion();
        }
      });
    });
  }

  /* ══════════════════════════════════════
     QUESTION TYPE: Matching / Connecting
     ══════════════════════════════════════ */
  let matchSelection = null; // { side: 'left'|'right', id/text }

  function renderMatching(q, qId) {
    const matched = studentAnswers[qId] || {}; // { answerId: matchTarget }
    const matchedLeftIds = Object.keys(matched).map(Number);
    const matchedRightVals = Object.values(matched);

    let html = '<div class="tq-match-container">';

    // Left column
    html += '<div class="tq-match-col"><h4>Items</h4>';
    (q.leftItems || []).forEach(item => {
      const isMatched = matchedLeftIds.includes(item.id);
      html += `<div class="tq-match-item ${isMatched ? 'matched' : ''}" data-side="left" data-mid="${item.id}">${esc(item.text)}</div>`;
    });
    html += '</div>';

    // Right column
    html += '<div class="tq-match-col"><h4>Matches</h4>';
    (q.rightItems || []).forEach(rt => {
      const isMatched = matchedRightVals.includes(rt);
      html += `<div class="tq-match-item ${isMatched ? 'matched' : ''}" data-side="right" data-mval="${esc(rt)}">${esc(rt)}</div>`;
    });
    html += '</div>';
    html += '</div>';

    // Matched pairs display
    const pairEntries = Object.entries(matched);
    if (pairEntries.length > 0) {
      html += '<div class="tq-match-pairs-list">';
      pairEntries.forEach(([aid, target]) => {
        const left = (q.leftItems || []).find(l => l.id === +aid);
        html += `<span class="tq-match-pair-tag" data-unpair="${aid}" title="Click to remove">${esc(left?.text || '?')} ↔ ${esc(target)}</span>`;
      });
      html += '</div>';
    }

    return html;
  }

  function bindMatching(q, qId) {
    const matched = studentAnswers[qId] || {};
    matchSelection = null;

    $('questionArea').querySelectorAll('.tq-match-item:not(.matched)').forEach(item => {
      item.addEventListener('click', () => {
        const side = item.dataset.side;
        if (side === 'left') {
          const id = +item.dataset.mid;
          if (matchSelection && matchSelection.side === 'right') {
            // Complete the match
            matched[String(id)] = matchSelection.val;
            studentAnswers[qId] = { ...matched };
            const totalPairs = (q.leftItems || []).length;
            const isComplete = totalPairs > 0 && Object.keys(matched).length >= totalPairs;
            matchSelection = null;
            renderQuestion();
            if (isComplete) scheduleAutoAdvance();
          } else {
            // Select left
            clearMatchHighlight();
            item.classList.add('selected');
            matchSelection = { side: 'left', id };
          }
        } else {
          const val = item.dataset.mval;
          if (matchSelection && matchSelection.side === 'left') {
            // Complete the match
            matched[String(matchSelection.id)] = val;
            studentAnswers[qId] = { ...matched };
            const totalPairs = (q.leftItems || []).length;
            const isComplete = totalPairs > 0 && Object.keys(matched).length >= totalPairs;
            matchSelection = null;
            renderQuestion();
            if (isComplete) scheduleAutoAdvance();
          } else {
            // Select right
            clearMatchHighlight();
            item.classList.add('selected');
            matchSelection = { side: 'right', val };
          }
        }
      });
    });

    // Unpair
    $('questionArea').querySelectorAll('.tq-match-pair-tag').forEach(tag => {
      tag.addEventListener('click', () => {
        const aid = tag.dataset.unpair;
        delete matched[aid];
        studentAnswers[qId] = { ...matched };
        renderQuestion();
      });
    });
  }

  function clearMatchHighlight() {
    $('questionArea').querySelectorAll('.tq-match-item.selected').forEach(el => el.classList.remove('selected'));
  }

  /* ══════════════════════════════════════
     SUBMIT QUIZ
     ══════════════════════════════════════ */
  async function submitQuiz() {
    stopTimer();

    const unanswered = questionList.filter(q => studentAnswers[q.id] === undefined).length;
    if (unanswered > 0 && !confirm(`You have ${unanswered} unanswered question(s). Submit anyway?`)) {
      return;
    }

    if (isPreview) {
      showResults({ score: 0, maxScore: 0, percentage: 0, passed: false, xpEarned: 0, attemptNumber: 0 });
      return;
    }

    const timeSpent = Math.round((Date.now() - startTime) / 1000);

    // Build answers object keyed by question ID
    const answersPayload = {};
    questionList.forEach(q => {
      if (studentAnswers[q.id] !== undefined) {
        answersPayload[q.id] = studentAnswers[q.id];
      }
    });

    try {
      const res = await apiFetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'submit',
          quizId: quizData.quiz.id,
          answers: answersPayload,
          timeSpent,
        }),
      });
      const json = await res.json();
      if (!json.success) throw new Error(json.message);
      showResults(json.data);
    } catch (e) {
      alert('Submission error: ' + e.message);
    }
  }

  /* ══════════════════════════════════════
     RESULTS SCREEN
     ══════════════════════════════════════ */
  function showResults(data) {
    showScreen('quizResultScreen');

    const pct = Math.round(data.percentage || 0);
    const passed = data.passed;

    // Theme result card based on outcome
    const resultCard = document.querySelector('.tq-result-card');
    if (resultCard) resultCard.classList.toggle('passed', !!passed);

    $('resultEmoji').textContent = passed ? '🎉' : (pct >= 50 ? '😊' : '😔');
    $('resultTitle').textContent = passed ? 'You Passed!' : (pct >= 50 ? 'Almost There!' : 'Keep Practicing!');
    window.dispatchEvent(new CustomEvent('petReact', { detail: { type: passed ? 'complete' : 'encourage' } }));

    const showScore = data.showScore !== false && (quizData?.quiz?.showScore !== false);
    if (showScore) {
      $('resultScore').textContent = `${data.score} / ${data.maxScore}`;
      $('resultFill').style.width = pct + '%';
      $('resultPct').textContent = pct + '%';
      document.querySelector('.tq-result-bar')?.classList.remove('hidden');
    } else {
      $('resultScore').textContent = passed ? '✓ Passed' : '✗ Not Passed';
      document.querySelector('.tq-result-bar')?.classList.add('hidden');
      $('resultPct').textContent = '';
    }
    // Stats grid
    const statsEl = $('resultStats');
    if (statsEl) {
      if (showScore && Array.isArray(data.results) && data.results.length > 0) {
        const correct = data.results.filter(r => r.correct).length;
        const wrong = data.results.length - correct;
        statsEl.innerHTML = `
          <div class="tq-stat-item tq-stat-correct">
            <span class="tq-stat-val">${correct}</span>
            <span class="tq-stat-label">✅ Correct</span>
          </div>
          <div class="tq-stat-item tq-stat-wrong">
            <span class="tq-stat-val">${wrong}</span>
            <span class="tq-stat-label">❌ Wrong</span>
          </div>
          <div class="tq-stat-item">
            <span class="tq-stat-val">#${data.attemptNumber}</span>
            <span class="tq-stat-label">Attempt</span>
          </div>`;
      } else {
        statsEl.innerHTML = `
          <div class="tq-stat-item">
            <span class="tq-stat-val">#${data.attemptNumber}</span>
            <span class="tq-stat-label">Attempt</span>
          </div>`;
      }
    }

    let meta = `Attempt #${data.attemptNumber}`;
    if (quizData?.quiz?.passPercentage) meta += ` • Need ${quizData.quiz.passPercentage}% to pass`;
    $('resultMeta').textContent = meta;

    if (data.xpEarned > 0) {
      $('xpEarned').textContent = data.xpEarned;
      $('resultXP').classList.remove('hidden');
    } else {
      $('resultXP').classList.add('hidden');
    }

    // Retry button
    const maxAttempts = quizData?.quiz?.maxAttempts || 0;
    const canRetry = maxAttempts === 0 || data.attemptNumber < maxAttempts;
    $('btnRetry').style.display = canRetry && !isPreview ? '' : 'none';

    if (quizMeta) quizMeta.my_attempts = data.attemptNumber;
    if (quizData?.quiz) quizData.quiz.myAttempts = data.attemptNumber;

    // Gamified popup — celebrate quiz completion
    if (!isPreview && window.showGamePopup) {
      const popTitle = passed ? 'Quiz Complete! \uD83C\uDF89' : pct >= 50 ? 'Nice Try! \uD83D\uDCAA' : 'Keep Going! \uD83D\uDCDA';
      const popMsg   = passed
        ? 'You passed the quiz. Fantastic effort — you earned it!'
        : pct >= 50
            ? 'You were so close! Review your answers and try again.'
            : 'Every attempt is practice. You\'ve got this — try again!';
      showGamePopup({
        type:      passed ? 'success' : 'encouragement',
        title:     popTitle,
        icon:      passed ? '\uD83C\uDF89' : pct >= 50 ? '\uD83D\uDCAA' : '\uD83D\uDCDA',
        message:   popMsg,
        confetti:  passed,
        autoClose: 4500,
      });
    }

    // Per-question review breakdown
    const oldBreakdown = document.getElementById('resultBreakdown');
    if (oldBreakdown) oldBreakdown.remove();
    if (Array.isArray(data.results) && data.results.length > 0) {
      const wrap = document.createElement('div');
      wrap.id = 'resultBreakdown';
      wrap.style.marginTop = '1rem';
      wrap.style.textAlign = 'left';
      wrap.innerHTML = `
        <h3 style="margin-bottom:0.5rem;font-size:1rem">Question Review</h3>
        <div style="display:flex;flex-direction:column;gap:0.5rem">
          ${data.results.map((r, idx) => `
            <div style="padding:0.6rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;background:${r.correct ? '#f0fdf4' : '#fef2f2'}">
              <div style="font-weight:700;font-size:0.85rem">Q${idx + 1} ${r.correct ? '✅ Correct' : '❌ Incorrect'}</div>
              ${r.explanation ? `<div style="font-size:0.8rem;color:#475569;margin-top:0.2rem">${esc(r.explanation)}</div>` : ''}
            </div>`).join('')}
        </div>`;
      document.querySelector('.tq-result-card').appendChild(wrap);
    }
  }

  /* ══════════════════════════════════════
     ATTEMPT REVIEW OVERLAY
     ══════════════════════════════════════ */
  async function openAttemptReview(attemptId) {
    const overlay = $('reviewOverlay');
    const body    = $('reviewBody');
    const metaRow = $('reviewMetaRow');
    $('reviewTitle').textContent = '\uD83D\uDCCB Attempt Review';
    metaRow.innerHTML = '';
    body.innerHTML = '<div class="tq-review-loading">Loading review\u2026</div>';
    overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    try {
      const res  = await apiFetch(API + '?action=review&attempt_id=' + attemptId);
      const json = await res.json();
      if (!json.success) throw new Error(json.message || 'Failed to load review');
      renderAttemptReview(json.data.attempt, json.data.questions);
    } catch (e) {
      body.innerHTML = `<p class="tq-review-error">❌ Could not load review: ${esc(e.message)}</p>`;
    }
  }

  function renderAttemptReview(attempt, questions) {
    const body    = $('reviewBody');
    const metaRow = $('reviewMetaRow');
    const title   = $('reviewTitle');

    title.textContent = '\uD83D\uDCCB ' + (attempt.quiz_title || 'Quiz') + ' \u2014 Attempt #' + attempt.attempt_number;

    const pct    = Math.round(attempt.percentage || 0);
    const passed = attempt.passed;
    const date   = attempt.completed_at ? new Date(attempt.completed_at).toLocaleDateString() : '';
    const time   = attempt.time_spent_sec > 0 ? formatTime(attempt.time_spent_sec) : '';

    metaRow.innerHTML = [
      attempt.show_score ? `<span class="tq-rmeta-pill ${passed ? 'pass' : 'fail'}">${passed ? '\u2705 Passed' : '\u274C Failed'} \u2014 ${pct}%</span>` : `<span class="tq-rmeta-pill ${passed ? 'pass' : 'fail'}">${passed ? '\u2705 Passed' : '\u274C Failed'}</span>`,
      attempt.show_score ? `<span class="tq-rmeta-pill">\uD83C\uDFAF ${attempt.score}/${attempt.max_score} pts</span>` : '',
      attempt.xp_earned > 0 ? `<span class="tq-rmeta-pill xp">\u26A1 +${attempt.xp_earned} XP</span>` : '',
      time   ? `<span class="tq-rmeta-pill">\u23F1 ${time}</span>` : '',
      date   ? `<span class="tq-rmeta-pill">\uD83D\uDCC5 ${date}</span>` : '',
    ].join('');

    if (!questions || questions.length === 0) {
      body.innerHTML = '<p class="tq-review-empty">No question data available for this attempt.</p>';
      return;
    }

    const items = questions.map((q, idx) => {
      const icon  = q.correct ? '\u2705' : '\u274C';
      const bg    = q.correct ? 'tq-rq-correct' : 'tq-rq-wrong';
      const stuLines = q.student_answer ? q.student_answer.split('\n').map(l => esc(l)).join('<br>') : '(no answer)';
      const corLines = q.correct_answer  ? q.correct_answer.split('\n').map(l => esc(l)).join('<br>') : null;

      const typeLabel = { multiple_choice:'Multiple Choice', fill_blank:'Fill in the Blank', matching:'Matching', drag_drop:'Drag & Drop', choose_from_box:'Choose from Box' }[q.type] || q.type;

      return `<div class="tq-rq-card ${bg}">
        <div class="tq-rq-header">
          <span class="tq-rq-num">${icon} Q${idx + 1}</span>
          <span class="tq-rq-type">${typeLabel}</span>
          <span class="tq-rq-pts">${q.points} pt${q.points !== 1 ? 's' : ''}</span>
        </div>
        <div class="tq-rq-text">${esc(q.text)}</div>
        ${q.image ? `<img src="../../EDUQUEST/uploads/${esc(q.image)}" class="tq-rq-img" alt="">` : ''}
        <div class="tq-rq-answers">
          <div class="tq-rq-ans-row tq-rq-student">
            <span class="tq-rq-ans-label">Your answer:</span>
            <span class="tq-rq-ans-val">${stuLines}</span>
          </div>
          ${corLines !== null && !q.correct ? `<div class="tq-rq-ans-row tq-rq-correct-ans">
            <span class="tq-rq-ans-label">Correct answer:</span>
            <span class="tq-rq-ans-val">${corLines}</span>
          </div>` : ''}
        </div>
        ${q.explanation ? `<div class="tq-rq-explain">\uD83D\uDCA1 ${esc(q.explanation)}</div>` : ''}
      </div>`;
    }).join('');

    body.innerHTML = `<div class="tq-rq-list">${items}</div>`;
  }

  /* ══════════════════════════════════════
     QUIZ ATTEMPT HISTORY
     ══════════════════════════════════════ */
  async function toggleQuizHistory(quizId) {
    const drawer = document.getElementById('histDrawer_' + quizId);
    if (!drawer) return;
    const isOpen = !drawer.classList.contains('hidden');
    if (isOpen) { drawer.classList.add('hidden'); return; }
    drawer.classList.remove('hidden');
    if (_historyCache[quizId]) { renderQuizHistory(quizId, _historyCache[quizId]); return; }
    drawer.innerHTML = '<p class="tq-history-loading">Loading history\u2026</p>';
    try {
      const res  = await apiFetch('../../EDUQUEST/api/attempt/my_attempts.php?type=quiz&quiz_id=' + quizId);
      const json = await res.json();
      if (!json.success) throw new Error(json.message || 'Failed to load');
      _historyCache[quizId] = json.data.attempts || [];
      renderQuizHistory(quizId, _historyCache[quizId]);
    } catch (e) {
      drawer.innerHTML = '<p class="tq-history-error">Could not load history.</p>';
    }
  }

  function renderQuizHistory(quizId, attempts) {
    const drawer = document.getElementById('histDrawer_' + quizId);
    if (!drawer) return;
    if (attempts.length === 0) {
      drawer.innerHTML = '<p class="tq-history-empty">No completed attempts yet.</p>';
      return;
    }
    const rows = attempts.map((a, i) => {
      const num    = a.attempt_number || (i + 1);
      const pct    = a.percentage != null ? Math.round(a.percentage) + '%' : '\u2014';
      const status = a.is_abandoned ? '\u26a0\ufe0f Abandoned' : a.passed ? '\u2705 Passed' : '\u274c Failed';
      const xp     = a.xp_earned > 0 ? '+' + a.xp_earned + ' XP' : '\u2014';
      const date   = a.completed_at ? new Date(a.completed_at).toLocaleDateString() : '\u2014';
      return `<tr>
        <td>#${num}</td>
        <td>${pct}</td>
        <td>${status}</td>
        <td>${xp}</td>
        <td>${date}</td>
        <td><button class="tq-review-btn" data-attempt-id="${a.id}">\uD83D\uDCCB Review</button></td>
      </tr>`;
    }).join('');
    drawer.innerHTML = `
      <table class="tq-hist-table">
        <thead><tr><th>#</th><th>Score</th><th>Result</th><th>XP</th><th>Date</th><th></th></tr></thead>
        <tbody>${rows}</tbody>
      </table>`;
    drawer.querySelectorAll('.tq-review-btn').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        openAttemptReview(+btn.dataset.attemptId);
      });
    });
  }

  /* ══════════════════════════════════════
     HELPERS
     ══════════════════════════════════════ */
  function showScreen(id) {
    ['quizListScreen', 'quizIntroScreen', 'quizPlayScreen', 'quizResultScreen'].forEach(s => {
      $(s).classList.toggle('hidden', s !== id);
    });
  }

  function apiFetch(url, opts = {}) {
    const token = typeof Auth !== 'undefined' ? Auth.getToken() : localStorage.getItem('eq_token');
    const headers = opts.headers || {};
    if (token) headers['Authorization'] = 'Bearer ' + token;
    return fetch(url, { ...opts, headers });
  }

  function esc(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
  }

  function formatTime(sec) {
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return m + ':' + String(s).padStart(2, '0');
  }

  function getAttemptInfo(quiz) {
    const attempts = toInt(quiz?.myAttempts, quiz?.my_attempts, 0);
    const maxAttempts = toInt(quiz?.maxAttempts, quiz?.max_attempts, 0);
    const exhausted = maxAttempts > 0 && attempts >= maxAttempts;
    return { attempts, maxAttempts, exhausted };
  }

  function toInt(...vals) {
    for (const v of vals) {
      const n = Number(v);
      if (Number.isFinite(n)) return n;
    }
    return 0;
  }
})();
