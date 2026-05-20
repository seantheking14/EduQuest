<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Course</title>
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

  <main class="main-content cv-main">

    <!-- Course banner -->
    <div class="cv-banner" id="cvBanner">
      <div class="cv-banner-inner">
        <a href="courses.php" class="cv-back-link">&larr; All Courses</a>
        <div class="cv-banner-meta">
          <span id="cvSubjectBadge" class="cv-subject-badge hidden"></span>
          <span id="cvGradeBadge"   class="cv-grade-badge   hidden"></span>
          <span id="cvYearBadge"    class="cv-year-badge    hidden"></span>
        </div>
        <h1 id="cvTitle">Loading…</h1>
        <p  id="cvDescription" class="cv-description"></p>
      </div>
    </div>

    <!-- Tab nav -->
    <nav class="cv-tabs" id="cvTabs">
      <button class="cv-tab active" data-tab="content">&#128218; Content</button>
      <button class="cv-tab" data-tab="announcements">&#128226; Announcements</button>
      <button class="cv-tab" data-tab="students">&#128101; Students</button>
      <button class="cv-tab" data-tab="settings">&#9881; Settings</button>
    </nav>

    <div id="cvAlert" class="alert hidden" style="margin:0 0 1rem;"></div>

    <!-- ══════════════════════════════════════
         TAB: CONTENT
         ══════════════════════════════════════ -->
    <div id="tabContent" class="cv-tab-panel">

      <div class="cv-toolbar">
        <p class="muted" style="font-size:0.88rem">
          Organise lessons into modules. Students see content in the order listed.
        </p>
        <button class="btn btn-primary btn-sm" id="addModuleBtn">&#43; Add Module</button>
      </div>

      <!-- Add module inline form -->
      <div id="addModuleForm" class="cv-inline-form hidden">
        <input type="text" id="newModuleTitle" placeholder="Module title, e.g. Week 1 – Introduction" />
        <textarea id="newModuleDesc" rows="2" placeholder="Optional description…"></textarea>
        <div class="cv-inline-actions">
          <button type="button" class="btn btn-primary btn-sm"  id="saveNewModuleBtn">Save Module</button>
          <button type="button" class="btn btn-outline btn-sm"  id="cancelNewModuleBtn">Cancel</button>
        </div>
      </div>

      <!-- Module list -->
      <div id="moduleList" class="cv-module-list"></div>

      <div id="contentEmpty" class="empty-state hidden">
        <span class="empty-icon">&#128218;</span>
        <h3>No content yet</h3>
        <p>Add a module to start organising your lessons and materials.</p>
      </div>
    </div>

    <!-- ══════════════════════════════════════
         TAB: ANNOUNCEMENTS
         ══════════════════════════════════════ -->
    <div id="tabAnnouncements" class="cv-tab-panel hidden">

      <div class="cv-toolbar">
        <p class="muted" style="font-size:0.88rem">Post updates, reminders, or important notices for this course.</p>
        <button class="btn btn-primary btn-sm" id="addAnnBtn">&#43; Post Announcement</button>
      </div>

      <!-- Announcement compose form -->
      <div id="annForm"  class="cv-inline-form hidden">
        <input  type="hidden" id="annEditId" value="" />
        <input  type="text" id="annTitle"   placeholder="Announcement title" />
        <textarea id="annContent" rows="4"  placeholder="Write your announcement here…"></textarea>
        <label class="checkbox-item">
          <input type="checkbox" id="annPinned" /> Pin to top
        </label>
        <div class="cv-inline-actions">
          <button type="button" class="btn btn-primary btn-sm" id="saveAnnBtn">Post</button>
          <button type="button" class="btn btn-outline btn-sm" id="cancelAnnBtn">Cancel</button>
        </div>
      </div>

      <div id="annList" class="cv-ann-list"></div>

      <div id="annEmpty" class="empty-state hidden">
        <span class="empty-icon">&#128226;</span>
        <h3>No announcements yet</h3>
        <p>Post your first announcement to keep students informed.</p>
      </div>
    </div>

    <!-- ══════════════════════════════════════
         TAB: STUDENTS
         ══════════════════════════════════════ -->
    <div id="tabStudents" class="cv-tab-panel hidden">

      <div class="cv-toolbar">
        <p class="muted" style="font-size:0.88rem">
          Enrolled students can access this course's content and announcements.
        </p>
        <button class="btn btn-primary btn-sm" id="enrollBtn">&#43; Enroll Students</button>
      </div>

      <div id="enrolledList" class="cv-student-list"></div>

      <div id="studentsEmpty" class="empty-state hidden">
        <span class="empty-icon">&#128101;</span>
        <h3>No students enrolled</h3>
        <p>Enroll students so they can access this course.</p>
      </div>

      <!-- Enroll picker panel -->
      <div id="enrollPicker" class="cv-enroll-picker hidden">
        <h4>Available Students</h4>
        <p class="muted" style="font-size:0.82rem;margin-bottom:0.75rem">
          Select students to add to this course.
        </p>
        <input type="search" id="enrollSearch" placeholder="Search by name…" class="search-input" style="margin-bottom:0.75rem" />
        <div id="availableList" class="cv-available-list"></div>

        <!-- Registered students suggestions -->
        <div id="enrollSuggestSection" class="hidden" style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #e2e8f0">
          <h4 style="font-size:0.9rem;color:#475569">📋 Registered Students <span style="font-size:0.78rem;font-weight:400;color:#94a3b8">(not yet in your class)</span></h4>
          <div id="enrollSuggestList" class="cv-available-list" style="margin-top:0.5rem"></div>
        </div>

        <div class="cv-inline-actions mt-3">
          <button type="button" class="btn btn-primary btn-sm" id="confirmEnrollBtn">Enroll Selected</button>
          <button type="button" class="btn btn-outline btn-sm" id="cancelEnrollBtn">Cancel</button>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════
         TAB: SETTINGS
         ══════════════════════════════════════ -->
    <div id="tabSettings" class="cv-tab-panel hidden">
      <div class="cv-settings-panel">
        <h3>Course Settings</h3>

        <div id="settingsAlert" class="alert hidden"></div>

        <form id="settingsForm" novalidate>
          <div class="form-group">
            <label>Course Title *</label>
            <input type="text" id="setTitle" required />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Subject</label>
              <input type="text" id="setSubject" />
            </div>
            <div class="form-group">
              <label>Grade Level</label>
              <input type="text" id="setGrade" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>School Year</label>
              <input type="text" id="setYear" />
            </div>
            <div class="form-group">
              <label>Cover Colour</label>
              <div class="color-picker-row">
                <input type="color" id="setColor" />
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
            <textarea id="setDescription" rows="3"></textarea>
          </div>
          <div class="cv-settings-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>

        <hr style="margin:2rem 0;border:none;border-top:1px solid #e5e7eb;" />

        <div class="danger-zone">
          <h4>Danger Zone</h4>
          <p class="muted">Deleting this course will permanently remove all modules, materials, and enrollment records.</p>
          <button type="button" class="btn btn-danger" id="deleteCourseBtn">Delete This Course</button>
        </div>
      </div>
    </div>

  </main><!-- /main-content -->

  <!-- ═══ Add / Edit Material Modal ═══ -->
  <div id="materialModal" class="modal-backdrop hidden">
    <div class="modal-box">
      <div class="modal-header">
        <h3 id="materialModalTitle">Add Content</h3>
        <button type="button" class="modal-close" id="closeMaterialModal">&#10005;</button>
      </div>

      <!-- Type selector (shown only when adding new) -->
      <div id="materialTypeSelector" class="mat-type-grid">
        <button type="button" class="mat-type-btn active" data-type="file">
          <span>&#128196;</span> File Upload
        </button>
        <button type="button" class="mat-type-btn" data-type="link">
          <span>&#128279;</span> Web Link
        </button>
        <button type="button" class="mat-type-btn" data-type="text">
          <span>&#128221;</span> Text / Note
        </button>
        <button type="button" class="mat-type-btn" data-type="assignment">
          <span>&#9998;</span> Assignment
        </button>
      </div>

      <form id="materialForm" novalidate enctype="multipart/form-data">
        <input type="hidden" id="matModuleId"   value="" />
        <input type="hidden" id="matMaterialId" value="" />
        <input type="hidden" id="matType"        value="file" />

        <div class="modal-body">
          <div id="materialFormAlert" class="alert hidden"></div>

          <div class="form-group">
            <label>Title *</label>
            <input type="text" id="matTitle" required placeholder="e.g. Week 1 Reading – The Giver" />
          </div>
          <div class="form-group">
            <label>Description <span class="muted">(optional)</span></label>
            <textarea id="matDescription" rows="2" placeholder="Brief description for students…"></textarea>
          </div>

          <!-- File-specific -->
          <div id="matFileArea" class="form-group">
            <label>Upload File</label>
            <div class="upload-zone mat-upload-zone" id="matDropZone">
              <div class="upload-zone-inner" style="pointer-events:none">
                <span class="upload-icon">&#128196;</span>
                <p>Drag &amp; drop or click to browse</p>
                <p class="muted">PDF, Word, Excel, PowerPoint, Images — max 10 MB</p>
              </div>
              <input type="file" id="matFileInput"
                     accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.txt,.csv,.mp4,.mp3" />
            </div>
            <div id="matSelectedFile" class="selected-file-info hidden">
              <span id="matFileName"></span>
              <button type="button" class="btn btn-outline btn-xs" id="matClearFile">&#10005;</button>
            </div>
          </div>

          <!-- Link-specific -->
          <div id="matLinkArea" class="form-group hidden">
            <label>URL *</label>
            <input type="url" id="matUrl" placeholder="https://…" />
          </div>

          <!-- Text / Note specific -->
          <div id="matTextArea" class="form-group hidden">
            <label>Content *</label>
            <textarea id="matTextContent" rows="6"
                      placeholder="Write your note or instructions here…"></textarea>
          </div>

          <!-- Assignment-specific -->
          <div id="matAssignArea" class="hidden">
            <div class="form-group">
              <label>Instructions *</label>
              <textarea id="matAssignContent" rows="5"
                        placeholder="Describe what students need to do…"></textarea>
            </div>
            <div class="form-group">
              <label>Due Date <span class="muted">(optional)</span></label>
              <input type="date" id="matDueDate" />
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" id="cancelMaterialBtn">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveMaterialBtn">
            <span class="btn-text">Add Content</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Material Viewer Modal -->
  <div id="materialViewerModal" class="mv-overlay hidden">
    <div class="mv-modal">
      <div class="mv-header">
        <span class="mv-title" id="mvTitle"></span>
        <div class="mv-header-actions">
          <button class="btn btn-outline btn-sm" id="mvMaxBtn" onclick="toggleMaxViewer()" title="Maximize">&#9634;</button>
          <a id="mvDownloadBtn" class="btn btn-outline btn-sm" target="_blank" title="Download">&#8595;</a>
          <button class="btn btn-outline btn-sm" onclick="closeMaterialViewer()" title="Close">&#10005;</button>
        </div>
      </div>
      <div class="mv-body" id="mvBody">
        <div class="dv-loading">Loading&hellip;</div>
      </div>
    </div>
  </div>

  <script src="../assets/js/mammoth.browser.min.js"></script>
  <script src="../assets/js/xlsx.full.min.js"></script>
  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/course-view.js"></script>
</body>
</html>
