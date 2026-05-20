/**
 * dashboard.js — Minimalist student dashboard
 */
(function () {
    'use strict';

    const token = localStorage.getItem('eq_token');
    const user = JSON.parse(localStorage.getItem('eduquest_user') || 'null');

    if (!token || !user || user.role !== 'student') {
        window.location.href = '../../auth/login/login.html?role=student';
        return;
    }

    const authHeaders = () => ({
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token,
    });

    document.addEventListener('DOMContentLoaded', () => {
        setStudentName();
        loadProfile();
        // Real-time XP & Level — refresh every 30 seconds silently
        setInterval(() => loadProfile(true), 30000);
    });

    /* ── Name + time-of-day greeting ── */
    function setStudentName() {
        const name = user.firstName
            || (user.email || '').split('@')[0].replace(/^\w/, c => c.toUpperCase());
        const el = document.getElementById('studentName');
        if (el) el.textContent = name;

        // Dynamic time-of-day subtitle
        const hour = new Date().getHours();
        const subEl = document.getElementById('welcomeSub');
        if (subEl) {
            if (hour < 12) subEl.textContent = '\u2600\ufe0f Good morning! Ready for today\u2019s quests?';
            else if (hour < 17) subEl.textContent = '\ud83c\udf24\ufe0f Good afternoon! Let\u2019s keep the streak going!';
            else subEl.textContent = '\ud83c\udf19 Good evening! Time for some quest adventures!';
        }
    }

    /* ── Load gamification profile (exposed for onboarding.js) ── */
    window.loadProfile = loadProfile;
    // silent=true skips popups/onboarding (used by polling)
    async function loadProfile(silent) {
        try {
            const res = await fetch('../../EDUQUEST/api/gamification/profile.php', {
                headers: authHeaders(),
            });
            const json = await res.json();
            if (json.success && json.data) {
                const p = json.data.profile;
                updateHero(p);
                updateStats(p, json.data);
                if (!silent) {
                    loadRecentActivity();
                    checkGamePopups(p, json.data);
                    // First-time onboarding: NPC guide + team + starter egg
                    if (!p.team && json.data.settings && json.data.settings.teamsEnabled !== false) {
                        showOnboardingModal();
                    }
                }
                return;
            }
        } catch (_) { /* silent */ }

        // Fallback
        if (!silent) updateHero({ totalXp: 0, level: 1, streakDays: 0 });
    }

    /* ── Hero bar ── */
    function updateHero(p) {
        const levelEl = document.getElementById('heroLevel');
        const fillEl = document.getElementById('heroXpFill');
        const textEl = document.getElementById('heroXpText');
        const navXp = document.getElementById('navXp') || document.getElementById('xpPoints');
        const navStreak = document.getElementById('navStreak') || document.getElementById('streakDays');
        const navLevel = document.getElementById('navLevel');

        const level = p.level || 1;
        const xp = p.totalXp || 0;
        // Use server-provided progress values when available; fall back to simple calc
        const current = p.xpProgress != null ? p.xpProgress : xp;
        const xpNeeded = p.xpNeeded != null ? p.xpNeeded : (level * 400);
        const pct = xpNeeded > 0 ? Math.min(100, Math.round((current / xpNeeded) * 100)) : 0;

        if (levelEl) levelEl.textContent = 'Lv ' + level;
        if (fillEl) fillEl.style.width = pct + '%';
        if (textEl) textEl.textContent = current.toLocaleString() + ' / ' + xpNeeded.toLocaleString() + ' XP';
        if (navXp) navXp.textContent = xp.toLocaleString() + ' XP';
        if (navStreak) navStreak.textContent = (p.streakDays || 0) + ' days';
        if (navLevel) navLevel.textContent = 'Lv ' + level;
    }

    /* ── Stat cards ── */
    function updateStats(p, data) {
        const xpEl = document.getElementById('statXp');
        const streakEl = document.getElementById('statStreak');
        const badgesEl = document.getElementById('statBadges');

        if (xpEl) xpEl.textContent = (p.totalXp || 0).toLocaleString();
        if (streakEl) streakEl.textContent = p.streakDays || 0;

        // Badges count — achievements may be an object { unlocked, total, recent[] }
        if (data.achievements && badgesEl) {
            if (typeof data.achievements.unlocked === 'number') {
                badgesEl.textContent = data.achievements.unlocked;
            } else if (Array.isArray(data.achievements)) {
                badgesEl.textContent = data.achievements.filter(a => a.unlockedAt).length;
            }
        }
    }

    /* ── Recent XP Activity ── */
    async function loadRecentActivity() {
        try {
            const res = await fetch('../../EDUQUEST/api/gamification/profile.php', {
                headers: authHeaders(),
            });
            const json = await res.json();
            if (!json.success || !json.data || !json.data.recentXp || json.data.recentXp.length === 0) return;

            const section = document.getElementById('recentSection');
            const list = document.getElementById('recentList');
            if (!section || !list) return;

            const icons = {
                quest: '🎯', lesson: '📖', quiz: '📝',
                daily_challenge: '⚡', achievement: '🏆', login: '🔥',
            };

            list.innerHTML = json.data.recentXp.slice(0, 5).map(item => `
                <div class="recent-item">
                    <span class="recent-icon">${icons[item.source_type] || '✨'}</span>
                    <span class="recent-text">${escapeHtml(item.description || item.source_type)}</span>
                    <span class="recent-xp">+${item.xp_amount} XP</span>
                </div>
            `).join('');

            section.style.display = '';
        } catch (_) { /* silent */ }
    }

    /* ── First-Time Onboarding ──
       Full two-step flow (team + egg) lives in onboarding.js.
       showOnboardingModal() is exposed globally by that script. */

    /* ── Notification ── */
    function showNotification(msg, type) {
        const el = document.createElement('div');
        el.style.cssText = `
            position:fixed;top:80px;right:20px;padding:12px 20px;
            background:${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color:#fff;border-radius:10px;font-size:14px;font-weight:600;
            z-index:10002;animation:slideUp .3s ease;max-width:280px;
        `;
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 300); }, 3000);
    }

    /* ── Helpers ── */
    function escapeHtml(str) {
        const el = document.createElement('span');
        el.textContent = str;
        return el.innerHTML;
    }

    /* ── Gamified popup triggers (runs once per page load) ── */
    function checkGamePopups(p, data) {
        // Guard: only run once per page load
        if (sessionStorage.getItem('gp_checked')) return;
        sessionStorage.setItem('gp_checked', '1');

        var today = new Date().toDateString();
        var delay = 700; // ms — wait for UI to settle

        // ── TRIGGER 3: Level Up ──────────────────────────────────
        var prevLevel = parseInt(sessionStorage.getItem('gp_lastLevel') || '0', 10);
        var curLevel  = p.level || 1;
        if (prevLevel > 0 && curLevel > prevLevel && window.showGamePopup) {
            setTimeout(function () {
                showGamePopup({
                    type:     'levelup',
                    title:    'Level Up! \uD83D\uDE80',
                    icon:     '\uD83D\uDE80',
                    message:  'You reached Level ' + curLevel + '! You are on fire \u2014 keep earning that XP!',
                    confetti: true,
                });
            }, delay);
            delay += 200;
        }
        sessionStorage.setItem('gp_lastLevel', curLevel);

        // ── TRIGGER 2: New Badge ─────────────────────────────────
        var prevBadges = [];
        try { prevBadges = JSON.parse(sessionStorage.getItem('gp_badges') || '[]'); } catch (_) {}
        var achievementPopupsEnabled = !(user && user.preferences && user.preferences.achievementPopups === false);
        if (data.achievements && Array.isArray(data.achievements.recent)) {
            var newBadges = data.achievements.recent.filter(function (a) {
                return +a.is_unlocked === 1 && prevBadges.indexOf(a.title) === -1;
            });
            if (newBadges.length > 0 && window.showGamePopup && achievementPopupsEnabled) {
                newBadges.forEach(function (badge, i) {
                    setTimeout(function () {
                        showGamePopup({
                            type:       'badge',
                            title:      'New Badge Unlocked! \uD83C\uDFC5',
                            icon:       badge.icon || '\uD83C\uDFC5',
                            message:    'You earned the \u201C' + badge.title + '\u201D badge. Amazing work!',
                            confetti:   true,
                            buttonText: 'Claim it!',
                        });
                    }, delay + i * 200);
                });
                delay += newBadges.length * 200;
            }
            // Record all now-seen badges so we don't re-fire
            var allSeen = prevBadges.slice();
            data.achievements.recent.forEach(function (a) {
                if (+a.is_unlocked === 1 && allSeen.indexOf(a.title) === -1) {
                    allSeen.push(a.title);
                }
            });
            sessionStorage.setItem('gp_badges', JSON.stringify(allSeen));
        }

        // ── TRIGGER 4 & 5: Streak Bonus OR Welcome Back ──────────
        // Fire at most once per calendar day
        var dailyShown = sessionStorage.getItem('gp_dailyShown');
        if (dailyShown !== today) {
            sessionStorage.setItem('gp_dailyShown', today);
            var streak = p.streakDays || 0;
            if (streak > 1 && window.showGamePopup) {
                // Trigger 4: Streak Bonus
                setTimeout(function () {
                    showGamePopup({
                        type:      'streak',
                        title:     streak + '-Day Streak! \uD83D\uDD25',
                        icon:      '\uD83D\uDD25',
                        message:   streak + ' days in a row! You\u2019re unstoppable \u2014 keep the streak alive!',
                        autoClose: 3500,
                    });
                }, delay);
            } else if (window.showGamePopup) {
                // Trigger 5: Welcome Back
                var firstName = (user && user.firstName)
                    || ((user && user.email) ? user.email.split('@')[0] : 'there');
                setTimeout(function () {
                    showGamePopup({
                        type:      'welcome',
                        title:     'Welcome Back! \uD83D\uDC4B',
                        icon:      '\uD83D\uDC4B',
                        message:   'Great to see you, ' + firstName + '! Ready for today\u2019s quests?',
                        autoClose: 3000,
                    });
                }, delay);
            }
        }

        // ── TRIGGER 6: Unfinished Quiz Reminder ──────────────────
        // Show once per day, only if student has an in-progress (attempted but unpassed) quiz
        var reminderShown = sessionStorage.getItem('gp_reminderShown');
        if (reminderShown !== today && window.showGamePopup) {
            var reminderDelay = delay + 1200;
            (async function () {
                try {
                    var qRes = await fetch('../../EDUQUEST/api/courses/student-quizzes.php?action=list', {
                        headers: { Authorization: 'Bearer ' + token },
                    });
                    var qJson = await qRes.json();
                    if (!qJson.success || !Array.isArray(qJson.data && qJson.data.quizzes)) return;
                    var unfinished = qJson.data.quizzes.find(function (q) {
                        var attempts  = +q.my_attempts  || 0;
                        var maxAtt    = +q.max_attempts || 0;
                        var exhausted = maxAtt > 0 && attempts >= maxAtt;
                        return attempts > 0 && !+q.ever_passed && !exhausted;
                    });
                    if (!unfinished) return;
                    sessionStorage.setItem('gp_reminderShown', today);
                    setTimeout(function () {
                        var quizId    = unfinished.id;
                        var quizTitle = unfinished.title || 'a quiz';
                        showGamePopup({
                            type:       'reminder',
                            title:      'Pick Up Where You Left Off! \uD83D\uDCDA',
                            icon:       '\uD83D\uDCDA',
                            message:    'You still have \u201C' + quizTitle + '\u201D waiting. You were so close!',
                            buttonText: 'Go Finish It!',
                            onClose: function () {
                                window.location.href = '../quests/take-quiz.html?id=' + quizId;
                            },
                        });
                    }, reminderDelay);
                } catch (_) { /* silent */ }
            })();
        }
    }

    /* ── Logout ── */
    window.handleLogout = async function () {        if (token) {
            await fetch('../../EDUQUEST/api/auth/logout.php', {
                method: 'POST',
                headers: { Authorization: 'Bearer ' + token },
                credentials: 'include',
            }).catch(() => {});
        }
        ['eq_token', 'eq_teacher', 'eq_student', 'eduquest_user',
         'student_progress', 'eduquest_remember_me'].forEach(k => localStorage.removeItem(k));
        sessionStorage.removeItem('user_id');
        sessionStorage.removeItem('user_role');
        window.location.href = '../../auth/login/login.html?role=student';
    };
})();
// Student Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard
    initDashboard();
    loadStudentData();
    animateProgress();
    initQuestInteractions();
    updateDailyChallengeTimer();
});

