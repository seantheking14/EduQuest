<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – My Profile</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <style>
    .profile-card { display:flex; gap:2rem; align-items:flex-start; background:#fff; border-radius:10px; padding:2rem; margin-bottom:1.5rem; box-shadow:0 1px 4px rgba(0,0,0,.06); }
    .profile-avatar { width:96px; height:96px; border-radius:50%; background:#3b82f6; color:#fff; font-size:2.2rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .profile-meta h2 { margin:0 0 .3rem; font-size:1.4rem; }
    .profile-meta .badge { display:inline-block; background:#eff6ff; color:#2563eb; border-radius:999px; padding:.2rem .75rem; font-size:.75rem; font-weight:600; margin-left:.5rem; }
    .profile-meta .sub { color:#64748b; font-size:.9rem; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    .form-grid .full { grid-column:1 / -1; }
    .section-card { background:#fff; border-radius:10px; padding:1.75rem; margin-bottom:1.5rem; box-shadow:0 1px 4px rgba(0,0,0,.06); }
    .section-card h3 { margin:0 0 1.25rem; font-size:1rem; font-weight:600; color:#1e2a3b; border-bottom:1px solid #e2e8f0; padding-bottom:.75rem; }
    .form-group { margin-bottom:0; }
    .form-group label { display:block; font-size:.82rem; font-weight:600; color:#475569; margin-bottom:.35rem; }
    .form-group input, .form-group select { width:100%; padding:.55rem .75rem; border:1px solid #cbd5e1; border-radius:6px; font-size:.9rem; background:#f8fafc; }
    .form-group input:disabled { background:#f1f5f9; color:#94a3b8; cursor:not-allowed; }
    .form-group input:focus { outline:none; border-color:#3b82f6; background:#fff; }
    .btn-row { display:flex; gap:.75rem; margin-top:1.25rem; }
    .alert { padding:.75rem 1rem; border-radius:6px; font-size:.88rem; margin-bottom:1rem; }
    .alert-success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
    .alert-error   { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
    /* Password modal */
    .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:1000; }
    .modal-box { background:#fff; border-radius:12px; padding:2rem; width:420px; max-width:95vw; }
    .modal-box h3 { margin:0 0 1.25rem; }
    .modal-box .form-group { margin-bottom:1rem; }
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
      <li><a href="analytics.php">&#128202; Analytics</a></li>
      <li><a href="behavioral-logs.php">&#128203; Behavioral Logs</a></li>
      <li class="nav-section-label">Settings</li>
      <li><a href="gamification-settings.php">&#127918; Gamification</a></li>
      <li><a href="profile.php" class="active">&#128100; My Profile</a></li>
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
      <h2>My Profile</h2>
      <?php require_once 'notifications.php'; ?>
    </header>

    <div id="alertBox" class="alert hidden"></div>

    <!-- Profile Header Card -->
    <div class="profile-card">
      <div class="profile-avatar" id="avatarInitials">–</div>
      <div class="profile-meta">
        <h2 id="displayName">Loading…<span class="badge" id="roleBadge">Teacher</span></h2>
        <div class="sub" id="displayEmail"></div>
        <div class="sub" id="displaySchool"></div>
        <div class="sub" id="displayDept"></div>
        <div class="sub" id="memberSince" style="margin-top:.5rem;color:#94a3b8;font-size:.8rem;"></div>
      </div>
    </div>

    <!-- Personal Information -->
    <div class="section-card">
      <h3>Personal Information</h3>
      <div class="form-grid">
        <div class="form-group">
          <label>First Name *</label>
          <input type="text" id="firstName" placeholder="First name" />
        </div>
        <div class="form-group">
          <label>Last Name *</label>
          <input type="text" id="lastName" placeholder="Last name" />
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" id="email" disabled />
        </div>
        <div class="form-group">
          <label>Department</label>
          <input type="text" id="department" placeholder="e.g. Special Education" />
        </div>
        <div class="form-group full">
          <label>School / Institution</label>
          <input type="text" id="schoolName" placeholder="School name" />
        </div>
      </div>
      <div class="btn-row">
        <button class="btn btn-primary" id="saveProfileBtn">Save Changes</button>
        <button class="btn btn-outline" id="changePasswordBtn">Change Password</button>
      </div>
    </div>
  </main>

  <!-- Change Password Modal -->
  <div id="passwordModal" class="modal-overlay hidden">
    <div class="modal-box">
      <h3>Change Password</h3>
      <div id="pwModalAlert" class="alert hidden"></div>
      <div class="form-group">
        <label>Current Password *</label>
        <input type="password" id="currentPassword" placeholder="Your current password" />
      </div>
      <div class="form-group">
        <label>New Password *</label>
        <input type="password" id="newPassword" placeholder="At least 8 characters" />
      </div>
      <div class="form-group">
        <label>Confirm New Password *</label>
        <input type="password" id="confirmPassword" placeholder="Repeat new password" />
      </div>
      <div class="btn-row">
        <button class="btn btn-primary" id="submitPasswordBtn">Update Password</button>
        <button class="btn btn-outline" id="cancelPasswordBtn">Cancel</button>
      </div>
    </div>
  </div>

  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/profile.js"></script>
</body>
</html>
