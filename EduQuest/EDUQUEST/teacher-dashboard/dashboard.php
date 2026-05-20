<?php require_once __DIR__ . '/../gamified_flash.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Dashboard</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/gamified_popup.css" />
</head>
<body class="app-page">

  <!-- Sidebar -->
  <nav class="sidebar">
    <div class="sidebar-logo">
      <span>&#127891;</span> EduQuest
    </div>
    <ul class="sidebar-nav">
      <li class="nav-section-label">Overview</li>
      <li><a href="dashboard.php" class="active">&#127968; Dashboard</a></li>
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

    <!-- Welcome Banner -->
    <div class="welcome-banner">
      <div class="wb-content">
        <div class="wb-greeting">
          <h1 id="wbGreeting">Welcome back!</h1>
          <p id="wbSubtitle">Here's what's happening in your classroom today.</p>
        </div>
        <div class="wb-meta">
          <div class="wb-date" id="wbDate"></div>
          <?php require_once 'notifications.php'; ?>
          <a href="student-form.php" class="btn btn-primary btn-sm">&#43; Add Student</a>
        </div>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row" id="statsRow">
      <div class="stat-card sc-blue">
        <div class="sc-icon">&#128100;</div>
        <div class="sc-body">
          <div class="stat-value" id="statTotal">–</div>
          <div class="stat-label">Total Students</div>
        </div>
      </div>
      <div class="stat-card sc-green">
        <div class="sc-icon">&#9989;</div>
        <div class="sc-body">
          <div class="stat-value" id="statActive">–</div>
          <div class="stat-label">Active Students</div>
        </div>
      </div>
      <div class="stat-card sc-amber">
        <div class="sc-icon">&#128683;</div>
        <div class="sc-body">
          <div class="stat-value" id="statInactive">–</div>
          <div class="stat-label">Inactive Students</div>
        </div>
      </div>
      <div class="stat-card sc-purple" id="statAssignCard" style="cursor:pointer;" onclick="document.getElementById('assignmentActivitySection').scrollIntoView({behavior:'smooth'})">
        <div class="sc-icon">&#128203;</div>
        <div class="sc-body">
          <div class="stat-value" id="statAssignActive">–</div>
          <div class="stat-label">Active Assignments</div>
        </div>
      </div>
    </div>

    <!-- Assignment Activity -->
    <section class="card" id="assignmentActivitySection" style="margin-bottom:1.5rem;">
      <div class="card-header"><h3>&#128203; Assignment Activity</h3></div>
      <div class="card-body" id="assignmentActivityBody">
        <p class="muted">Loading…</p>
      </div>
    </section>

    <!-- Recent Students Table -->
    <section class="card">
      <div class="card-header dash-card-header">
        <div class="dch-left">
          <h3>&#128100; Recent Students</h3>
          <span class="dch-count" id="studentCount"></span>
        </div>
        <div class="dch-right">
          <input type="text" id="searchInput" placeholder="&#128269; Search students…" class="dash-search" />
          <a href="students.php" class="btn btn-outline btn-sm">View All &rarr;</a>
        </div>
      </div>
      <div class="table-wrapper">
        <table class="data-table dash-table" id="studentTable">
          <thead>
            <tr>
              <th>Student</th>
              <th>Grade</th>
              <th>ADHD Type</th>
              <th>Severity</th>
              <th>School</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="studentTableBody">
            <tr><td colspan="6" class="text-center muted">Loading…</td></tr>
          </tbody>
        </table>
      </div>
      <div id="pagination" class="pagination" style="padding:0.75rem 1.25rem"></div>
    </section>

  </main>

  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/gamified_popup.js"></script>
  <script src="../assets/js/dashboard.js"></script>
  <script>
  /* Welcome banner greeting + date */
  (function () {
    const h = new Date().getHours();
    const greeting = h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
    const dateStr  = new Date().toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    const teacher  = JSON.parse(localStorage.getItem('eq_teacher') || '{}');
    const name     = teacher.first_name || '';
    document.getElementById('wbGreeting').textContent  = name ? greeting + ', ' + name + '!' : greeting + '!';
    document.getElementById('wbSubtitle').textContent  = 'Here\'s what\'s happening in your classroom today.';
    document.getElementById('wbDate').textContent      = dateStr;
    if (name) {
      const initials = (name[0] + (teacher.last_name ? teacher.last_name[0] : '')).toUpperCase();
      const el = document.getElementById('teacherAvatarInitials');
      if (el) el.textContent = initials;
    }
  })();
  </script>
  <?php echo render_popup_flash(); ?>
</body>
</html>