function initDashboard() {
    // Check authentication – require real session token
    const token = localStorage.getItem('eq_token');
    const user = JSON.parse(localStorage.getItem('eduquest_user') || '{}');

    if (!token || !user.email || user.role !== 'student') {
        window.location.href = '../../auth/login/login.html?role=student';
        return;
    }
}

function loadStudentData() {
    const user = JSON.parse(localStorage.getItem('eduquest_user') || '{}');

    // Use first name only for a friendlier greeting
    const displayName = user.firstName
        || (user.email || '').split('@')[0].replace(/^\w/, c => c.toUpperCase());

    const studentNameEl = document.getElementById('studentName');
    if (studentNameEl) studentNameEl.textContent = displayName;
    
    // Load progress from gamification API if available, else use cache
    loadGamificationProgress();
}

async function loadGamificationProgress() {
    const token = localStorage.getItem('eq_token');
    if (!token) return loadCachedProgress();

    try {
        const res = await fetch('../../EDUQUEST/api/gamification/profile.php', {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token,
            },
        });
        const json = await res.json();

        if (json.success && json.data && json.data.profile) {
            const p = json.data.profile;
            const progress = {
                xp: p.totalXp,
                level: p.level,
                streak: p.streakDays,
                team: p.team || null,
                eggStage: p.eggStage || 1,
                completedQuests: 0,
                achievements: [],
                notificationFrequency: json.data.settings ? json.data.settings.notificationFrequency : 'important',
            };
            localStorage.setItem('student_progress', JSON.stringify(progress));
            updateProgressUI(progress);

            // Prompt onboarding if student hasn't chosen team yet
            if (!p.team && json.data.settings && json.data.settings.teamsEnabled !== false) {
                showOnboardingModal();
            }
            return;
        }
    } catch (e) {
        console.warn('Gamification API unavailable, using cached data');
    }

    loadCachedProgress();
}

