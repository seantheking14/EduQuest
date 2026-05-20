<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduQuest – Student Dashboard View</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <style>
    /* ── POV Banner ── */
    .pov-banner {
      background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
      color: #fff;
      padding: 0.7rem 1.25rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
      border-radius: var(--radius);
      margin-bottom: 1.5rem;
      box-shadow: 0 4px 16px rgba(99,102,241,0.3);
    }
    .pov-banner .pov-label {
      display: flex; align-items: center; gap: 0.6rem;
      font-size: 0.875rem; font-weight: 700;
    }
    .pov-banner .pov-label span { font-size: 1.15rem; }
    .pov-banner a { color: rgba(255,255,255,0.85); font-size: 0.82rem; }
    .pov-banner a:hover { color: #fff; text-decoration: underline; }

    /* ── Student Hero ── */
    .hero-card {
      background: var(--surface);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      box-shadow: var(--shadow-sm);
      padding: 1.75rem 2rem;
      display: flex;
      align-items: center;
      gap: 1.75rem;
      flex-wrap: wrap;
      margin-bottom: 1.5rem;
      position: relative;
      overflow: hidden;
    }
    .hero-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; height: 4px;
      background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
    }
    .hero-avatar-wrap { position: relative; flex-shrink: 0; }
    .hero-avatar {
      width: 88px; height: 88px; border-radius: 50%;
      object-fit: cover; border: 3px solid #e2e8f0;
    }
    .level-ring {
      position: absolute; bottom: -6px; left: 50%; transform: translateX(-50%);
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: #fff; font-size: 0.7rem; font-weight: 800;
      padding: 2px 8px; border-radius: 20px;
      white-space: nowrap; border: 2px solid #fff;
    }
    .hero-info { flex: 1; min-width: 200px; }
    .hero-name { font-size: 1.55rem; font-weight: 800; color: var(--text-1); margin-bottom: 0.1rem; }
    .hero-meta { font-size: 0.85rem; color: var(--text-2); margin-bottom: 0.75rem; }
    .hero-xp-bar { margin-top: 0.5rem; }
    .xp-label { font-size: 0.75rem; color: var(--text-2); font-weight: 600; margin-bottom: 0.3rem;
                display: flex; justify-content: space-between; }
    .xp-track { height: 10px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
    .xp-fill  { height: 100%; border-radius: 99px;
                background: linear-gradient(90deg, #6366f1, #8b5cf6); transition: width 0.6s ease; }
    .hero-stats { display: flex; gap: 1.5rem; flex-wrap: wrap; }
    .hs-item { text-align: center; }
    .hs-val  { font-size: 1.6rem; font-weight: 800; color: var(--text-1); line-height: 1; }
    .hs-lbl  { font-size: 0.7rem; color: var(--text-2); font-weight: 600;
               text-transform: uppercase; letter-spacing: 0.4px; margin-top: 3px; }

    /* ── Tab Navigation ── */
    .pov-tabs {
      display: flex; gap: 0.5rem; margin-bottom: 1.5rem;
      flex-wrap: wrap; border-bottom: 2px solid var(--border); padding-bottom: 0;
    }
    .pov-tab {
      padding: 0.65rem 1.1rem; font-size: 0.875rem; font-weight: 600;
      color: var(--text-2); cursor: pointer; border: none; background: none;
      border-bottom: 2px solid transparent; margin-bottom: -2px;
      transition: color 0.15s, border-color 0.15s;
      font-family: inherit; border-radius: 4px 4px 0 0;
    }
    .pov-tab:hover { color: var(--text-1); }
    .pov-tab.active { color: var(--accent); border-bottom-color: var(--accent); }

    /* ── Tab Panels ── */
    .pov-panel { display: none; }
    .pov-panel.active { display: block; }

    /* ── Stat Cards Row ── */
    .pov-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 1rem; margin-bottom: 1.5rem;
    }
    .pov-stat-card {
      background: var(--surface); border-radius: var(--radius);
      border: 1px solid var(--border); padding: 1.1rem 1.25rem;
      text-align: center;
      box-shadow: var(--shadow-sm);
      transition: box-shadow var(--transition), transform var(--transition);
    }
    .pov-stat-card:hover { box-shadow: var(--shadow); transform: translateY(-2px); }
    .pov-stat-icon { font-size: 1.6rem; margin-bottom: 0.35rem; }
    .pov-stat-val  { font-size: 1.8rem; font-weight: 800; color: var(--text-1); line-height: 1; }
    .pov-stat-lbl  { font-size: 0.72rem; color: var(--text-2); font-weight: 600;
                     text-transform: uppercase; letter-spacing: 0.4px; margin-top: 4px; }

    /* ── Achievements grid ── */
    .ach-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 1rem;
    }
    .ach-card {
      background: var(--surface); border-radius: var(--radius-sm);
      border: 1px solid var(--border); padding: 1rem 1.1rem;
      display: flex; align-items: center; gap: 0.9rem;
      transition: box-shadow var(--transition);
    }
    .ach-card:hover { box-shadow: var(--shadow-sm); }
    .ach-card.locked { opacity: 0.45; filter: grayscale(0.6); }
    .ach-icon  { font-size: 2rem; flex-shrink: 0; }
    .ach-title { font-weight: 700; font-size: 0.875rem; color: var(--text-1); }
    .ach-desc  { font-size: 0.75rem; color: var(--text-2); margin-top: 1px; }
    .ach-prog  { margin-top: 0.4rem; }
    .ach-prog-bar { height: 5px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
    .ach-prog-fill{ height: 100%; border-radius: 99px; background: linear-gradient(90deg, #6366f1, #8b5cf6); }
    .ach-badge { font-size: 0.7rem; font-weight: 700; padding: 1px 7px; border-radius: 20px;
                 background: #d1fae5; color: #065f46; margin-left: auto; flex-shrink: 0; }
    .ach-badge.locked-badge { background: #f1f5f9; color: var(--text-3); }

    /* ── XP Timeline ── */
    .xp-timeline { display: flex; flex-direction: column; gap: 0.6rem; }
    .xp-item {
      display: flex; align-items: center; gap: 0.85rem;
      padding: 0.7rem 1rem; background: #fafafa;
      border-radius: var(--radius-sm); border: 1px solid var(--border);
    }
    .xp-item-icon { font-size: 1.2rem; flex-shrink: 0; width: 28px; text-align: center; }
    .xp-item-desc { flex: 1; font-size: 0.875rem; color: var(--text-1); }
    .xp-item-amount { font-size: 0.875rem; font-weight: 700; color: #059669;
                      white-space: nowrap; }
    .xp-item-date { font-size: 0.72rem; color: var(--text-3); white-space: nowrap; }

    /* ── Grades table extras ── */
    .grade-summary {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 0.75rem; margin-bottom: 1.25rem;
    }
    .grade-summary-card {
      background: var(--surface); border-radius: var(--radius-sm); border: 1px solid var(--border);
      padding: 0.9rem 1rem; text-align: center;
    }
    .grade-summary-val { font-size: 1.5rem; font-weight: 800; color: var(--text-1); }
    .grade-summary-lbl { font-size: 0.72rem; color: var(--text-2); font-weight: 600;
                         text-transform: uppercase; letter-spacing: 0.4px; margin-top: 3px; }
    .grade-chip {
      display: inline-flex; align-items: center; justify-content: center;
      width: 44px; height: 44px; border-radius: 50%; font-weight: 800; font-size: 0.85rem;
    }
    .grade-a  { background: #d1fae5; color: #065f46; }
    .grade-b  { background: #dbeafe; color: #1d4ed8; }
    .grade-c  { background: #fef3c7; color: #92400e; }
    .grade-d  { background: #fee2e2; color: #991b1b; }
    .grade-f  { background: #fce7f3; color: #9d174d; }

    /* ── Quiz Scores ── */
    .quiz-row { display: flex; align-items: center; justify-content: space-between;
                gap: 1rem; padding: 0.8rem 1rem; flex-wrap: wrap; }
    .quiz-title { font-weight: 600; font-size: 0.875rem; color: var(--text-1); }
    .quiz-subject { font-size: 0.75rem; color: var(--text-2); }
    .quiz-score-ring {
      min-width: 52px; height: 52px; border-radius: 50%;
      display: flex; flex-direction: column; align-items: center;
      justify-content: center; font-weight: 800; font-size: 0.8rem;
      background: conic-gradient(#6366f1 var(--pct), #e2e8f0 0%);
      box-shadow: 0 0 0 4px #fff inset;
      position: relative;
    }
    .quiz-score-ring::after {
      content: attr(data-score) '%';
      position: absolute; font-size: 0.72rem; font-weight: 800; color: var(--text-1);
    }

    /* ── Learning Progress ── */
    .learn-row { padding: 0.85rem 1rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
    .learn-title { flex: 1; font-weight: 600; font-size: 0.875rem; color: var(--text-1); min-width: 150px; }
    .learn-subject { font-size: 0.72rem; color: var(--text-2); }
    .learn-bar-wrap { flex: 1; min-width: 100px; }
    .learn-bar-track { height: 8px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
    .learn-bar-fill  { height: 100%; border-radius: 99px;
                       background: linear-gradient(90deg, #6366f1, #8b5cf6); }
    .learn-pct { font-size: 0.78rem; font-weight: 700; color: var(--accent); width: 36px; text-align: right; flex-shrink: 0; }
    .status-chip {
      font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 20px;
    }
    .status-completed { background: #d1fae5; color: #065f46; }
    .status-in_progress { background: #dbeafe; color: #1d4ed8; }
    .status-not_started { background: #f1f5f9; color: var(--text-3); }

    /* ── Egg / Pet companion ── */
    .egg-showcase {
      display: flex; flex-direction: column; align-items: center;
      gap: 0.75rem; padding: 1.5rem;
    }
    .egg-sprite { font-size: 5rem; animation: eggFloat 3s ease-in-out infinite; }
    @keyframes eggFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
    .egg-name { font-weight: 700; font-size: 1rem; color: var(--text-1); }
    .egg-sub  { font-size: 0.82rem; color: var(--text-2); text-align: center; }

    /* ── Leaderboard rank card ── */
    .rank-card {
      display: flex; align-items: center; gap: 1.25rem;
      padding: 1.25rem 1.5rem;
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      border-radius: var(--radius); border: 1px solid #fbbf24;
      margin-bottom: 1.25rem;
    }
    .rank-medal { font-size: 3rem; flex-shrink: 0; }
    .rank-val { font-size: 2.5rem; font-weight: 900; color: #92400e; line-height: 1; }
    .rank-sub { font-size: 0.85rem; color: #78350f; }

    /* ── Responsive ── */
    @media (max-width: 640px) {
      .hero-card { flex-direction: column; align-items: flex-start; gap: 1rem; }
      .hero-stats { gap: 1rem; }
      .pov-stats  { grid-template-columns: 1fr 1fr; }
      .ach-grid   { grid-template-columns: 1fr; }
      .grade-summary { grid-template-columns: 1fr 1fr; }
    }
  </style>
</head>
<body class="app-page">

  <!-- Sidebar -->
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

  <!-- Main Content -->
  <main class="main-content" id="povContent">
    <div class="loading-msg">Loading student dashboard…</div>
  </main>

  <script src="../assets/js/auth-guard.js"></script>
  <script>
  (function () {
    'use strict';

    const params    = new URLSearchParams(window.location.search);
    const studentId = parseInt(params.get('id'), 10);
    if (!studentId) {
      document.getElementById('povContent').innerHTML =
        '<div class="alert alert-error">No student ID provided.</div>';
      return;
    }

    document.addEventListener('DOMContentLoaded', loadPOV);

    /* ── XP Icons ── */
    const XP_ICONS = {
      quest: '🎯', lesson: '📖', quiz: '📝', activity: '📖',
      daily_challenge: '⚡', achievement: '🏆', login: '🔥',
      streak_bonus: '🔥', teacher_award: '⭐', correction: '✏️', default: '✨',
    };

    /* ── Egg sprites by stage ── */
    const EGG_SPRITES = ['🥚','🥚','🐣','🐣','🐥','🐥','🐔','🦅'];
    const EGG_NAMES   = ['Egg','Warm Egg','Cracking…','Almost!','Hatchling','Young','Growing','Evolved!'];

    /* ── Grade letter helper ── */
    function gradeLetter(score) {
      if (score >= 90) return { letter: 'A', cls: 'grade-a' };
      if (score >= 80) return { letter: 'B', cls: 'grade-b' };
      if (score >= 70) return { letter: 'C', cls: 'grade-c' };
      if (score >= 60) return { letter: 'D', cls: 'grade-d' };
      return { letter: 'F', cls: 'grade-f' };
    }

    /* ── Build quiz tab HTML (pre-computed to avoid nested template issues) ── */
    function buildQuizHTML(scores) {
      if (!Array.isArray(scores) || scores.length === 0) {
        return '<div class="card-body"><div class="empty-state">No quizzes taken yet.</div></div>';
      }
      return scores.map(function(q, i) {
        var pct = (q.percentage !== null && q.percentage !== '' && q.percentage !== undefined)
          ? Math.round(Number(q.percentage))
          : (Number(q.max_score) > 0 ? Math.round(Number(q.score) / Number(q.max_score) * 100) : 0);
        var gl      = gradeLetter(pct);
        var passed  = (q.passed == 1);
        var xpEarned = Number(q.xp_earned) || 0;
        var border  = i < scores.length - 1 ? 'border-bottom:1px solid var(--border)' : '';
        return '<div class="quiz-row" style="' + border + '">'
          + '<div>'
          +   '<div class="quiz-title">' + esc(q.quiz_title || 'Untitled Quiz') + '</div>'
          +   '<div class="quiz-subject">'
          +     (q.subject ? esc(q.subject) + ' · ' : '')
          +     Number(q.score) + '/' + Number(q.max_score) + ' pts'
          +     (passed  ? ' · <span style="color:#059669">✓ Passed</span>' : '')
          +     (xpEarned > 0 ? ' · <span style="color:#059669">+' + xpEarned + ' XP</span>' : '')
          +     ' · ' + fmtDate(q.completed_at)
          +   '</div>'
          + '</div>'
          + '<div style="display:flex;align-items:center;gap:.75rem">'
          +   '<span class="grade-chip ' + gl.cls + '">' + gl.letter + '</span>'
          +   '<div style="font-size:1.1rem;font-weight:800;color:var(--text-1)">' + pct + '%</div>'
          + '</div>'
          + '</div>';
      }).join('');
    }

    /* ── Team element helpers ── */
    function teamIcon(team) {
      const t = (team || '').toLowerCase().trim();
      if (t === 'fire')  return '🔥';
      if (t === 'water') return '💧';
      if (t === 'grass') return '🌿';
      return '🛡️';
    }
    function teamLabel(team) {
      if (!team) return '';
      return team.charAt(0).toUpperCase() + team.slice(1).toLowerCase();
    }
    function teamStyle(team) {
      const t = (team || '').toLowerCase().trim();
      if (t === 'fire')  return { bg: '#fff7ed', border: '#fb923c', text: '#c2410c' };
      if (t === 'water') return { bg: '#eff6ff', border: '#3b82f6', text: '#1d4ed8' };
      if (t === 'grass') return { bg: '#f0fdf4', border: '#22c55e', text: '#166534' };
      return { bg: '#f8fafc', border: '#94a3b8', text: '#475569' };
    }

    function esc(str) {
      const d = document.createElement('span');
      d.textContent = str || '—';
      return d.innerHTML;
    }

    function fmtDate(str) {
      if (!str) return '—';
      return new Date(str).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    /* ── Load everything ── */
    async function loadPOV() {
      try {
        const res  = await fetch(`../api/students/student-pov.php?student_id=${studentId}`, {
          headers: EQ.authHeaders(),
        });
        const json = await res.json();
        if (!json.success) {
          document.getElementById('povContent').innerHTML =
            `<div class="alert alert-error">${esc(json.message)}</div>`;
          return;
        }
        render(json.data);
      } catch (err) {
        document.getElementById('povContent').innerHTML =
          '<div class="alert alert-error">Failed to load student data.</div>';
      }
    }

    /* ── Render full POV ── */
    function render(d) {
      const s   = d.student;
      const g   = d.gamification;
      const ldr = d.leaderboard;

      // Pre-compute quiz HTML outside the big template literal
      const quizTabContent = buildQuizHTML(d.quizScores);

      const xpPct    = g.xpNeeded > 0 ? Math.min(100, Math.round((g.xpProgress / g.xpNeeded) * 100)) : 0;
      const photoUrl = s.profile_photo
        ? `../uploads/photos/${s.profile_photo}`
        : '../assets/img/default-avatar.php';

      const tIcon  = teamIcon(g.team);
      const tLabel = teamLabel(g.team);
      const tStyle = teamStyle(g.team);

      document.title = `EduQuest – ${s.first_name} ${s.last_name}'s Dashboard`;

      document.getElementById('povContent').innerHTML = `

        <!-- POV Banner -->
        <div class="pov-banner">
          <div class="pov-label">
            <span>👁️</span>
            Viewing as <strong>&nbsp;${esc(s.first_name)} ${esc(s.last_name)}</strong>
            &nbsp;— Read-only student dashboard view
          </div>
          <a href="students.php">← Back to My Students</a>
        </div>

        <!-- Hero Card -->
        <div class="hero-card">
          <div class="hero-avatar-wrap">
            <img src="${photoUrl}" class="hero-avatar" alt="${esc(s.first_name)}"
                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'88\\' height=\\'88\\' viewBox=\\'0 0 88 88\\'%3E%3Ccircle cx=\\'44\\' cy=\\'44\\' r=\\'44\\' fill=\\'%23e2e8f0\\'/%3E%3Ctext x=\\'44\\' y=\\'52\\' text-anchor=\\'middle\\' font-size=\\'36\\' fill=\\'%2394a3b8\\'%3E🧑‍🎓%3C/text%3E%3C/svg%3E'" />
            <div class="level-ring">Lv ${g.level}</div>
          </div>
          <div class="hero-info">
            <div class="hero-name">${esc(s.first_name)} ${esc(s.last_name)}</div>
            <div class="hero-meta">
              ${[s.grade_level, s.school_name].filter(Boolean).map(v => esc(v)).join(' · ')}
              ${s.email ? (s.grade_level || s.school_name ? ' · ' : '') + esc(s.email) : ''}
              ${s.has_account ? ' <span class="badge badge-success" style="font-size:0.68rem">Has Account</span>' : ' <span class="badge" style="font-size:0.68rem">No Account</span>'}
            </div>
            <div class="hero-xp-bar">
              <div class="xp-label">
                <span>Level ${g.level} — ${g.xpProgress.toLocaleString()} / ${g.xpNeeded.toLocaleString()} XP</span>
                <span>${xpPct}%</span>
              </div>
              <div class="xp-track"><div class="xp-fill" style="width:${xpPct}%"></div></div>
            </div>
          </div>
          <div class="hero-stats">
            <div class="hs-item">
              <div class="hs-val">⚡ ${g.totalXp.toLocaleString()}</div>
              <div class="hs-lbl">Total XP</div>
            </div>
            <div class="hs-item">
              <div class="hs-val">🔥 ${g.streak}</div>
              <div class="hs-lbl">Day Streak</div>
            </div>
            <div class="hs-item">
              <div class="hs-val">🏆 ${d.achievements.unlocked}</div>
              <div class="hs-lbl">Badges</div>
            </div>
            ${g.team ? `
            <div class="hs-item">
              <div class="hs-val">${tIcon} ${tLabel}</div>
              <div class="hs-lbl">Team</div>
            </div>` : ''}
            ${ldr.rank ? `
            <div class="hs-item">
              <div class="hs-val">#${ldr.rank}</div>
              <div class="hs-lbl">Rank / ${ldr.total}</div>
            </div>` : ''}
          </div>
        </div>

        <!-- Tabs -->
        <div class="pov-tabs" id="povTabs">
          <button class="pov-tab active" data-tab="overview">🏠 Overview</button>
          <button class="pov-tab" data-tab="progress">🌟 Progress</button>
          <button class="pov-tab" data-tab="achievements">🏆 Achievements</button>
          <button class="pov-tab" data-tab="grades">📊 Grades</button>
          <button class="pov-tab" data-tab="quizzes">📝 Quizzes</button>
          <button class="pov-tab" data-tab="learning">📚 Learning</button>
          <button class="pov-tab" data-tab="pet">🥚 Pet</button>
        </div>

        <!-- ── OVERVIEW ── -->
        <div class="pov-panel active" id="tab-overview">
          <div class="pov-stats">
            <div class="pov-stat-card">
              <div class="pov-stat-icon">⚡</div>
              <div class="pov-stat-val">${g.totalXp.toLocaleString()}</div>
              <div class="pov-stat-lbl">Total XP</div>
            </div>
            <div class="pov-stat-card">
              <div class="pov-stat-icon">📈</div>
              <div class="pov-stat-val">${g.level}</div>
              <div class="pov-stat-lbl">Level</div>
            </div>
            <div class="pov-stat-card">
              <div class="pov-stat-icon">🔥</div>
              <div class="pov-stat-val">${g.streak}</div>
              <div class="pov-stat-lbl">Day Streak</div>
            </div>
            <div class="pov-stat-card">
              <div class="pov-stat-icon">🏆</div>
              <div class="pov-stat-val">${d.achievements.unlocked} / ${d.achievements.total}</div>
              <div class="pov-stat-lbl">Badges</div>
            </div>
            ${d.gradeSummary.count ? `
            <div class="pov-stat-card">
              <div class="pov-stat-icon">📊</div>
              <div class="pov-stat-val">${d.gradeSummary.average}%</div>
              <div class="pov-stat-lbl">Avg Grade</div>
            </div>` : ''}
            ${d.quizTotal > 0 ? `
            <div class="pov-stat-card">
              <div class="pov-stat-icon">📝</div>
              <div class="pov-stat-val">${d.quizTotal}</div>
              <div class="pov-stat-lbl">Quizzes Taken</div>
            </div>` : ''}
            ${g.team ? `
            <div class="pov-stat-card" style="border:2px solid ${tStyle.border};background:${tStyle.bg}">
              <div class="pov-stat-icon">${tIcon}</div>
              <div class="pov-stat-val" style="font-size:1.1rem;color:${tStyle.text}">${tLabel}</div>
              <div class="pov-stat-lbl" style="color:${tStyle.text}">Team</div>
            </div>` : ''}
          </div>

          <!-- Leaderboard rank -->
          ${ldr.rank ? `
          <div class="rank-card">
            <div class="rank-medal">${ldr.rank === 1 ? '🥇' : ldr.rank === 2 ? '🥈' : ldr.rank === 3 ? '🥉' : '🏅'}</div>
            <div>
              <div class="rank-val">#${ldr.rank}</div>
              <div class="rank-sub">out of ${ldr.total} students in your class</div>
            </div>
          </div>` : ''}

          <!-- Recent XP -->
          <div class="card">
            <div class="card-header"><h3>⚡ Recent XP Activity</h3></div>
            <div class="card-body">
              ${d.recentXp.length === 0
                ? '<div class="empty-state">No XP activity yet.</div>'
                : `<div class="xp-timeline">${d.recentXp.slice(0, 8).map(x => `
                    <div class="xp-item">
                      <div class="xp-item-icon">${XP_ICONS[x.source_type] || XP_ICONS.default}</div>
                      <div class="xp-item-desc">${esc(x.description || x.source_type)}</div>
                      <div class="xp-item-amount">+${x.xp_amount} XP</div>
                      <div class="xp-item-date">${fmtDate(x.created_at)}</div>
                    </div>`).join('')}
                  </div>`}
            </div>
          </div>
        </div>

        <!-- ── PROGRESS ── -->
        <div class="pov-panel" id="tab-progress">
          <div class="card">
            <div class="card-header"><h3>🌟 XP &amp; Level Progress</h3></div>
            <div class="card-body">
              <div style="margin-bottom:1.25rem">
                <div class="xp-label" style="font-size:0.9rem;margin-bottom:.5rem">
                  <strong>Level ${g.level}</strong>
                  <span>${g.xpProgress.toLocaleString()} / ${g.xpNeeded.toLocaleString()} XP to Level ${g.level + 1}</span>
                </div>
                <div class="xp-track" style="height:16px">
                  <div class="xp-fill" style="width:${xpPct}%"></div>
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:.35rem;font-size:.78rem;color:var(--text-2)">
                  <span>Level ${g.level}</span><span>${xpPct}% complete</span><span>Level ${g.level + 1}</span>
                </div>
              </div>
              <div class="pov-stats" style="margin-top:1rem">
                <div class="pov-stat-card">
                  <div class="pov-stat-icon">⚡</div>
                  <div class="pov-stat-val">${g.totalXp.toLocaleString()}</div>
                  <div class="pov-stat-lbl">Total XP Earned</div>
                </div>
                <div class="pov-stat-card">
                  <div class="pov-stat-icon">🔥</div>
                  <div class="pov-stat-val">${g.streak}</div>
                  <div class="pov-stat-lbl">Current Streak</div>
                </div>
                <div class="pov-stat-card">
                  <div class="pov-stat-icon">📈</div>
                  <div class="pov-stat-val">${g.level}</div>
                  <div class="pov-stat-lbl">Current Level</div>
                </div>
                ${g.team ? `
                <div class="pov-stat-card" style="border:2px solid ${tStyle.border};background:${tStyle.bg}">
                  <div class="pov-stat-icon">${tIcon}</div>
                  <div class="pov-stat-val" style="font-size:1.1rem;color:${tStyle.text}">${tLabel}</div>
                  <div class="pov-stat-lbl">Team</div>
                </div>` : ''}
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><h3>⚡ Full XP History</h3></div>
            <div class="card-body">
              ${d.recentXp.length === 0
                ? '<div class="empty-state">No XP activity yet.</div>'
                : `<div class="xp-timeline">${d.recentXp.map(x => `
                    <div class="xp-item">
                      <div class="xp-item-icon">${XP_ICONS[x.source_type] || XP_ICONS.default}</div>
                      <div class="xp-item-desc">${esc(x.description || x.source_type)}</div>
                      <div class="xp-item-amount">+${x.xp_amount} XP</div>
                      <div class="xp-item-date">${fmtDate(x.created_at)}</div>
                    </div>`).join('')}
                  </div>`}
            </div>
          </div>
        </div>

        <!-- ── ACHIEVEMENTS ── -->
        <div class="pov-panel" id="tab-achievements">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem">
            <div>
              <strong>${d.achievements.unlocked}</strong> of <strong>${d.achievements.total}</strong> badges unlocked
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap" id="achFilter">
              <button class="entry-btn active" data-filter="all">All</button>
              <button class="entry-btn" data-filter="academic">Academic</button>
              <button class="entry-btn" data-filter="streak">Streak</button>
              <button class="entry-btn" data-filter="milestone">Milestone</button>
              <button class="entry-btn" data-filter="social">Social</button>
            </div>
          </div>
          <div class="ach-grid" id="achGrid">
            ${renderAchievements(d.achievements.list, 'all')}
          </div>
        </div>

        <!-- ── GRADES ── -->
        <div class="pov-panel" id="tab-grades">
          ${d.gradeSummary.count ? `
          <div class="grade-summary">
            <div class="grade-summary-card">
              <div class="grade-summary-val">${d.gradeSummary.average}%</div>
              <div class="grade-summary-lbl">Average</div>
            </div>
            <div class="grade-summary-card">
              <div class="grade-summary-val">${d.gradeSummary.highest}%</div>
              <div class="grade-summary-lbl">Highest</div>
            </div>
            <div class="grade-summary-card">
              <div class="grade-summary-val">${d.gradeSummary.lowest}%</div>
              <div class="grade-summary-lbl">Lowest</div>
            </div>
            <div class="grade-summary-card">
              <div class="grade-summary-val">${d.gradeSummary.count}</div>
              <div class="grade-summary-lbl">Total Entries</div>
            </div>
          </div>` : ''}
          <div class="card">
            <div class="card-header"><h3>📊 Grade History</h3></div>
            ${d.grades.length === 0
              ? '<div class="card-body"><div class="empty-state">No grades recorded yet.</div></div>'
              : `<div class="table-wrapper"><table class="data-table">
                  <thead><tr>
                    <th>Assessment</th><th>Score</th><th>Grade</th>
                    <th>Subject</th><th>Date</th>
                  </tr></thead>
                  <tbody>
                    ${d.grades.map(gr => {
                      const g2 = gradeLetter(gr.score_pct);
                      return `<tr>
                        <td><div style="font-weight:600">${esc(gr.assessment_name)}</div>
                          ${gr.course_title ? `<div class="sub-text">${esc(gr.course_title)}</div>` : ''}</td>
                        <td>${gr.score_pct}%
                          ${gr.max_score != 100 ? `<span style="font-size:.75rem;color:var(--text-2)"> (${gr.score}/${gr.max_score})</span>` : ''}</td>
                        <td><span class="grade-chip ${g2.cls}">${g2.letter}</span></td>
                        <td>${esc(gr.subject || '—')}</td>
                        <td>${fmtDate(gr.graded_at)}</td>
                      </tr>`;
                    }).join('')}
                  </tbody>
                </table></div>`}
          </div>
        </div>

        <!-- ── QUIZZES ── -->
        <div class="pov-panel" id="tab-quizzes">
          <div class="card">
            <div class="card-header"><h3>📝 Quiz Results</h3></div>
            ${quizTabContent}
          </div>
        </div>

        <!-- ── LEARNING ── -->
        <div class="pov-panel" id="tab-learning">
          <div class="card">
            <div class="card-header">
              <h3>📚 Course Modules &amp; Learning Progress</h3>
              <span style="font-size:0.78rem;color:var(--text-2)">Shows all your uploaded courses. Enrollment &amp; assignment progress per student.</span>
            </div>
            ${d.learningProgress.length === 0
              ? '<div class="card-body"><div class="empty-state">No courses with modules found. Upload modules to a course first.</div></div>'
              : d.learningProgress.map((lp, i) => `
                  <div style="padding:1rem 1.25rem;${i < d.learningProgress.length - 1 ? 'border-bottom:1px solid var(--border)' : ''}">
                    <!-- Course header row -->
                    <div style="display:flex;align-items:flex-start;gap:0.75rem;flex-wrap:wrap;margin-bottom:0.6rem">
                      <div style="flex:1;min-width:160px">
                        <div style="font-weight:700;font-size:0.9rem;color:var(--text-1)">${esc(lp.title)}</div>
                        <div style="font-size:0.75rem;color:var(--text-2);margin-top:1px">
                          ${lp.subject ? `<span>${esc(lp.subject)}</span> · ` : ''}
                          ${lp.total_modules} module${lp.total_modules !== 1 ? 's' : ''}
                          · ${lp.total_materials} material${lp.total_materials !== 1 ? 's' : ''}
                          ${lp.total_assignments > 0 ? ` · ${lp.submitted}/${lp.total_assignments} assignments done` : ''}
                        </div>
                      </div>
                      <div style="display:flex;align-items:center;gap:0.5rem;flex-shrink:0">
                        <span class="status-chip status-${lp.status}">${lp.status.replace(/_/g,' ')}</span>
                        ${lp.is_enrolled
                          ? '<span class="status-chip" style="background:#d1fae5;color:#065f46">✓ Enrolled</span>'
                          : '<span class="status-chip" style="background:#fef3c7;color:#92400e">Not enrolled</span>'}
                      </div>
                    </div>
                    <!-- Progress bar -->
                    ${lp.total_assignments > 0 ? `
                    <div style="display:flex;align-items:center;gap:0.6rem">
                      <div class="learn-bar-track" style="flex:1">
                        <div class="learn-bar-fill" style="width:${lp.progress_pct}%"></div>
                      </div>
                      <span class="learn-pct">${lp.progress_pct}%</span>
                    </div>` : ''}
                    <!-- Module list -->
                    ${lp.modules && lp.modules.length > 0 ? `
                    <div style="margin-top:0.5rem;display:flex;flex-wrap:wrap;gap:0.4rem">
                      ${lp.modules.map(m => `
                        <span style="font-size:0.72rem;padding:2px 8px;background:#f1f5f9;border:1px solid var(--border);
                                     border-radius:20px;color:var(--text-2)">
                          📁 ${esc(m.title)}
                          ${m.total_materials > 0 ? `<span style="color:var(--text-3)"> (${m.total_materials})</span>` : ''}
                        </span>`).join('')}
                    </div>` : ''}
                  </div>`).join('')}
          </div>
        </div>

        <!-- ── PET / EGG ── -->
        <div class="pov-panel" id="tab-pet">
          <div class="card">
            <div class="card-header"><h3>🥚 Pet Companion</h3></div>
            <div class="card-body">
              <div class="egg-showcase">
                <div class="egg-sprite">${EGG_SPRITES[Math.min(g.eggStage, EGG_SPRITES.length - 1)]}</div>
                <div class="egg-name">${g.petName ? esc(g.petName) : EGG_NAMES[Math.min(g.eggStage, EGG_NAMES.length - 1)]}</div>
                <div class="egg-sub">
                  Stage ${g.eggStage} · Grows as ${esc(s.first_name)} levels up!
                  ${g.eggType ? ` &nbsp;${teamIcon(g.eggType)} ${teamLabel(g.eggType)} Element` : ''}
                </div>
                <div style="margin-top:.75rem;display:flex;flex-wrap:wrap;gap:.5rem;justify-content:center">
                  ${g.team
                    ? `<span style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.4rem 1rem;
                                   border-radius:var(--radius);border:2px solid ${tStyle.border};
                                   background:${tStyle.bg};color:${tStyle.text};font-weight:700;font-size:0.9rem">
                         ${tIcon} Team ${tLabel}
                       </span>`
                    : '<span class="muted">Not yet joined a team</span>'}
                </div>
              </div>
            </div>
          </div>
        </div>

      `;

      bindTabs();
      bindAchFilter(d.achievements.list);
    }

    /* ── Render achievements HTML ── */
    function renderAchievements(list, filter) {
      const filtered = filter === 'all' ? list : list.filter(a => a.category === filter);
      if (!filtered.length) return '<div class="empty-state" style="grid-column:1/-1">No achievements in this category yet.</div>';
      return filtered.map(a => {
        const pct = a.target_value > 0 ? Math.min(100, Math.round((a.progress / a.target_value) * 100)) : 0;
        return `<div class="ach-card${a.is_unlocked ? '' : ' locked'}">
          <div class="ach-icon">${a.icon || '🏆'}</div>
          <div style="flex:1;min-width:0">
            <div class="ach-title">${esc(a.title)}</div>
            <div class="ach-desc">${esc(a.description)}</div>
            ${!a.is_unlocked && a.target_value > 0 ? `
            <div class="ach-prog">
              <div class="ach-prog-bar"><div class="ach-prog-fill" style="width:${pct}%"></div></div>
              <div style="font-size:.7rem;color:var(--text-2);margin-top:2px">${a.progress} / ${a.target_value}</div>
            </div>` : ''}
          </div>
          <span class="ach-badge${a.is_unlocked ? '' : ' locked-badge'}">${a.is_unlocked ? '✓ Earned' : `${pct}%`}</span>
        </div>`;
      }).join('');
    }

    /* ── Tab switching ── */
    function bindTabs() {
      document.getElementById('povTabs').addEventListener('click', e => {
        const btn = e.target.closest('.pov-tab');
        if (!btn) return;
        document.querySelectorAll('.pov-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.pov-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
      });
    }

    /* ── Achievement filter ── */
    function bindAchFilter(list) {
      const bar  = document.getElementById('achFilter');
      const grid = document.getElementById('achGrid');
      if (!bar || !grid) return;
      bar.addEventListener('click', e => {
        const btn = e.target.closest('.entry-btn');
        if (!btn) return;
        bar.querySelectorAll('.entry-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        grid.innerHTML = renderAchievements(list, btn.dataset.filter);
      });
    }

  })();
  </script>
</body>
</html>
