<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Import Student Profiles</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/import.css" />
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
      <li><a href="student-import.php" class="active">&#8679; Import Profiles</a></li>
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
        <a href="students.php" class="link-muted">&larr; Back to Students</a>
        <h2>Import Student Profiles</h2>
        <p class="muted mt-1">Upload existing student records from physical document scans.</p>
      </div>
      <?php require_once 'notifications.php'; ?>
    </header>

    <!-- ═══════════ Document Upload ═══════════ -->
    <div id="tabDocument" class="import-tab-content active">

      <div class="import-step-card">
        <div class="import-step-number">1</div>
        <div class="import-step-body">
          <h3>How This Works</h3>
          <div class="how-it-works-grid">
            <div class="how-step">
              <span class="how-step-icon">&#8679;</span>
              <div>
                <strong>Upload Documents</strong>
                <p>Upload scanned IEPs, psychological evaluations, school reports, or any existing student profile documents.</p>
              </div>
            </div>
            <div class="how-step">
              <span class="how-step-icon">&#128196;</span>
              <div>
                <strong>Profiles Created</strong>
                <p>Each uploaded file becomes a complete student profile with the document stored and viewable directly from the profile page.</p>
              </div>
            </div>
            <div class="how-step">
              <span class="how-step-icon">&#9998;</span>
              <div>
                <strong>Optionally Enrich the Profile</strong>
                <p>Open any profile to add ADHD details, medications, accommodations, or any additional information at any time.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="import-step-card">
        <div class="import-step-number">2</div>
        <div class="import-step-body">
          <h3>Upload Documents</h3>
          <p class="muted mb-3">Supported formats: PDF, Word (.doc/.docx), JPEG, PNG, TIFF. Max 10 MB per file. Up to 10 files at once.</p>

          <div class="upload-zone" id="docDropZone">
            <div class="upload-zone-inner">
              <span class="upload-icon">&#128196;</span>
              <p>Drag &amp; drop files here, or click to browse</p>
              <p class="muted">PDF · Word · JPEG · PNG · TIFF</p>
              <input type="file" id="docFilesInput" multiple
                     accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.tif,.tiff" />
            </div>
          </div>

          <!-- File queue -->
          <div id="docFileQueue" class="doc-queue mt-3"></div>

          <!-- Options -->
          <div id="docUploadOptions" class="hidden mt-3">
            <div class="form-row">
              <div class="form-group">
                <label>Default Document Type</label>
                <select id="docTypeSelect">
                  <option value="other">Other / General Profile</option>
                  <option value="iep">IEP (Individualized Education Program)</option>
                  <option value="psychological_evaluation">Psychological Evaluation</option>
                  <option value="medical_report">Medical Report</option>
                  <option value="progress_report">Progress Report</option>
                  <option value="504_plan">504 Plan</option>
                  <option value="parent_consent">Parent Consent Form</option>
                </select>
              </div>
              <div class="form-group">
                <label>Optional: Student Name Hints</label>
                <p class="muted" style="font-size:0.78rem; margin-top:0.25rem;">
                  Enter names to pre-populate profile names.
                  Each line corresponds to a file in the order listed above.
                </p>
                <textarea id="nameHints" rows="3"
                          placeholder="Jane Smith&#10;John Doe&#10;Alex Johnson"></textarea>
              </div>
            </div>
            <button class="btn btn-primary" id="uploadDocsBtn">
              &#8679; Upload &amp; Create Profiles
            </button>
          </div>

          <!-- Upload progress -->
          <div id="docUploadProgress" class="hidden">
            <div class="progress-bar-bg">
              <div class="progress-bar-fill" id="docProgressFill" style="width:0%"></div>
            </div>
            <p id="docProgressText" class="muted mt-1">Uploading…</p>
          </div>
        </div>
      </div>

      <!-- Upload results -->
      <div id="docUploadResult" class="hidden">
        <div class="import-step-card">
          <div class="import-step-number done">&#10003;</div>
          <div class="import-step-body">
            <h3>Profiles Created</h3>
            <p class="muted mb-3">Each uploaded file has been saved as a complete student profile. Click <strong>View Profile</strong> to open it.</p>
            <div id="draftProfileList" class="draft-list"></div>
          </div>
        </div>
      </div>

    </div><!-- /tabDocument -->

  </main>

  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/student-import.js"></script>
</body>
</html>
