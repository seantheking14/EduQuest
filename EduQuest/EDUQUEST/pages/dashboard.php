<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Dashboard</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body class="app-page">

  <!-- Sidebar -->
  <nav class="sidebar">
    <div class="sidebar-logo">
      <span>&#127891;</span> EduQuest
    </div>
    <ul class="sidebar-nav">
      <li><a href="dashboard.php" class="active">&#127968; Dashboard</a></li>
      <li><a href="students.php">&#128100; My Students</a></li>
      <li><a href="courses.php">&#128218; My Courses</a></li>
      <li><a href="student-form.php">&#43; Add Student</a></li>
      <li><a href="analytics.php">&#128202; Analytics</a></li>
      <li><a href="profile.php">&#128100; My Profile</a></li>
    </ul>
    <div class="sidebar-footer">
      <span id="teacherName">Loading…</span>
      <button id="logoutBtn" class="btn btn-outline btn-sm">Sign Out</button>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="main-content">
    <header class="page-header">
      <h2>Dashboard</h2>
      <a href="student-form.php" class="btn btn-primary">&#43; Add New Student</a>
    </header>

    <!-- Stats Row -->
    <div class="stats-row" id="statsRow">
      <div class="stat-card">
        <div class="stat-value" id="statTotal">–</div>
        <div class="stat-label">Total Students</div>
      </div>
      <div class="stat-card">
        <div class="stat-value" id="statCombined">–</div>
        <div class="stat-label">Combined Presentation</div>
      </div>
      <div class="stat-card">
        <div class="stat-value" id="statInattentive">–</div>
        <div class="stat-label">Predominantly Inattentive</div>
      </div>
      <div class="stat-card">
        <div class="stat-value" id="statHyper">–</div>
        <div class="stat-label">Hyperactive-Impulsive</div>
      </div>
    </div>

    <!-- Recent Students Table -->
    <section class="card mt-4">
      <div class="card-header">
        <h3>Recent Student Profiles</h3>
        <a href="students.php" class="link-muted">View All &rarr;</a>
      </div>
      <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by name or student ID…" />
      </div>
      <div class="table-wrapper">
        <table class="data-table" id="studentTable">
          <thead>
            <tr>
              <th>Name</th>
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
      <div id="pagination" class="pagination"></div>
    </section>
  </main>

  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/dashboard.js"></script>
</body>
</html>
