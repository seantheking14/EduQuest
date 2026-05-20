<?php require_once __DIR__ . '/../gamified_flash.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Activities</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/quiz-builder.css" />
  <link rel="stylesheet" href="../assets/css/gamified_popup.css" />
</head>
<body class="app-page activity-builder-page">

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
      <li><a href="quiz-builder.php">&#128221; Quizzes</a></li>
      <li><a href="activity-builder.php" class="active">🎮 Activities</a></li>
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
         LIST VIEW — all activities
         ══════════════════════════════════════ -->
    <div id="listView">
      <div class="page-header">
        <div>
          <h1>🎮 Activities</h1>
          <p class="muted">Create custom gamified learning activities for your students.</p>
        </div>
        <button class="btn btn-primary" id="btnNewActivity">&#43; Create Activity</button>
      </div>

      <div class="qb-toolbar">
        <input type="search" id="activitySearch" class="search-input" placeholder="Search activities…" />
        <select id="activityCategoryFilter" class="search-input" style="max-width:220px">
          <option value="">All Categories</option>
          <option value="math">🔢 Math</option>
          <option value="english">📖 English</option>
          <option value="selfcare">🌱 Self Care</option>
        </select>
      </div>

      <div id="activityList" class="qb-quiz-list">
        <div class="loading-msg">Loading activities…</div>
      </div>

      <div id="activityEmpty" class="empty-state hidden">
        <span class="empty-icon">🎮</span>
        <h3>No activities yet</h3>
        <p>Create your first activity to get started!</p>
      </div>
    </div>

    <!-- ══════════════════════════════════════
         BUILDER VIEW — create/edit activity
         ══════════════════════════════════════ -->
    <div id="builderView" class="hidden">
      <div class="page-header">
        <div>
          <a href="#" class="qb-back-link" id="btnBackToList">&larr; Back to Activities</a>
          <h1 id="builderTitle">Create New Activity</h1>
        </div>
        <div class="qb-header-actions" id="builderHeaderActions">
          <button class="btn btn-outline" id="btnPreviewActivity">👁️ Preview</button>
          <button class="btn btn-primary" id="btnSaveActivity">💾 Save Activity</button>
        </div>
      </div>

      <!-- Detail tabs — only visible when viewing an existing activity -->
      <div class="qb-detail-tabs hidden" id="activityDetailTabs">
        <button class="qb-detail-tab active" data-tab="edit">✏️ Edit</button>
        <button class="qb-detail-tab" data-tab="results">📊 Results</button>
        <button class="qb-detail-tab" data-tab="assign">📝 Assign</button>
        <button class="qb-detail-tab" data-tab="duplicate">🔄 Duplicate</button>
      </div>

      <!-- ── Edit pane ── -->
      <div class="qb-tab-pane" id="activityPane-edit">
      <div id="builderAlert" class="alert hidden" style="margin-bottom:1rem"></div>

      <!-- Activity Settings -->
      <div class="qb-settings card">
        <div class="act-settings-header">
          <span class="act-settings-icon">⚙️</span>
          <h3 class="act-settings-title">Activity Settings</h3>
        </div>

        <!-- ── Section 1: Required Info (Blue) ── -->
        <div class="act-section act-sec-required">
          <div class="act-sec-head">
            <span class="act-sec-num">1</span>
            <span class="act-sec-label">Required Info</span>
            <span class="act-req-badge">✱ Fill these first</span>
          </div>
          <div class="act-sec-body">
            <div class="form-group">
              <label class="qb-req-label" for="actTitle">Activity Title <span class="qb-req-star">*</span></label>
              <input type="text" id="actTitle" class="form-input" placeholder="e.g., Arrange Numbers Ascending" />
            </div>
            <div class="form-group">
            <div class="form-row">
              <div class="form-group">
                <label class="qb-req-label" for="actCategory">Category <span class="qb-req-star">*</span></label>
                <select id="actCategory" class="form-input">
                  <option value="">— Select Category —</option>
                  <option value="math">🔢 Math</option>
                  <option value="english">📖 English</option>
                  <option value="selfcare">🌱 Self Care</option>
                </select>
              </div>
              <div class="form-group">
                <label class="qb-req-label" for="actType">Activity Type <span class="qb-req-star">*</span></label>
                <select id="actType" class="form-input">
                  <option value="sort-order">Sort / Order</option>
                  <option value="classify">Classify / Sort Categories</option>
                  <option value="compare">Compare</option>
                  <option value="choose">Multiple Choice</option>
                  <option value="build-word">Build Word</option>
                  <option value="truefalse">True or False</option>
                  <option value="match-pairs">Match Pairs</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Section 2: Content Details (Green) ── -->
        <div class="act-section act-sec-content">
          <div class="act-sec-head">
            <span class="act-sec-num">2</span>
            <span class="act-sec-label">Content Details</span>
            <span class="act-opt-badge">Optional</span>
          </div>
          <div class="act-sec-body">
            <div class="form-group">
              <label for="actDescription">Description</label>
              <textarea id="actDescription" class="form-input" placeholder="Brief description of the activity" rows="2"></textarea>
            </div>
            <div class="form-group">
              <label for="actInstructions">Instructions <span class="muted">(shown during gameplay)</span></label>
              <textarea id="actInstructions" class="form-input" placeholder="Instructions shown during gameplay" rows="2"></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="actIcon">Icon (emoji)</label>
                <input type="text" id="actIcon" class="form-input" placeholder="🎮" maxlength="2" />
              </div>
              <div class="form-group">
                <label for="actRounds">Number of Rounds</label>
                <input type="number" id="actRounds" class="form-input" value="6" min="1" max="50" />
              </div>
            </div>
          </div>
        </div>

        <!-- ── Section 3: Game Settings (Amber) ── -->
        <div class="act-section act-sec-game">
          <div class="act-sec-head">
            <span class="act-sec-num">3</span>
            <span class="act-sec-label">Game Settings</span>
            <span class="act-opt-badge">Optional</span>
          </div>
          <div class="act-sec-body">
            <div class="form-row">
              <div class="form-group">
                <label for="actXP">XP Reward</label>
                <input type="number" id="actXP" class="form-input" value="50" min="0" />
              </div>
              <div class="form-group">
                <label for="actPassPercentage">Pass % <span class="muted">(0–100)</span></label>
                <input type="number" id="actPassPercentage" class="form-input" value="70" min="0" max="100" />
              </div>
              <div class="form-group">
                <label for="actMaxAttempts">Max Attempts <span class="muted">(0 = unlimited)</span></label>
                <input type="number" id="actMaxAttempts" class="form-input" value="0" min="0" />
              </div>
              <div class="form-group">
                <label for="actTimeLimit">Time Limit <span class="muted">(sec, 0 = none)</span></label>
                <input type="number" id="actTimeLimit" class="form-input" value="0" min="0" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Activity Items/Rounds -->
      <div class="qb-questions card" style="margin-top:2rem;">
        <div class="card-header">
          <h3 class="card-title">Activity Items</h3>
          <button type="button" class="btn btn-sm btn-primary" id="btnAddItem">+ Add Item</button>
        </div>

        <div id="itemsList" class="qb-questions-list">
          <!-- Items added dynamically -->
        </div>
      </div>

      </div>

      <!-- ── Results tab ── -->
      <div class="qb-tab-pane hidden" id="activityPane-results">
        <div class="card">
          <h3>Student Attempts</h3>
          <div id="resultsContent" class="muted">Select an activity to view results.</div>
        </div>
      </div>

      <!-- ── Assign tab ── -->
      <div class="qb-tab-pane hidden" id="activityPane-assign">
        <div class="card">
          <h3>Assign Activity</h3>
          <div id="assignContent" class="muted">Select an activity to assign to students.</div>
        </div>
      </div>

      <!-- ── Duplicate tab ── -->
      <div class="qb-tab-pane hidden" id="activityPane-duplicate">
        <div class="card">
          <h3>Duplicate Activity</h3>
          <p class="muted" style="margin-bottom:1rem;">Create a copy of this activity with all its items.</p>
          <button type="button" class="btn btn-primary" id="btnConfirmDuplicate">🔄 Duplicate Now</button>
        </div>
      </div>

    </div>

  </main>

  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/gamified_popup.js"></script>
  <script src="../assets/js/activity-builder.js" defer></script>
</body>
</html>