function loadCachedProgress() {
    let progress = JSON.parse(localStorage.getItem('student_progress') || '{}');
    
    if (!progress.xp) {
        progress = {
            xp: 0,
            level: 1,
            streak: 0,
            completedQuests: 0,
            achievements: []
        };
        localStorage.setItem('student_progress', JSON.stringify(progress));
    }
    
    updateProgressUI(progress);
}

function updateProgressUI(progress) {
    // Update XP display
    const xpElement = document.getElementById('navXp') || document.getElementById('xpPoints');
    if (xpElement) {
        xpElement.textContent = `${progress.xp.toLocaleString()} XP`;
    }
    
    // Update streak
    const streakElement = document.getElementById('navStreak') || document.getElementById('streakDays');
    if (streakElement) {
        streakElement.textContent = `${progress.streak} days`;
    }

    const levelElement = document.getElementById('navLevel');
    if (levelElement) {
        levelElement.textContent = `Lv ${progress.level}`;
    }
    
    // Calculate level progress
    const xpForNextLevel = progress.level * 400;
    const currentLevelXP = progress.xp % xpForNextLevel;
    const levelProgress = (currentLevelXP / xpForNextLevel) * 100;
    
    // Update XP bar
    const xpBar = document.querySelector('.xp-fill');
    if (xpBar) {
        xpBar.style.width = `${levelProgress}%`;
    }
    
    const xpText = document.querySelector('.xp-text');
    if (xpText) {
        xpText.textContent = `${currentLevelXP.toLocaleString()} / ${xpForNextLevel.toLocaleString()} XP`;
    }
}

