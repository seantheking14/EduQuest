<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Grade Analytics</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <style>
    /* ── Grade Analytics styles ── */
    .analytics-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem; margin-bottom:1.5rem; }
    .stat-card { background:#fff; border-radius:10px; padding:1.25rem 1.5rem; box-shadow:0 1px 4px rgba(0,0,0,.06); }
    .stat-card .stat-icon  { font-size:1.5rem; margin-bottom:.5rem; }
    .stat-card .stat-value { font-size:2rem; font-weight:700; color:#1e2a3b; }
    .stat-card .stat-label { font-size:.8rem; color:#64748b; margin-top:.2rem; }

    .charts-row { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem; }
    @media(max-width:768px){ .charts-row { grid-template-columns:1fr; } }

    .chart-card { background:#fff; border-radius:10px; padding:1.5rem; box-shadow:0 1px 4px rgba(0,0,0,.06); }
    .chart-card h3 { margin:0 0 1.25rem; font-size:.95rem; font-weight:600; color:#1e2a3b; }

    /* Bar chart */
    .bar-chart { display:flex; flex-direction:column; gap:.6rem; }
    .bar-row { display:flex; align-items:center; gap:.75rem; }
    .bar-label { width:140px; font-size:.82rem; color:#475569; flex-shrink:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .bar-outer { flex:1; background:#f1f5f9; border-radius:999px; height:12px; overflow:hidden; }
    .bar-inner { height:100%; border-radius:999px; transition:width .6s ease; min-width:4px; }
    .bar-count { font-size:.8rem; color:#64748b; width:40px; text-align:right; flex-shrink:0; }

    /* Trend chart (simple inline bars for monthly averages) */
    .trend-chart { display:flex; align-items:flex-end; gap:6px; height:140px; padding-top:.5rem; }
    .trend-bar-wrap { display:flex; flex-direction:column; align-items:center; flex:1; min-width:0; }
    .trend-bar { width:100%; max-width:48px; border-radius:6px 6px 0 0; transition:height .6s ease; min-height:4px; }
    .trend-label { font-size:.65rem; color:#94a3b8; margin-top:4px; white-space:nowrap; }
    .trend-val   { font-size:.7rem; font-weight:600; color:#1e2a3b; margin-bottom:2px; }

    /* Student ranking table */
    .ranking-table { width:100%; border-collapse:collapse; }
    .ranking-table th { text-align:left; font-size:.75rem; color:#64748b; font-weight:600; padding:.5rem .75rem; border-bottom:1px solid #e2e8f0; }
    .ranking-table td { font-size:.82rem; color:#1e2a3b; padding:.6rem .75rem; border-bottom:1px solid #f1f5f9; }
    .ranking-table tr:hover { background:#f8fafc; }

    /* Letter-grade colors */
    .grade-A { background:#16a34a; } .grade-B { background:#3b82f6; }
    .grade-C { background:#d97706; } .grade-D { background:#ea580c; }
    .grade-F { background:#dc2626; }

    /* Type colors */
    .type-quiz { background:#8b5cf6; }          .type-exam { background:#dc2626; }
    .type-assignment { background:#3b82f6; }     .type-project { background:#0ea5e9; }
    .type-participation { background:#16a34a; }  .type-final { background:#b91c1c; }

    .badge-type { display:inline-block; border-radius:999px; padding:.15rem .6rem; font-size:.72rem; font-weight:600; }
    .badge-a { background:#dcfce7; color:#166534; } .badge-b { background:#dbeafe; color:#1e40af; }
    .badge-c { background:#fef9c3; color:#854d0e; } .badge-d { background:#ffedd5; color:#9a3412; }
    .badge-f { background:#fee2e2; color:#991b1b; }

    .pct-bar { display:inline-block; height:8px; border-radius:4px; vertical-align:middle; margin-right:6px; }

    .recent-section { background:#fff; border-radius:10px; padding:1.5rem; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:1.5rem; }
    .recent-section h3 { margin:0 0 1rem; font-size:.95rem; font-weight:600; color:#1e2a3b; }

    .empty-state { text-align:center; padding:3rem 1rem; color:#94a3b8; }
    .empty-state .empty-icon { font-size:3rem; margin-bottom:.75rem; }

    /* Modal */
    .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:1000; }
    .modal-card { background:#fff; border-radius:12px; width:100%; max-width:540px; box-shadow:0 8px 32px rgba(0,0,0,.18); animation:modalIn .2s ease; }
    @keyframes modalIn { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
    .modal-header { display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid #e2e8f0; }
    .modal-header h3 { margin:0; font-size:1rem; font-weight:600; color:#1e2a3b; }
    .modal-close { background:none; border:none; font-size:1.4rem; cursor:pointer; color:#94a3b8; padding:0 .25rem; }
    .modal-close:hover { color:#1e2a3b; }
    .modal-body { padding:1.5rem; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    .form-row.cols-3 { grid-template-columns:1fr 1fr 1fr; }
    .form-group { margin-bottom:1rem; }
    .form-group label { display:block; font-size:.8rem; font-weight:600; color:#475569; margin-bottom:.3rem; }
    .form-group input, .form-group select, .form-group textarea { width:100%; padding:.55rem .75rem; border:1px solid #cbd5e1; border-radius:8px; font-size:.85rem; color:#1e2a3b; background:#fff; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.15); }
    .form-actions { display:flex; justify-content:flex-end; gap:.75rem; padding-top:.5rem; }
    .alert-error { background:#fee2e2; color:#991b1b; padding:.75rem 1rem; border-radius:8px; font-size:.82rem; margin-bottom:.75rem; }

    /* Delete btn in recent table */
    .btn-delete { background:none; border:none; color:#dc2626; cursor:pointer; font-size:.8rem; padding:.2rem .5rem; border-radius:4px; }
    .btn-delete:hover { background:#fee2e2; }
  </style>
</head>
<body class="app-page">

  <!-- Sidebar -->
  <nav class="sidebar">
    <div class="sidebar-logo">&#127891; EduQuest</div>
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
      <li><a href="grade-analytics.php" class="active">&#127942; Grade Analytics</a></li>
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

  <!-- Main Content -->
  <main class="main-content">
    <header class="page-header">
      <h2>Grade Analytics</h2>
      <?php require_once 'notifications.php'; ?>
      <div>
        <button class="btn btn-primary" id="addGradeBtn">&#43; Add Grade</button>
        <button class="btn btn-outline" id="refreshBtn">&#8635; Refresh</button>
      </div>
    </header>

    <!-- Add Grade Modal -->
    <div id="gradeModal" class="modal-overlay" style="display:none">
      <div class="modal-card">
        <div class="modal-header">
          <h3>Add New Grade</h3>
          <button class="modal-close" id="closeModal">&times;</button>
        </div>
        <form id="gradeForm" class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label for="gradeStudent">Student *</label>
              <select id="gradeStudent" required><option value="">Select a student…</option></select>
            </div>
            <div class="form-group">
              <label for="gradeType">Assessment Type *</label>
              <select id="gradeType" required>
                <option value="quiz">Quiz</option>
                <option value="assignment" selected>Assignment</option>
                <option value="exam">Exam</option>
                <option value="project">Project</option>
                <option value="participation">Participation</option>
                <option value="final">Final</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="gradeName">Assessment Name *</label>
            <input type="text" id="gradeName" placeholder="e.g. Math Quiz 1" required />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="gradeScore">Score *</label>
              <input type="number" id="gradeScore" min="0" step="0.01" required />
            </div>
            <div class="form-group">
              <label for="gradeMax">Max Score</label>
              <input type="number" id="gradeMax" value="100" min="0.01" step="0.01" required />
            </div>
            <div class="form-group">
              <label for="gradeDate">Date *</label>
              <input type="date" id="gradeDate" required />
            </div>
          </div>
          <div class="form-group">
            <label for="gradeRemarks">Remarks</label>
            <textarea id="gradeRemarks" rows="2" placeholder="Optional notes…"></textarea>
          </div>
          <div id="gradeFormError" class="alert alert-error" style="display:none"></div>
          <div class="form-actions">
            <button type="button" class="btn btn-outline" id="cancelModal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="saveGradeBtn">Save Grade</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Top Stats -->
    <div class="analytics-grid" id="topStats">
      <div class="stat-card"><div class="stat-icon">&#128203;</div><div class="stat-value" id="statTotal">–</div><div class="stat-label">Total Assessments</div></div>
      <div class="stat-card"><div class="stat-icon">&#128100;</div><div class="stat-value" id="statStudents">–</div><div class="stat-label">Students Graded</div></div>
      <div class="stat-card"><div class="stat-icon">&#127942;</div><div class="stat-value" id="statAvg">–</div><div class="stat-label">Class Average</div></div>
      <div class="stat-card"><div class="stat-icon">&#128200;</div><div class="stat-value" id="statHigh">–</div><div class="stat-label">Highest Score</div></div>
      <div class="stat-card"><div class="stat-icon">&#128201;</div><div class="stat-value" id="statLow">–</div><div class="stat-label">Lowest Score</div></div>
    </div>

    <!-- Charts Row 1 -->
    <div class="charts-row">
      <div class="chart-card">
        <h3>Grade Distribution</h3>
        <div id="distributionChart" class="bar-chart">
          <div class="empty-state"><div class="empty-icon">&#128202;</div><p>Loading…</p></div>
        </div>
      </div>
      <div class="chart-card">
        <h3>Average by Assessment Type</h3>
        <div id="typeChart" class="bar-chart">
          <div class="empty-state"><div class="empty-icon">&#128202;</div><p>Loading…</p></div>
        </div>
      </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="charts-row">
      <div class="chart-card">
        <h3>Monthly Performance Trend</h3>
        <div id="trendChart" class="trend-chart">
          <div class="empty-state"><div class="empty-icon">&#128202;</div><p>Loading…</p></div>
        </div>
      </div>
      <div class="chart-card">
        <h3>Student Rankings</h3>
        <div id="rankingPanel" style="max-height:260px;overflow-y:auto">
          <div class="empty-state"><div class="empty-icon">&#128202;</div><p>Loading…</p></div>
        </div>
      </div>
    </div>

    <!-- Recent Grades Table -->
    <div class="recent-section">
      <h3>Recent Grades</h3>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr><th>Student</th><th>Assessment</th><th>Type</th><th>Score</th><th>%</th><th>Date</th><th></th></tr>
          </thead>
          <tbody id="recentGrades">
            <tr><td colspan="6" class="text-center muted">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/grade-analytics.js"></script>
</body>
</html>
