<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – My Courses</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/courses.css" />
</head>
<body class="app-page">

  <nav class="sidebar">
    <div class="sidebar-logo"><span>&#127891;</span> EduQuest</div>
    <ul class="sidebar-nav">
      <li class="nav-section-label">Overview</li>
      <li><a href="dashboard.php">&#127968; Dashboard</a></li>
      <li class="nav-section-label">Students</li>
      <li><a href="students.php">&#128100; My Students</a></li>
      <li><a href="student-form.php">&#43; Add Student</a></li>
      <li class="nav-section-label">Academic</li>
      <li><a href="courses.php" class="active">&#128218; My Courses</a></li>
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
      <div>
        <h2>My Courses</h2>
        <p class="muted mt-1">Create and manage course content for your students.</p>
      </div>
      <?php require_once 'notifications.php'; ?>
      <button class="btn btn-primary" id="newCourseBtn">&#43; New Course</button>
    </header>

    <!-- Search -->
    <div class="search-bar-row">
      <input type="search" id="searchInput" class="search-input" placeholder="Search courses…" />
    </div>

    <!-- Loading / empty states -->
    <div id="coursesLoading" class="loading-msg">Loading courses…</div>
    <div id="coursesEmpty"   class="empty-state hidden">
      <span class="empty-icon">&#128218;</span>
      <h3>No courses yet</h3>
      <p>Create your first course to start adding lessons and materials.</p>
      <button class="btn btn-primary" id="newCourseEmptyBtn">&#43; Create Course</button>
    </div>

    <!-- Course grid -->
    <div id="coursesGrid" class="course-grid hidden"></div>

    <!-- Pagination -->
    <div id="paginationArea" class="pagination hidden"></div>
  </main>

  <!-- ═══ Create / Edit Course Modal ═══ -->
  <div id="courseModal" class="modal-backdrop hidden">
    <div class="modal-box">
      <div class="modal-header">
        <h3 id="courseModalTitle">New Course</h3>
        <button type="button" class="modal-close" id="closeModalBtn">&#10005;</button>
      </div>
      <form id="courseForm" novalidate>
        <input type="hidden" id="courseId" value="" />
        <div class="modal-body">
          <div id="courseFormAlert" class="alert hidden"></div>

          <div class="form-group">
            <label>Course Title *</label>
            <input type="text" id="courseTitle" placeholder="e.g. English Language Arts – Grade 4" required />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Subject</label>
              <input type="text" id="courseSubject" placeholder="e.g. Mathematics, Science" />
            </div>
            <div class="form-group">
              <label>Grade Level</label>
              <input type="text" id="courseGrade" placeholder="e.g. Grade 3, Year 8" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>School Year</label>
              <input type="text" id="courseYear" placeholder="e.g. 2025–2026" />
            </div>
            <div class="form-group">
              <label>Cover Colour</label>
              <div class="color-picker-row">
                <input type="color" id="courseColor" value="#6366f1" />
                <div class="color-presets">
                  <button type="button" class="color-swatch" style="background:#6366f1" data-color="#6366f1"></button>
                  <button type="button" class="color-swatch" style="background:#0ea5e9" data-color="#0ea5e9"></button>
                  <button type="button" class="color-swatch" style="background:#10b981" data-color="#10b981"></button>
                  <button type="button" class="color-swatch" style="background:#f59e0b" data-color="#f59e0b"></button>
                  <button type="button" class="color-swatch" style="background:#ef4444" data-color="#ef4444"></button>
                  <button type="button" class="color-swatch" style="background:#8b5cf6" data-color="#8b5cf6"></button>
                  <button type="button" class="color-swatch" style="background:#ec4899" data-color="#ec4899"></button>
                  <button type="button" class="color-swatch" style="background:#64748b" data-color="#64748b"></button>
                </div>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea id="courseDescription" rows="3" placeholder="Brief description of this course…"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" id="cancelModalBtn">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveCourseBtnModal">
            <span class="btn-text">Save Course</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/courses.js"></script>
</body>
</html>
