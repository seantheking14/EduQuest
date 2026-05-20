<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Gamification</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/gamification-settings.css" />
  <style>








  </style>
</head>
<body class="app-page">

  <!-- Sidebar -->
  <nav class="sidebar">
    <div class="sidebar-logo">
      <span>&#127891;</span> EduQuest
    </div>
    <ul class="sidebar-nav">
      <li class="nav-section-label">Overview</li>
      <li><a href="dashboard.php">&#127968; Dashboard</a></li>
      <li class="nav-section-label">Students</li>
      <li><a href="students.php">&#128100; My Students</a></li>
      <li><a href="student-form.php">&#43; Add Student</a></li>
      <li class="nav-section-label">Academic</li>
      <li><a href="courses.php">&#128218; My Courses</a></li>
      <li><a href="quiz-builder.php">&#128221; Quizzes</a></li>
      <li><a href="activity-builder.php">🎮 Activities</a></li>
      <li><a href="grade-analytics.php">&#127942; Grade Analytics</a></li>
      <li class="nav-section-label">Insights</li>
      <li><a href="analytics.php">&#128202; Analytics</a></li>
      <li><a href="behavioral-logs.php">&#128203; Behavioral Logs</a></li>
      <li class="nav-section-label">Settings</li>
      <li><a href="gamification-settings.php" class="active">&#127918; Gamification</a></li>
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

  <!-- Main Content -->
  <main class="main-content">

    <header class="page-header">
      <div>
        <h1>&#127918; Gamification</h1>
        <p class="muted">Manage XP rules, leaderboards, pacing, and game assignments for your class.</p>
      </div>
      <?php require_once 'notifications.php'; ?>
    </header>

    <!-- Alert banners -->
    <div id="successAlert" class="gs-alert success" style="display:none">Settings saved successfully!</div>
    <div id="errorAlert"   class="gs-alert error"   style="display:none">Failed to save settings.</div>

    <!-- Attention note -->
    <div class="gs-note">
      <span class="gs-note-icon">&#9888;&#65039;</span>
      <div>
        <strong>Note for attention-sensitive classrooms:</strong>
        Use timers, leaderboard visibility, and daily XP caps intentionally so the system stays motivating without becoming overstimulating.
      </div>
    </div>

    <!-- ── Student Overview ──────────────────────────────────── -->
    <div class="gs-overview" id="overviewSection">
      <div class="gs-stat-card">
        <div class="gs-stat-icon">&#128101;</div>
        <div class="gs-stat-value" id="overviewStudents">–</div>
        <div class="gs-stat-label">Active Students</div>
      </div>
      <div class="gs-stat-card">
        <div class="gs-stat-icon">&#9889;</div>
        <div class="gs-stat-value" id="overviewAvgXp">–</div>
        <div class="gs-stat-label">Average XP</div>
      </div>
      <div class="gs-stat-card">
        <div class="gs-stat-icon">&#128200;</div>
        <div class="gs-stat-value" id="overviewAvgLevel">–</div>
        <div class="gs-stat-label">Avg Level</div>
      </div>
      <div class="gs-stat-card">
        <div class="gs-stat-icon">&#9876;&#65039;</div>
        <div class="gs-stat-value" id="overviewTeams">–</div>
        <div class="gs-stat-label">Teams</div>
        <div class="gs-team-badges" id="teamBreakdown"></div>
      </div>
    </div>

    <!-- ── Section Tabs ──────────────────────────────────────── -->
    <div class="gs-tabs">
      <button class="gs-tab active"  data-gs-panel="panel-settings">&#9881;&#65039; Class Settings</button>
      <button class="gs-tab"         data-gs-panel="panel-override">&#128100; Per-Student</button>
      <button class="gs-tab"         data-gs-panel="panel-xp">&#127873; Award XP</button>
      <button class="gs-tab"         data-gs-panel="panel-games">&#127918; Mini-Games</button>
    </div>

    <!-- ══════════════════════════════════════════════════════
         PANEL 1 — Class Settings
         ══════════════════════════════════════════════════════ -->
    <div class="gs-panel" id="panel-settings">

      <!-- Leaderboard -->
      <div class="gs-card">
        <div class="gs-card-head">
          <span class="gs-card-title">&#128081; Leaderboard</span>
          <div style="display:flex;align-items:center;gap:0.6rem">
            <span id="lbSaveStatus" class="save-status"></span>
            <button class="btn btn-primary btn-sm" id="saveLeaderboardBtn">Save</button>
          </div>
        </div>
        <div class="gs-card-body">
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">Leaderboard Mode</div>
              <div class="setting-desc">Choose how rankings are shown for your class.</div>
            </div>
            <select class="setting-select" id="settLeaderboardMode">
              <option value="individual">Leading Team</option>
              <option value="top_only">Top Adventurers (Top students)</option>
              <option value="disabled">Disabled Ranking</option>
            </select>
          </div>
        </div>
      </div>

      <!-- XP & Difficulty -->
      <div class="gs-card">
        <div class="gs-card-head">
          <span class="gs-card-title">&#9889; XP &amp; Difficulty</span>
          <div style="display:flex;align-items:center;gap:0.6rem">
            <span id="xpSaveStatus" class="save-status"></span>
            <button class="btn btn-primary btn-sm" id="saveXpDiffBtn">Save</button>
          </div>
        </div>
        <div class="gs-card-body">
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">XP Multiplier</div>
              <div class="setting-desc">Scale all XP rewards (0.5× = half, 2× = double)</div>
            </div>
            <div class="setting-range-wrapper">
              <input type="range" id="settXpMultiplier" min="0.5" max="3" step="0.1" value="1">
              <span class="range-value" id="xpMultiplierValue">1.0x</span>
            </div>
          </div>
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">Daily XP Cap</div>
              <div class="setting-desc">Maximum XP a student can earn per day (prevents burnout)</div>
            </div>
            <input type="number" class="setting-number" id="settMaxDailyXp" value="500" min="50" max="5000" step="50">
          </div>
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">Difficulty Level</div>
              <div class="setting-desc">Adjusts pacing and requirements for the class</div>
            </div>
            <select class="setting-select" id="settDifficulty">
              <option value="easy">Easy (slower level-ups, generous rewards)</option>
              <option value="moderate" selected>Moderate (balanced)</option>
              <option value="challenging">Challenging (faster-paced)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Accessibility & Pacing -->
      <div class="gs-card">
        <div class="gs-card-head">
          <span class="gs-card-title">&#9851;&#65039; Accessibility &amp; Pacing</span>
          <div style="display:flex;align-items:center;gap:0.6rem">
            <span id="paceSaveStatus" class="save-status"></span>
            <button class="btn btn-primary btn-sm" id="savePacingBtn">Save</button>
          </div>
        </div>
        <div class="gs-card-body">
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">Quiz Timer (per question)</div>
              <div class="setting-desc">Seconds per quiz question. Set to 0 to disable the timer.</div>
            </div>
            <div class="setting-range-wrapper">
              <input type="range" id="settQuizTimer" min="0" max="120" step="5" value="30">
              <span class="range-value" id="quizTimerValue">30s</span>
            </div>
          </div>
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">Game Timer (per round)</div>
              <div class="setting-desc">Seconds per mini-game round in Quests. Set to 0 to disable.</div>
            </div>
            <div class="setting-range-wrapper">
              <input type="range" id="settGameTimer" min="0" max="120" step="5" value="30">
              <span class="range-value" id="gameTimerValue">30s</span>
            </div>
          </div>
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">Show Activity Score to Students</div>
              <div class="setting-desc">When enabled, students can see their score after completing mini-game activities. Disable to show only completion.</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" id="settShowGameScore" checked>
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>
      </div>

    </div><!-- /panel-settings -->

    <!-- ══════════════════════════════════════════════════════
         PANEL 2 — Per-Student Overrides
         ══════════════════════════════════════════════════════ -->
    <div class="gs-panel hidden" id="panel-override">

      <div class="gs-note info">
        <span class="gs-note-icon">&#9432;</span>
        <div>
          Check a setting to <strong>override</strong> it for this student only.
          These values take priority over class-wide settings for the selected student.
        </div>
      </div>

      <!-- Student picker -->
      <div class="gs-card">
        <div class="gs-card-head">
          <span class="gs-card-title">&#128100; Select Student</span>
        </div>
        <div class="gs-card-body">
          <select id="overrideStudentPicker" class="setting-select" style="width:100%;max-width:380px">
            <option value="">— Choose a student —</option>
          </select>
        </div>
      </div>

      <!-- Override controls (hidden until student chosen) -->
      <div id="overrideBody" style="display:none">
        <div id="overrideToast" class="game-toast"></div>

        <!-- Leaderboard Overrides -->
        <div class="gs-card">
          <div class="gs-card-head">
            <span class="gs-card-title">&#128081; Leaderboard</span>
          </div>
          <div class="gs-card-body">
            <div class="ovr-row">
              <input type="checkbox" class="ovr-check" id="ovrLeaderboardMode">
              <span class="ovr-label">Leaderboard Mode <span class="ovr-hint" id="ovrLeaderboardModeHint"></span></span>
              <select class="setting-select ovr-control" id="ovrLeaderboardModeVal" disabled>
                <option value="individual">Leading Team</option>
                <option value="top_only">Top Adventurers</option>
                <option value="disabled">Disabled Ranking</option>
              </select>
            </div>
          </div>
        </div>

        <!-- XP & Difficulty Overrides -->
        <div class="gs-card">
          <div class="gs-card-head">
            <span class="gs-card-title">&#9889; XP &amp; Difficulty</span>
          </div>
          <div class="gs-card-body">
            <div class="ovr-row">
              <input type="checkbox" class="ovr-check" id="ovrXpMultiplier">
              <span class="ovr-label">XP Multiplier <span class="ovr-hint" id="ovrXpMultiplierHint"></span></span>
              <div class="setting-range-wrapper ovr-control">
                <input type="range" id="ovrXpMultiplierVal" min="0.5" max="3" step="0.1" value="1" disabled>
                <span class="range-value" id="ovrXpMultiplierDisplay">1.0x</span>
              </div>
            </div>
            <div class="ovr-row">
              <input type="checkbox" class="ovr-check" id="ovrMaxDailyXp">
              <span class="ovr-label">Daily XP Cap <span class="ovr-hint" id="ovrMaxDailyXpHint"></span></span>
              <input type="number" class="setting-number ovr-control" id="ovrMaxDailyXpVal" disabled min="50" max="5000" step="50" value="500">
            </div>
            <div class="ovr-row">
              <input type="checkbox" class="ovr-check" id="ovrDifficulty">
              <span class="ovr-label">Difficulty Level <span class="ovr-hint" id="ovrDifficultyHint"></span></span>
              <select class="setting-select ovr-control" id="ovrDifficultyVal" disabled>
                <option value="easy">Easy</option>
                <option value="moderate">Moderate</option>
                <option value="challenging">Challenging</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Accessibility & Pacing Overrides -->
        <div class="gs-card">
          <div class="gs-card-head">
            <span class="gs-card-title">&#9851;&#65039; Accessibility &amp; Pacing</span>
          </div>
          <div class="gs-card-body">
            <div class="ovr-row">
              <input type="checkbox" class="ovr-check" id="ovrQuizTimer">
              <span class="ovr-label">Quiz Timer <span class="ovr-hint" id="ovrQuizTimerHint"></span></span>
              <div class="setting-range-wrapper ovr-control">
                <input type="range" id="ovrQuizTimerVal" min="0" max="120" step="5" value="30" disabled>
                <span class="range-value" id="ovrQuizTimerDisplay">30s</span>
              </div>
            </div>
            <div class="ovr-row">
              <input type="checkbox" class="ovr-check" id="ovrGameTimer">
              <span class="ovr-label">Game Timer <span class="ovr-hint" id="ovrGameTimerHint"></span></span>
              <div class="setting-range-wrapper ovr-control">
                <input type="range" id="ovrGameTimerVal" min="0" max="120" step="5" value="30" disabled>
                <span class="range-value" id="ovrGameTimerDisplay">30s</span>
              </div>
            </div>
            <div class="ovr-row">
              <input type="checkbox" class="ovr-check" id="ovrShowGameScore">
              <span class="ovr-label">Show Activity Score <span class="ovr-hint" id="ovrShowGameScoreHint"></span></span>
              <label class="toggle-switch ovr-control" style="margin-left:auto">
                <input type="checkbox" id="ovrShowGameScoreVal" checked disabled>
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>

        <div style="display:flex;align-items:center;gap:0.75rem;padding:0.25rem 0 0.75rem">
          <button class="btn btn-primary" id="saveOverridesBtn">&#128190; Save Overrides</button>
          <span id="overrideSaveStatus" class="save-status"></span>
        </div>
      </div>

    </div><!-- /panel-override -->

    <!-- ══════════════════════════════════════════════════════
         PANEL 3 — Award / Deduct XP
         ══════════════════════════════════════════════════════ -->
    <div class="gs-panel hidden" id="panel-xp">

      <div class="gs-card">
        <div class="gs-card-head">
          <span class="gs-card-title">&#127873; Award / Deduct XP</span>
          <span class="gs-card-meta" style="font-size:0.95rem;color:#94a3b8">Manually adjust a student's XP balance</span>
        </div>
        <div class="gs-card-body">
          <div class="xp-mode-toggle">
            <input type="radio" name="xpMode" id="xpModeAward" value="award" checked>
            <label for="xpModeAward">&#127873; Award XP</label>
            <input type="radio" name="xpMode" id="xpModeDeduct" value="deduct">
            <label for="xpModeDeduct">&#128308; Deduct XP</label>
          </div>

          <div class="xp-award-form">
            <div class="form-group">
              <label>Student</label>
              <select id="awardStudentId" class="setting-select" style="width:100%">
                <option value="">Select student...</option>
              </select>
            </div>
            <div class="form-group">
              <label>XP Amount</label>
              <input type="number" id="awardXpAmount" class="setting-number" style="width:100%" value="50" min="1" max="1000">
            </div>
            <div class="form-group">
              <label for="awardReason">Reason <span style="color:#dc2626;font-weight:700">*</span></label>
              <div style="display:flex;gap:0.5rem">
                <input type="text" id="awardReason" required aria-required="true" placeholder="e.g. Great participation today" style="flex:1;padding:0.7rem 0.9rem;border:2px solid #e2e8f0;border-radius:9px;font-size:0.95rem;font-family:inherit">
                <button class="btn btn-success" id="awardXpBtn">Award XP</button>
              </div>
            </div>
          </div>

          <div id="xpDeductNote" class="gs-deduct-note" style="display:none">
            &#9888;&#65039; Deducting XP will reduce the student's total XP. It will not go below 0.
          </div>

          <div class="xp-result-toast" id="xpResultToast">
            <span class="xp-toast-icon" id="xpToastIcon"></span>
            <div class="xp-toast-body">
              <div id="xpToastMsg"></div>
              <div class="xp-toast-sub" id="xpToastSub"></div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /panel-xp -->

    <!-- ══════════════════════════════════════════════════════
         PANEL 4 — Student Mini-Games
         ══════════════════════════════════════════════════════ -->
    <div class="gs-panel hidden" id="panel-games">

      <div class="gs-note info">
        <span class="gs-note-icon">&#9432;</span>
        <div>
          <strong>9 default games</strong> (3 per subject) are always available to every student.
          Select a student to assign additional games or change their team.
        </div>
      </div>

      <!-- Student picker -->
      <div class="gs-card">
        <div class="gs-card-head">
          <span class="gs-card-title">&#128100; Select Student</span>
        </div>
        <div class="gs-card-body">
          <select id="gameStudentPicker" class="setting-select" style="width:100%;max-width:380px">
            <option value="">— Choose a student —</option>
          </select>
        </div>
      </div>

      <div id="gameAssignBody" style="display:none">

        <!-- Team Assignment -->
        <div class="gs-card">
          <div class="gs-card-head">
            <span class="gs-card-title">&#9876;&#65039; Team Assignment</span>
          </div>
          <div class="gs-card-body">
            <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
              <span style="font-size:1.5rem" id="teamAssignEmoji">&#10067;</span>
              <div style="flex:1">
                <div style="font-weight:700;font-size:1rem;color:#1e293b">Team</div>
                <div style="font-size:0.88rem;color:#64748b">Change this student's team assignment</div>
              </div>
              <select id="teamAssignSelect" class="setting-select" style="min-width:160px">
                <option value="">No Team</option>
                <option value="fire">&#128293; Fire</option>
                <option value="water">&#128167; Water</option>
                <option value="grass">&#127807; Grass</option>
              </select>
            </div>
          </div>
        </div>
        <div id="teamToast" class="game-toast"></div>

        <!-- Game List -->
        <div class="gs-card">
          <div class="gs-card-head">
            <span class="gs-card-title">&#127918; Mini-Game Access</span>
          </div>
          <div class="gs-card-body">
            <div style="display:flex;gap:0.5rem;margin-bottom:1rem;flex-wrap:wrap">
              <button class="btn btn-sm game-filter-btn active" data-filter="all">All</button>
              <button class="btn btn-sm game-filter-btn" data-filter="math">&#128290; Math</button>
              <button class="btn btn-sm game-filter-btn" data-filter="english">&#128218; English</button>
              <button class="btn btn-sm game-filter-btn" data-filter="selfcare">&#128150; Self Care</button>
            </div>

            <div id="gameToast" class="game-toast"></div>
            <div id="gameAssignList">
              <p style="text-align:center;color:#9ca3af;padding:20px">Loading games...</p>
            </div>

            <div style="display:flex;align-items:center;gap:0.75rem;padding-top:0.75rem;margin-top:0.25rem;border-top:1px solid #f1f5f9">
              <button class="btn btn-primary" id="saveGameSettingsBtn">&#128190; Save Settings</button>
              <span id="gameSaveStatus" class="save-status"></span>
            </div>
          </div>
        </div>

      </div><!-- /gameAssignBody -->

    </div><!-- /panel-games -->

  </main>




  <script src="../assets/js/auth-guard.js"></script>
  <script>
  (function() {
    'use strict';

    const API_BASE = '../api/gamification';

    // ── Load Settings ──
    async function loadSettings() {
      try {
        const res = await fetch(API_BASE + '/settings.php', {
          headers: EQ.authHeaders(),
        });
        const json = await res.json();
        if (!json.success) return;

        const s = json.data.settings;
        const o = json.data.overview;

        // Populate overview
        document.getElementById('overviewStudents').textContent = o.activeStudents;
        document.getElementById('overviewAvgXp').textContent = formatNum(o.avgXp);
        document.getElementById('overviewAvgLevel').textContent = o.avgLevel.toFixed(1);

        const totalTeams = o.teamCounts.fire + o.teamCounts.water + o.teamCounts.grass;
        document.getElementById('overviewTeams').textContent = totalTeams;
        document.getElementById('teamBreakdown').innerHTML =
          `<span class="team-count fire">&#128293; ${o.teamCounts.fire}</span>
           <span class="team-count water">&#128167; ${o.teamCounts.water}</span>
           <span class="team-count grass">&#127807; ${o.teamCounts.grass}</span>
           <span class="team-count none">— ${o.teamCounts.none}</span>`;

        // Populate settings
        const normalizedLeaderboardMode =
          (s.leaderboardMode === 'individual' || s.leaderboardMode === 'top_only' || s.leaderboardMode === 'disabled')
            ? s.leaderboardMode
            : 'top_only';
        document.getElementById('settLeaderboardMode').value = normalizedLeaderboardMode;
        document.getElementById('settXpMultiplier').value = s.xpMultiplier;
        document.getElementById('xpMultiplierValue').textContent = s.xpMultiplier.toFixed(1) + 'x';
        document.getElementById('settMaxDailyXp').value = s.maxDailyXp;
        document.getElementById('settDifficulty').value = s.difficultyLevel;
        document.getElementById('settQuizTimer').value = s.quizTimerSeconds;
        document.getElementById('quizTimerValue').textContent = s.quizTimerSeconds === 0 ? 'Off' : s.quizTimerSeconds + 's';
        document.getElementById('settGameTimer').value = s.gameTimerSeconds;
        document.getElementById('gameTimerValue').textContent = s.gameTimerSeconds === 0 ? 'Off' : s.gameTimerSeconds + 's';
        document.getElementById('settShowGameScore').checked = s.showGameScore !== false;

      } catch (err) {
        console.error('Failed to load settings:', err);
      }
    }

    function formatSavedTime() {
      const now = new Date();
      return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function setStatus(elId, text, kind) {
      const el = document.getElementById(elId);
      if (!el) return;
      el.className = 'save-status' + (kind === 'ok' ? ' ok' : kind === 'err' ? ' err' : '');
      el.textContent = text;
    }

    // ── Save Settings ──
    async function saveSettings(statusElId, buttonElId) {
      const data = {
        leaderboardMode:        document.getElementById('settLeaderboardMode').value,
        leaderboardTopN:        document.getElementById('settLeaderboardMode').value === 'top_only' ? 5 : 1,
        xpMultiplier:           parseFloat(document.getElementById('settXpMultiplier').value),
        maxDailyXp:             parseInt(document.getElementById('settMaxDailyXp').value),
        difficultyLevel:        document.getElementById('settDifficulty').value,
        quizTimerSeconds:       parseInt(document.getElementById('settQuizTimer').value),
        gameTimerSeconds:       parseInt(document.getElementById('settGameTimer').value),
        showGameScore:          document.getElementById('settShowGameScore').checked,
      };

      const button = buttonElId ? document.getElementById(buttonElId) : null;
      if (button) {
        button.disabled = true;
        button.textContent = 'Saving...';
      }

      try {
        const res = await fetch(API_BASE + '/settings.php', {
          method: 'POST',
          headers: EQ.authHeaders(),
          body: JSON.stringify(data),
        });
        const json = await res.json();

        if (json.success) {
          showAlert('success', 'Settings saved successfully!');
          if (statusElId) setStatus(statusElId, 'Saved at ' + formatSavedTime(), 'ok');
        } else {
          showAlert('error', json.message || 'Failed to save.');
          if (statusElId) setStatus(statusElId, 'Failed to save', 'err');
        }
      } catch (err) {
        showAlert('error', 'Network error. Please try again.');
        if (statusElId) setStatus(statusElId, 'Network error', 'err');
      } finally {
        if (button) {
          button.disabled = false;
          button.textContent = 'Save Settings';
        }
      }
    }

    // ── Award / Deduct XP ──
    async function awardXp() {
      const studentId = document.getElementById('awardStudentId').value;
      const rawAmount = parseInt(document.getElementById('awardXpAmount').value);
      const reasonEl  = document.getElementById('awardReason');
      const reason    = reasonEl.value.trim();
      const isDeduct  = document.querySelector('input[name="xpMode"]:checked').value === 'deduct';

      if (!studentId || !rawAmount || !reason) {
        showAlert('error', 'Please provide a reason for every XP award or deduction');
        reasonEl.focus();
        return;
      }

      const xpAmount = isDeduct ? -Math.abs(rawAmount) : Math.abs(rawAmount);

      // Get student name for the indicator
      const studentSelect = document.getElementById('awardStudentId');
      const studentName = studentSelect.options[studentSelect.selectedIndex]?.text || 'Student';

      try {
        const res = await fetch(API_BASE + '/award-xp.php', {
          method: 'POST',
          headers: EQ.authHeaders(),
          body: JSON.stringify({
            studentId: parseInt(studentId),
            xpAmount: xpAmount,
            sourceType: isDeduct ? 'correction' : 'teacher_award',
            description: (isDeduct ? '[Deduction] ' : '') + reason,
          }),
        });
        const json = await res.json();

        if (json.success) {
          const awarded = Math.abs(json.data.xpAwarded);
          const total = json.data.totalXp;
          if (isDeduct) {
            showXpToast('deducted', `\u2796 Deducted ${awarded} XP from ${studentName}`, `New total: ${total} XP`);
          } else {
            showXpToast('awarded', `\u2728 Awarded +${awarded} XP to ${studentName}!`, `New total: ${total} XP`);
          }
          document.getElementById('awardReason').value = '';
          document.getElementById('awardXpAmount').value = '50';
        } else {
          showXpToast('error', 'Failed to process XP', json.message || 'Please try again.');
        }
      } catch (err) {
        showXpToast('error', 'Network error', 'Could not reach the server. Please try again.');
      }
    }

    // ── Load Students for Award Dropdown + Game Picker ──
    async function loadStudents() {
      try {
        const res = await fetch('../api/students/list.php?per_page=200', {
          headers: EQ.authHeaders(),
        });
        const json = await res.json();
        if (!json.success) return;

        const select = document.getElementById('awardStudentId');
        const students = json.data.students || json.data || [];
        students.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s.id;
          opt.textContent = s.first_name + ' ' + s.last_name + (s.grade_level ? ' (' + s.grade_level + ')' : '');
          select.appendChild(opt);
        });

        // Also populate game student picker + override picker
        populateGameStudentPicker(students);
        populateOverrideStudentPicker(students);
      } catch (err) {
        console.error('Failed to load students:', err);
      }
    }

    // ── UI Helpers ──
    let gameToastTimer = null;
    function showGameToast(type, msg) {
      const toast = document.getElementById('gameToast');
      toast.className = 'game-toast ' + (type === 'success' ? 'toast-success' : 'toast-error');
      toast.innerHTML = msg;
      toast.style.display = 'flex';
      if (gameToastTimer) clearTimeout(gameToastTimer);
      gameToastTimer = setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    function showAlert(type, msg) {
      const successAlert = document.getElementById('successAlert');
      const errorAlert = document.getElementById('errorAlert');
      successAlert.style.display = 'none';
      errorAlert.style.display = 'none';

      if (type === 'success') {
        successAlert.textContent = msg;
        successAlert.style.display = 'block';
      } else {
        errorAlert.textContent = msg;
        errorAlert.style.display = 'block';
      }
      setTimeout(() => {
        successAlert.style.display = 'none';
        errorAlert.style.display = 'none';
      }, 4000);
    }

    let xpToastTimer = null;
    function showXpToast(type, msg, sub) {
      const toast = document.getElementById('xpResultToast');
      const icon  = document.getElementById('xpToastIcon');
      const body  = document.getElementById('xpToastMsg');
      const subEl = document.getElementById('xpToastSub');

      toast.className = 'xp-result-toast xp-toast-visible xp-toast-' + type;
      icon.textContent = type === 'awarded' ? '\u2705' : type === 'deducted' ? '\u26A0\uFE0F' : '\u274C';
      body.textContent = msg;
      subEl.textContent = sub || '';

      // scroll into view
      toast.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

      if (xpToastTimer) clearTimeout(xpToastTimer);
      xpToastTimer = setTimeout(() => {
        toast.classList.remove('xp-toast-visible');
      }, 5000);
    }

    function formatNum(n) {
      n = parseInt(n) || 0;
      if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
      return n.toString();
    }

    // ── Per-Student Override Management ──
    // Map of override IDs to their API keys and display helpers
    const OVERRIDE_MAP = {
      ovrLeaderboardMode:  { key: 'leaderboard_mode',       globalEl: 'settLeaderboardMode',   type: 'select' },
      ovrXpMultiplier:     { key: 'xp_multiplier',          globalEl: 'settXpMultiplier',      type: 'range', display: 'ovrXpMultiplierDisplay', fmt: v => parseFloat(v).toFixed(1) + 'x' },
      ovrMaxDailyXp:       { key: 'max_daily_xp',           globalEl: 'settMaxDailyXp',        type: 'number' },
      ovrDifficulty:       { key: 'difficulty_level',        globalEl: 'settDifficulty',        type: 'select' },
      ovrQuizTimer:        { key: 'quiz_timer_seconds',      globalEl: 'settQuizTimer',         type: 'range', display: 'ovrQuizTimerDisplay', fmt: v => parseInt(v) === 0 ? 'Off' : v + 's' },
      ovrGameTimer:        { key: 'game_timer_seconds',      globalEl: 'settGameTimer',         type: 'range', display: 'ovrGameTimerDisplay', fmt: v => parseInt(v) === 0 ? 'Off' : v + 's' },
      ovrShowGameScore:    { key: 'show_game_score',         globalEl: 'settShowGameScore',     type: 'toggle', fmt: v => v ? 'Yes' : 'No' },
    };

    let selectedOverrideStudentId = null;

    function populateOverrideStudentPicker(students) {
      const picker = document.getElementById('overrideStudentPicker');
      students.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.first_name + ' ' + s.last_name + (s.grade_level ? ' (' + s.grade_level + ')' : '');
        picker.appendChild(opt);
      });
    }

    function updateOverrideHints() {
      for (const [checkId, cfg] of Object.entries(OVERRIDE_MAP)) {
        const hint = document.getElementById(checkId + 'Hint');
        if (!hint) continue;
        const globalEl = document.getElementById(cfg.globalEl);
        if (!globalEl) continue;
        const gVal = cfg.type === 'select' ? globalEl.options[globalEl.selectedIndex].text
                   : cfg.type === 'toggle' ? (globalEl.checked ? 'Yes' : 'No')
                   : globalEl.value;
        hint.textContent = '(global: ' + (cfg.fmt ? cfg.fmt(cfg.type === 'toggle' ? globalEl.checked : globalEl.value) : gVal) + ')';
      }
    }

    async function loadOverrides(studentId) {
      // Reset all checkboxes and controls
      for (const [checkId, cfg] of Object.entries(OVERRIDE_MAP)) {
        const check = document.getElementById(checkId);
        const valEl = document.getElementById(checkId + 'Val');
        check.checked = false;
        if (valEl) valEl.disabled = true;
        if (cfg.display) {
          const globalEl = document.getElementById(cfg.globalEl);
          document.getElementById(cfg.display).textContent = cfg.fmt ? cfg.fmt(globalEl.value) : globalEl.value;
        }
      }
      updateOverrideHints();

      try {
        const res = await fetch(API_BASE + '/student-settings-override.php?student_id=' + studentId, {
          headers: EQ.authHeaders(),
        });
        const json = await res.json();
        if (!json.success) return;

        const overrides = json.data.overrides || {};
        for (const [checkId, cfg] of Object.entries(OVERRIDE_MAP)) {
          if (cfg.key in overrides) {
            const check = document.getElementById(checkId);
            const valEl = document.getElementById(checkId + 'Val');
            check.checked = true;
            if (valEl) {
              valEl.disabled = false;
              if (cfg.type === 'toggle') {
                valEl.checked = overrides[cfg.key] === '1';
              } else {
                valEl.value = overrides[cfg.key];
              }
            }
            if (cfg.display) {
              document.getElementById(cfg.display).textContent = cfg.fmt ? cfg.fmt(overrides[cfg.key]) : overrides[cfg.key];
            }
          }
        }
      } catch (err) {
        console.error('Failed to load overrides:', err);
      }
    }

    async function saveOverrides() {
      if (!selectedOverrideStudentId) return;

      const overrides = {};
      for (const [checkId, cfg] of Object.entries(OVERRIDE_MAP)) {
        const check = document.getElementById(checkId);
        const valEl = document.getElementById(checkId + 'Val');
        if (check.checked && valEl) {
          if (cfg.type === 'toggle') {
            overrides[cfg.key] = valEl.checked ? '1' : '0';
          } else {
            overrides[cfg.key] = valEl.value;
          }
          if (cfg.key === 'leaderboard_mode' && valEl.value === 'top_only') {
            overrides['leaderboard_top_n'] = 5;
          }
        }
      }

      const btn = document.getElementById('saveOverridesBtn');
      btn.disabled = true;
      btn.textContent = 'Saving...';

      try {
        const res = await fetch(API_BASE + '/student-settings-override.php', {
          method: 'POST',
          headers: EQ.authHeaders(),
          body: JSON.stringify({ student_id: selectedOverrideStudentId, overrides }),
        });
        const json = await res.json();

        if (json.success) {
          showOverrideToast('success', '✅ ' + json.message);
          setStatus('overrideSaveStatus', 'Saved at ' + formatSavedTime(), 'ok');
        } else {
          showOverrideToast('error', json.message || 'Failed to save.');
          setStatus('overrideSaveStatus', 'Failed to save', 'err');
        }
      } catch (err) {
        showOverrideToast('error', 'Network error — please try again.');
        setStatus('overrideSaveStatus', 'Network error', 'err');
      } finally {
        btn.disabled = false;
        btn.textContent = '💾 Save Overrides';
      }
    }

    let overrideToastTimer = null;
    function showOverrideToast(type, msg) {
      const toast = document.getElementById('overrideToast');
      toast.className = 'game-toast ' + (type === 'success' ? 'toast-success' : 'toast-error');
      toast.innerHTML = msg;
      toast.style.display = 'flex';
      if (overrideToastTimer) clearTimeout(overrideToastTimer);
      overrideToastTimer = setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    function setupOverrideListeners() {
      // Override student picker
      document.getElementById('overrideStudentPicker').addEventListener('change', function() {
        selectedOverrideStudentId = this.value ? parseInt(this.value) : null;
        const body = document.getElementById('overrideBody');
        if (selectedOverrideStudentId) {
          body.style.display = '';
          loadOverrides(selectedOverrideStudentId);
        } else {
          body.style.display = 'none';
        }
      });

      // Checkbox toggles enable/disable the corresponding control
      for (const [checkId, cfg] of Object.entries(OVERRIDE_MAP)) {
        const check = document.getElementById(checkId);
        const valEl = document.getElementById(checkId + 'Val');
        if (!check || !valEl) continue;

        check.addEventListener('change', function() {
          valEl.disabled = !this.checked;
          if (this.checked && cfg.type === 'toggle') {
            const globalEl = document.getElementById(cfg.globalEl);
            if (globalEl) valEl.checked = globalEl.checked;
          }
          if (this.checked && cfg.type !== 'range' && cfg.type !== 'toggle') {
            // Pre-fill with global value
            const globalEl = document.getElementById(cfg.globalEl);
            if (globalEl) valEl.value = globalEl.value;
          }
          if (this.checked && cfg.type === 'range') {
            const globalEl = document.getElementById(cfg.globalEl);
            if (globalEl) {
              valEl.value = globalEl.value;
              if (cfg.display) {
                document.getElementById(cfg.display).textContent = cfg.fmt ? cfg.fmt(globalEl.value) : globalEl.value;
              }
            }
          }
        });

        // Range display update
        if (cfg.type === 'range' && cfg.display) {
          valEl.addEventListener('input', function() {
            document.getElementById(cfg.display).textContent = cfg.fmt ? cfg.fmt(this.value) : this.value;
          });
        }
      }

      // Save overrides button
      document.getElementById('saveOverridesBtn').addEventListener('click', saveOverrides);

      // XP mode toggle (award/deduct)
      document.querySelectorAll('input[name="xpMode"]').forEach(radio => {
        radio.addEventListener('change', function() {
          const isDeduct = this.value === 'deduct';
          const btn = document.getElementById('awardXpBtn');
          const note = document.getElementById('xpDeductNote');
          const placeholder = document.getElementById('awardReason');
          btn.textContent = isDeduct ? 'Deduct XP' : 'Award XP';
          btn.className = isDeduct ? 'btn btn-danger' : 'btn btn-success';
          note.style.display = isDeduct ? 'block' : 'none';
          placeholder.placeholder = isDeduct ? 'e.g. Missed assignment' : 'e.g. Great participation today';
        });
      });
    }

    // ── Game Assignment Management ──
    // All games with metadata for the teacher UI
    const ALL_GAMES = [
      { id: 'math-sort-asc',       subject: 'math',     icon: '🔢', title: 'Arrange Numbers ↑',    desc: 'Sort numbers smallest to biggest' },
      { id: 'math-compare',        subject: 'math',     icon: '⚖️', title: 'Compare Numbers',       desc: 'Choose <, = or > to compare' },
      { id: 'math-ordinal',        subject: 'math',     icon: '🏅', title: 'Ordinal Numbers',       desc: '1st, 2nd, 3rd quiz' },
      { id: 'math-sort-desc',      subject: 'math',     icon: '🔢', title: 'Arrange Numbers ↓',    desc: 'Sort numbers biggest to smallest' },
      { id: 'math-coins',          subject: 'math',     icon: '🪙', title: 'Coin Values',           desc: 'Philippine coin identification' },
      { id: 'math-numwords',       subject: 'math',     icon: '🔤', title: 'Number Words',          desc: 'Match numbers to words' },
      { id: 'eng-build-cvc',       subject: 'english',  icon: '🧩', title: 'Build CVC Words',       desc: 'Drag letters to build words' },
      { id: 'eng-read-cvc',        subject: 'english',  icon: '📖', title: 'Read /Ii/ Words',       desc: 'Match words with /Ii/ sound' },
      { id: 'eng-sentences',       subject: 'english',  icon: '✏️', title: '/Ii/ Sentences',         desc: 'Fill in sentences with /Ii/ words' },
      { id: 'sc-living',           subject: 'selfcare', icon: '🌱', title: 'Living vs Non-Living',  desc: 'Classify living and non-living things' },
      { id: 'sc-food',             subject: 'selfcare', icon: '🥗', title: 'Healthy vs Unhealthy',  desc: 'Sort healthy and unhealthy foods' },
      { id: 'sc-eating-habits',    subject: 'selfcare', icon: '🍽️', title: 'Good Eating Habits',    desc: 'Quiz on proper eating habits' },
      { id: 'sc-weather',          subject: 'selfcare', icon: '🌤️', title: 'Weather Types',         desc: 'Identify weather conditions' },
      { id: 'sc-weather-clothes',  subject: 'selfcare', icon: '👕', title: 'Weather & Clothes',     desc: 'Match clothes to weather' },
      { id: 'sc-animals',          subject: 'selfcare', icon: '🐾', title: 'Animal Classification', desc: 'Classify animals by type' },
      { id: 'sc-rawfood',          subject: 'selfcare', icon: '🍳', title: 'Raw vs Cooked Food',    desc: 'Sort raw and cooked foods' },
    ];

    const DEFAULT_GAME_IDS = [
      'math-sort-asc', 'math-compare', 'math-ordinal',
      'eng-build-cvc', 'eng-read-cvc', 'eng-sentences',
      'sc-living', 'sc-food', 'sc-eating-habits',
    ];

    let gameAssignments = {}; // persisted state { gameId: boolean }
    let pendingGameAssignments = {}; // staged state { gameId: boolean }
    let gameFilter = 'all';
    let selectedGameStudentId = null;
    let currentStudentTeam = '';
    let pendingStudentTeam = '';

    async function loadGameAssignments() {
      if (!selectedGameStudentId) return;
      try {
        const res = await fetch(API_BASE + '/game-assignments.php?student_id=' + selectedGameStudentId, {
          headers: EQ.authHeaders(),
        });
        const json = await res.json();
        if (!json.success) return;

        gameAssignments = {};
        json.data.games.forEach(g => {
          gameAssignments[g.game_id] = g.is_enabled;
        });
        pendingGameAssignments = { ...gameAssignments };
        renderGameList();
      } catch (err) {
        console.error('Failed to load game assignments:', err);
      }
    }

    function renderGameList() {
      const container = document.getElementById('gameAssignList');
      if (!selectedGameStudentId) {
        container.innerHTML = '<p style="text-align:center;color:#9ca3af;padding:20px">Select a student above to manage their games.</p>';
        return;
      }

      let games = ALL_GAMES;
      if (gameFilter !== 'all') {
        games = games.filter(g => g.subject === gameFilter);
      }

      container.innerHTML = games.map(g => {
        const isDefault = DEFAULT_GAME_IDS.includes(g.id);
        const isEnabled = isDefault || !!pendingGameAssignments[g.id];
        const subjectClass = g.subject;

        return `
          <div class="game-card" data-game-id="${g.id}" data-subject="${g.subject}">
            <div class="game-card-info">
              <div class="game-card-icon">${g.icon}</div>
              <div>
                <div class="game-card-title">${g.title}</div>
                <div class="game-card-desc">${g.desc}</div>
              </div>
            </div>
            <span class="game-card-subject ${subjectClass}">${g.subject === 'selfcare' ? 'Self Care' : g.subject}</span>
            ${isDefault ? '<span class="default-badge">Default</span>' : ''}
            <label class="toggle-switch">
              <input type="checkbox" ${isEnabled ? 'checked' : ''} ${isDefault ? 'disabled' : ''} data-game-id="${g.id}">
              <span class="toggle-slider"></span>
            </label>
          </div>`;
      }).join('');

      // Bind toggle events for extra games (saved when clicking Save Settings)
      container.querySelectorAll('input[type="checkbox"]:not(:disabled)').forEach(cb => {
        cb.addEventListener('change', function() {
          const gameId = this.dataset.gameId;
          pendingGameAssignments[gameId] = this.checked;
          setStatus('gameSaveStatus', 'Unsaved changes', '');
        });
      });
    }

    // Populate the game-section student dropdown (reuses loadStudents data)
    function populateGameStudentPicker(students) {
      const picker = document.getElementById('gameStudentPicker');
      students.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.first_name + ' ' + s.last_name + (s.grade_level ? ' (' + s.grade_level + ')' : '');
        picker.appendChild(opt);
      });
    }

    // Load current team for the selected student
    async function loadStudentTeam(studentId) {
      const select = document.getElementById('teamAssignSelect');
      const emoji  = document.getElementById('teamAssignEmoji');
      const teamEmojis = { fire: '🔥', water: '💧', grass: '🌿' };
      try {
        const res = await fetch(API_BASE + '/student-progress.php?studentId=' + studentId, {
          headers: EQ.authHeaders(),
        });
        const json = await res.json();
        const team = (json.success && json.data && json.data.team) ? json.data.team : '';
        currentStudentTeam = team;
        pendingStudentTeam = team;
        select.value = team;
        emoji.textContent = team ? (teamEmojis[team] || '❓') : '❓';
      } catch (_) {
        currentStudentTeam = '';
        pendingStudentTeam = '';
        select.value = '';
        emoji.textContent = '❓';
      }
    }

    async function saveGameSettings() {
      if (!selectedGameStudentId) {
        setStatus('gameSaveStatus', 'Select a student first', 'err');
        return;
      }

      const btn = document.getElementById('saveGameSettingsBtn');
      btn.disabled = true;
      btn.textContent = 'Saving...';

      try {
        if (pendingStudentTeam !== currentStudentTeam) {
          const teamRes = await fetch(API_BASE + '/assign-team.php', {
            method: 'POST',
            headers: EQ.authHeaders(),
            body: JSON.stringify({ student_id: selectedGameStudentId, team: pendingStudentTeam }),
          });
          const teamJson = await teamRes.json();
          if (!teamJson.success) throw new Error(teamJson.message || 'Failed to save team.');
          currentStudentTeam = pendingStudentTeam;
        }

        for (const g of ALL_GAMES) {
          if (DEFAULT_GAME_IDS.includes(g.id)) continue;
          const prev = !!gameAssignments[g.id];
          const next = !!pendingGameAssignments[g.id];
          if (prev === next) continue;

          const res = await fetch(API_BASE + '/game-assignments.php', {
            method: 'POST',
            headers: EQ.authHeaders(),
            body: JSON.stringify({ game_id: g.id, student_id: selectedGameStudentId, is_enabled: next }),
          });
          const json = await res.json();
          if (!json.success) throw new Error(json.message || 'Failed to save game settings.');
          gameAssignments[g.id] = next;
        }

        setStatus('gameSaveStatus', 'Saved at ' + formatSavedTime(), 'ok');
        showGameToast('success', '✅ Student mini-game settings saved.');
      } catch (err) {
        setStatus('gameSaveStatus', 'Failed to save', 'err');
        showGameToast('error', err.message || 'Failed to save mini-game settings.');
        await loadGameAssignments();
        await loadStudentTeam(selectedGameStudentId);
      } finally {
        btn.disabled = false;
        btn.textContent = 'Save Settings';
      }
    }

    let teamToastTimer = null;
    function showTeamToast(type, msg) {
      const toast = document.getElementById('teamToast');
      toast.className = 'game-toast ' + (type === 'success' ? 'toast-success' : 'toast-error');
      toast.innerHTML = msg;
      toast.style.display = 'flex';
      if (teamToastTimer) clearTimeout(teamToastTimer);
      teamToastTimer = setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    // ── Event Listeners ──
    document.addEventListener('DOMContentLoaded', () => {
      // Tab switching
      document.querySelectorAll('.gs-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
          document.querySelectorAll('.gs-tab').forEach(function(t) { t.classList.remove('active'); });
          document.querySelectorAll('.gs-panel').forEach(function(p) { p.classList.add('hidden'); });
          tab.classList.add('active');
          var panel = document.getElementById(tab.dataset.gsPanel);
          if (panel) panel.classList.remove('hidden');
        });
      });

      loadSettings();
      loadStudents();
      setupOverrideListeners();

      document.getElementById('saveLeaderboardBtn').addEventListener('click', () => saveSettings('lbSaveStatus', 'saveLeaderboardBtn'));
      document.getElementById('saveXpDiffBtn').addEventListener('click', () => saveSettings('xpSaveStatus', 'saveXpDiffBtn'));
      document.getElementById('savePacingBtn').addEventListener('click', () => saveSettings('paceSaveStatus', 'savePacingBtn'));

      document.getElementById('settXpMultiplier').addEventListener('input', function() {
        document.getElementById('xpMultiplierValue').textContent = parseFloat(this.value).toFixed(1) + 'x';
      });

      document.getElementById('settQuizTimer').addEventListener('input', function() {
        const v = parseInt(this.value);
        document.getElementById('quizTimerValue').textContent = v === 0 ? 'Off' : v + 's';
      });

      document.getElementById('settGameTimer').addEventListener('input', function() {
        const v = parseInt(this.value);
        document.getElementById('gameTimerValue').textContent = v === 0 ? 'Off' : v + 's';
      });

      document.getElementById('awardXpBtn').addEventListener('click', awardXp);

      // Game student picker — load assignments + team when student changes
      document.getElementById('gameStudentPicker').addEventListener('change', function() {
        selectedGameStudentId = this.value ? parseInt(this.value) : null;
        const body = document.getElementById('gameAssignBody');
        if (selectedGameStudentId) {
          body.style.display = '';
          loadGameAssignments();
          loadStudentTeam(selectedGameStudentId);
          setStatus('gameSaveStatus', '', '');
        } else {
          body.style.display = 'none';
          gameAssignments = {};
          pendingGameAssignments = {};
          currentStudentTeam = '';
          pendingStudentTeam = '';
        }
      });

      // Team assignment dropdown (staged until Save Settings)
      document.getElementById('teamAssignSelect').addEventListener('change', function() {
        if (!selectedGameStudentId) return;
        const newTeam = this.value;
        const teamEmojis = { fire: '🔥', water: '💧', grass: '🌿' };
        pendingStudentTeam = newTeam;
        document.getElementById('teamAssignEmoji').textContent = teamEmojis[newTeam] || '❓';
        setStatus('gameSaveStatus', 'Unsaved changes', '');
      });

      // Game filter tabs
      document.querySelectorAll('.game-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          document.querySelectorAll('.game-filter-btn').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          gameFilter = btn.dataset.filter;
          renderGameList();
        });
      });

      document.getElementById('saveGameSettingsBtn').addEventListener('click', saveGameSettings);
    });
  })();
  </script>
</body>
</html>
