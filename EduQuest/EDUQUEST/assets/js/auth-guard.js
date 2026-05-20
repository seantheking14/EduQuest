/**
 * auth-guard.js
 * Ensures only authenticated teachers can view protected pages.
 * Runs before page-specific scripts.
 *
 * Unified auth keys:
 *   eq_token      – Bearer token (written by any login entry point)
 *   eq_teacher    – Teacher profile with first_name / last_name
 *   eduquest_user – Generic user object (firstName / lastName, camelCase)
 */
(function () {
  'use strict';

  const token = localStorage.getItem('eq_token');

  // Support both eq_teacher (canonical) and eduquest_user (fallback)
  let teacher = JSON.parse(localStorage.getItem('eq_teacher') || 'null');

  if (!teacher) {
    // Build a compatible teacher object from the shared eduquest_user key
    const u = JSON.parse(localStorage.getItem('eduquest_user') || 'null');
    if (u && (u.role === 'teacher' || u.role === 'admin')) {
      teacher = {
        id:         u.id,
        first_name: u.firstName || u.first_name || '',
        last_name:  u.lastName  || u.last_name  || '',
        email:      u.email,
      };
    }
  }

  if (!token || !teacher) {
    window.location.href = '../../auth/login/login.html?role=teacher';
    return;
  }

  // ── Whitelist check ───────────────────────────────────────────────────────
  // Runs asynchronously on every page load. If the admin has revoked this
  // teacher's whitelist entry since they last logged in, they are redirected
  // to the login page immediately.
  fetch('../api/auth/whitelist-check.php', {
    method: 'GET',
    headers: { 'Authorization': 'Bearer ' + token },
    credentials: 'include',
  })
    .then(function (r) { return r.json(); })
    .then(function (result) {
      if (!result.success) {
        ['eq_token', 'eq_teacher', 'eq_student', 'eduquest_user', 'student_progress'].forEach(function (k) {
          localStorage.removeItem(k);
        });
        window.location.href = '../../auth/login/login.html?role=teacher&reason=access_revoked';
      }
    })
    .catch(function () {
      // Network error – fail open so connectivity issues don't lock teachers out
    });

  // Populate teacher name in sidebar
  document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('teacherName');
    if (el) el.textContent = teacher.first_name + ' ' + teacher.last_name;

    // Populate avatar initials
    const avatarEl = document.getElementById('teacherAvatarInitials');
    if (avatarEl) {
      const f = teacher.first_name || '';
      const l = teacher.last_name  || '';
      avatarEl.textContent = ((f[0] || '') + (l[0] || '')).toUpperCase() || 'T';
    }

    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', async () => {
        await fetch('../api/auth/logout.php', {
          method: 'POST',
          headers: { Authorization: 'Bearer ' + token },
          credentials: 'include',
        }).catch(() => {});
        // Clear all unified auth keys
        ['eq_token', 'eq_teacher', 'eq_student', 'eduquest_user', 'student_progress'].forEach(k =>
          localStorage.removeItem(k)
        );
        window.location.href = '../../auth/login/login.html?role=teacher';
      });
    }

    // ── Sidebar: inject section labels + reorder nav items ─────────────────
    setupSidebarNav();

    // ── Mobile Navigation ──────────────────────────────────────────────────
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
      // Inject mobile top bar
      const topbar = document.createElement('div');
      topbar.className = 'mobile-topbar';
      topbar.innerHTML =
        '<button class="hamburger" id="sidebarToggle" aria-label="Open menu">' +
          '<span></span><span></span><span></span>' +
        '</button>' +
        '<div class="topbar-brand"><span>&#127891;</span> EduQuest</div>' +
        '<div style="width:34px"></div>';
      document.body.insertBefore(topbar, document.body.firstChild);

      // Inject overlay
      const overlay = document.createElement('div');
      overlay.className = 'sidebar-overlay';
      overlay.id = 'sidebarOverlay';
      document.body.appendChild(overlay);

      function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
      }
      function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
      }

      document.getElementById('sidebarToggle').addEventListener('click', openSidebar);
      overlay.addEventListener('click', closeSidebar);

      // Close sidebar on nav link click (mobile)
      sidebar.querySelectorAll('.sidebar-nav a').forEach(function(link) {
        link.addEventListener('click', function() {
          if (window.innerWidth <= 768) closeSidebar();
        });
      });
    }
  });

  // Expose token globally for other scripts
  window.EQ = window.EQ || {};
  window.EQ.token   = token;
  window.EQ.teacher = teacher;
  window.EQ.authHeaders = () => ({
    'Content-Type':  'application/json',
    'Authorization': 'Bearer ' + token,
  });
  window.EQ.authFetchHeaders = () => ({
    'Authorization': 'Bearer ' + token,
  });

  // ── Sidebar: section labels + nav reorder ────────────────────────────────
  function setupSidebarNav() {
    const nav = document.querySelector('.sidebar-nav');
    if (!nav) return;

    const sections = [
      { label: 'Overview', hrefs: ['dashboard.php'] },
      { label: 'Students', hrefs: ['students.php', 'student-form.php', 'student-import.php', 'student-view.php', 'student-pov.php'] },
      { label: 'Academic', hrefs: ['courses.php', 'course-view.php', 'quiz-builder.php', 'activity-builder.php', 'grade-analytics.php'] },
      { label: 'Insights', hrefs: ['analytics.php', 'behavioral-logs.php'] },
      { label: 'Settings', hrefs: ['gamification-settings.php', 'profile.php'] },
    ];

    // Remove any existing section labels (e.g. from dashboard.php's PHP)
    nav.querySelectorAll('.nav-section-label').forEach(function(el) { el.remove(); });

    // Collect remaining link items
    const allLis = Array.from(nav.querySelectorAll('li'));

    // Build ordered list with injected section labels
    const ordered = [];
    sections.forEach(function(sec) {
      const sectionLis = [];
      sec.hrefs.forEach(function(href) {
        const li = allLis.find(function(li) {
          const a = li.querySelector('a');
          if (!a) return false;
          const h = a.getAttribute('href') || '';
          return h === href || h.endsWith('/' + href);
        });
        if (li && !sectionLis.includes(li)) sectionLis.push(li);
      });
      if (sectionLis.length > 0) {
        const labelLi = document.createElement('li');
        labelLi.className = 'nav-section-label';
        labelLi.textContent = sec.label;
        ordered.push(labelLi);
        sectionLis.forEach(function(li) { ordered.push(li); });
      }
    });

    // Append any unmatched items at the end (safety net)
    allLis.forEach(function(li) { if (!ordered.includes(li)) ordered.push(li); });

    // Re-render the nav
    while (nav.firstChild) nav.removeChild(nav.firstChild);
    ordered.forEach(function(li) { nav.appendChild(li); });
  }

})();
