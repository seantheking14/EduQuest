<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – My Students</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <style>
    .suggestions-header { border-bottom: 2px solid #e2e8f0; padding-bottom: 0.75rem; }
    .suggestions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem; }
    .suggest-card { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; transition: border-color 0.15s, box-shadow 0.15s; }
    .suggest-card:hover { border-color: #3b82f6; box-shadow: 0 2px 8px rgba(59,130,246,0.08); }
    .suggest-avatar { width: 44px; height: 44px; border-radius: 50%; background: #dbeafe; color: #3b82f6; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
    .suggest-info { flex: 1; min-width: 0; }
    .suggest-name { font-weight: 600; color: #1e293b; }
    .suggest-meta { font-size: 0.82rem; color: #64748b; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .suggest-actions { flex-shrink: 0; display: flex; gap: 0.5rem; }
    .mt-4 { margin-top: 2rem; }
  </style>
</head>
<body class="app-page">

  <nav class="sidebar">
    <div class="sidebar-logo"><span>&#127891;</span> EduQuest</div>
    <ul class="sidebar-nav">
      <li class="nav-section-label">Overview</li>
      <li><a href="dashboard.php">&#127968; Dashboard</a></li>
      <li class="nav-section-label">Students</li>
      <li><a href="students.php" class="active">&#128100; My Students</a></li>
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

  <main class="main-content">
    <header class="page-header">
      <h2>My Students</h2>
      <?php require_once 'notifications.php'; ?>
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

    <!-- ═══ Registered Student Suggestions ═══ -->
    <section id="suggestionsSection" class="mt-4 hidden">
      <div class="suggestions-header">
        <h3>📋 Registered Students</h3>
        <p class="muted" style="font-size:0.85rem;margin:0.25rem 0 0">Students who registered themselves. Add them to your class to manage their profile and diagnostic info.</p>
      </div>
      <div class="filter-bar" style="margin-top:0.75rem">
        <input type="text" id="suggestSearch" placeholder="Search registered students by name or email…" class="search-input" />
      </div>
      <div class="suggestions-grid" id="suggestionsGrid"></div>
      <div id="suggestPagination" class="pagination"></div>
    </section>
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

  <!-- Link Student Confirmation Modal -->
  <div id="linkModal" class="modal-overlay hidden">
    <div class="modal">
      <h3>Add Registered Student?</h3>
      <p>This will add <strong id="linkStudentName"></strong> to your class. You'll be able to view, edit, and manage their full profile including diagnostic information.</p>
      <div class="modal-actions">
        <button class="btn btn-outline" id="cancelLinkBtn">Cancel</button>
        <button class="btn btn-primary" id="confirmLinkBtn">Add to My Students</button>
      </div>
    </div>
  </div>

  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/students.js"></script>
</body>
</html>
