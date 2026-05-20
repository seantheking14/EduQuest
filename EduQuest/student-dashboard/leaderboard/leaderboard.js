/**
 * leaderboard.js – Team Leaderboard Page
 */
(function () {
  'use strict';

  const TEAM_META = {
    fire:  { icon: '🔥', name: 'Fire Team',  element: 'fire'  },
    water: { icon: '💧', name: 'Water Team', element: 'water' },
    grass: { icon: '🌿', name: 'Grass Team', element: 'grass' },
  };

  const PODIUM_MEDALS = ['🥇', '🥈', '🥉'];

  // ── Auth check ─────────────────────────────────────────────
  const token = localStorage.getItem('eq_token');
  const user  = JSON.parse(localStorage.getItem('eduquest_user') || '{}');

  if (!token || !user.email || user.role !== 'student') {
    window.location.href = '../../auth/login/login.html?role=student';
    return;
  }

  // ── Nav stats ──────────────────────────────────────────────
  function loadNavStats() {
    const progress = JSON.parse(localStorage.getItem('student_progress') || '{}');
    const xpEl     = document.getElementById('navXp');
    const streakEl = document.getElementById('navStreak');
    if (xpEl && progress.xp)     xpEl.textContent     = progress.xp.toLocaleString() + ' XP';
    if (streakEl && progress.streak) streakEl.textContent = progress.streak + ' days';
  }

  // ── Current user's team ────────────────────────────────────
  let myTeam = null;
  (function loadMyTeam() {
    const gam = JSON.parse(localStorage.getItem('student_progress') || '{}');
    myTeam = gam.team || null;
  })();

  // ── Load leaderboard ──────────────────────────────────────
  async function loadLeaderboard() {
    try {
      const res  = await fetch('../../EDUQUEST/api/gamification/leaderboard.php', {
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + token,
        },
      });
      const json = await res.json();

      if (!json.success) {
        showDisabled(json.message || 'Could not load leaderboard.');
        return;
      }

      const d = json.data;

      if (!d.enabled) {
        showDisabled(d.message || 'Leaderboard is not available.');
        return;
      }

      // Detect current user's team from entries if not in localStorage
      if (!myTeam && d.entries) {
        const me = d.entries.find(e => e.isCurrentUser);
        if (me) myTeam = me.team;
      }

      document.getElementById('leaderboardContent').style.display = '';
      document.getElementById('disabledState').style.display      = 'none';

      renderTeamPodium(d.teamStats);
      renderTeamCards(d.teamStats);
      renderRankings(d.entries);

    } catch (err) {
      console.error('Leaderboard error:', err);
      showDisabled('Failed to load leaderboard. Please refresh.');
    }
  }

  function showDisabled(msg) {
    document.getElementById('disabledState').style.display      = '';
    document.getElementById('leaderboardContent').style.display = 'none';
    const p = document.querySelector('.disabled-state p');
    if (p && msg) p.textContent = msg;
  }

  // ── Team podium (ranked) ───────────────────────────────────
  function renderTeamPodium(teamStats) {
    const el = document.getElementById('teamPodium');

    // Ensure all three teams are present
    const all = ['fire', 'water', 'grass'].map(t => {
      const found = teamStats.find(s => s.team === t);
      return {
        team:        t,
        team_xp:     found ? parseInt(found.team_xp) : 0,
        avg_xp:      found ? parseInt(found.avg_xp)  : 0,
        member_count: found ? parseInt(found.member_count) : 0,
      };
    });

    // Sort by team XP descending
    all.sort((a, b) => b.team_xp - a.team_xp);

    // Podium layout: [2nd, 1st, 3rd]
    const ordered = [all[1], all[0], all[2]];

    el.innerHTML = ordered.map((t, i) => {
      const actualRank = i === 1 ? 1 : i === 0 ? 2 : 3;
      const meta = TEAM_META[t.team];
      return `
        <div class="podium-slot rank-${actualRank} team-${t.team}">
          <div class="podium-team-icon">${meta.icon}</div>
          <div class="podium-team-name">${meta.name}</div>
          <div class="podium-team-xp">${t.team_xp.toLocaleString()} XP</div>
          <div class="podium-bar">${PODIUM_MEDALS[actualRank - 1]}</div>
        </div>`;
    }).join('');
  }

  // ── Team detail cards ──────────────────────────────────────
  function renderTeamCards(teamStats) {
    const el = document.getElementById('teamCards');

    // Ensure all three teams
    const all = ['fire', 'water', 'grass'].map(t => {
      const found = teamStats.find(s => s.team === t);
      return {
        team:        t,
        team_xp:     found ? parseInt(found.team_xp) : 0,
        avg_xp:      found ? parseInt(found.avg_xp)  : 0,
        member_count: found ? parseInt(found.member_count) : 0,
      };
    });

    el.innerHTML = all.map(t => {
      const meta  = TEAM_META[t.team];
      const isMy  = myTeam === t.team;
      return `
        <div class="team-card team-${t.team}${isMy ? ' is-my-team' : ''}">
          ${isMy ? '<span class="my-team-badge">Your Team</span>' : ''}
          <div class="team-card-icon">${meta.icon}</div>
          <div class="team-card-name">${meta.name}</div>
          <div class="team-card-stats">
            <div class="team-stat-row">
              <span class="team-stat-label">Total XP</span>
              <span class="team-stat-val">${t.team_xp.toLocaleString()}</span>
            </div>
            <div class="team-stat-row">
              <span class="team-stat-label">Avg XP</span>
              <span class="team-stat-val">${t.avg_xp.toLocaleString()}</span>
            </div>
            <div class="team-stat-row">
              <span class="team-stat-label">Members</span>
              <span class="team-stat-val">${t.member_count}</span>
            </div>
          </div>
        </div>`;
    }).join('');
  }

  // ── Individual rankings ────────────────────────────────────
  function renderRankings(entries) {
    const el = document.getElementById('rankingsList');

    if (!entries || !entries.length) {
      el.innerHTML = `<div class="empty-rankings"><div class="empty-icon">🏅</div><p>No adventurers ranked yet. Start earning XP!</p></div>`;
      return;
    }

    el.innerHTML = entries.map(e => {
      const meta  = TEAM_META[e.team] || { icon: '❓', name: 'No Team' };
      const teamClass = e.team ? 'team-' + e.team : 'team-none';
      const isMe  = e.isCurrentUser;
      const medal = e.rank <= 3 ? PODIUM_MEDALS[e.rank - 1] : '';
      const initial = (e.firstName || '?').charAt(0).toUpperCase();

      return `
        <div class="rank-row ${isMe ? 'is-me' : ''} ${e.rank <= 3 ? 'rank-' + e.rank : ''}">
          <div class="rank-number">
            ${medal ? '<span class="rank-medal">' + medal + '</span>' : e.rank}
          </div>
          <div class="rank-avatar ${teamClass}">${initial}</div>
          <div class="rank-info">
            <div class="rank-name">${esc(e.firstName)} ${esc(e.lastName)}${isMe ? ' (You)' : ''}</div>
            <div class="rank-meta">
              ${e.team ? '<span class="rank-team-dot ' + teamClass + '"></span> ' + meta.name : '<span style="color:var(--gray-400)">No team</span>'}
              &nbsp;·&nbsp;🔥 ${e.streakDays}d streak
            </div>
          </div>
          <span class="rank-xp">⚡ ${e.totalXp.toLocaleString()} XP</span>
          <span class="rank-level">Lv ${e.level}</span>
        </div>`;
    }).join('');
  }

  // ── Helpers ────────────────────────────────────────────────
  function esc(str) {
    return str ? String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') : '';
  }

  // ── Logout ─────────────────────────────────────────────────
  window.handleLogout = async function () {
    await fetch('../../EDUQUEST/api/auth/logout.php', {
      method: 'POST',
      headers: { Authorization: 'Bearer ' + token },
      credentials: 'include',
    }).catch(() => {});
    ['eq_token', 'eq_teacher', 'eq_student', 'eduquest_user', 'student_progress'].forEach(k =>
      localStorage.removeItem(k)
    );
    window.location.href = '../../auth/login/login.html?role=student';
  };

  // ── Init ───────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    loadNavStats();
    loadLeaderboard();

    // Mobile nav toggle
    const navToggle = document.getElementById('navToggle');
    const navMenu   = document.getElementById('navMenu');
    if (navToggle && navMenu) {
      navToggle.addEventListener('click', () => {
        navMenu.classList.toggle('show');
        navToggle.classList.toggle('active');
      });
    }

    // Profile dropdown
    const profileMenu = document.getElementById('profileMenu');
    if (profileMenu) {
      profileMenu.addEventListener('click', (e) => {
        profileMenu.classList.toggle('active');
        e.stopPropagation();
      });
      document.addEventListener('click', () => profileMenu.classList.remove('active'));
    }
  });
})();
