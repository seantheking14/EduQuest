<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Analytics</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <style>
    /* ── Analytics-specific styles ── */
    .analytics-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; margin-bottom:1.5rem; }
    .stat-card { background:#fff; border-radius:10px; padding:1.25rem 1.5rem; box-shadow:0 1px 4px rgba(0,0,0,.06); }
    .stat-card .stat-value { font-size:2rem; font-weight:700; color:#1e2a3b; }
    .stat-card .stat-label { font-size:.8rem; color:#64748b; margin-top:.2rem; }
    .stat-card .stat-icon  { font-size:1.5rem; margin-bottom:.5rem; }

    .charts-row { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem; }
    @media(max-width:768px){ .charts-row { grid-template-columns:1fr; } }

    .chart-card { background:#fff; border-radius:10px; padding:1.5rem; box-shadow:0 1px 4px rgba(0,0,0,.06); }
    .chart-card h3 { margin:0 0 1.25rem; font-size:.95rem; font-weight:600; color:#1e2a3b; }

    /* Bar chart */
    .bar-chart { display:flex; flex-direction:column; gap:.6rem; }
    .bar-row { display:flex; align-items:center; gap:.75rem; }
    .bar-label { width:140px; font-size:.82rem; color:#475569; flex-shrink:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .bar-outer { flex:1; background:#f1f5f9; border-radius:999px; height:12px; overflow:hidden; }
    .bar-inner { height:100%; border-radius:999px; background:#3b82f6; transition:width .6s ease; min-width:4px; }
    .bar-count  { font-size:.8rem; color:#64748b; width:28px; text-align:right; flex-shrink:0; }d

    /* Donut-style text pies replaced with horizontal bar breakdown */
    .adhd-breakdown { display:flex; flex-direction:column; gap:.75rem; }
    .adhd-type-row   { display:flex; align-items:center; gap:.75rem; }
    .adhd-dot   { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
    .adhd-name  { font-size:.85rem; color:#475569; flex:1; }
    .adhd-pct   { font-size:.85rem; font-weight:600; color:#1e2a3b; }
    .adhd-bar-outer { width:80px; background:#f1f5f9; border-radius:999px; height:8px; }
    .adhd-bar-inner { height:100%; border-radius:999px; transition:width .6s ease; }

    /* Recent students table */
    .recent-section { background:#fff; border-radius:10px; padding:1.5rem; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:1.5rem; }
    .recent-section h3 { margin:0 0 1rem; font-size:.95rem; font-weight:600; color:#1e2a3b; }

    .badge-type { display:inline-block; border-radius:999px; padding:.15rem .6rem; font-size:.72rem; font-weight:600; }
    .badge-combined    { background:#ede9fe; color:#7c3aed; }
    .badge-inattentive { background:#fef3c7; color:#b45309; }
    .badge-hyperactive { background:#fee2e2; color:#b91c1c; }
    .badge-unspecified { background:#f1f5f9; color:#64748b; }
    .badge-mild     { background:#dcfce7; color:#166534; }
    .badge-moderate { background:#fef9c3; color:#854d0e; }
    .badge-severe   { background:#fee2e2; color:#991b1b; }

    .empty-state { text-align:center; padding:3rem 1rem; color:#94a3b8; }
    .empty-state .empty-icon { font-size:3rem; margin-bottom:.75rem; }
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
      <li><a href="grade-analytics.php">&#127942; Grade Analytics</a></li>
      <li class="nav-section-label">Insights</li>
      <li><a href="analytics.php" class="active">&#128202; Analytics</a></li>
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
      <h2>Analytics</h2>
      <?php require_once 'notifications.php'; ?>
      <button class="btn btn-outline" id="refreshBtn">&#8635; Refresh</button>
    </header>

    <!-- Top Stats Row -->
    <div class="analytics-grid" id="topStats">
      <div class="stat-card"><div class="stat-icon">&#128100;</div><div class="stat-value" id="statTotal">–</div><div class="stat-label">Total Students</div></div>
      <div class="stat-card"><div class="stat-icon">&#128218;</div><div class="stat-value" id="statCourses">–</div><div class="stat-label">Active Courses</div></div>
      <div class="stat-card"><div class="stat-icon">&#128140;</div><div class="stat-value" id="statMeds">–</div><div class="stat-label">On Medication</div></div>
      <div class="stat-card"><div class="stat-icon">&#127891;</div><div class="stat-value" id="statEnrollments">–</div><div class="stat-label">Course Enrollments</div></div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
      <!-- ADHD Type Breakdown -->
      <div class="chart-card">
        <h3>ADHD Presentation Breakdown</h3>
        <div id="adhdBreakdown" class="adhd-breakdown">
          <div class="empty-state"><div class="empty-icon">&#128202;</div><p>Loading…</p></div>
        </div>
      </div>

      <!-- Severity Distribution -->
      <div class="chart-card">
        <h3>Severity Distribution</h3>
        <div id="severityChart" class="bar-chart">
          <div class="empty-state"><div class="empty-icon">&#128202;</div><p>Loading…</p></div>
        </div>
      </div>
    </div>

    <div class="charts-row">
      <!-- Top Comorbid Conditions -->
      <div class="chart-card">
        <h3>Top Comorbid Conditions</h3>
        <div id="comorbidChart" class="bar-chart">
          <div class="empty-state"><div class="empty-icon">&#128202;</div><p>Loading…</p></div>
        </div>
      </div>

      <!-- Accommodation Categories -->
      <div class="chart-card">
        <h3>Active Accommodation Types</h3>
        <div id="accommodationChart" class="bar-chart">
          <div class="empty-state"><div class="empty-icon">&#128202;</div><p>Loading…</p></div>
        </div>
      </div>
    </div>

    <div class="charts-row">
      <!-- Grade Level Distribution -->
      <div class="chart-card">
        <h3>Students by Grade Level</h3>
        <div id="gradeChart" class="bar-chart">
          <div class="empty-state"><div class="empty-icon">&#128202;</div><p>Loading…</p></div>
        </div>
      </div>

      <!-- Course Stats -->
      <div class="chart-card">
        <h3>Course Overview</h3>
        <div id="courseStats" class="bar-chart">
          <div class="empty-state"><div class="empty-icon">&#128202;</div><p>Loading…</p></div>
        </div>
      </div>
    </div>

    <!-- Recent Students -->
    <div class="recent-section">
      <h3>Recently Added Students</h3>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr><th>Name</th><th>Grade</th><th>ADHD Type</th><th>Severity</th><th>Added</th><th></th></tr>
          </thead>
          <tbody id="recentStudents">
            <tr><td colspan="6" class="text-center muted">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/analytics.js"></script>
</body>
</html>
