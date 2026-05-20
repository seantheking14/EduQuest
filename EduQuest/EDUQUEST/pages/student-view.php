<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Student Profile View</title>
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

  <main class="main-content" id="profileContent">
    <div class="loading-msg">Loading student profile…</div>
  </main>

  <!-- Add Note Modal -->
  <div id="noteModal" class="modal-overlay hidden">
    <div class="modal modal-wide">
      <h3>Add Observation / Note</h3>
      <div class="form-row">
        <div class="form-group">
          <label>Date</label>
          <input type="date" id="noteDate" />
        </div>
        <div class="form-group">
          <label>Note Type</label>
          <select id="noteType">
            <option value="general">General</option>
            <option value="observation">Observation</option>
            <option value="progress">Progress</option>
            <option value="incident">Incident</option>
            <option value="meeting">Meeting</option>
          </select>
        </div>
        <div class="form-group">
          <label>Subject Area</label>
          <input type="text" id="noteSubject" placeholder="Math, Reading, Behavior…" />
        </div>
      </div>
      <div class="form-group">
        <label>Note *</label>
        <textarea id="noteContent" rows="5" placeholder="Write your observation or note here…"></textarea>
      </div>
      <div class="form-group">
        <label class="checkbox-item">
          <input type="checkbox" id="notePrivate" /> Mark as private (visible to me only)
        </label>
      </div>
      <div class="modal-actions">
        <button class="btn btn-outline" id="cancelNoteBtn">Cancel</button>
        <button class="btn btn-primary" id="saveNoteBtn">Save Note</button>
      </div>
    </div>
  </div>

  <script src="../assets/js/xlsx.full.min.js"></script>
  <script src="../assets/js/mammoth.browser.min.js"></script>
  <script src="../assets/js/auth-guard.js"></script>
  <script src="../../shared/utils/pet-sprites.js"></script>
  <script src="../assets/js/student-view.js"></script>
</body>
</html>