function animateProgress() {
    // Animate all progress bars on page load
    const progressBars = document.querySelectorAll('.progress-fill');
    
    progressBars.forEach(bar => {
        const targetWidth = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(() => {
            bar.style.width = targetWidth;
        }, 300);
    });
}

function initQuestInteractions() {
    // Continue Quest buttons
    const continueButtons = document.querySelectorAll('.btn-continue');
    continueButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const questCard = this.closest('.quest-card');
            const questTitle = questCard.querySelector('.quest-title').textContent;
            
            // Add click animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
            
            // Redirect to quest page
            window.location.href = `../quests/quests.html?quest=${encodeURIComponent(questTitle)}`;
        });
    });
    
    // Start Quest buttons
    const startButtons = document.querySelectorAll('.btn-start');
    startButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const questCard = this.closest('.quest-card');
            const questTitle = questCard.querySelector('.quest-title').textContent;
            
            // Add click animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
            
            // Show start confirmation
            showQuestStartModal(questTitle);
        });
    });
    
    // Download buttons
    const downloadButtons = document.querySelectorAll('.btn-download');
    downloadButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            showNotification('📄 Download started!', 'success');
            // In production, trigger actual download
        });
    });
    
    // Daily challenge button
    const challengeBtn = document.querySelector('.btn-challenge');
    if (challengeBtn) {
        challengeBtn.addEventListener('click', function() {
            // Track the daily challenge start
            if (window.EduGamification) {
                EduGamification.trackActivity({
                    activityType: 'daily_challenge',
                    title: 'Daily Challenge',
                    attempts: 1,
                });
            }
            showNotification('⚡ Starting daily challenge...', 'info');
            setTimeout(() => {
                window.location.href = '../quests/quests.html?type=challenge';
            }, 1000);
        });
    }
    
    // Game panel demo interaction
    const gamePanels = document.querySelectorAll('.game-panel-container');
    gamePanels.forEach(panel => {
        panel.addEventListener('click', function() {
            if (!this.classList.contains('game-loaded')) {
                showGameIntegrationInfo();
            }
        });
    });
}

