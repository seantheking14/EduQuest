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
      <li><a href="dashboard.php">&#127968; Dashboard</a></li>
      <li><a href="students.php">&#128100; My Students</a></li>
      <li><a href="courses.php">&#128218; My Courses</a></li>
      <li><a href="student-form.php">&#43; Add Student</a></li>
      <li><a href="analytics.php">&#128202; Analytics</a></li>
      <li><a href="profile.php">&#128100; My Profile</a></li>
      <li><a href="student-import.php" class="active">&#8679; Import Profiles</a></li>
    </ul>
    <div class="sidebar-footer">
      <span id="teacherName">Loading…</span>
      <button id="logoutBtn" class="btn btn-outline btn-sm">Sign Out</button>
    </div>
  </nav>

  <main class="main-content">
    <header class="page-header">
      <div>
        <a href="students.php" class="link-muted">&larr; Back to Students</a>
        <h2>Import Student Profiles</h2>
        <p class="muted mt-1">Upload existing student records from spreadsheets or physical document scans.</p>
      </div>
    </header>

    <!-- Tab bar -->
    <div class="import-tabs">
      <button class="import-tab active" data-tab="csv">
        <span class="tab-icon">&#128202;</span>
        <div>
          <strong>Spreadsheet / CSV Import</strong>
          <span>Bulk-import from a filled-in template</span>
        </div>
      </button>
      <button class="import-tab" data-tab="document">
        <span class="tab-icon">&#128196;</span>
        <div>
          <strong>Physical Document Upload</strong>
          <span>Upload scans, PDFs, or Word docs to create student profiles</span>
        </div>
      </button>
    </div>

    <!-- ═══════════ TAB 1: CSV Import ═══════════ -->
    <div id="tabCsv" class="import-tab-content active">

      <!-- Step A: Download template -->
      <div class="import-step-card" id="csvStepA">
        <div class="import-step-number">1</div>
        <div class="import-step-body">
          <h3>Download the Template</h3>
          <p>Fill in the CSV template with your students' information. Each row = one student.
             Open with Excel, Google Sheets, or any spreadsheet app.</p>
          <a href="../api/students/template.php" class="btn btn-primary" id="downloadTemplateBtn">
            &#8595; Download CSV Template
          </a>
          <div class="template-fields-hint mt-3">
            <strong>Template covers:</strong> Basic info · ADHD type & ratings · Challenges checklist ·
            Comorbid conditions · Medications · Accommodations · IEP / 504 status
          </div>
        </div>
      </div>

      <!-- Step B: Upload filled CSV -->
      <div class="import-step-card" id="csvStepB">
        <div class="import-step-number">2</div>
        <div class="import-step-body">
          <h3>Upload Your Completed File</h3>
          <div class="upload-zone" id="csvDropZone">
            <div class="upload-zone-inner">
              <span class="upload-icon">&#128202;</span>
              <p>Drag &amp; drop your CSV here, or click to browse</p>
              <p class="muted">Accepts .csv files — maximum 5 MB</p>
              <input type="file" id="csvFileInput" accept=".csv,.txt" />
            </div>
          </div>
          <div id="csvSelectedFile" class="selected-file-info hidden">
            <span class="doc-icon">&#128202;</span>
            <span id="csvFileName"></span>
            <button type="button" class="btn btn-outline btn-xs" id="csvClearFile">&#10005; Clear</button>
          </div>
        </div>
      </div>

      <!-- Step C: Preview & confirm -->
      <div class="import-step-card" id="csvStepC">
        <div class="import-step-number">3</div>
        <div class="import-step-body">
          <h3>Preview &amp; Confirm</h3>
          <p class="muted">Review the data before importing. Rows with errors will be skipped.</p>
          <div class="preview-actions">
            <button class="btn btn-secondary" id="previewBtn" disabled>&#128270; Preview Data</button>
            <button class="btn btn-success hidden"  id="confirmImportBtn">&#10003; Import All Valid Rows</button>
            <button class="btn btn-outline  hidden"  id="cancelPreviewBtn">&#10005; Cancel</button>
          </div>

          <!-- Preview loading -->
          <div id="previewLoading" class="loading-msg hidden">Parsing file…</div>

          <!-- Preview summary -->
          <div id="previewSummary" class="import-summary hidden">
            <div class="summary-stat summary-ok"  id="summaryValid"></div>
            <div class="summary-stat summary-err" id="summaryErrors"></div>
          </div>

          <!-- Error list -->
          <div id="previewErrors" class="error-list hidden"></div>

          <!-- Preview table -->
          <div id="previewTableWrapper" class="hidden">
            <div class="table-wrapper mt-3">
              <table class="data-table" id="previewTable">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Grade</th>
                    <th>School</th>
                    <th>ADHD Type</th>
                    <th>Severity</th>
                    <th>Conditions</th>
                    <th>Meds</th>
                    <th>Accommodations</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody id="previewTableBody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Import result -->
      <div id="csvImportResult" class="import-result hidden"></div>
    </div><!-- /tabCsv -->

    <!-- ═══════════ TAB 2: Document Upload ═══════════ -->
    <div id="tabDocument" class="import-tab-content hidden">

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
