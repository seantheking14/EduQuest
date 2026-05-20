<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Quizzes </title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/quiz-builder.css" />
  <link rel="stylesheet" href="../assets/css/gamified_popup.css" />
</head>
<body class="app-page quiz-builder-page">

  <nav class="sidebar">
    <div class="sidebar-logo"><span>&#127891;</span> EduQuest</div>
    <ul class="sidebar-nav">
      <li class="nav-section-label">Overview</li>
      <li><a href="dashboard.php">&#127968; Dashboard</a></li>
      <li class="nav-section-label">Students</li>
      <li><a href="students.php">&#128100; My Students</a></li>
      <li><a href="student-form.php">&#43; Add Student</a></li>
      <li class="nav-section-label">Academic</li>
      <li><a href="courses.php">&#128218; My Courses</a></li>
      <li><a href="quiz-builder.php" class="active">&#128221; Quizzes</a></li>
      <li><a href="activity-builder.php">🎮 Activities</a></li>
      <li><a href="grade-analytics.php">&#127942; Grade Analytics</a></li>
      <li class="nav-section-label">Insights</li>
      <li><a href="analytics.php">&#128202; Analytics</a></li>
      <li><a href="behavioral-logs.php">&#128203; Behavioral Logs</a></li>
      <li class="nav-section-label">Settings</li>
      <li><a href="gamification-settings.php">&#127918; Gamification</a></li>
      <li><a href="profile.php">&#128100; My Profile</a></li>
    </ul>
    <div class="sidebar-footer">
      <div class="sf-info">
        <div class="sf-avatar" id="teacherAvatarInitials">T</div>
        <div class="sf-details">
          <span id="teacherName">Loading…</span>
          <span class="sf-role">Teacher</span>
        </div>
      </div>
      <button id="logoutBtn" class="btn btn-outline btn-sm" style="margin-top:0.5rem">Sign Out</button>
    </div>
  </nav>

  <main class="main-content">

    <!-- ══════════════════════════════════════
         LIST VIEW — all quizzes
         ══════════════════════════════════════ -->
    <div id="listView">
      <div class="page-header">
        <div>
          <h1>&#128221; Quizzes </h1>
          <p class="muted">Create custom quizzes with multiple question types.</p>
        </div>
        <button class="btn btn-primary" id="btnNewQuiz">&#43; Create Quiz</button>
      </div>

      <div class="qb-toolbar">
        <input type="search" id="quizSearch" class="search-input" placeholder="Search quizzes…" />
        <select id="quizCourseFilter" class="search-input" style="max-width:220px">
          <option value="">All Courses</option>
        </select>
      </div>

      <div id="quizList" class="qb-quiz-list">
        <div class="loading-msg">Loading quizzes…</div>
      </div>

      <div id="quizEmpty" class="empty-state hidden">
        <span class="empty-icon">&#128221;</span>
        <h3>No quizzes yet</h3>
        <p>Create your first quiz to get started!</p>
      </div>
    </div>

    <!-- ══════════════════════════════════════
         BUILDER VIEW — create/edit quiz
         ══════════════════════════════════════ -->
    <div id="builderView" class="hidden">
      <div class="page-header">
        <div>
          <a href="#" class="qb-back-link" id="btnBackToList">&larr; Back to Quizzes</a>
          <h1 id="builderTitle">Create New Quiz</h1>
        </div>
        <div class="qb-header-actions" id="builderHeaderActions">
          <button class="btn btn-outline" id="btnPreview">&#128065; Preview</button>
          <button class="qu-upload-btn" id="btnUploadQuiz">&#128228; Upload Quiz File</button>
          <button class="btn btn-primary" id="btnSaveQuiz">&#128190; Save Quiz</button>
        </div>
      </div>

      <!-- Detail tabs — only visible when viewing an existing quiz -->
      <div class="qb-detail-tabs hidden" id="quizDetailTabs">
        <button class="qb-detail-tab active" data-tab="edit">&#9998; Edit</button>
        <button class="qb-detail-tab" data-tab="results">&#128202; Results</button>
        <button class="qb-detail-tab" data-tab="assign">&#128203; Assign</button>
        <button class="qb-detail-tab" data-tab="duplicate">&#128196; Duplicate</button>
      </div>

      <!-- ── Edit pane ── -->
      <div class="qb-tab-pane" id="quizPane-edit">
      <div id="builderAlert" class="alert hidden" style="margin-bottom:1rem"></div>

      <!-- Quiz Settings -->
      <div class="qb-settings card">
        <div class="card-header"><h3>&#9881; Quiz Settings</h3></div>
        <div class="qb-settings-body">
          <div class="form-row">
            <div class="form-group" style="flex:2">
              <label class="qb-req-label">Quiz Title <span class="qb-req-star">*</span></label>
              <input type="text" id="qTitle" placeholder="e.g. Week 3 Math Review" required />
            </div>
            <div class="form-group">
              <label class="qb-req-label">Course <span class="qb-req-star">*</span></label>
              <select id="qCourse" required><option value="">— Select a Course —</option></select>
            </div>
          </div>
          <div class="form-group">
            <label>Description <span class="muted">(optional)</span></label>
            <textarea id="qDescription" rows="2" placeholder="Brief description of this quiz…"></textarea>
          </div>
          <div class="form-group">
            <label>Instructions <span class="muted">(shown before starting)</span></label>
            <textarea id="qInstructions" rows="2" placeholder="e.g. Read each question carefully. You have 30 minutes."></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Pass % <span class="muted">(0–100)</span></label>
              <input type="number" id="qPass" min="0" max="100" value="70" />
            </div>
            <div class="form-group">
              <label>Max Attempts <span class="muted">(0=unlimited)</span></label>
              <input type="number" id="qAttempts" min="0" value="0" />
            </div>
            <div class="form-group">
              <label>Time Limit <span class="muted">(seconds, 0=none)</span></label>
              <input type="number" id="qTime" min="0" value="0" />
            </div>
            <div class="form-group">
              <label>XP Reward</label>
              <input type="number" id="qXP" min="0" value="50" />
            </div>
          </div>
          <div class="form-row">
            <label class="checkbox-item">
              <input type="checkbox" id="qShuffleQ" checked /> Shuffle questions
            </label>
            <label class="checkbox-item">
              <input type="checkbox" id="qShuffleA" checked /> Shuffle answers
            </label>
            <label class="checkbox-item">
              <input type="checkbox" id="qShowScore" checked /> Show score to students after completing
            </label>
          </div>
        </div>
      </div>

      <!-- Assigned Students Indicator — populated by JS when a quiz is opened -->
      <div id="editAssignedSummary" class="qb-assigned-summary" style="display:none"></div>

      <!-- Questions List -->
      <div class="qb-questions-header">
        <h3>&#128220; Questions <span id="questionCount" class="muted">(0)</span></h3>
        <div class="qb-builder-tools">
          <div class="qb-add-menu">
            <button class="btn btn-primary btn-sm" id="btnAddQuestion">&#43; Add Question</button>
            <div class="qb-type-dropdown hidden" id="typeDropdown">
              <button class="qb-type-opt" data-type="multiple_choice">&#9745; Multiple Choice</button>
              <button class="qb-type-opt" data-type="fill_blank">&#9998; Fill in the Blank</button>
              <button class="qb-type-opt" data-type="matching">&#128279; Matching / Connecting</button>
              <button class="qb-type-opt" data-type="drag_drop">&#128229; Drag &amp; Drop</button>
            </div>
          </div>
          <button class="btn btn-outline btn-sm" id="btnBulkAdd">Bulk Add</button>
          <button class="btn btn-outline btn-sm" id="btnExpandAll">Expand All</button>
          <button class="btn btn-outline btn-sm" id="btnCollapseAll">Collapse All</button>
          <select id="jumpQuestion" class="search-input qb-jump-select">
            <option value="">Jump to question...</option>
          </select>
          <div class="qb-move-inline">
            <input type="number" id="moveFrom" min="1" placeholder="From #" />
            <input type="number" id="moveTo" min="1" placeholder="To #" />
            <button class="btn btn-outline btn-sm" id="btnMoveQuestion">Move</button>
          </div>
        </div>
      </div>

      <div id="questionsList" class="qb-questions-list"></div>

      <div id="questionsEmpty" class="empty-state">
        <span class="empty-icon">&#128220;</span>
        <h3>No questions yet</h3>
        <p>Click "Add Question" to start building your quiz.</p>
      </div>
      </div><!-- /quizPane-edit -->

      <!-- ── Results pane ── -->
      <div class="qb-tab-pane hidden" id="quizPane-results">
        <div id="resultsContent">
          <div class="loading-msg">Loading results…</div>
        </div>
      </div>

      <!-- ── Assign pane ── -->
      <div class="qb-tab-pane hidden" id="quizPane-assign">
        <div class="qb-inline-pane">
          <h3 style="margin:0 0 0.75rem;font-size:1rem;font-weight:700;color:#1e293b">&#128203; Assign Quiz to a Course</h3>
          <!-- Currently-assigned-courses indicator — populated by JS -->
          <div id="assignedCourseSummary" class="qb-assigned-summary"></div>
          <div id="assignAlert" class="alert hidden" style="margin-bottom:1rem"></div>
          <div class="form-group" style="max-width:360px;margin-bottom:0.75rem">
            <label>Course *</label>
            <select id="assignCourse"><option value="">— Select Course —</option></select>
          </div>
          <div class="form-group" style="max-width:240px;margin-bottom:1rem">
            <label>Due Date <span class="muted">(optional)</span></label>
            <input type="date" id="assignDue" />
          </div>
          <button class="btn btn-primary" id="confirmAssign">&#128203; Assign to Course</button>
        </div>

        <hr style="border:none;border-top:1px solid #e2e8f0;margin:1.5rem 0" />

        <div>
          <h3 style="margin:0 0 1rem;font-size:1rem;font-weight:700;color:#1e293b">&#128100; Assign to Individual Students</h3>
          <div id="inlineAssignAlert" class="alert hidden" style="margin-bottom:.75rem"></div>
          <input type="search" id="inlineStudentSearch" placeholder="Search student by name&hellip;" style="width:100%;max-width:360px;padding:.45rem .7rem;border:1px solid #e2e8f0;border-radius:8px;font-size:.87rem;margin-bottom:.6rem;box-sizing:border-box" />
          <div style="margin-bottom:.5rem;display:flex;align-items:center;gap:.5rem;max-width:360px">
            <label class="checkbox-item" style="font-size:.85rem;color:#475569">
              <input type="checkbox" id="inlineSelectAll" /> <strong>Select All</strong>
            </label>
            <span id="inlineMatchCount" style="margin-left:auto;font-size:.78rem;color:#64748b"></span>
          </div>
          <div id="inlineStudentList" style="max-height:220px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;padding:.5rem .75rem;margin-bottom:1rem;max-width:360px"><p class="muted" style="padding:.35rem 0">Open a quiz and switch to this tab to load students.</p></div>
          <div style="display:flex;gap:1rem;flex-wrap:wrap;max-width:360px;margin-bottom:1rem">
            <div class="form-group" style="flex:1;min-width:130px;margin-bottom:0">
              <label style="font-size:.82rem">Due Date <span class="muted">(optional)</span></label>
              <input type="date" id="inlineDueDate" />
            </div>
            <div class="form-group" style="flex:1;min-width:130px;margin-bottom:0">
              <label style="font-size:.82rem">Max Attempts <span class="muted">(0&nbsp;= quiz default)</span></label>
              <input type="number" id="inlineMaxAttempts" min="0" value="0" />
            </div>
          </div>
          <button class="btn btn-primary" id="confirmAssignStudents">&#128100; Assign to Students</button>
        </div>
      </div>

      <!-- ── Duplicate pane ── -->
      <div class="qb-tab-pane hidden" id="quizPane-duplicate">
        <div class="qb-inline-pane qb-dup-pane">
          <div style="font-size:3rem;margin-bottom:0.75rem">&#128196;</div>
          <h3 style="margin:0 0 0.5rem;font-size:1.1rem;color:#1e293b">Duplicate This Quiz</h3>
          <p id="dupQuizTitle" style="color:#64748b;margin-bottom:1.5rem;font-size:0.9rem"></p>
          <p style="color:#475569;margin-bottom:1.5rem;font-size:0.85rem;max-width:360px;margin-left:auto;margin-right:auto">
            A full copy of this quiz will be created with all questions and settings. The copy will be inactive by default.
          </p>
          <button class="btn btn-primary" id="confirmDuplicate">&#128196; Create a Copy</button>
          <div id="dupResult" class="alert hidden" style="margin-top:1rem"></div>
        </div>
      </div>

    </div><!-- /builderView -->

    <!-- ══════════════════════════════════════
         BULK ADD MODAL
         ══════════════════════════════════════ -->
    <div id="bulkAddModal" class="modal-backdrop hidden">
      <div class="modal-box" style="max-width:520px">
        <div class="modal-header">
          <h3>&#9881; Bulk Add Questions</h3>
          <button class="modal-close" id="closeBulkAddModal">&#10005;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>How many questions?</label>
            <input type="number" id="bulkQuestionCount" min="1" max="200" value="10" />
          </div>
          <div class="form-group">
            <label>Question types to use</label>
            <div class="qb-bulk-types">
              <label class="checkbox-item"><input type="checkbox" class="bulk-type-check" value="multiple_choice" checked /> Multiple Choice</label>
              <label class="checkbox-item"><input type="checkbox" class="bulk-type-check" value="fill_blank" /> Fill in the Blank</label>
              <label class="checkbox-item"><input type="checkbox" class="bulk-type-check" value="matching" /> Matching / Connecting</label>
              <label class="checkbox-item"><input type="checkbox" class="bulk-type-check" value="drag_drop" /> Drag &amp; Drop</label>
            </div>
          </div>
          <div class="form-group">
            <p class="muted" style="margin:0;font-size:0.82rem">Tip: Select one question type for faster bulk creation.</p>
          </div>
          <div id="bulkAddAlert" class="alert hidden"></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" id="cancelBulkAdd">Cancel</button>
          <button class="btn btn-primary" id="confirmBulkAdd">Add Questions</button>
        </div>
      </div>
    </div>

  <?php require_once __DIR__ . '/quiz_upload_modal.php'; ?>

    <!-- ══════════════════════════════════════
         ASSIGN TO STUDENTS MODAL
         ══════════════════════════════════════ -->
    <div id="assignStudentsModal" class="modal-backdrop hidden">
      <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
          <h3>&#128101; Assign Quiz to Students</h3>
          <button class="modal-close" id="asCloseBtn">&#10005;</button>
        </div>
        <div class="modal-body">
          <div id="asAlert" class="alert hidden" style="margin-bottom:.75rem"></div>
          <input type="search" id="asStudentSearch" placeholder="Search student by name…" style="width:100%;padding:.45rem .7rem;border:1px solid #e2e8f0;border-radius:8px;font-size:.87rem;margin-bottom:.6rem;box-sizing:border-box" />
          <div style="margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem">
            <label class="checkbox-item" style="font-size:.85rem;color:#475569">
              <input type="checkbox" id="asSelectAll" /> <strong>Select All</strong>
            </label>
            <span id="asMatchCount" style="margin-left:auto;font-size:.78rem;color:#64748b"></span>
          </div>
          <div id="asStudentList" style="max-height:220px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;padding:.5rem .75rem;margin-bottom:1rem"></div>
          <div style="display:flex;gap:1rem;flex-wrap:wrap">
            <div class="form-group" style="flex:1;min-width:130px;margin-bottom:0">
              <label style="font-size:.82rem">Due Date <span class="muted">(optional)</span></label>
              <input type="date" id="asDueDate" />
            </div>
            <div class="form-group" style="flex:1;min-width:130px;margin-bottom:0">
              <label style="font-size:.82rem">Max Attempts <span class="muted">(0 = quiz default)</span></label>
              <input type="number" id="asMaxAttempts" min="0" value="0" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" id="asCancelBtn">Cancel</button>
          <button class="btn btn-primary" id="asConfirmBtn">Assign</button>
        </div>
      </div>
    </div>

  </main>

  <!-- ══════════════════════════════════════
       GRADE OVERRIDE OVERLAY
       ══════════════════════════════════════ -->
  <div id="gradeOverlay" class="qb-grade-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="gradeTitle">
    <div class="qb-grade-panel">

      <!-- header -->
      <div class="qb-grade-header">
        <div class="qb-grade-title-row">
          <span id="gradeTitle" class="qb-grade-title">✏️ Grade Submission</span>
          <button id="btnCloseGrade" class="qb-grade-close" aria-label="Close">✕</button>
        </div>
        <div id="gradeMetaRow" class="qb-grade-meta-row"></div>
      </div>

      <!-- scrollable question list -->
      <div class="qb-grade-body" id="gradeBody">
        <div class="qb-grade-loading">Loading submission…</div>
      </div>

      <!-- override footer -->
      <div class="qb-grade-footer" id="gradeFooter" style="display:none">
        <div class="qb-grade-divider"></div>
        <p class="qb-grade-override-title">Override Final Score</p>
        <div class="qb-grade-override-row">
          <input type="number" id="gradeScoreInput" class="qb-grade-score-input" min="0" placeholder="0" />
          <span id="gradeMaxScore" class="qb-grade-max-pts">/ 0 pts</span>
          <span id="gradeScorePct" class="qb-grade-score-pct">0%</span>
        </div>
        <div class="qb-grade-override-row" style="margin-top:.6rem">
          <input type="text" id="gradeNotesInput" class="qb-grade-notes-input" placeholder="Optional notes for your record…" />
        </div>
        <div id="gradeAlert" class="qb-grade-alert hidden"></div>
        <div class="qb-grade-footer-actions">
          <button id="btnSaveGrade" class="btn btn-primary qb-grade-save-btn">💾 Save Override</button>
        </div>
      </div>

    </div>
  </div>

  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/gamified_popup.js"></script>
  <script src="../assets/js/quiz-builder.js"></script>
</body>
</html>