function showQuestStartModal(questTitle) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content quest-start-modal">
            <h2>🎯 Start Quest?</h2>
            <p>You're about to start: <strong>${questTitle}</strong></p>
            <p>Complete this quest to earn XP and unlock achievements!</p>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="this.closest('.modal-overlay').remove()">Cancel</button>
                <button class="btn-primary" onclick="startQuest('${questTitle}')">Start Now!</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Add modal styles
    if (!document.getElementById('modalStyles')) {
        const style = document.createElement('style');
        style.id = 'modalStyles';
        style.textContent = `
            .modal-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: var(--z-modal);
                animation: fadeIn 0.3s ease;
                padding: 20px;
            }
            .modal-content {
                background: white;
                padding: 2rem;
                border-radius: var(--border-radius-xl);
                max-width: 500px;
                width: 100%;
                animation: slideUp 0.3s ease;
            }
            .modal-content h2 {
                margin-bottom: 1rem;
                color: var(--text-primary);
            }
            .modal-content p {
                margin-bottom: 1rem;
                color: var(--text-secondary);
            }
            .modal-actions {
                display: flex;
                gap: 1rem;
                margin-top: 1.5rem;
            }
            .modal-actions button {
                flex: 1;
                padding: 0.75rem;
                border: none;
                border-radius: var(--border-radius-md);
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .btn-secondary {
                background: var(--gray-200);
                color: var(--text-primary);
            }
            .btn-secondary:hover {
                background: var(--gray-300);
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideUp {
                from { transform: translateY(50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }
}

function showGameIntegrationInfo() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content game-info-modal">
            <h2>🎮 Game Panel Integration</h2>
            <p>This panel is designed to host interactive game content using your preferred game engine:</p>
            <ul style="text-align: left; margin: 1rem 0; padding-left: 1.5rem;">
                <li><strong>Phaser.js</strong> - 2D game framework</li>
                <li><strong>PixiJS</strong> - WebGL renderer</li>
                <li><strong>Unity WebGL</strong> - Unity games in browser</li>
                <li><strong>Three.js</strong> - 3D graphics</li>
                <li><strong>Canvas API</strong> - Custom HTML5 canvas games</li>
                <li><strong>iframe</strong> - External game content</li>
            </ul>
            <p>The flexible design allows you to integrate any game engine seamlessly!</p>
            <div class="modal-actions">
                <button class="btn-primary" onclick="this.closest('.modal-overlay').remove()">Got it!</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function startQuest(questTitle) {
    // Close modal
    document.querySelector('.modal-overlay')?.remove();
    
    // Track quest start via gamification
    if (window.EduGamification) {
        EduGamification.trackActivity({
            activityType: 'quest',
            title: questTitle,
            attempts: 1,
        });
    }
    
    // Show loading notification
    showNotification(`🎯 Loading ${questTitle}...`, 'info');
    
    // Redirect to quest page
    setTimeout(() => {
        window.location.href = `../quests/quests.html?quest=${encodeURIComponent(questTitle)}`;
    }, 1000);
}

function updateDailyChallengeTimer() {
    const timerElement = document.querySelector('.challenge-timer');
    if (!timerElement) return;
    
    // Set a target time (end of day)
    const now = new Date();
    const endOfDay = new Date(now);
    endOfDay.setHours(23, 59, 59, 999);
    
    function updateTimer() {
        const now = new Date();
        const diff = endOfDay - now;
        
        if (diff <= 0) {
            timerElement.textContent = '⏰ Challenge reset!';
            return;
        }
        
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        timerElement.textContent = `⏰ ${hours}h ${minutes}m left`;
    }
    
    updateTimer();
    setInterval(updateTimer, 60000); // Update every minute
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? 'var(--success-color)' : type === 'error' ? 'var(--error-color)' : 'var(--info-color)'};
        color: white;
        border-radius: var(--border-radius-md);
        box-shadow: var(--shadow-xl);
        z-index: var(--z-notification);
        animation: slideInRight 0.3s ease;
        max-width: 300px;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ── Team Selection Modal (handled by onboarding.js) ──

// Export functions
window.startQuest = startQuest;
window.showNotification = showNotification;
window.handleLogout = handleLogout;

async function handleLogout() {
    // Invalidate session on the server
    const token = localStorage.getItem('eq_token');
    if (token) {
        await fetch('../../EDUQUEST/api/auth/logout.php', {
            method: 'POST',
            headers: { Authorization: 'Bearer ' + token },
            credentials: 'include',
        }).catch(() => {});
    }

    // Clear all unified auth keys
    ['eq_token', 'eq_teacher', 'eq_student', 'eduquest_user',
     'student_progress', 'eduquest_remember_me'].forEach(k =>
        localStorage.removeItem(k)
    );
    sessionStorage.removeItem('user_id');
    sessionStorage.removeItem('user_role');

    window.location.href = '../../auth/login/login.html?role=student';
}
