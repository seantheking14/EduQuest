/**
 * gamification.js
 * Student gamification page logic: profile, egg evolution, team selection,
 * achievements preview, leaderboard, and XP activity.
 */
(function () {
    'use strict';

    const API_BASE = '../../EDUQUEST/api/gamification';
    const token = localStorage.getItem('eq_token');
    const user = JSON.parse(localStorage.getItem('eduquest_user') || 'null');

    if (!token || !user) {
        window.location.href = '../../auth/login/login.html';
        return;
    }

    const authHeaders = () => ({
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token,
    });

    // ── State ──
    let profileData = null;
    let settings = {};

    // ── Init ──
    document.addEventListener('DOMContentLoaded', async () => {
        setPlayerName();
        preRenderTeamFromCache();
        await loadProfile();
        await loadLeaderboard();
        setupTeamListeners();
    });

    // ── Pre-render team section from localStorage before API returns ──
    function preRenderTeamFromCache() {
        const cached = JSON.parse(localStorage.getItem('student_progress') || 'null');
        if (!cached || !cached.team) return;
        renderTeamCard(cached.team);
    }

    function renderTeamCard(team) {
        const section = document.getElementById('teamSelectionSection');
        const grid    = document.getElementById('teamGrid');
        const heading = section ? section.querySelector('.section-heading') : null;
        const desc    = document.getElementById('teamDescription');
        if (!section || !grid) return;

        const teamIcons  = { fire: '🔥', water: '💧', grass: '🌿' };
        const teamNames  = { fire: 'Team Fire', water: 'Team Water', grass: 'Team Grass' };
        const teamMottos = { fire: 'Courage & Determination', water: 'Wisdom & Adaptability', grass: 'Growth & Resilience' };

        if (heading) heading.textContent = '⚔️ Your Team';
        if (desc)    desc.textContent    = "You're a proud member of your team!";

        grid.innerHTML = `
            <div class="team-option selected locked" data-team="${team}" style="pointer-events:none;cursor:default;">
                <div class="team-icon-large">${teamIcons[team] || '❓'}</div>
                <h4 class="team-name">${teamNames[team] || team}</h4>
                <p class="team-motto">${teamMottos[team] || ''}</p>
                <div class="team-color-bar ${team}-bar"></div>
            </div>`;
    }

    function setPlayerName() {
        const name = user.firstName || user.first_name || 'Adventurer';
        const el = document.getElementById('playerName');
        if (el) el.textContent = name;
    }

    // ── Load Profile ──
    async function loadProfile() {
        try {
            const res = await fetch(API_BASE + '/profile.php', { headers: authHeaders() });
            const json = await res.json();

            if (!json.success) {
                console.error('Failed to load profile:', json.message);
                return;
            }

            profileData = json.data;
            settings = json.data.settings || {};

            applyAnimationLevel(settings.animationLevel);

            try { renderOverview(json.data.profile); }         catch (e) { console.error('renderOverview:', e); }
            try { renderDailyXp(json.data.profile, settings); } catch (e) { console.error('renderDailyXp:', e); }
            try { renderEggEvolution(json.data.eggEvolution, json.data.profile, settings); } catch (e) { console.error('renderEggEvolution:', e); }
            try { renderTeamSelection(json.data.profile, settings); } catch (e) { console.error('renderTeamSelection:', e); }
            try { renderAchievements(json.data.achievements, settings); } catch (e) {
                console.error('renderAchievements:', e);
                const g = document.getElementById('achievementsGrid');
                if (g) g.innerHTML = '<div class="empty-state"><p>Could not load achievements.</p></div>';
            }
            try { renderXpActivity(json.data.recentXp); } catch (e) {
                console.error('renderXpActivity:', e);
                const l = document.getElementById('xpActivityList');
                if (l) l.innerHTML = '<div class="empty-state"><p>Could not load XP activity.</p></div>';
            }

            // Update nav stats
            updateNavStats(json.data.profile);

            // Update localStorage for other pages
            localStorage.setItem('student_progress', JSON.stringify({
                xp: json.data.profile.totalXp,
                level: json.data.profile.level,
                streak: json.data.profile.streakDays,
                team: json.data.profile.team,
                eggStage: json.data.profile.eggStage,
            }));

        } catch (err) {
            console.error('Error loading profile:', err);
        }
    }

    // ── Animation Level ──
    function applyAnimationLevel(level) {
        document.body.classList.remove('anim-none', 'anim-reduced');
        if (level === 'none') document.body.classList.add('anim-none');
        else if (level === 'reduced') document.body.classList.add('anim-reduced');
    }

    // ── Render Overview ──
    function renderOverview(profile) {
        document.getElementById('levelNumber').textContent = profile.level;
        document.getElementById('totalXpStat').textContent = formatXP(profile.totalXp);
        document.getElementById('streakStat').textContent = profile.streakDays;
        document.getElementById('achievementsStat').textContent =
            profileData.achievements ? profileData.achievements.unlocked : 0;
        document.getElementById('rewardsStat').textContent = profileData.rewardsCount || 0;

        // XP bar
        const pct = profile.xpNeeded > 0
            ? Math.round((profile.xpProgress / profile.xpNeeded) * 100)
            : 100;
        document.getElementById('xpFill').style.width = pct + '%';
        document.getElementById('xpLabel').textContent =
            formatXP(profile.xpProgress) + ' / ' + formatXP(profile.xpNeeded) + ' XP to Level ' + (profile.level + 1);

        // Team badge
        if (profile.team) {
            const teamIcons = { fire: '🔥', water: '💧', grass: '🌿' };
            const teamNames = { fire: 'Team Fire', water: 'Team Water', grass: 'Team Grass' };
            document.getElementById('teamBadgeDisplay').innerHTML =
                `<span class="team-badge ${profile.team}">${teamIcons[profile.team]} ${teamNames[profile.team]}</span>`;
        }
    }

    // ── Render Daily XP ──
    function renderDailyXp(profile, settings) {
        const maxDaily = settings.maxDailyXp || 500;
        const earned = profile.dailyXpEarned || 0;
        const pct = Math.min(100, Math.round((earned / maxDaily) * 100));

        document.getElementById('dailyXpCap').textContent = formatXP(earned) + ' / ' + formatXP(maxDaily) + ' XP today';
        document.getElementById('dailyXpFill').style.width = pct + '%';
    }

    // ── Render Egg Evolution ──
    function renderEggEvolution(eggData, profile, settings) {
        if (!settings.eggEvolutionEnabled) {
            document.getElementById('eggEvolutionSection').style.display = 'none';
            return;
        }

        // Level thresholds for each stage
        const stageLevels = { 1: 1, 2: 3, 3: 7, 4: 12, 5: 20 };

        const stage = eggData.stage;
        const level = profile.level || 1;
        const team  = profile.team || 'fire';
        const customPetName = (profile.petName || '').trim();

        // ── Pet sprite — file-based SVGs ──
        const PET_NAMES_MAP = {
            fire:  ['Fire Egg', 'Iggy',    'Blazeback', 'Thornflare'],
            water: ['Water Egg','Bubbles', 'Shellby',   'Tidalback'],
            grass: ['Grass Egg','Sprout',  'Twigster',  'Vinespark'],
        };
        const STAGE_FILE_NAMES = ['egg', 'baby', 'young', 'adult'];
        const PETS_BASE = '../../EDUQUEST/assets/pets/';

        // Map legacy 5-stage to display 4-stage
        function lgToDisp(s) { if (s <= 2) return 0; if (s === 3) return 1; if (s === 4) return 2; return 3; }
        const dispStage   = lgToDisp(stage);
        const dispTeam    = (team in PET_NAMES_MAP) ? team : 'fire';
        const petNameHere = customPetName || PET_NAMES_MAP[dispTeam][dispStage];
        const svgSrc      = PETS_BASE + dispTeam + '/' + STAGE_FILE_NAMES[dispStage] + '.svg';

        const spriteEl = document.getElementById('eggSprite');
        spriteEl.innerHTML = '<img class="pet-img" src="' + svgSrc + '" alt="' + petNameHere + '" style="width:100%;height:100%;object-fit:contain;">';

        // Stage name & description
        const sName = petNameHere;
        const sDesc = eggData.stageName || '';
        document.getElementById('eggStageName').textContent = sName;
        document.getElementById('eggStageDesc').textContent = sDesc || eggData.stageName;

        // ── Team-coloured glow ──
        const glowEl = document.getElementById('eggGlow');
        const TEAM_GLOW = { fire: 'rgba(255,100,0,0.55)', water: 'rgba(26,188,156,0.5)', grass: 'rgba(39,174,96,0.5)' };
        if (glowEl) {
            const glowColor = TEAM_GLOW[dispTeam] || TEAM_GLOW.fire;
            glowEl.style.background = 'radial-gradient(ellipse, ' + glowColor + ', transparent)';
        }

        // ── Crack overlay for stages 1-2 (shows hatching progress) ──
        const cracksEl = document.getElementById('eggCracks');
        if (cracksEl) {
            cracksEl.innerHTML = '';
            if (stage === 1 && level >= 2) {
                cracksEl.innerHTML = '<span class="crack crack-small">╲</span>';
            } else if (stage === 2) {
                cracksEl.innerHTML = '<span class="crack crack-med">╲</span><span class="crack crack-med crack-r">╱</span>';
            }
        }

        // ── Wobble animation intensity based on proximity to next stage ──
        const container = document.getElementById('eggContainer');
        container.classList.remove('egg-wobble-light', 'egg-wobble-heavy', 'egg-hatch-glow');
        if (stage < 5) {
            const nextLv   = stageLevels[stage + 1];
            const startLv  = stageLevels[stage];
            const pctToNext = Math.min(1, (level - startLv) / Math.max(1, nextLv - startLv));
            if (pctToNext >= 0.75) {
                container.classList.add('egg-wobble-heavy');
            } else if (pctToNext >= 0.4) {
                container.classList.add('egg-wobble-light');
            }
        } else {
            container.classList.add('egg-hatch-glow');
        }

        // ── Level progress toward next evolution ──
        const progressEl  = document.getElementById('eggLevelProgress');
        const fillEl      = document.getElementById('eggProgressFill');
        const labelEl     = document.getElementById('eggProgressLabel');
        if (progressEl && fillEl && labelEl) {
            // Tint progress bar to team colour
            const TEAM_COLORS = { fire: '#c0392b', water: '#1abc9c', grass: '#27ae60' };
            fillEl.style.background = TEAM_COLORS[dispTeam] || TEAM_COLORS.fire;
            if (stage < 5) {
                progressEl.style.display = 'block';
                const nextLv  = stageLevels[stage + 1];
                const startLv = stageLevels[stage];
                const pct = Math.min(100, Math.round(((level - startLv) / Math.max(1, nextLv - startLv)) * 100));
                fillEl.style.width = pct + '%';
                labelEl.textContent = 'Level ' + level + ' → Next evolution at Level ' + nextLv + ' (' + pct + '%)';
            } else {
                progressEl.style.display = 'block';
                fillEl.style.width = '100%';
                labelEl.textContent = '🎉 Fully evolved! Max stage reached.';
            }
        }

        // ── Update timeline with team-aware SVGs ──
        const steps = document.querySelectorAll('.evo-step');
        steps.forEach(step => {
            const s = parseInt(step.dataset.stage);
            step.classList.remove('active', 'completed');
            if (s === stage) step.classList.add('active');
            else if (s < stage) step.classList.add('completed');

            // Replace emoji icons with mini file-based SVG images
            const iconEl = step.querySelector('.evo-icon');
            if (iconEl) {
                const ds = lgToDisp(s);
                const miniSrc = PETS_BASE + dispTeam + '/' + STAGE_FILE_NAMES[ds] + '.svg';
                iconEl.innerHTML = '<img src="' + miniSrc + '" alt="" style="width:32px;height:32px;object-fit:contain;">';
            }
            // Update label to team-specific pet name
            const labelStep = step.querySelector('.evo-label');
            if (labelStep) {
                labelStep.textContent = customPetName || PET_NAMES_MAP[dispTeam][lgToDisp(s)];
            }
        });

        // Also color the connectors between completed stages
        const connectors = document.querySelectorAll('.evo-connector');
        connectors.forEach((conn, idx) => {
            conn.classList.remove('filled');
            if (idx + 1 < stage) conn.classList.add('filled');
            // Tint filled connectors to team colour
            if (idx + 1 < stage) {
                conn.style.background = TEAM_COLORS[dispTeam] || TEAM_COLORS.fire;
            } else {
                conn.style.background = '';
            }
        });

        // Next hint
        const hintEl = document.getElementById('evoNextHint');
        if (eggData.nextStage && eggData.levelNeeded) {
            const nextDispStage = lgToDisp(stage + 1);
            const nextName = customPetName || PET_NAMES_MAP[dispTeam][nextDispStage] || eggData.nextStage;
            hintEl.textContent = `Reach Level ${eggData.levelNeeded} to evolve into ${nextName}!`;
        } else {
            hintEl.textContent = '🎉 Your companion has reached its final form!';
        }
    }

    // ── Render Team Selection ──
    function renderTeamSelection(profile, settings) {
        const section = document.getElementById('teamSelectionSection');
        if (!settings.teamsEnabled) {
            section.style.display = 'none';
            return;
        }

        const resolvedTeam = profile.team || (JSON.parse(localStorage.getItem('student_progress') || 'null') || {}).team;
        if (resolvedTeam) {
            renderTeamCard(resolvedTeam);
        } else {
            // No team chosen yet — show all 3 selectable options
            const grid = document.getElementById('teamGrid');
            grid.innerHTML = `
                <button class="team-option" data-team="fire" id="teamFire" data-track="Select Fire Team">
                    <div class="team-icon-large">🔥</div>
                    <h4 class="team-name">Team Fire</h4>
                    <p class="team-motto">Courage &amp; Determination</p>
                    <div class="team-color-bar fire-bar"></div>
                </button>
                <button class="team-option" data-team="water" id="teamWater" data-track="Select Water Team">
                    <div class="team-icon-large">💧</div>
                    <h4 class="team-name">Team Water</h4>
                    <p class="team-motto">Wisdom &amp; Adaptability</p>
                    <div class="team-color-bar water-bar"></div>
                </button>
                <button class="team-option" data-team="grass" id="teamGrass" data-track="Select Grass Team">
                    <div class="team-icon-large">🌿</div>
                    <h4 class="team-name">Team Grass</h4>
                    <p class="team-motto">Growth &amp; Resilience</p>
                    <div class="team-color-bar grass-bar"></div>
                </button>`;
        }
    }

    function setupTeamListeners() {
        // Team selection is only available during onboarding.
        // After onboarding, the team grid is read-only.
    }

    // ── Render Achievements Preview ──
    function renderAchievements(achData, settings) {
        if (settings.achievementsEnabled === false) {
            document.getElementById('achievementsSection').style.display = 'none';
            return;
        }

        const grid = document.getElementById('achievementsGrid');
        const recent = (achData && achData.recent) ? achData.recent : [];

        if (recent.length === 0) {
            grid.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">🏆</div>
                    <p>Complete quests to earn your first achievement!</p>
                </div>`;
            return;
        }

        grid.innerHTML = recent.map(ach => {
            const isUnlocked = parseInt(ach.is_unlocked);
            const progress = parseInt(ach.progress);
            const target = parseInt(ach.target_value);
            const pct = target > 0 ? Math.min(100, Math.round(progress / target * 100)) : 0;

            return `
                <div class="ach-preview-card ${isUnlocked ? 'unlocked' : 'locked'}">
                    <div class="ach-preview-icon">${ach.icon}</div>
                    <div class="ach-preview-info">
                        <h4>${ach.title}</h4>
                        <p>${isUnlocked ? 'Unlocked!' : ach.description}</p>
                        ${!isUnlocked ? `
                            <div class="ach-preview-progress">
                                <div class="ach-preview-progress-fill" style="width: ${pct}%"></div>
                            </div>
                        ` : ''}
                    </div>
                </div>`;
        }).join('');
    }

    // ── Load Leaderboard ──
    async function loadLeaderboard() {
        try {
            const res = await fetch(API_BASE + '/leaderboard.php', { headers: authHeaders() });
            const json = await res.json();

            if (!json.success || !json.data.enabled) {
                document.getElementById('leaderboardSection').style.display = 'none';
                renderTeamProgressMeter([], false, 'Team progress is currently hidden by class settings.');
                return;
            }

            document.getElementById('leaderboardSection').style.display = 'block';

            const data = json.data;
            renderTeamProgressMeter(data.teamStats || [], true);
            document.getElementById('leaderboardModeBadge').textContent =
                data.mode === 'top_only' ? 'Top performers' : 'Full rankings';

            // Team stats
            if (data.teamStats && data.teamStats.length > 0) {
                document.getElementById('teamLeaderboard').style.display = 'grid';
                data.teamStats.forEach(ts => {
                    const el = document.getElementById(`team${capitalize(ts.team)}Xp`);
                    if (el) el.textContent = formatXP(parseInt(ts.team_xp)) + ' XP';
                });
            }

            // Individual entries
            const list = document.getElementById('leaderboardList');
            if (data.entries.length === 0) {
                list.innerHTML = '<div class="empty-state"><p>No leaderboard data yet.</p></div>';
                return;
            }

            const rankIcons = { 1: '🥇', 2: '🥈', 3: '🥉' };
            const teamIcons = { fire: '🔥', water: '💧', grass: '🌿' };

            list.innerHTML = data.entries.map(e => `
                <div class="lb-entry ${e.isCurrentUser ? 'current-user' : ''}">
                    <span class="lb-rank">${rankIcons[e.rank] || '#' + e.rank}</span>
                    <span class="lb-name">${e.isCurrentUser ? 'You!' : e.firstName + ' ' + e.lastName.charAt(0) + '.'}</span>
                    ${e.team ? `<span class="lb-team-icon">${teamIcons[e.team] || ''}</span>` : ''}
                    <span class="lb-level">Lv ${e.level}</span>
                    <span class="lb-xp">${formatXP(e.totalXp)} XP</span>
                </div>
            `).join('');

        } catch (err) {
            console.error('Error loading leaderboard:', err);
            document.getElementById('leaderboardSection').style.display = 'none';
            renderTeamProgressMeter([], false, 'Could not load team progress right now.');
        }
    }

    function renderTeamProgressMeter(teamStats, enabled, disabledMessage) {
        const fillEls = {
            fire: document.getElementById('tpFillFire'),
            water: document.getElementById('tpFillWater'),
            grass: document.getElementById('tpFillGrass'),
        };
        const valueEls = {
            fire: document.getElementById('tpValueFire'),
            water: document.getElementById('tpValueWater'),
            grass: document.getElementById('tpValueGrass'),
        };
        const hintEl = document.getElementById('teamProgressHint');

        if (!fillEls.fire || !fillEls.water || !fillEls.grass || !hintEl) return;

        const teamXp = { fire: 0, water: 0, grass: 0 };
        (teamStats || []).forEach(ts => {
            const key = String(ts.team || '').toLowerCase();
            if (key in teamXp) teamXp[key] = Math.max(0, parseInt(ts.team_xp, 10) || 0);
        });

        const total = teamXp.fire + teamXp.water + teamXp.grass;
        const fromCache = JSON.parse(localStorage.getItem('student_progress') || 'null') || {};
        const myTeam = (profileData && profileData.profile && profileData.profile.team) || fromCache.team || null;

        const rows = document.querySelectorAll('.team-progress-row');
        rows.forEach(row => {
            const rowTeam = row.dataset.team;
            row.classList.toggle('is-my-team', !!myTeam && rowTeam === myTeam);
        });

        if (!enabled) {
            ['fire', 'water', 'grass'].forEach(team => {
                fillEls[team].style.width = '0%';
                valueEls[team].textContent = '0%';
            });
            hintEl.textContent = disabledMessage || 'Team progress is unavailable.';
            return;
        }

        if (total <= 0) {
            ['fire', 'water', 'grass'].forEach(team => {
                fillEls[team].style.width = '0%';
                valueEls[team].textContent = '0%';
            });
            hintEl.textContent = 'No team XP recorded yet. Complete quests to boost your team!';
            return;
        }

        const pctMap = {};
        ['fire', 'water', 'grass'].forEach(team => {
            const pct = Math.round((teamXp[team] / total) * 100);
            pctMap[team] = pct;
            fillEls[team].style.width = pct + '%';
            valueEls[team].textContent = pct + '%';
        });

        const teamNames = { fire: 'Fire Team', water: 'Water Team', grass: 'Grass Team' };
        if (myTeam && (myTeam in pctMap)) {
            hintEl.textContent = teamNames[myTeam] + ' currently has ' + pctMap[myTeam] + '% of total team XP.';
        } else {
            hintEl.textContent = 'Team share is based on total class XP earned by each team.';
        }
    }

    // ── Render XP Activity ──
    function renderXpActivity(activities) {
        const list = document.getElementById('xpActivityList');

        if (!activities || activities.length === 0) {
            list.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">⚡</div>
                    <p>No XP activity yet. Complete quests to start earning!</p>
                </div>`;
            return;
        }

        const sourceIcons = {
            quest: '🎯',
            quiz: '📝',
            activity: '📚',
            achievement: '🏆',
            daily_challenge: '⚡',
            streak_bonus: '🔥',
            teacher_award: '🎓',
            correction: '✏️',
        };

        list.innerHTML = activities.map(a => {
            const amount = parseInt(a.xp_amount);
            const isPositive = amount >= 0;

            return `
                <div class="xp-activity-item">
                    <span class="xp-activity-icon">${sourceIcons[a.source_type] || '⚡'}</span>
                    <span class="xp-activity-desc">${escapeHtml(a.description)}</span>
                    <span class="xp-activity-amount ${isPositive ? 'positive' : 'negative'}">
                        ${isPositive ? '+' : ''}${amount} XP
                    </span>
                    <span class="xp-activity-time">${getRelativeTime(a.created_at)}</span>
                </div>`;
        }).join('');
    }

    // ── Update Nav Stats ──
    function updateNavStats(profile) {
        const navXp = document.getElementById('navXp');
        const navStreak = document.getElementById('navStreak');
        const navLevel = document.getElementById('navLevel');

        if (navXp) navXp.textContent = formatXP(profile.totalXp) + ' XP';
        if (navStreak) navStreak.textContent = profile.streakDays + ' days';
        if (navLevel) navLevel.textContent = 'Lv ' + profile.level;
    }

    // ── Achievement Modal ──
    window.showAchievementModal = function (icon, name, desc, xp) {
        document.getElementById('modalAchIcon').textContent = icon;
        document.getElementById('modalAchName').textContent = name;
        document.getElementById('modalAchDesc').textContent = desc;
        document.getElementById('modalXpBadge').textContent = '+' + xp + ' XP';
        document.getElementById('achievementModal').style.display = 'flex';
    };

    window.closeAchievementModal = function () {
        document.getElementById('achievementModal').style.display = 'none';
    };

    // ── Utility Functions ──
    function formatXP(num) {
        num = parseInt(num) || 0;
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(num >= 10000 ? 0 : 1) + 'K';
        return num.toLocaleString();
    }

    function capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function getRelativeTime(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        const now = new Date();
        const diffMs = now - date;
        const diffMin = Math.floor(diffMs / 60000);
        const diffHr = Math.floor(diffMs / 3600000);
        const diffDay = Math.floor(diffMs / 86400000);

        if (diffMin < 1) return 'Just now';
        if (diffMin < 60) return diffMin + 'm ago';
        if (diffHr < 24) return diffHr + 'h ago';
        if (diffDay < 7) return diffDay + 'd ago';
        return date.toLocaleDateString();
    }

    // ── Logout ──
    window.handleLogout = async function () {
        try {
            await fetch('../../EDUQUEST/api/auth/logout.php', {
                method: 'POST',
                headers: { Authorization: 'Bearer ' + token },
                credentials: 'include',
            });
        } catch (e) { /* ignore */ }
        ['eq_token', 'eq_teacher', 'eq_student', 'eduquest_user',
         'student_progress', 'eduquest_remember_me'].forEach(k =>
            localStorage.removeItem(k)
        );
        sessionStorage.removeItem('user_id');
        sessionStorage.removeItem('user_role');
        window.location.href = '../../auth/login/login.html?role=student';
    };

})();
