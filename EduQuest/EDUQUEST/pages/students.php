<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – My Students</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body class="app-page">

  <nav class="sidebar">
    <div class="sidebar-logo"><span>&#127891;</span> EduQuest</div>
    <ul class="sidebar-nav">
      <li><a href="dashboard.php">&#127968; Dashboard</a></li>
      <li><a href="students.php" class="active">&#128100; My Students</a></li>
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

  <main class="main-content">
    <header class="page-header">
      <h2>My Students</h2>
      <a href="student-form.php" class="btn btn-primary">&#43; Add New Student</a>
    </header>

    <div class="filter-bar">
      <input type="text" id="searchInput" placeholder="Search by name or student ID…" class="search-input" />
      <select id="filterAdhd">
        <option value="">All ADHD Types</option>
        <option value="predominantly_inattentive">Predominantly Inattentive</option>
        <option value="predominantly_hyperactive_impulsive">Hyperactive-Impulsive</option>
        <option value="combined_presentation">Combined Presentation</option>
      </select>
    </div>

    <div class="student-grid" id="studentGrid">
      <div class="loading-msg">Loading students…</div>
    </div>

    <div id="pagination" class="pagination"></div>
  </main>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal-overlay hidden">
    <div class="modal">
      <h3>Archive Student Profile?</h3>
      <p>This will archive <strong id="deleteStudentName"></strong>'s profile. You can restore it later from the admin panel.</p>
      <div class="modal-actions">
        <button class="btn btn-outline" id="cancelDeleteBtn">Cancel</button>
        <button class="btn btn-danger" id="confirmDeleteBtn">Archive Profile</button>
      </div>
    </div>
  </div>

  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/students.js"></script>
</body>
</html>
