<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Student Profile</title>
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
      <li><a href="student-form.php" class="active">&#43; Add Student</a></li>
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
      <div>
        <a href="students.php" class="link-muted">&larr; Back to Students</a>
        <h2 id="formTitle">Add New Student Profile</h2>
      </div>
    </header>

    <div id="formAlert" class="alert hidden"></div>

    <!-- ═══ Method Chooser (add-new mode only) ═══ -->
    <div id="methodChooser" class="hidden">
      <p class="muted mb-3">How would you like to add this student?</p>
      <div class="method-chooser-grid">
        <button type="button" class="method-card" id="chooseManual">
          <span class="method-icon">&#9998;</span>
          <strong>Enter Manually</strong>
          <span>Fill in the student profile step-by-step using the guided form</span>
        </button>
        <button type="button" class="method-card" id="chooseImport">
          <span class="method-icon">&#8679;</span>
          <strong>Import from File</strong>
          <span>Upload a CSV spreadsheet or physical document scans to create profiles in bulk</span>
        </button>
      </div>
    </div>

    <!-- ═══ Manual entry section ═══ -->
    <div id="manualSection" class="hidden">
    <div class="back-to-chooser hidden" id="backToChooserManual">
      <button type="button" class="btn btn-outline btn-sm" id="backToChooserManualBtn">&larr; Change method</button>
    </div>

    <form id="studentForm" novalidate>
      <input type="hidden" id="studentId" value="" />

      <!-- ── Step Indicator ── -->
      <div class="steps-nav">
        <div class="step active" data-step="1"><span>1</span> Basic Info</div>
        <div class="step" data-step="2"><span>2</span> ADHD Profile</div>
        <div class="step" data-step="3"><span>3</span> Comorbidities</div>
        <div class="step" data-step="4"><span>4</span> Medications</div>
        <div class="step" data-step="5"><span>5</span> Accommodations</div>
        <div class="step" data-step="6"><span>6</span> SPED Plans</div>
        <div class="step" data-step="7"><span>7</span> Documents</div>
      </div>

      <!-- ══════ Step 1: Basic Info ══════ -->
      <section class="form-step active" id="step1">
        <div class="card">
          <div class="card-header"><h3>Basic Information</h3></div>
          <div class="card-body">
            <div class="photo-upload-area">
              <img id="photoPreview" src="../assets/img/default-avatar.php" alt="Student Photo" class="profile-photo-preview" />
              <label class="btn btn-outline btn-sm">
                Upload Photo <input type="file" id="photoInput" accept="image/*" class="hidden" />
              </label>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>First Name *</label>
                <input type="text" id="firstName" required />
              </div>
              <div class="form-group">
                <label>Last Name *</label>
                <input type="text" id="lastName" required />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" id="dob" />
              </div>
              <div class="form-group">
                <label>Gender</label>
                <select id="gender">
                  <option value="">Prefer not to say</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="non_binary">Non-Binary</option>
                  <option value="prefer_not_to_say">Prefer not to say</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Grade Level</label>
                <input type="text" id="gradeLevel" placeholder="e.g. Grade 5, Year 7" />
              </div>
              <div class="form-group">
                <label>School-Assigned ID</label>
                <input type="text" id="studentIdNum" placeholder="Optional" />
              </div>
            </div>
            <div class="form-group">
              <label>School Name</label>
              <input type="text" id="schoolName" />
            </div>
            <hr />
            <h4>Parent / Guardian</h4>
            <div class="form-row">
              <div class="form-group">
                <label>Guardian Name</label>
                <input type="text" id="parentName" />
              </div>
              <div class="form-group">
                <label>Guardian Email</label>
                <input type="email" id="parentEmail" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Guardian Phone</label>
                <input type="tel" id="parentPhone" />
              </div>
              <div class="form-group">
                <label>Emergency Contact</label>
                <input type="text" id="emergContact" />
              </div>
            </div>
            <div class="form-group">
              <label>Emergency Phone</label>
              <input type="tel" id="emergPhone" />
            </div>
            <div class="form-group">
              <label>General Notes</label>
              <textarea id="generalNotes" rows="3" placeholder="General teacher notes about this student…"></textarea>
            </div>
          </div>
        </div>
      </section><!-- /step1 -->

      <!-- ══════ Step 2: ADHD Profile ══════ -->
      <section class="form-step hidden" id="step2">
        <div class="card">
          <div class="card-header"><h3>ADHD Profile</h3></div>
          <div class="card-body">
            <div class="form-row">
              <div class="form-group">
                <label>ADHD Presentation *</label>
                <select id="adhdType">
                  <option value="unspecified">Unspecified</option>
                  <option value="predominantly_inattentive">Predominantly Inattentive</option>
                  <option value="predominantly_hyperactive_impulsive">Predominantly Hyperactive-Impulsive</option>
                  <option value="combined_presentation">Combined Presentation</option>
                  <option value="other_specified">Other Specified</option>
                </select>
              </div>
              <div class="form-group">
                <label>Severity</label>
                <select id="adhdSeverity">
                  <option value="mild">Mild</option>
                  <option value="moderate" selected>Moderate</option>
                  <option value="severe">Severe</option>
                </select>
              </div>
            </div>
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

            <h4>Symptom Ratings <small>(1 = Rarely &nbsp;|&nbsp; 5 = Very Often)</small></h4>
            <div class="form-row">
              <div class="form-group">
                <label>Inattention</label>
                <input type="range" id="inattentionRating" min="1" max="5" value="3" oninput="updateRatingLabel(this)" />
                <span class="rating-label" id="inattentionLabel">3</span>
              </div>
              <div class="form-group">
                <label>Hyperactivity</label>
                <input type="range" id="hyperactivityRating" min="1" max="5" value="3" oninput="updateRatingLabel(this)" />
                <span class="rating-label" id="hyperactivityLabel">3</span>
              </div>
              <div class="form-group">
                <label>Impulsivity</label>
                <input type="range" id="impulsivityRating" min="1" max="5" value="3" oninput="updateRatingLabel(this)" />
                <span class="rating-label" id="impulsivityLabel">3</span>
              </div>
            </div>

            <h4>Specific Challenges</h4>
            <div class="checkbox-grid">
              <label class="checkbox-item"><input type="checkbox" id="chkReading" /> Reading Difficulty</label>
              <label class="checkbox-item"><input type="checkbox" id="chkWriting" /> Writing Difficulty</label>
              <label class="checkbox-item"><input type="checkbox" id="chkMath" /> Math Difficulty</label>
              <label class="checkbox-item"><input type="checkbox" id="chkFocus" /> Sustaining Focus</label>
              <label class="checkbox-item"><input type="checkbox" id="chkOrg" /> Organization</label>
              <label class="checkbox-item"><input type="checkbox" id="chkTime" /> Time Management</label>
              <label class="checkbox-item"><input type="checkbox" id="chkMemory" /> Working Memory</label>
              <label class="checkbox-item"><input type="checkbox" id="chkEmotion" /> Emotional Regulation</label>
            </div>

            <h4>Plans in Place</h4>
            <div class="checkbox-grid">
              <label class="checkbox-item"><input type="checkbox" id="chkIEP" /> IEP (Individualized Education Program)</label>
              <label class="checkbox-item"><input type="checkbox" id="chk504" /> Section 504 Plan</label>
            </div>

            <div class="form-group mt-3">
              <label>Additional Notes</label>
              <textarea id="adhdNotes" rows="3" placeholder="Any additional diagnostic or clinical notes…"></textarea>
            </div>
          </div>
        </div>
      </section><!-- /step2 -->

      <!-- ══════ Step 3: Comorbid Conditions ══════ -->
      <section class="form-step hidden" id="step3">
        <div class="card">
          <div class="card-header">
            <h3>Comorbid Conditions</h3>
            <button type="button" class="btn btn-secondary btn-sm" id="addConditionBtn">&#43; Add Condition</button>
          </div>
          <div class="card-body">
            <p class="muted mb-3">Add all known comorbid diagnoses. Common examples include Anxiety Disorder, Dyslexia, ASD, Depression, ODD, Dyscalculia, Sleep Disorders, Sensory Processing Disorder.</p>
            <div id="conditionsList"></div>
          </div>
        </div>
      </section><!-- /step3 -->

      <!-- ══════ Step 4: Medications ══════ -->
      <section class="form-step hidden" id="step4">
        <div class="card">
          <div class="card-header">
            <h3>Current Medications</h3>
            <button type="button" class="btn btn-secondary btn-sm" id="addMedBtn">&#43; Add Medication</button>
          </div>
          <div class="card-body">
            <p class="muted mb-3">Record ADHD and comorbidity-related medications. This information assists with understanding the student's needs and is kept confidential.</p>
            <div id="medicationsList"></div>
          </div>
        </div>
      </section><!-- /step4 -->

      <!-- ══════ Step 5: Accommodations ══════ -->
      <section class="form-step hidden" id="step5">
        <div class="card">
          <div class="card-header">
            <h3>Accommodations &amp; Strategies</h3>
            <button type="button" class="btn btn-secondary btn-sm" id="addAccomBtn">&#43; Add Accommodation</button>
          </div>
          <div class="card-body">
            <p class="muted mb-3">List teaching strategies, assessment accommodations, environmental adjustments, and behavioral supports tailored for this student.</p>
            <!-- Quick-add presets -->
            <div class="preset-badges" id="presetBadges"></div>
            <div id="accommodationsList" class="mt-3"></div>
          </div>
        </div>
      </section><!-- /step5 -->

      <!-- ══════ Step 6: SPED Plans ══════ -->
      <section class="form-step hidden" id="step6">
        <div class="card">
          <div class="card-header"><h3>&#128221; SPED Plans</h3></div>
          <div class="card-body">
            <p class="muted mb-3">Enter IEP, ITP, or Individual Profile data manually — or upload a document for each plan type.</p>

            <!-- Plan Tab Buttons -->
            <div class="plan-tab-strip">
              <button type="button" class="plan-tab-btn active" data-plan="iep">IEP</button>
              <button type="button" class="plan-tab-btn" data-plan="itp">ITP</button>
              <button type="button" class="plan-tab-btn" data-plan="sip">Individual Profile</button>
            </div>

            <!-- ═══ IEP Panel ═══ -->
            <div class="plan-panel" id="planPanelIep">
              <div class="entry-toggle mb-3">
                <button type="button" class="entry-btn active" data-panel="iep" data-mode="manual">&#9998; Manual Entry</button>
                <button type="button" class="entry-btn" data-panel="iep" data-mode="upload">&#8679; Upload Document</button>
              </div>

              <div id="iepFormFields">
                <h4>IEP Information</h4>
                <div class="form-row">
                  <div class="form-group"><label>Effective Date</label><input type="date" id="iep_effective_date" /></div>
                  <div class="form-group"><label>Review Date</label><input type="date" id="iep_review_date" /></div>
                  <div class="form-group"><label>Meeting Date</label><input type="date" id="iep_meeting_date" /></div>
                </div>
                <div class="form-row">
                  <div class="form-group"><label>Disability Classification</label><input type="text" id="iep_disability_class" /></div>
                  <div class="form-group"><label>SPED Category</label><input type="text" id="iep_sped_category" /></div>
                </div>
                <div class="form-group"><label>IEP Team</label><textarea id="iep_team" rows="2" placeholder="Names and roles, comma-separated"></textarea></div>

                <h4>Present Level of Educational Performance (PLEP)</h4>
                <div class="form-group"><label>Academic Performance</label><textarea id="iep_plep_academic" rows="2"></textarea></div>
                <div class="form-group"><label>Functional Performance</label><textarea id="iep_plep_functional" rows="2"></textarea></div>
                <div class="form-group"><label>Social / Emotional Performance</label><textarea id="iep_plep_social" rows="2"></textarea></div>

                <h4>Annual Goals &amp; Objectives</h4>
                <div class="form-group"><label>Annual Goals</label><textarea id="iep_annual_goals" rows="2"></textarea></div>
                <div class="form-group"><label>Short-Term Objectives</label><textarea id="iep_objectives" rows="2"></textarea></div>

                <h4>Services</h4>
                <div class="form-group"><label>Special Education Services</label><textarea id="iep_sped_services" rows="2"></textarea></div>
                <div class="form-group"><label>Related Services</label><textarea id="iep_related_services" rows="2"></textarea></div>

                <h4>Accommodations &amp; Placement</h4>
                <div class="form-group"><label>Accommodations</label><textarea id="iep_accommodations" rows="2"></textarea></div>
                <div class="form-group"><label>Modifications</label><textarea id="iep_modifications" rows="2"></textarea></div>
                <div class="form-row">
                  <div class="form-group"><label>Regular Ed Participation %</label><input type="number" id="iep_regular_ed_pct" min="0" max="100" /></div>
                  <div class="form-group"><label>Assessment Accommodations</label><textarea id="iep_assess_accom" rows="2"></textarea></div>
                </div>

                <h4>Transition &amp; Notes</h4>
                <div class="form-group"><label>Transition Services</label><textarea id="iep_transition" rows="2"></textarea></div>
                <div class="form-group"><label>Additional Notes</label><textarea id="iep_notes" rows="2"></textarea></div>
              </div><!-- /iepFormFields -->

              <div id="iepUploadArea" class="hidden">
                <div class="upload-zone" id="iepUploadZone">
                  <div class="upload-zone-inner">
                    <span class="upload-icon">&#128196;</span>
                    <p>Drag &amp; drop IEP document, or click to browse</p>
                    <input type="file" id="iepFileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                  </div>
                </div>
                <div id="iepFilePreview" class="mt-2"></div>
              </div><!-- /iepUploadArea -->
            </div><!-- /planPanelIep -->

            <!-- ═══ ITP Panel ═══ -->
            <div class="plan-panel hidden" id="planPanelItp">
              <div class="entry-toggle mb-3">
                <button type="button" class="entry-btn active" data-panel="itp" data-mode="manual">&#9998; Manual Entry</button>
                <button type="button" class="entry-btn" data-panel="itp" data-mode="upload">&#8679; Upload Document</button>
              </div>

              <div id="itpFormFields">
                <h4>ITP Information</h4>
                <div class="form-row">
                  <div class="form-group"><label>Effective Date</label><input type="date" id="itp_effective_date" /></div>
                  <div class="form-group"><label>Anticipated Graduation Date</label><input type="date" id="itp_graduation_date" /></div>
                </div>
                <div class="form-group"><label>Disability Category</label><input type="text" id="itp_disability_category" /></div>

                <h4>Present Level of Performance</h4>
                <div class="form-group"><label>Career Interests</label><textarea id="itp_career_interests" rows="2"></textarea></div>
                <div class="form-group"><label>Assessed Strengths</label><textarea id="itp_assessed_strengths" rows="2"></textarea></div>
                <div class="form-group"><label>Work Experiences</label><textarea id="itp_work_experiences" rows="2"></textarea></div>
                <div class="form-group"><label>Community Experiences</label><textarea id="itp_community_experiences" rows="2"></textarea></div>
                <div class="form-group"><label>Daily Living Skills</label><textarea id="itp_daily_living" rows="2"></textarea></div>

                <h4>Post-Secondary Goals</h4>
                <div class="form-group"><label>Education / Training</label><textarea id="itp_goal_education" rows="2"></textarea></div>
                <div class="form-group"><label>Employment</label><textarea id="itp_goal_employment" rows="2"></textarea></div>
                <div class="form-group"><label>Independent Living</label><textarea id="itp_goal_independent" rows="2"></textarea></div>
                <div class="form-group"><label>Community Participation</label><textarea id="itp_goal_community" rows="2"></textarea></div>

                <h4>Transition Services</h4>
                <div class="form-group"><label>Instruction</label><textarea id="itp_services_instruction" rows="2"></textarea></div>
                <div class="form-group"><label>Community Experiences</label><textarea id="itp_services_community" rows="2"></textarea></div>
                <div class="form-group"><label>Employment / Post-School</label><textarea id="itp_services_employment" rows="2"></textarea></div>
                <div class="form-group"><label>Adult Living / Daily Living</label><textarea id="itp_services_adult" rows="2"></textarea></div>

                <h4>Course of Study &amp; Linkages</h4>
                <div class="form-group"><label>Course of Study</label><textarea id="itp_course_of_study" rows="2"></textarea></div>
                <div class="form-group"><label>Agency Linkages</label><textarea id="itp_agency_linkages" rows="2"></textarea></div>

                <h4>Annual Goals &amp; Notes</h4>
                <div class="form-group"><label>Annual Transition Goals</label><textarea id="itp_annual_goals" rows="2"></textarea></div>
                <div class="form-group"><label>Additional Notes</label><textarea id="itp_notes" rows="2"></textarea></div>
              </div><!-- /itpFormFields -->

              <div id="itpUploadArea" class="hidden">
                <div class="upload-zone" id="itpUploadZone">
                  <div class="upload-zone-inner">
                    <span class="upload-icon">&#128196;</span>
                    <p>Drag &amp; drop ITP document, or click to browse</p>
                    <input type="file" id="itpFileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                  </div>
                </div>
                <div id="itpFilePreview" class="mt-2"></div>
              </div><!-- /itpUploadArea -->
            </div><!-- /planPanelItp -->

            <!-- ═══ Individual Student Profile Panel ═══ -->
            <div class="plan-panel hidden" id="planPanelSip">
              <div class="entry-toggle mb-3">
                <button type="button" class="entry-btn active" data-panel="sip" data-mode="manual">&#9998; Manual Entry</button>
                <button type="button" class="entry-btn" data-panel="sip" data-mode="upload">&#8679; Upload Document</button>
              </div>

              <div id="sipFormFields">
                <h4>Student Classification</h4>
                <div class="form-row">
                  <div class="form-group"><label>Disability Classification</label><input type="text" id="sip_disability_class" /></div>
                  <div class="form-group"><label>SPED Category</label><input type="text" id="sip_sped_category" /></div>
                </div>
                <div class="form-row">
                  <div class="form-group"><label>Years in SPED</label><input type="number" id="sip_years_in_sped" min="0" /></div>
                  <div class="form-group"><label>Preferred Name</label><input type="text" id="sip_preferred_name" /></div>
                </div>
                <div class="form-row">
                  <div class="form-group"><label>Pronouns</label><input type="text" id="sip_pronouns" placeholder="e.g. she/her, he/him, they/them" /></div>
                  <div class="form-group"><label>Primary Language</label><input type="text" id="sip_language" /></div>
                </div>

                <h4>Strengths &amp; Challenges</h4>
                <div class="form-group"><label>Academic Strengths</label><textarea id="sip_academic_strengths" rows="2"></textarea></div>
                <div class="form-group"><label>Academic Challenges</label><textarea id="sip_academic_challenges" rows="2"></textarea></div>
                <div class="form-group"><label>Behavioral Strengths</label><textarea id="sip_behavioral_strengths" rows="2"></textarea></div>
                <div class="form-group"><label>Behavioral Challenges</label><textarea id="sip_behavioral_challenges" rows="2"></textarea></div>
                <div class="form-group"><label>Social Strengths</label><textarea id="sip_social_strengths" rows="2"></textarea></div>
                <div class="form-group"><label>Social Challenges</label><textarea id="sip_social_challenges" rows="2"></textarea></div>

                <h4>Learning Profile</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label>Learning Style</label>
                    <select id="sip_learning_style">
                      <option value="mixed">Mixed</option>
                      <option value="visual">Visual</option>
                      <option value="auditory">Auditory</option>
                      <option value="kinesthetic">Kinesthetic</option>
                      <option value="other">Other</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Attention Span</label>
                    <select id="sip_attention_span">
                      <option value="variable">Variable</option>
                      <option value="short">Short</option>
                      <option value="moderate">Moderate</option>
                      <option value="good">Good</option>
                    </select>
                  </div>
                </div>
                <div class="form-group"><label>Learning Style Notes</label><textarea id="sip_learning_style_notes" rows="2"></textarea></div>

                <h4>Communication &amp; Behavior</h4>
                <div class="form-group"><label>Communication Profile</label><textarea id="sip_communication" rows="2"></textarea></div>
                <div class="form-group"><label>Motivators</label><textarea id="sip_motivators" rows="2"></textarea></div>
                <div class="form-group"><label>Triggers</label><textarea id="sip_triggers" rows="2"></textarea></div>
                <div class="form-group"><label>Calming Strategies</label><textarea id="sip_calming" rows="2"></textarea></div>
                <div class="form-group"><label>Reinforcement Strategies</label><textarea id="sip_reinforcement" rows="2"></textarea></div>

                <h4>Support Network &amp; Observations</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label>Family Support Level</label>
                    <select id="sip_family_support">
                      <option value="unknown">Unknown</option>
                      <option value="high">High</option>
                      <option value="moderate">Moderate</option>
                      <option value="limited">Limited</option>
                    </select>
                  </div>
                  <div class="form-group"><label>Outside Services</label><textarea id="sip_outside_services" rows="2"></textarea></div>
                </div>
                <div class="form-group"><label>Student Voice</label><textarea id="sip_student_voice" rows="2" placeholder="What the student says about their own learning"></textarea></div>
                <div class="form-group"><label>Teacher Observations</label><textarea id="sip_teacher_observations" rows="2"></textarea></div>
              </div><!-- /sipFormFields -->

              <div id="sipUploadArea" class="hidden">
                <div class="upload-zone" id="sipUploadZone">
                  <div class="upload-zone-inner">
                    <span class="upload-icon">&#128196;</span>
                    <p>Drag &amp; drop Individual Profile document, or click to browse</p>
                    <input type="file" id="sipFileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                  </div>
                </div>
                <div id="sipFilePreview" class="mt-2"></div>
              </div><!-- /sipUploadArea -->
            </div><!-- /planPanelSip -->

          </div><!-- /card-body -->
        </div><!-- /card -->
      </section><!-- /step6 -->

      <!-- ══════ Step 7: Documents ══════ -->
      <section class="form-step hidden" id="step7">
        <div class="card">
          <div class="card-header"><h3>Supporting Documents</h3></div>
          <div class="card-body">
            <p class="muted mb-3">Upload IEPs, psychological evaluations, medical reports, or 504 plans. Accepted formats: PDF, Word, Excel, JPEG, PNG. Max 10 MB per file.</p>
            <div class="upload-zone" id="docUploadZone">
              <div class="upload-zone-inner">
                <span class="upload-icon">&#128196;</span>
                <p>Drag &amp; drop files here, or click to browse</p>
                <input type="file" id="docFileInput" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" />
              </div>
            </div>
            <div id="docUploadList" class="doc-upload-list mt-3"></div>
            <div id="existingDocsList" class="mt-3"></div>
          </div>
        </div>
      </section><!-- /step7 -->

      <!-- ── Navigation Buttons ── -->
      <div class="form-nav-btns">
        <button type="button" class="btn btn-outline" id="prevBtn" disabled>&#8592; Previous</button>
        <button type="button" class="btn btn-primary"  id="nextBtn">Next &#8594;</button>
        <button type="submit"  class="btn btn-success hidden" id="submitBtn">
          <span class="btn-text">Save Student Profile</span>
          <span class="btn-spinner hidden">&#8987;</span>
        </button>
      </div>
    </form>
    </div><!-- /manualSection -->

    <!-- ═══ Import section ═══ -->
    <div id="importSection" class="hidden">
      <div class="back-to-chooser" id="backToChooserImport">
        <button type="button" class="btn btn-outline btn-sm" id="backToChooserImportBtn">&larr; Change method</button>
      </div>

      <!-- Tab bar -->
      <div class="import-tabs mt-3">
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
            <strong>Document Upload</strong>
            <span>Upload scans, PDFs, Word docs or spreadsheets to create profiles</span>
          </div>
        </button>
      </div>

      <!-- ═══ TAB: CSV Import ═══ -->
      <div id="tabCsv" class="import-tab-content active">
        <div class="import-step-card">
          <div class="import-step-number">1</div>
          <div class="import-step-body">
            <h3>Download the Template</h3>
            <p>Fill in the CSV template with your students&apos; information. Each row = one student. Open with Excel or Google Sheets.</p>
            <a href="../api/students/template.php" class="btn btn-primary">&#8595; Download CSV Template</a>
            <div class="template-fields-hint mt-3">
              <strong>Template covers:</strong> Basic info &middot; ADHD type &amp; ratings &middot; Challenges &middot;
              Comorbid conditions &middot; Medications &middot; Accommodations &middot; IEP / 504 status
            </div>
          </div>
        </div>

        <div class="import-step-card">
          <div class="import-step-number">2</div>
          <div class="import-step-body">
            <h3>Upload Your Completed File</h3>
            <div class="upload-zone" id="csvDropZone">
              <div class="upload-zone-inner">
                <span class="upload-icon">&#128202;</span>
                <p>Drag &amp; drop your CSV here, or click to browse</p>
                <p class="muted">Accepts .csv files &mdash; maximum 5 MB</p>
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

        <div class="import-step-card">
          <div class="import-step-number">3</div>
          <div class="import-step-body">
            <h3>Preview &amp; Confirm</h3>
            <p class="muted">Review the data before importing. Rows with errors will be skipped.</p>
            <div class="preview-actions">
              <button class="btn btn-secondary" id="previewBtn" disabled>&#128270; Preview Data</button>
              <button class="btn btn-success hidden" id="confirmImportBtn">&#10003; Import All Valid Rows</button>
              <button class="btn btn-outline  hidden" id="cancelPreviewBtn">&#10005; Cancel</button>
            </div>
            <div id="previewLoading" class="loading-msg hidden">Parsing file&hellip;</div>
            <div id="previewSummary" class="import-summary hidden">
              <div class="summary-stat summary-ok"  id="summaryValid"></div>
              <div class="summary-stat summary-err" id="summaryErrors"></div>
            </div>
            <div id="previewErrors" class="error-list hidden"></div>
            <div id="previewTableWrapper" class="hidden">
              <div class="table-wrapper mt-3">
                <table class="data-table" id="previewTable">
                  <thead>
                    <tr>
                      <th>#</th><th>Name</th><th>Grade</th><th>School</th>
                      <th>ADHD Type</th><th>Severity</th>
                      <th>Conditions</th><th>Meds</th><th>Accommodations</th><th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="previewTableBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div id="csvImportResult" class="import-result hidden"></div>
      </div><!-- /tabCsv -->

      <!-- ═══ TAB: Document Upload ═══ -->
      <div id="tabDocument" class="import-tab-content hidden">
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
      </div><!-- /tabDocument -->
    </div><!-- /importSection -->

  </main><!-- /main-content -->

  <script src="../assets/js/auth-guard.js"></script>
  <script src="../assets/js/student-form.js"></script>
  <script src="../assets/js/student-import.js"></script>
</body>
</html>
