<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Behavioral Logs</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <style>
    /* ─── Page Tabs ─────────────────────────────────────────────────────────── */
    .page-tabs {
      display: flex;
      gap: .75rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }
    .page-tab {
      padding: .75rem 1.75rem;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 700;
      background: #f1f5f9;
      color: #475569;
      cursor: pointer;
      border: none;
      transition: background .15s, color .15s, box-shadow .15s;
      letter-spacing: .01em;
    }
    .page-tab:hover { background: #e2e8f0; color: #1e293b; }
    .page-tab.active {
      background: #3b82f6;
      color: #fff;
      box-shadow: 0 4px 12px rgba(59,130,246,.3);
    }

    .tab-pane { display: none; }
    .tab-pane.active { display: block; }

    /* ─── Filter bar ─────────────────────────────────────────────────────────── */
    .filter-row {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: flex-end;
      margin-bottom: 1.75rem;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 1.25rem 1.5rem;
    }
    .filter-row label {
      display: block;
      font-size: .9rem;
      color: #374151;
      font-weight: 700;
      margin-bottom: .35rem;
    }
    .filter-row input,
    .filter-row select {
      padding: .7rem 1rem;
      border: 2px solid #e2e8f0;
      border-radius: 9px;
      font-size: .95rem;
      background: #fff;
      min-width: 160px;
      color: #1e293b;
      font-family: inherit;
      transition: border-color .15s, box-shadow .15s;
    }
    .filter-row input:focus,
    .filter-row select:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59,130,246,.15);
    }
    .filter-row .btn { align-self: flex-end; }

    /* ─── Cards ──────────────────────────────────────────────────────────────── */
    .log-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
      border: 1px solid #e9eef5;
      padding: 1.75rem 2rem;
      margin-bottom: 1.5rem;
    }
    .log-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.25rem;
      flex-wrap: wrap;
      gap: .75rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid #f1f5f9;
    }
    .log-card-header h3 {
      margin: 0;
      font-size: 1.2rem;
      font-weight: 800;
      color: #1e2a3b;
    }

    /* ─── Data table ─────────────────────────────────────────────────────────── */
    .data-table { width: 100%; border-collapse: collapse; font-size: .95rem; }
    .data-table th {
      text-align: left;
      font-size: .85rem;
      color: #475569;
      font-weight: 800;
      padding: .75rem 1rem;
      border-bottom: 2px solid #e2e8f0;
      white-space: nowrap;
      text-transform: uppercase;
      letter-spacing: .04em;
      background: #f8fafc;
    }
    .data-table td {
      padding: .9rem 1rem;
      border-bottom: 1px solid #f1f5f9;
      color: #1e2a3b;
      font-size: .95rem;
      line-height: 1.5;
    }
    .data-table tr:last-child td { border-bottom: none; }
    .data-table tr:hover td { background: #f0f7ff; }
    .empty-msg {
      text-align: center;
      color: #94a3b8;
      padding: 2.5rem 1rem;
      font-size: 1rem;
    }
    .table-scroll { overflow-x: auto; border-radius: 10px; }

    /* ─── count chip next to card header ─────────────────────────────────────── */
    #logs-count {
      font-size: .9rem !important;
      color: #475569 !important;
      background: #f1f5f9;
      padding: .3rem .85rem;
      border-radius: 999px;
      font-weight: 700;
    }

    /* ─── Badges ─────────────────────────────────────────────────────────────── */
    .badge {
      display: inline-block;
      border-radius: 999px;
      padding: .3rem .85rem;
      font-size: .82rem;
      font-weight: 700;
    }
    .badge-engagement { background: #dbeafe; color: #1d4ed8; }
    .badge-self-reg   { background: #ede9fe; color: #7c3aed; }
    .badge-system     { background: #f1f5f9; color: #64748b; }
    .badge-teacher    { background: #fef3c7; color: #b45309; }

    /* ─── Engagement summary grid ────────────────────────────────────────────── */
    .indicator-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 1.1rem;
    }
    .indicator-cell {
      background: #f8fafc;
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      padding: 1.25rem 1.4rem;
      transition: box-shadow .15s;
    }
    .indicator-cell:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }
    .indicator-name {
      font-size: 1rem;
      font-weight: 800;
      color: #1e2a3b;
      margin-bottom: .65rem;
    }
    .indicator-row  { font-size: .9rem; color: #475569; margin-bottom: .3rem; }
    .indicator-row span { font-weight: 700; color: #1e2a3b; }

    /* ─── Log-entry form ─────────────────────────────────────────────────────── */
    .log-form {
      background: linear-gradient(135deg, #f0f7ff 0%, #f8fafc 100%);
      border: 2px solid #bfdbfe;
      border-radius: 14px;
      padding: 1.75rem 2rem;
      margin-bottom: 1.75rem;
      box-shadow: 0 2px 8px rgba(59,130,246,.07);
    }
    .log-form h4 {
      margin: 0 0 1.25rem;
      font-size: 1.15rem;
      font-weight: 800;
      color: #1e2a3b;
    }
    .log-form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      align-items: end;
    }
    .log-form-grid .form-group { display: flex; flex-direction: column; gap: .4rem; }
    .log-form-grid label {
      font-size: .95rem;
      font-weight: 700;
      color: #374151;
    }
    .log-form-grid input,
    .log-form-grid select,
    .log-form-grid textarea {
      padding: .7rem 1rem;
      border: 2px solid #e2e8f0;
      border-radius: 9px;
      font-size: .95rem;
      font-family: inherit;
      color: #1e293b;
      background: #fff;
      transition: border-color .15s, box-shadow .15s;
    }
    .log-form-grid input:focus,
    .log-form-grid select:focus,
    .log-form-grid textarea:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59,130,246,.15);
    }
    .log-form-grid textarea { resize: vertical; min-height: 80px; }
    .log-form-actions {
      margin-top: 1.1rem;
      display: flex;
      gap: .75rem;
      align-items: center;
    }
    .log-form-actions .btn {
      font-size: 1rem !important;
      padding: .7rem 1.75rem !important;
      border-radius: 9px !important;
      font-weight: 700 !important;
    }
    .alert-success {
      color: #166534;
      background: #dcfce7;
      border: 1px solid #bbf7d0;
      border-radius: 8px;
      padding: .55rem 1rem;
      font-size: .9rem;
      font-weight: 600;
    }
    .alert-error {
      color: #991b1b;
      background: #fee2e2;
      border: 1px solid #fecaca;
      border-radius: 8px;
      padding: .55rem 1rem;
      font-size: .9rem;
      font-weight: 600;
    }

    /* ─── Summary bar chart ──────────────────────────────────────────────────── */
    .bar-list { display: flex; flex-direction: column; gap: .75rem; }
    .bar-list-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      font-size: .95rem;
    }
    .bar-list-label {
      width: 220px;
      flex-shrink: 0;
      color: #374151;
      font-weight: 600;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .bar-list-outer {
      flex: 1;
      background: #f1f5f9;
      border-radius: 999px;
      height: 13px;
      overflow: hidden;
    }
    .bar-list-inner {
      height: 100%;
      border-radius: 999px;
      background: linear-gradient(90deg, #3b82f6, #6366f1);
      min-width: 6px;
    }
    .bar-list-val {
      width: 70px;
      text-align: right;
      flex-shrink: 0;
      color: #1e2a3b;
      font-weight: 700;
    }

    /* ─── Summary filter inputs ──────────────────────────────────────────────── */
    #sreg-student-filter,
    #eng-student-filter {
      padding: .6rem 1rem !important;
      font-size: .95rem !important;
      border: 2px solid #e2e8f0 !important;
      border-radius: 9px !important;
      color: #1e293b !important;
      width: 220px !important;
    }

    /* ─── Interaction summary sections ──────────────────────────────────────── */
    .charts-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
      margin-bottom: 1.5rem;
    }
    @media (max-width: 768px) { .charts-row { grid-template-columns: 1fr; } }

    .spinner {
      display: inline-block;
      width: 18px; height: 18px;
      border: 2.5px solid #e2e8f0;
      border-top-color: #3b82f6;
      border-radius: 50%;
      animation: spin .7s linear infinite;
      vertical-align: middle;
      margin-left: .5rem;
    }
    .hidden { display: none !important; }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body class="app-page">

  <!-- Sidebar -->
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
      <li><a href="activity-builder.php">🎮 Activities</a></li>
      <li><a href="grade-analytics.php">&#127942; Grade Analytics</a></li>
      <li class="nav-section-label">Insights</li>
      <li><a href="analytics.php">&#128202; Analytics</a></li>
      <li><a href="behavioral-logs.php" class="active">&#128203; Behavioral Logs</a></li>
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

  <!-- Main Content -->
  <main class="main-content">
    <header class="page-header">
      <h2>&#128203; Behavioral Logs</h2>
      <?php require_once 'notifications.php'; ?>
    </header>

    <!-- Page Tabs -->
    <div class="page-tabs">
      <button class="page-tab active" data-pane="pane-logs">Behavioral Logs</button>
      <button class="page-tab"        data-pane="pane-summary">Behavioral Summary</button>
    </div>

    <!-- ════════════ TAB 1 – Behavioral Logs ════════════ -->
    <div class="tab-pane active" id="pane-logs">

      <!-- Log Self-Regulation Entry Form -->
      <div class="log-form">
        <h4>&#128221; Log Self-Regulation Observation</h4>
        <div class="log-form-grid">
          <div class="form-group">
            <label for="lf-student">Student</label>
            <select id="lf-student">
              <option value="">Select student…</option>
            </select>
          </div>
          <div class="form-group">
            <label for="lf-indicator">Indicator</label>
            <select id="lf-indicator">
              <option value="task_initiation">Task Initiation</option>
              <option value="task_persistence">Task Persistence</option>
              <option value="consistency_of_completion">Consistency of Completion</option>
              <option value="responsiveness_to_feedback">Responsiveness to Feedback</option>
              <option value="frustration_management">Frustration Management</option>
            </select>
          </div>
          <div class="form-group">
            <label for="lf-date">Session Date</label>
            <input type="date" id="lf-date" />
          </div>
          <div class="form-group" style="grid-column:1/-1;">
            <label for="lf-value">Observation / Value</label>
            <textarea id="lf-value" placeholder="e.g. Initiated task independently after 2 prompts…" maxlength="255"></textarea>
          </div>
        </div>
        <div class="log-form-actions">
          <button class="btn btn-primary" id="lf-submit">Save Observation</button>
          <span class="spinner hidden" id="lf-spinner"></span>
          <span id="lf-msg"></span>
        </div>
      </div>

      <!-- Filter bar -->
      <div class="filter-row">
        <div>
          <label for="f-student">Student</label>
          <select id="f-student">
            <option value="">All Students</option>
          </select>
        </div>
        <div>
          <label for="f-type">Log Type</label>
          <select id="f-type">
            <option value="">All Types</option>
            <option value="engagement">Engagement</option>
            <option value="self_regulation">Self-Regulation</option>
          </select>
        </div>
        <div>
          <label for="f-from">Date From</label>
          <input type="date" id="f-from" />
        </div>
        <div>
          <label for="f-to">Date To</label>
          <input type="date" id="f-to" />
        </div>
        <div>
          <button class="btn btn-primary" id="btn-apply">Apply Filter</button>
          <span class="spinner hidden" id="logs-spinner"></span>
        </div>
      </div>

      <!-- Logs Table -->
      <div class="log-card">
        <div class="log-card-header">
          <h3>Log Entries</h3>
          <span id="logs-count"></span>
        </div>
        <div class="table-scroll">
          <table class="data-table" id="logs-table">
            <thead>
              <tr>
                <th>Student</th>
                <th>Indicator</th>
                <th>Type</th>
                <th>Source</th>
                <th>Value / Observation</th>
                <th>Session Date</th>
                <th>Logged By</th>
                <th>Recorded At</th>
              </tr>
            </thead>
            <tbody id="logs-tbody">
              <tr><td colspan="8" class="empty-msg">Set filters and click "Apply Filter" to load logs.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Engagement indicator summary -->
      <div class="log-card" id="eng-summary-wrap" style="display:none;">
        <div class="log-card-header">
          <h3>Engagement Indicator Summary (Your Students)</h3>
        </div>
        <div class="indicator-grid" id="eng-summary-grid"></div>
      </div>
    </div><!-- /pane-logs -->

    <!-- ════════════ TAB 2 – Behavioral Summary ════════════ -->
    <div class="tab-pane" id="pane-summary">

      <!-- Section: Self-Regulation Observations per Indicator -->
      <div class="log-card">
        <div class="log-card-header">
          <h3>Self-Regulation Observations — By Indicator</h3>
          <span class="spinner hidden" id="sreg-spinner"></span>
        </div>
        <div class="bar-list" id="sreg-bar-list">
          <p class="empty-msg">Loading…</p>
        </div>
      </div>

      <!-- Section: Per-Student Self-Regulation Summary -->
      <div class="log-card">
        <div class="log-card-header">
          <h3>Per-Student Self-Regulation Log Count</h3>
          <input type="text" id="sreg-student-filter" placeholder="Filter by name…" style="width:180px;" />
        </div>
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th>Student</th>
                <th>Task Initiation</th>
                <th>Task Persistence</th>
                <th>Consistency</th>
                <th>Responsiveness</th>
                <th>Frustration Mgmt</th>
                <th>Total Logs</th>
              </tr>
            </thead>
            <tbody id="sreg-student-tbody">
              <tr><td colspan="7" class="empty-msg">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Section: Engagement Indicators per Student -->
      <div class="log-card">
        <div class="log-card-header">
          <h3>Engagement Indicators — Per-Student Averages</h3>
          <input type="text" id="eng-student-filter" placeholder="Filter by name…" style="width:180px;" />
        </div>
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th>Student</th>
                <th>Completion Rate</th>
                <th>Time on Task</th>
                <th>Attempt Freq.</th>
                <th>Response Rate</th>
                <th>XP Rate</th>
              </tr>
            </thead>
            <tbody id="eng-student-tbody">
              <tr><td colspan="6" class="empty-msg">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Section: Interaction Tracking Summary (Page Time, Clicks, Hovers) -->
      <div class="charts-row">
        <div class="log-card">
          <div class="log-card-header"><h3>&#128200; Page Time (Top Pages)</h3></div>
          <div class="bar-list" id="page-time-bars"><p class="empty-msg">Loading…</p></div>
        </div>
        <div class="log-card">
          <div class="log-card-header"><h3>&#128432; Most Clicked Elements</h3></div>
          <div class="bar-list" id="click-bars"><p class="empty-msg">Loading…</p></div>
        </div>
      </div>

      <div class="charts-row">
        <div class="log-card">
          <div class="log-card-header"><h3>&#128065; Most Hovered Elements</h3></div>
          <div class="bar-list" id="hover-bars"><p class="empty-msg">Loading…</p></div>
        </div>
        <div class="log-card">
          <div class="log-card-header"><h3>&#9889; Engagement Scores</h3></div>
          <div class="bar-list" id="score-bars"><p class="empty-msg">Loading…</p></div>
        </div>
      </div>

    </div><!-- /pane-summary -->

  </main>

  <script src="../assets/js/auth-guard.js"></script>
  <script>
  (function () {
    'use strict';

    const TOKEN      = localStorage.getItem('eq_token');
    const API_BASE   = '../api';
    const API_LOGS   = API_BASE + '/teacher_logs_fetch.php';
    const API_LOG    = API_BASE + '/teacher_logs_log.php';
    const API_SUM    = API_BASE + '/teacher_interaction_summary.php';

    // ── Utility ──────────────────────────────────────────────────────────────
    function esc(str) {
      const d = document.createElement('div');
      d.textContent = str ?? '–';
      return d.innerHTML;
    }

    function apiFetch(url, opts = {}) {
      return fetch(url, {
        ...opts,
        headers: { 'Authorization': 'Bearer ' + TOKEN, 'Content-Type': 'application/json', ...(opts.headers || {}) },
      });
    }

    // ── Tab switching ─────────────────────────────────────────────────────────
    let summaryLoaded = false;
    document.querySelectorAll('.page-tab').forEach(btn => {
      btn.addEventListener('click', () => {
        const paneId = btn.dataset.pane;
        document.querySelectorAll('.page-tab').forEach(b => b.classList.toggle('active', b === btn));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.toggle('active', p.id === paneId));
        if (paneId === 'pane-summary' && !summaryLoaded) {
          summaryLoaded = true;
          loadSummaryTab();
        }
      });
    });

    // ── Populate student dropdowns from first API call ────────────────────────
    let studentListCache = [];

    function populateStudentDropdowns(students) {
      studentListCache = students || [];
      ['f-student', 'lf-student'].forEach(id => {
        const sel = document.getElementById(id);
        const cur = sel.value;
        // Remove all but the first placeholder option
        while (sel.options.length > 1) sel.remove(1);
        students.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s.id;
          opt.textContent = s.name;
          sel.appendChild(opt);
        });
        if (cur) sel.value = cur;
      });
    }

    // ── Load student list on page load ────────────────────────────────────────
    apiFetch(API_LOGS)
      .then(r => r.json())
      .then(data => {
        if (data.success) populateStudentDropdowns(data.students || []);
      })
      .catch(() => {});

    // Set today as default log date
    document.getElementById('lf-date').value = new Date().toISOString().slice(0, 10);

    // ── Indicator labels ──────────────────────────────────────────────────────
    const INDICATOR_LABELS = {
      task_completion_rate:       'Task Completion Rate',
      time_on_task:               'Time on Task',
      module_attempt_frequency:   'Module Attempt Frequency',
      response_rate:              'Response Rate',
      exp_accumulation_rate:      'XP Accumulation Rate',
      task_initiation:            'Task Initiation',
      task_persistence:           'Task Persistence',
      consistency_of_completion:  'Consistency of Completion',
      responsiveness_to_feedback: 'Responsiveness to Feedback',
      frustration_management:     'Frustration Management',
    };

    // ── TAB 1 — Fetch logs ────────────────────────────────────────────────────
    async function fetchLogs() {
      const params = new URLSearchParams();
      const sid  = document.getElementById('f-student').value;
      const type = document.getElementById('f-type').value;
      const from = document.getElementById('f-from').value;
      const to   = document.getElementById('f-to').value;
      if (sid)  params.set('student_id', sid);
      if (type) params.set('log_type', type);
      if (from) params.set('date_from', from);
      if (to)   params.set('date_to', to);

      const spinner  = document.getElementById('logs-spinner');
      const tbody    = document.getElementById('logs-tbody');
      const countEl  = document.getElementById('logs-count');

      spinner.classList.remove('hidden');
      tbody.innerHTML = '<tr><td colspan="8" class="empty-msg">Loading…</td></tr>';

      try {
        const res  = await apiFetch(API_LOGS + '?' + params.toString());
        const data = await res.json();

        if (!data.success) throw new Error(data.message || 'API error');

        if (data.students && data.students.length) populateStudentDropdowns(data.students);

        if (!data.logs || data.logs.length === 0) {
          tbody.innerHTML = '<tr><td colspan="8" class="empty-msg">No log entries match the selected filters.</td></tr>';
          countEl.textContent = '0 entries';
        } else {
          countEl.textContent = data.logs.length + ' entries';
          tbody.innerHTML = data.logs.map(r => `
            <tr>
              <td>${esc(r.student_name)}</td>
              <td>${esc((INDICATOR_LABELS[r.indicator_key] || r.indicator_key))}</td>
              <td><span class="badge badge-${r.log_type === 'engagement' ? 'engagement' : 'self-reg'}">${esc(r.log_type === 'engagement' ? 'Engagement' : 'Self-Regulation')}</span></td>
              <td><span class="badge" style="background-color:${r.source === 'quiz' ? '#3b82f6' : r.source === 'activity' ? '#10b981' : '#6b7280'};">${esc((r.source || 'other').toUpperCase())}</span></td>
              <td>${esc(r.indicator_value)}</td>
              <td>${esc(r.session_date)}</td>
              <td><span class="badge badge-${r.logged_by === 'system' ? 'system' : 'teacher'}">${esc(r.logged_by === 'system' ? 'System' : (r.teacher_name || 'Teacher'))}</span></td>
              <td>${esc(r.created_at)}</td>
            </tr>
          `).join('');
        }

        renderEngagementSummary(data.engagement_summary || {});

      } catch (err) {
        tbody.innerHTML = `<tr><td colspan="7" class="empty-msg" style="color:#dc2626;">Failed to load logs: ${esc(err.message)}</td></tr>`;
      } finally {
        spinner.classList.add('hidden');
      }
    }

    function renderEngagementSummary(summary) {
      const wrap = document.getElementById('eng-summary-wrap');
      const grid = document.getElementById('eng-summary-grid');
      const engKeys = ['task_completion_rate','time_on_task','module_attempt_frequency','response_rate','exp_accumulation_rate'];
      
      // Check if data is nested by source (new format) or flat (old format)
      const isNestedBySource = engKeys.length > 0 && summary[engKeys[0]] && typeof summary[engKeys[0]].quiz !== 'undefined';
      
      const hasData = engKeys.some(k => {
        if (!summary[k]) return false;
        if (isNestedBySource) {
          return Object.values(summary[k]).some(s => s && s.avg !== null);
        } else {
          return summary[k].avg !== null;
        }
      });
      
      if (!hasData) { wrap.style.display = 'none'; return; }

      wrap.style.display = '';
      
      if (isNestedBySource) {
        // New format: nested by source
        const sources = ['quiz', 'activity'];
        grid.innerHTML = engKeys.map(key => {
          const summaryKey = summary[key] || {};
          return sources.map(src => {
            const s   = summaryKey[src] || {};
            const avg = s.avg  != null ? parseFloat(s.avg).toFixed(2) : '–';
            const hi  = s.max_value  != null ? s.max_value  : '–';
            const lo  = s.min_value  != null ? s.min_value  : '–';
            return `
              <div class="indicator-cell">
                <div class="indicator-name">${esc(INDICATOR_LABELS[key] || key)} <span style="font-size:0.85em; color:#666;">(${src.toUpperCase()})</span></div>
                <div class="indicator-row">Class avg: <span>${esc(avg)}</span></div>
                <div class="indicator-row">Highest: <span>${esc(String(hi))}</span> (${esc(s.max_student || '–')})</div>
                <div class="indicator-row">Lowest:  <span>${esc(String(lo))}</span> (${esc(s.min_student || '–')})</div>
              </div>
            `;
          }).join('');
        }).join('');
      } else {
        // Old format: flat (backward compatibility)
        grid.innerHTML = engKeys.map(key => {
          const s   = summary[key] || {};
          const avg = s.avg  != null ? parseFloat(s.avg).toFixed(2) : '–';
          const hi  = s.max_value  != null ? s.max_value  : '–';
          const lo  = s.min_value  != null ? s.min_value  : '–';
          return `
            <div class="indicator-cell">
              <div class="indicator-name">${esc(INDICATOR_LABELS[key] || key)}</div>
              <div class="indicator-row">Class avg: <span>${esc(avg)}</span></div>
              <div class="indicator-row">Highest: <span>${esc(String(hi))}</span> (${esc(s.max_student || '–')})</div>
              <div class="indicator-row">Lowest:  <span>${esc(String(lo))}</span> (${esc(s.min_student || '–')})</div>
            </div>
          `;
        }).join('');
      }
    }

    document.getElementById('btn-apply').addEventListener('click', fetchLogs);

    // ── Log self-regulation form submit ────────────────────────────────────────
    document.getElementById('lf-submit').addEventListener('click', async () => {
      const studentId = document.getElementById('lf-student').value;
      const key       = document.getElementById('lf-indicator').value;
      const value     = document.getElementById('lf-value').value.trim();
      const date      = document.getElementById('lf-date').value;
      const msgEl     = document.getElementById('lf-msg');
      const spinner   = document.getElementById('lf-spinner');

      msgEl.textContent = '';
      if (!studentId) { msgEl.innerHTML = '<span class="alert-error">Please select a student.</span>'; return; }
      if (!value)     { msgEl.innerHTML = '<span class="alert-error">Observation text is required.</span>'; return; }

      spinner.classList.remove('hidden');
      document.getElementById('lf-submit').disabled = true;

      try {
        const res  = await apiFetch(API_LOG, {
          method: 'POST',
          body: JSON.stringify({ student_id: +studentId, indicator_key: key, indicator_value: value, session_date: date }),
        });
        const data = await res.json();
        if (data.success) {
          msgEl.innerHTML = '<span class="alert-success">&#10003; Observation saved!</span>';
          document.getElementById('lf-value').value = '';
          // Refresh logs if currently filtered
        } else {
          msgEl.innerHTML = `<span class="alert-error">${esc(data.message || 'Error saving.')}</span>`;
        }
      } catch {
        msgEl.innerHTML = '<span class="alert-error">Network error. Please try again.</span>';
      } finally {
        spinner.classList.add('hidden');
        document.getElementById('lf-submit').disabled = false;
      }
    });

    // ── TAB 2 — Summary ───────────────────────────────────────────────────────
    async function loadSummaryTab() {
      await Promise.all([
        loadSelfRegSummary(),
        loadEngagementPerStudent(),
        loadInteractionSummary(),
      ]);
    }

    async function loadSelfRegSummary() {
      try {
        const res  = await apiFetch(API_LOGS + '?log_type=self_regulation');
        const data = await res.json();
        if (!data.success) throw new Error();

        const logs = data.logs || [];
        const selfRegKeys = [
          'task_initiation', 'task_persistence', 'consistency_of_completion',
          'responsiveness_to_feedback', 'frustration_management',
        ];

        // Count by indicator
        const counts = {};
        selfRegKeys.forEach(k => counts[k] = 0);
        logs.forEach(l => { if (counts[l.indicator_key] !== undefined) counts[l.indicator_key]++; });

        const max = Math.max(...Object.values(counts), 1);
        const barList = document.getElementById('sreg-bar-list');
        if (!logs.length) { barList.innerHTML = '<p class="empty-msg">No self-regulation logs yet.</p>'; } else {
          barList.innerHTML = selfRegKeys.map(k => {
            const pct = Math.round(counts[k] / max * 100);
            return `<div class="bar-list-item">
              <span class="bar-list-label">${esc(INDICATOR_LABELS[k] || k)}</span>
              <div class="bar-list-outer"><div class="bar-list-inner" style="width:${pct}%;background:#7c3aed;"></div></div>
              <span class="bar-list-val">${counts[k]}</span>
            </div>`;
          }).join('');
        }

        // Per-student breakdown
        const students = {};
        logs.forEach(l => {
          if (!students[l.student_name]) students[l.student_name] = {};
          students[l.student_name][l.indicator_key] = (students[l.student_name][l.indicator_key] || 0) + 1;
        });

        const tbody = document.getElementById('sreg-student-tbody');
        const rows  = Object.entries(students);
        if (!rows.length) { tbody.innerHTML = '<tr><td colspan="7" class="empty-msg">No data.</td></tr>'; return; }
        tbody.innerHTML = rows.map(([name, kv]) => {
          const total = Object.values(kv).reduce((s, v) => s + v, 0);
          return `<tr>
            <td>${esc(name)}</td>
            ${selfRegKeys.map(k => `<td>${kv[k] || 0}</td>`).join('')}
            <td><strong>${total}</strong></td>
          </tr>`;
        }).join('');

        // Client-side filter
        document.getElementById('sreg-student-filter').addEventListener('input', function () {
          const q = this.value.toLowerCase();
          document.querySelectorAll('#sreg-student-tbody tr').forEach(row => {
            row.style.display = !q || row.textContent.toLowerCase().includes(q) ? '' : 'none';
          });
        });

      } catch { document.getElementById('sreg-bar-list').innerHTML = '<p class="empty-msg" style="color:#dc2626;">Failed to load.</p>'; }
    }

    async function loadEngagementPerStudent() {
      try {
        const res  = await apiFetch(API_LOGS + '?log_type=engagement');
        const data = await res.json();
        if (!data.success) throw new Error();

        const engKeys = ['task_completion_rate','time_on_task','module_attempt_frequency','response_rate','exp_accumulation_rate'];
        const logs    = data.logs || [];

        // Accumulate per student per key
        const acc = {}; // { name: { key: [values...] } }
        logs.forEach(l => {
          if (!acc[l.student_name]) acc[l.student_name] = {};
          if (!acc[l.student_name][l.indicator_key]) acc[l.student_name][l.indicator_key] = [];
          const v = parseFloat(l.indicator_value);
          if (!isNaN(v)) acc[l.student_name][l.indicator_key].push(v);
        });

        const tbody = document.getElementById('eng-student-tbody');
        const rows  = Object.entries(acc);
        if (!rows.length) { tbody.innerHTML = '<tr><td colspan="6" class="empty-msg">No engagement logs yet.</td></tr>'; return; }

        tbody.innerHTML = rows.map(([name, kv]) => {
          return `<tr>
            <td>${esc(name)}</td>
            ${engKeys.map(k => {
              const vals = kv[k] || [];
              const avg  = vals.length ? (vals.reduce((s,v)=>s+v,0)/vals.length).toFixed(1) : '–';
              return `<td>${avg}</td>`;
            }).join('')}
          </tr>`;
        }).join('');

        document.getElementById('eng-student-filter').addEventListener('input', function () {
          const q = this.value.toLowerCase();
          document.querySelectorAll('#eng-student-tbody tr').forEach(row => {
            row.style.display = !q || row.textContent.toLowerCase().includes(q) ? '' : 'none';
          });
        });

      } catch { document.getElementById('eng-student-tbody').innerHTML = '<tr><td colspan="6" class="empty-msg" style="color:#dc2626;">Failed to load.</td></tr>'; }
    }

    async function loadInteractionSummary() {
      function renderBarListEl(containerId, items, labelKey, valueKey, unit, color) {
        const el = document.getElementById(containerId);
        if (!el) return;
        if (!items || !items.length) { el.innerHTML = '<p class="empty-msg">No data yet.</p>'; return; }
        const max = Math.max(...items.map(i => +i[valueKey] || 0), 1);
        el.innerHTML = items.map(i => {
          const pct = Math.round((+i[valueKey] / max) * 100);
          return `<div class="bar-list-item">
            <span class="bar-list-label">${esc(i[labelKey])}</span>
            <div class="bar-list-outer"><div class="bar-list-inner" style="width:${pct}%;background:${color};"></div></div>
            <span class="bar-list-val">${esc(String(i[valueKey]))} ${unit}</span>
          </div>`;
        }).join('');
      }

      try {
        const [pages, clicks, hovers, scores] = await Promise.all([
          fetch(API_SUM + '?section=interaction_pages',  { headers: { 'Authorization': 'Bearer ' + TOKEN } }).then(r=>r.json()).catch(()=>null),
          fetch(API_SUM + '?section=interaction_clicks', { headers: { 'Authorization': 'Bearer ' + TOKEN } }).then(r=>r.json()).catch(()=>null),
          fetch(API_SUM + '?section=interaction_hovers', { headers: { 'Authorization': 'Bearer ' + TOKEN } }).then(r=>r.json()).catch(()=>null),
          fetch(API_SUM + '?section=interaction_scores', { headers: { 'Authorization': 'Bearer ' + TOKEN } }).then(r=>r.json()).catch(()=>null),
        ]);

        if (pages  && pages.data)   renderBarListEl('page-time-bars',  pages.data.top_pages      || [], 'page_name',     'total_seconds', 's',      '#3b82f6');
        if (clicks && clicks.data)  renderBarListEl('click-bars',      clicks.data.top_elements  || [], 'element_label', 'total_clicks',  'clicks', '#0ea5e9');
        if (hovers && hovers.data)  renderBarListEl('hover-bars',      hovers.data.top_elements  || [], 'element_label', 'total_ms',      'ms',     '#8b5cf6');
        if (scores && scores.data) {
          const top = (scores.data || []).slice(0, 10);
          renderBarListEl('score-bars', top.map(r => ({ name: r.student_name, score: r.engagement_score })), 'name', 'score', '', '#16a34a');
        }

      } catch {
        ['page-time-bars','click-bars','hover-bars','score-bars'].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.innerHTML = '<p class="empty-msg" style="color:#dc2626;">Failed to load.</p>';
        });
      }
    }

  })();
  </script>
</body>
</html>
