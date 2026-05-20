<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Student Profile</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="../assets/css/import.css" />
  <link rel="stylesheet" href="../assets/css/student-form.css" />
</head>
<body class="app-page">

  <nav class="sidebar">
    <div class="sidebar-logo"><span>&#127891;</span> EduQuest</div>
    <ul class="sidebar-nav">
      <li class="nav-section-label">Overview</li>
      <li><a href="dashboard.php">&#127968; Dashboard</a></li>
      <li class="nav-section-label">Students</li>
      <li><a href="students.php">&#128100; My Students</a></li>
      <li><a href="student-form.php" class="active">&#43; Add Student</a></li>
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
          <span id="teacherName">Loading&hellip;</span>
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
        <h2 id="formTitle">Add New Student Profile</h2>
      </div>
      <?php require_once 'notifications.php'; ?>
    </header>

    <div id="formAlert" class="alert hidden"></div>

    <!-- ═══ Method Chooser (add-new mode only) ═══ -->
    <div id="methodChooser" class="hidden">
      <p class="muted mb-3">How would you like to add this student?</p>
      <div class="method-chooser-grid">
        <button type="button" class="method-card" id="chooseManual">
          <span class="method-icon">&#9998;</span>
          <strong>Enter Manually</strong>
          <span>Fill in the student profile using the guided form</span>
        </button>
        <button type="button" class="method-card" id="chooseImport">
          <span class="method-icon">&#8679;</span>
          <strong>Import from File</strong>
          <span>Upload physical document scans to create profiles</span>
        </button>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         MANUAL ENTRY SECTION — 4-Step Redesigned Form
         ═══════════════════════════════════════════════════ -->
    <div id="manualSection" class="hidden">
      <div class="back-to-chooser hidden" id="backToChooserManual">
        <button type="button" class="btn btn-outline btn-sm" id="backToChooserManualBtn">&larr; Change method</button>
      </div>

      <form id="studentForm" novalidate>
        <input type="hidden" id="studentId" value="" />

        <!-- ── Step Indicator ── -->
        <div class="sf-steps">
          <div class="sf-step active" data-step="1">
            <span class="sf-num">1</span> Student Info
          </div>
          <div class="sf-step" data-step="2">
            <span class="sf-num">2</span> Learning Profile
          </div>
          <div class="sf-step" data-step="3">
            <span class="sf-num">3</span> Accommodations
          </div>
          <div class="sf-step" data-step="4">
            <span class="sf-num">4</span> Plans &amp; Documents
          </div>
        </div>

        <!-- ══════════════════════════════════════════════
             STEP 1 — Student Information
             (Based on: Individual Learner's Profile Sec. I-II)
             ══════════════════════════════════════════════ -->
        <section class="form-step active" id="step1">

          <div class="sf-tip">
            <span class="sf-tip-icon">&#128161;</span>
            <span>Only <strong>First Name</strong> and <strong>Last Name</strong> are required. Fill in as much as you have &mdash; you can always update later.</span>
          </div>

          <!-- Basic Details -->
          <div class="sf-card">
            <div class="sf-card-head">
              <h3><span class="sf-icon">&#128100;</span> Basic Details</h3>
            </div>
            <div class="sf-card-body">
              <div class="sf-photo-area">
                <img id="photoPreview" src="../assets/img/default-avatar.php" alt="Student Photo" class="sf-photo-preview" />
                <label class="btn btn-outline btn-sm">
                  Upload Photo <input type="file" id="photoInput" accept="image/*" class="hidden" />
                </label>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>First Name <span style="color:#ef4444">*</span></label>
                  <input type="text" id="firstName" required placeholder="e.g. Skyler" />
                </div>
                <div class="form-group">
                  <label>Last Name <span style="color:#ef4444">*</span></label>
                  <input type="text" id="lastName" required placeholder="e.g. Cruz" />
                </div>
              </div>
              <div class="form-row three-col">
                <div class="form-group">
                  <label>Date of Birth</label>
                  <input type="date" id="dob" />
                </div>
                <div class="form-group">
                  <label>Gender</label>
                  <select id="gender">
                    <option value="">Select&hellip;</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="non_binary">Non-Binary</option>
                    <option value="prefer_not_to_say">Prefer not to say</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Grade Level</label>
                  <input type="text" id="gradeLevel" placeholder="e.g. Grade 5" />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>School Name</label>
                  <input type="text" id="schoolName" placeholder="e.g. Manila Central School" />
                </div>
                <div class="form-group">
                  <label>LRN / School ID</label>
                  <input type="text" id="studentIdNum" placeholder="Optional" />
                </div>
              </div>
            </div>

            <!-- Collapsible: Parent / Guardian -->
            <button type="button" class="sf-collapse-toggle" data-target="guardianSection">
              <span class="sf-chevron">&#9654;</span>
              &#128106; Parent / Guardian Information
            </button>
            <div class="sf-collapse-body" id="guardianSection">
              <div class="form-row">
                <div class="form-group">
                  <label>Guardian Name</label>
                  <input type="text" id="parentName" placeholder="Full name" />
                </div>
                <div class="form-group">
                  <label>Contact Number</label>
                  <input type="tel" id="parentPhone" placeholder="e.g. 0917-XXX-XXXX" />
                </div>
              </div>
              <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="parentEmail" placeholder="Optional" />
              </div>
              <div class="sf-section-label">Emergency Contact</div>
              <div class="form-row">
                <div class="form-group">
                  <label>Emergency Contact Person</label>
                  <input type="text" id="emergContact" placeholder="Name and relationship" />
                </div>
                <div class="form-group">
                  <label>Emergency Phone</label>
                  <input type="tel" id="emergPhone" placeholder="Phone number" />
                </div>
              </div>
            </div>
          </div>

          <!-- Student Login Account (optional) -->
          <div class="sf-card" id="studentAccountCard">
            <div class="sf-card-head">
              <h3><span class="sf-icon">&#128272;</span> Student Login Account</h3>
            </div>
            <div class="sf-card-body">
              <p class="muted" style="margin-bottom:.75rem">
                If this student can't register on their own, provide their email to create a login account.
                A default password <code>Password01!</code> will be assigned &mdash; the student can change it after first login.
              </p>
              <div class="form-row">
                <div class="form-group" style="flex:2">
                  <label>Student Email</label>
                  <input type="email" id="studentEmail" placeholder="e.g. student@email.com" />
                </div>
                <div class="form-group" style="flex:1;display:flex;align-items:flex-end">
                  <span id="emailStatus" class="muted" style="font-size:.85rem"></span>
                </div>
              </div>
            </div>
          </div>

          <!-- General Notes -->
          <div class="sf-card">
            <div class="sf-card-body">
              <div class="form-group" style="margin-bottom:0">
                <label>General Notes</label>
                <textarea id="generalNotes" rows="2" placeholder="Any quick notes about this student&hellip;"></textarea>
              </div>
            </div>
          </div>

        </section>


        <!-- ══════════════════════════════════════════════
             STEP 2 — Learning & Disability Profile
             (Based on: Individual Learner's Profile Sec. III-V + IEP PLEP)
             ══════════════════════════════════════════════ -->
        <section class="form-step hidden" id="step2">

          <div class="sf-tip">
            <span class="sf-tip-icon">&#128218;</span>
            <span>This section helps you understand how the student learns. Fill in what you know &mdash; it helps EduQuest personalize their experience.</span>
          </div>

          <!-- Disability & Classification -->
          <div class="sf-card">
            <div class="sf-card-head">
              <h3><span class="sf-icon">&#128203;</span> Disability / Condition</h3>
            </div>
            <div class="sf-card-body">
              <div class="form-row">
                <div class="form-group">
                  <label>Condition / Disability Type</label>
                  <select id="adhdType">
                    <option value="unspecified">Select&hellip;</option>
                    <option value="predominantly_inattentive">ADHD &ndash; Predominantly Inattentive</option>
                    <option value="predominantly_hyperactive_impulsive">ADHD &ndash; Predominantly Hyperactive-Impulsive</option>
                    <option value="combined_presentation">ADHD &ndash; Combined Presentation</option>
                    <option value="other_specified">Other (specify below)</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>DepEd SpEd Category</label>
                  <select id="iep_sped_category">
                    <option value="">Select&hellip;</option>
                    <option value="ADHD">ADHD</option>
                    <option value="ASD">ASD &ndash; Autism Spectrum</option>
                    <option value="ID">ID &ndash; Intellectual Disability</option>
                    <option value="HV">HV &ndash; Hard of Hearing / Deaf</option>
                    <option value="VI">VI &ndash; Visual Impairment</option>
                    <option value="OI">OI &ndash; Orthopedic Impairment</option>
                    <option value="LD">LD &ndash; Learning Disability</option>
                    <option value="SI">SI &ndash; Speech / Language Impairment</option>
                    <option value="MD">MD &ndash; Multiple Disabilities</option>
                    <option value="GD">GD &ndash; Gifted / Talented</option>
                    <option value="other">Other</option>
                  </select>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Severity</label>
                  <select id="adhdSeverity">
                    <option value="mild">Mild</option>
                    <option value="moderate" selected>Moderate</option>
                    <option value="severe">Severe</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Disability Classification</label>
                  <input type="text" id="sip_disability_class" placeholder="e.g. ADHD Combined Type, ASD Level 1" />
                </div>
              </div>
            </div>

            <!-- Collapsible: Diagnosis Details -->
            <button type="button" class="sf-collapse-toggle" data-target="diagnosisDetails">
              <span class="sf-chevron">&#9654;</span>
              &#129658; Diagnosis Details (Optional)
            </button>
            <div class="sf-collapse-body" id="diagnosisDetails">
              <div class="form-row">
                <div class="form-group">
                  <label>Diagnosis Date</label>
                  <input type="date" id="diagnosisDate" />
                </div>
                <div class="form-group">
                  <label>Diagnosing Professional</label>
                  <input type="text" id="diagnosingProf" placeholder="Dr. Name / Psychologist" />
                </div>
              </div>
              <div class="form-group">
                <label>Years in SpEd Program</label>
                <input type="number" id="sip_years_in_sped" min="0" max="20" placeholder="e.g. 3" style="max-width:120px" />
              </div>

              <div class="sf-section-label">Comorbid Conditions</div>
              <p class="sf-help" style="margin-bottom:.5rem">Add any additional diagnosed conditions (e.g. Anxiety, Dyslexia, ASD, ODD).</p>
              <div id="conditionsList"></div>
              <button type="button" class="btn btn-outline btn-sm" id="addConditionBtn">&#43; Add Condition</button>
            </div>
          </div>

          <!-- Learning Profile -->
          <div class="sf-card">
            <div class="sf-card-head">
              <h3><span class="sf-icon">&#127891;</span> How This Student Learns</h3>
            </div>
            <div class="sf-card-body">
              <div class="form-row three-col">
                <div class="form-group">
                  <label>Learning Style</label>
                  <select id="sip_learning_style">
                    <option value="mixed">Mixed / Multimodal</option>
                    <option value="visual">Visual</option>
                    <option value="auditory">Auditory</option>
                    <option value="kinesthetic">Kinesthetic / Hands-On</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Attention Span</label>
                  <select id="sip_attention_span">
                    <option value="variable">Variable</option>
                    <option value="short">Short (under 10 min)</option>
                    <option value="moderate">Moderate (10&ndash;20 min)</option>
                    <option value="good">Good (20+ min)</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Primary Language</label>
                  <input type="text" id="sip_language" placeholder="e.g. Filipino" />
                </div>
              </div>
            </div>
          </div>

          <!-- Strengths & Challenges -->
          <div class="sf-card">
            <div class="sf-card-head">
              <h3><span class="sf-icon">&#11088;</span> Strengths &amp; Challenges</h3>
            </div>
            <div class="sf-card-body">
              <div class="form-row">
                <div class="form-group">
                  <label>&#128154; Strengths &amp; Interests</label>
                  <textarea id="sip_academic_strengths" rows="3" placeholder="What is this student good at? What do they enjoy?&#10;e.g. Strong visual memory, loves drawing, good at math computation, eager to help others"></textarea>
                </div>
                <div class="form-group">
                  <label>&#128308; Challenges &amp; Needs</label>
                  <textarea id="sip_academic_challenges" rows="3" placeholder="What does this student struggle with?&#10;e.g. Reading comprehension below grade level, difficulty staying seated, struggles with written expression"></textarea>
                </div>
              </div>

              <div class="sf-section-label">Specific Difficulty Areas</div>
              <div class="sf-check-grid">
                <label class="sf-check-item"><input type="checkbox" id="chkReading" /> Reading Difficulty</label>
                <label class="sf-check-item"><input type="checkbox" id="chkWriting" /> Writing Difficulty</label>
                <label class="sf-check-item"><input type="checkbox" id="chkMath" /> Math Difficulty</label>
                <label class="sf-check-item"><input type="checkbox" id="chkFocus" /> Sustaining Focus</label>
                <label class="sf-check-item"><input type="checkbox" id="chkOrg" /> Organization</label>
                <label class="sf-check-item"><input type="checkbox" id="chkTime" /> Time Management</label>
                <label class="sf-check-item"><input type="checkbox" id="chkMemory" /> Working Memory</label>
                <label class="sf-check-item"><input type="checkbox" id="chkEmotion" /> Emotional Regulation</label>
              </div>
            </div>

            <!-- Collapsible: Behavioral & Social Profile -->
            <button type="button" class="sf-collapse-toggle" data-target="behaviorSection">
              <span class="sf-chevron">&#9654;</span>
              &#128588; Behavioral &amp; Social Profile (Optional)
            </button>
            <div class="sf-collapse-body" id="behaviorSection">
              <div class="form-row">
                <div class="form-group">
                  <label>Behavioral Strengths</label>
                  <textarea id="sip_behavioral_strengths" rows="2" placeholder="e.g. Responds well to routines, eager to help others"></textarea>
                </div>
                <div class="form-group">
                  <label>Behavioral Challenges</label>
                  <textarea id="sip_behavioral_challenges" rows="2" placeholder="e.g. Difficulty managing frustration, impulsive during transitions"></textarea>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Social Strengths</label>
                  <textarea id="sip_social_strengths" rows="2" placeholder="e.g. Kind and empathetic, thrives in cooperative activities"></textarea>
                </div>
                <div class="form-group">
                  <label>Social Challenges</label>
                  <textarea id="sip_social_challenges" rows="2" placeholder="e.g. Struggles reading social cues, difficulty in large groups"></textarea>
                </div>
              </div>

              <div class="sf-section-label">Behavioral Strategies</div>
              <div class="form-row">
                <div class="form-group">
                  <label>&#127775; Motivators &amp; Interests</label>
                  <textarea id="sip_motivators" rows="2" placeholder="e.g. Technology, drawing, earning points, peer recognition"></textarea>
                </div>
                <div class="form-group">
                  <label>&#9888;&#65039; Triggers</label>
                  <textarea id="sip_triggers" rows="2" placeholder="e.g. Sudden routine changes, loud noise, unclear instructions"></textarea>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>&#128524; Calming Strategies</label>
                  <textarea id="sip_calming" rows="2" placeholder="e.g. Quiet break (5 min), deep breathing, movement break"></textarea>
                </div>
                <div class="form-group">
                  <label>&#127942; Reinforcement Strategies</label>
                  <textarea id="sip_reinforcement" rows="2" placeholder="e.g. Token system, verbal praise, star chart, extra computer time"></textarea>
                </div>
              </div>

              <div class="sf-section-label">Communication Profile</div>
              <div class="form-group">
                <textarea id="sip_communication" rows="2" placeholder="How does this student communicate? Any assistive tools or language processing needs?"></textarea>
              </div>

              <div class="sf-section-label">Symptom Ratings</div>
              <p class="sf-help" style="margin-bottom:.5rem">1 = Rarely &nbsp;|&nbsp; 5 = Very Often</p>
              <div class="sf-rating-row">
                <div class="sf-rating-item">
                  <label>Inattention <span class="sf-rating-val" id="inattentionLabel">3</span></label>
                  <input type="range" id="inattentionRating" min="1" max="5" value="3" />
                </div>
                <div class="sf-rating-item">
                  <label>Hyperactivity <span class="sf-rating-val" id="hyperactivityLabel">3</span></label>
                  <input type="range" id="hyperactivityRating" min="1" max="5" value="3" />
                </div>
                <div class="sf-rating-item">
                  <label>Impulsivity <span class="sf-rating-val" id="impulsivityLabel">3</span></label>
                  <input type="range" id="impulsivityRating" min="1" max="5" value="3" />
                </div>
              </div>

              <div class="sf-section-label" style="margin-top:1rem">Additional Info</div>
              <div class="form-row">
                <div class="form-group">
                  <label>Preferred Name / Nickname</label>
                  <input type="text" id="sip_preferred_name" placeholder="How student prefers to be called" />
                </div>
                <div class="form-group">
                  <label>Pronouns</label>
                  <input type="text" id="sip_pronouns" placeholder="e.g. He/Him, She/Her" />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Family Support Level</label>
                  <select id="sip_family_support">
                    <option value="unknown">Unknown</option>
                    <option value="high">High &ndash; Actively involved</option>
                    <option value="moderate">Moderate &ndash; Supportive</option>
                    <option value="limited">Limited &ndash; Minimal involvement</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Outside Services</label>
                  <input type="text" id="sip_outside_services" placeholder="e.g. Private OT, Speech therapy, Psychiatrist" />
                </div>
              </div>
              <div class="form-group">
                <label>Student Voice</label>
                <textarea id="sip_student_voice" rows="2" placeholder="What has the student shared about their own goals and what helps them?"></textarea>
              </div>
              <div class="form-group">
                <label>Teacher Observations</label>
                <textarea id="sip_teacher_observations" rows="2" placeholder="Your observations about this student&hellip;"></textarea>
              </div>
            </div>
          </div>

        </section>


        <!-- ══════════════════════════════════════════════
             STEP 3 — Accommodations & Support
             (Based on: IEP Accommodations/Modifications section)
             ══════════════════════════════════════════════ -->
        <section class="form-step hidden" id="step3">

          <div class="sf-tip">
            <span class="sf-tip-icon">&#9997;&#65039;</span>
            <span><strong>Click a tag to add it</strong> &mdash; it will turn green with a ✓. Click again to remove it. Scroll down to see everything you&rsquo;ve added, or use &ldquo;Add Custom&rdquo; for anything not listed.</span>
          </div>

          <!-- Quick-add Presets -->
          <div class="sf-card">
            <div class="sf-card-head">
              <h3><span class="sf-icon">&#9889;</span> Quick Add</h3>
            </div>
            <div class="sf-card-body">
              <div class="sf-section-label" style="margin-top:0">Assessment</div>
              <div class="sf-preset-grid" id="presetAssessment"></div>

              <div class="sf-section-label">Instructional</div>
              <div class="sf-preset-grid" id="presetInstructional"></div>

              <div class="sf-section-label">Environmental</div>
              <div class="sf-preset-grid" id="presetEnvironmental"></div>

              <div class="sf-section-label">Behavioral &amp; Social-Emotional</div>
              <div class="sf-preset-grid" id="presetBehavioral"></div>

              <div class="sf-section-label">Technology</div>
              <div class="sf-preset-grid" id="presetTechnology"></div>
            </div>
          </div>

          <!-- Custom Accommodations List -->
          <div class="sf-card">
            <div class="sf-card-head">
              <h3><span class="sf-icon">&#128221;</span> Added Accommodations<span id="accomCount" class="sf-accom-count" style="display:none">0</span></h3>
              <button type="button" class="btn btn-secondary btn-sm" id="addAccomBtn">&#43; Add Custom</button>
            </div>
            <div class="sf-card-body">
              <div id="accommodationsList">
                <div id="accomChipsArea" class="sf-accom-chips-area"></div>
              </div>
              <p class="muted" id="noAccomMsg" style="font-size:.85rem;">No accommodations added yet. Click the tags above or use &ldquo;Add Custom&rdquo;.</p>
            </div>
          </div>

          <!-- Plans in Place -->
          <div class="sf-card">
            <div class="sf-card-body">
              <div class="sf-section-label" style="margin-top:0">Plans Currently in Place</div>
              <div class="sf-check-grid">
                <label class="sf-check-item"><input type="checkbox" id="chkIEP" /> IEP (Individualized Education Program)</label>
                <label class="sf-check-item"><input type="checkbox" id="chk504" /> Section 504 Plan</label>
              </div>
              <div class="form-group" style="margin-bottom:0">
                <label>Additional Notes on Accommodations</label>
                <textarea id="adhdNotes" rows="2" placeholder="Any other notes about accommodations or support strategies&hellip;"></textarea>
              </div>
            </div>
          </div>

          <!-- Add/Remove toast -->
          <div id="accomToast" class="sf-accom-toast"></div>

          <!-- Collapsible: Medications -->
          <div class="sf-card">
            <button type="button" class="sf-collapse-toggle" data-target="medicationsSection" style="border-top:none">
              <span class="sf-chevron">&#9654;</span>
              &#128138; Medications (Optional &amp; Confidential)
            </button>
            <div class="sf-collapse-body" id="medicationsSection">
              <p class="sf-help" style="margin-bottom:.75rem">This information is kept confidential and only visible to you.</p>
              <div id="medicationsList"></div>
              <button type="button" class="btn btn-outline btn-sm" id="addMedBtn">&#43; Add Medication</button>
            </div>
          </div>

        </section>


        <!-- ══════════════════════════════════════════════
             STEP 4 — Plans & Documents
             (Upload IEP, ITP, Individual Learner's Profile)
             ══════════════════════════════════════════════ -->
        <section class="form-step hidden" id="step4">

          <div class="sf-tip">
            <span class="sf-tip-icon">&#128196;</span>
            <span>Upload your existing IEP, ITP, or Individual Learner&rsquo;s Profile documents. You can also manually enter key plan details below.</span>
          </div>

          <!-- Plan Document Uploads -->
          <div class="sf-card">
            <div class="sf-card-head">
              <h3><span class="sf-icon">&#128449;</span> Upload Plan Documents</h3>
            </div>
            <div class="sf-card-body">
              <div class="sf-plan-cards">
                <div class="sf-plan-card" id="planCardIep" data-plan="iep">
                  <span class="sf-plan-icon">&#128214;</span>
                  <strong>IEP</strong>
                  <div class="sf-plan-hint">Individualized Education Program</div>
                  <input type="file" id="iepFileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="hidden" />
                  <div class="sf-plan-file-name" id="iepFileName"></div>
                </div>
                <div class="sf-plan-card" id="planCardItp" data-plan="itp">
                  <span class="sf-plan-icon">&#128640;</span>
                  <strong>ITP</strong>
                  <div class="sf-plan-hint">Individualized Transition Plan</div>
                  <input type="file" id="itpFileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="hidden" />
                  <div class="sf-plan-file-name" id="itpFileName"></div>
                </div>
                <div class="sf-plan-card" id="planCardSip" data-plan="sip">
                  <span class="sf-plan-icon">&#128101;</span>
                  <strong>Learner&rsquo;s Profile</strong>
                  <div class="sf-plan-hint">Individual Learner&rsquo;s Profile</div>
                  <input type="file" id="sipFileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="hidden" />
                  <div class="sf-plan-file-name" id="sipFileName"></div>
                </div>
              </div>
              <p class="muted" style="font-size:.78rem;text-align:center">Click a card to upload &bull; PDF, Word, or Image &bull; Max 10 MB</p>
            </div>
          </div>

          <!-- Collapsible: IEP Manual Entry (simplified) -->
          <div class="sf-card">
            <button type="button" class="sf-collapse-toggle" data-target="iepManualSection" style="border-top:none">
              <span class="sf-chevron">&#9654;</span>
              &#128214; IEP &mdash; Manual Entry (Optional)
            </button>
            <div class="sf-collapse-body" id="iepManualSection">
              <div class="form-row three-col">
                <div class="form-group"><label>Effective Date</label><input type="date" id="iep_effective_date" /></div>
                <div class="form-group"><label>Annual Review Date</label><input type="date" id="iep_review_date" /></div>
                <div class="form-group"><label>IEP Meeting Date</label><input type="date" id="iep_meeting_date" /></div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Disability Classification</label>
                  <input type="text" id="iep_disability_class" placeholder="e.g. ADHD Combined Type" />
                </div>
                <div class="form-group">
                  <label>IEP Team Members</label>
                  <input type="text" id="iep_team" placeholder="e.g. Ms. Reyes (SpEd), Dr. Cruz, Parent" />
                </div>
              </div>
              <div class="form-group">
                <label>Present Level of Performance</label>
                <textarea id="iep_plep_academic" rows="3" placeholder="Describe the student's current academic, functional, and social performance levels&hellip;"></textarea>
              </div>
              <div class="form-group">
                <label>Annual Goals</label>
                <textarea id="iep_annual_goals" rows="3" placeholder="One goal per line. Format: By [date], student will&hellip; as measured by&hellip;"></textarea>
              </div>
              <div class="form-group">
                <label>Special Education &amp; Related Services</label>
                <textarea id="iep_sped_services" rows="2" placeholder="e.g. Resource Room: 5x/week, 60 min; Speech Therapy: 2x/week"></textarea>
              </div>
              <div class="form-group">
                <label>Accommodations &amp; Modifications</label>
                <textarea id="iep_accommodations" rows="2" placeholder="e.g. Extended time, preferential seating, graphic organizers"></textarea>
              </div>
              <!-- Hidden fields to preserve API compatibility -->
              <input type="hidden" id="iep_plep_functional" />
              <input type="hidden" id="iep_plep_social" />
              <input type="hidden" id="iep_objectives" />
              <input type="hidden" id="iep_related_services" />
              <input type="hidden" id="iep_modifications" />
              <input type="hidden" id="iep_regular_ed_pct" />
              <input type="hidden" id="iep_assess_accom" />
              <input type="hidden" id="iep_transition" />
              <input type="hidden" id="iep_notes" />
            </div>
          </div>

          <!-- Collapsible: ITP Manual Entry (simplified) -->
          <div class="sf-card">
            <button type="button" class="sf-collapse-toggle" data-target="itpManualSection" style="border-top:none">
              <span class="sf-chevron">&#9654;</span>
              &#128640; ITP &mdash; Manual Entry (Optional)
            </button>
            <div class="sf-collapse-body" id="itpManualSection">
              <div class="form-row">
                <div class="form-group"><label>Effective Date</label><input type="date" id="itp_effective_date" /></div>
                <div class="form-group"><label>Anticipated Graduation Date</label><input type="date" id="itp_graduation_date" /></div>
              </div>
              <div class="form-group">
                <label>Disability Category</label>
                <input type="text" id="itp_disability_category" placeholder="e.g. ADHD, ASD" />
              </div>
              <div class="form-group">
                <label>Career Interests &amp; Strengths</label>
                <textarea id="itp_career_interests" rows="2" placeholder="What careers or activities interest this student?"></textarea>
              </div>
              <div class="form-group">
                <label>Post-Secondary Goals</label>
                <textarea id="itp_goal_education" rows="3" placeholder="Education, Employment, and Independent Living goals after school&hellip;"></textarea>
              </div>
              <div class="form-group">
                <label>Transition Services</label>
                <textarea id="itp_services_instruction" rows="2" placeholder="Instruction, community experiences, employment training&hellip;"></textarea>
              </div>
              <!-- Hidden fields to preserve API compatibility -->
              <input type="hidden" id="itp_assessed_strengths" />
              <input type="hidden" id="itp_work_experiences" />
              <input type="hidden" id="itp_community_experiences" />
              <input type="hidden" id="itp_daily_living" />
              <input type="hidden" id="itp_goal_employment" />
              <input type="hidden" id="itp_goal_independent" />
              <input type="hidden" id="itp_goal_community" />
              <input type="hidden" id="itp_services_community" />
              <input type="hidden" id="itp_services_employment" />
              <input type="hidden" id="itp_services_adult" />
              <input type="hidden" id="itp_course_of_study" />
              <input type="hidden" id="itp_agency_linkages" />
              <input type="hidden" id="itp_annual_goals" />
              <input type="hidden" id="itp_notes" />
            </div>
          </div>

          <!-- Other Supporting Documents -->
          <div class="sf-card">
            <div class="sf-card-head">
              <h3><span class="sf-icon">&#128196;</span> Other Supporting Documents</h3>
            </div>
            <div class="sf-card-body">
              <p class="sf-help" style="margin-bottom:.75rem">Medical reports, psychological evaluations, progress reports, consent forms, etc.</p>
              <div class="sf-upload-zone" id="docUploadZone">
                <span class="sf-upload-icon">&#128449;</span>
                <p>Drag &amp; drop files here, or click to browse</p>
                <p class="muted">PDF &middot; Word &middot; Excel &middot; JPEG &middot; PNG &middot; Max 10 MB</p>
                <input type="file" id="docFileInput" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" />
              </div>
              <div id="docUploadList"></div>
              <div id="existingDocsList"></div>
            </div>
          </div>

          <!-- Hidden fields for SIP plan data (already captured in Step 2) -->
          <input type="hidden" id="sip_sped_category" />
          <input type="hidden" id="sip_learning_style_notes" />

        </section>


        <!-- ── Navigation Buttons ── -->
        <div class="sf-nav">
          <button type="button" class="btn btn-outline" id="prevBtn" disabled>&#8592; Previous</button>
          <button type="button" class="btn btn-primary" id="nextBtn">Next &#8594;</button>
          <button type="submit" class="btn btn-success hidden" id="submitBtn">
            <span class="btn-text">Save Student Profile</span>
            <span class="btn-spinner hidden">&#8987;</span>
          </button>
        </div>
      </form>
    </div>


    <!-- ═══ Import section (unchanged — loaded by student-import.js) ═══ -->
    <div id="importSection" class="hidden">
      <div class="back-to-chooser" id="backToChooserImport">
        <button type="button" class="btn btn-outline btn-sm" id="backToChooserImportBtn">&larr; Change method</button>
      </div>

      <div id="tabDocument" class="import-tab-content active">
        <div class="import-step-card">
          <div class="import-step-number">1</div>
          <div class="import-step-body">
            <h3>How This Works</h3>
            <div class="how-it-works-grid">
              <div class="how-step"><span class="how-step-icon">&#8679;</span><div><strong>Upload Documents</strong><p>Upload scanned IEPs, psychological evaluations, school reports, Word docs or spreadsheets.</p></div></div>
              <div class="how-step"><span class="how-step-icon">&#128196;</span><div><strong>Profiles Created</strong><p>Each uploaded file becomes a complete student profile with the document viewable from the profile page.</p></div></div>
              <div class="how-step"><span class="how-step-icon">&#9998;</span><div><strong>Optionally Enrich the Profile</strong><p>Open any profile to add ADHD details, medications, accommodations, or additional information at any time.</p></div></div>
            </div>
          </div>
        </div>

        <div class="import-step-card">
          <div class="import-step-number">2</div>
          <div class="import-step-body">
            <h3>Upload Documents</h3>
            <p class="muted mb-3">Accepted: PDF, Word (.doc/.docx), Excel (.xls/.xlsx), JPEG, PNG, TIFF. Max 10 MB per file. Up to 10 files at once.</p>
            <div class="upload-zone" id="docDropZone">
              <div class="upload-zone-inner">
                <span class="upload-icon">&#128196;</span>
                <p>Drag &amp; drop files here, or click to browse</p>
                <p class="muted">PDF &middot; Word &middot; Excel &middot; JPEG &middot; PNG &middot; TIFF</p>
                <input type="file" id="docFilesInput" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.tif,.tiff" />
              </div>
            </div>
            <div id="docFileQueue" class="doc-queue mt-3"></div>
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
                  <p class="muted" style="font-size:0.78rem;margin-top:0.25rem">One name per line, matching the file order above.</p>
                  <textarea id="nameHints" rows="3" placeholder="Jane Smith&#10;John Doe&#10;Alex Johnson"></textarea>
                </div>
              </div>
              <button class="btn btn-primary" id="uploadDocsBtn">&#8679; Upload &amp; Create Profiles</button>
            </div>
            <div id="docUploadProgress" class="hidden">
              <div class="progress-bar-bg"><div class="progress-bar-fill" id="docProgressFill" style="width:0%"></div></div>
              <p id="docProgressText" class="muted mt-1">Uploading&hellip;</p>
            </div>
          </div>
        </div>

        <div id="docUploadResult" class="hidden">
          <div class="import-step-card">
            <div class="import-step-number done">&#10003;</div>
            <div class="import-step-body">
              <h3>Profiles Created</h3>
              <p class="muted mb-3">Each uploaded file has been saved as a student profile. Click <strong>View Profile</strong> to open it.</p>
              <div id="draftProfileList" class="draft-list"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>

  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/student-form.js"></script>
  <script src="../assets/js/student-import.js"></script>
</body>
</html>