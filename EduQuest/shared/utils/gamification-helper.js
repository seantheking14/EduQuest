/**
 * gamification-helper.js
 * Shared utility for student pages to track activities and display XP rewards.
 * Include this on any student page that needs gamification integration.
 *
 * Usage:
 *   await EduGamification.trackActivity({
 *       activityType: 'quest',
 *       title: 'Math Challenge',
 *       score: 85,
 *       maxScore: 100,
 *       attempts: 1,
 *       timeSpent: 420,  // seconds
 *       courseId: 5,
 *   });
 */
(function () {
    'use strict';

    const API_BASE = getApiBase();
    const token = localStorage.getItem('eq_token');

    // Notification frequency: 'all' | 'important' | 'minimal'
    // Loaded from gamification profile settings; default to 'important'
    let notificationFrequency = 'important';

    function getApiBase() {
        // Resolve path depending on page depth
        const path = window.location.pathname;
        if (path.includes('/student-dashboard/')) {
            return '../../EDUQUEST/api/gamification';
        }
        return '../api/gamification'; // fallback
    }

    function authHeaders() {
        return {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + (localStorage.getItem('eq_token') || ''),
        };
    }

    /**
     * Track a completed activity and award XP.
     * @param {Object} opts
     * @param {string} opts.activityType - quest|quiz|activity|daily_challenge
     * @param {string} opts.title        - Human-readable label
     * @param {number} [opts.score]      - Score achieved (0-100 or raw)
     * @param {number} [opts.maxScore]   - Maximum possible score
     * @param {number} [opts.attempts]   - Number of attempts
     * @param {number} [opts.timeSpent]  - Seconds spent on task
     * @param {number} [opts.courseId]    - Course ID if applicable
     * @param {number} [opts.activityId] - Source item ID
     * @param {Array}  [opts.responses]  - Optional response data
     * @returns {Promise<Object>} Server response with XP details
     */
    async function trackActivity(opts) {
        if (!token) return null;

        try {
            const res = await fetch(API_BASE + '/track-activity.php', {
                method: 'POST',
                headers: authHeaders(),
                body: JSON.stringify({
                    activityType: opts.activityType,
                    activityId: opts.activityId || null,
                    courseId: opts.courseId || null,
                    title: opts.title,
                    score: opts.score ?? null,
                    maxScore: opts.maxScore ?? null,
                    attempts: opts.attempts || 1,
                    timeSpent: opts.timeSpent || null,
                    responses: opts.responses || null,
                }),
            });
            const json = await res.json();

            if (json.success && json.data) {
                // Update localStorage cache
                updateLocalProgress(json.data);

                // Load notification preference from cached profile if available
                const cached = JSON.parse(localStorage.getItem('student_progress') || '{}');
                if (cached.notificationFrequency) notificationFrequency = cached.notificationFrequency;

                // Show XP notification (all frequencies)
                if (notificationFrequency !== 'minimal') {
                    showXpNotification(json.data);
                }

                // Show level-up celebration (important + all)
                if (json.data.leveledUp) {
                    showLevelUpNotification(json.data.level);
                }

                // Show egg evolution (important + all)
                if (json.data.eggEvolved) {
                    showEggEvolutionNotification(json.data.eggStage);
                }

                // Show new achievements (all frequencies — achievements are always 'important')
                if (json.data.newAchievements && json.data.newAchievements.length > 0) {
                    json.data.newAchievements.forEach(ach => {
                        setTimeout(() => showAchievementNotification(ach), 1500);
                    });
                }

                // Update nav stats if present on page
                updateNavStats(json.data);
            }

            return json;
        } catch (err) {
            console.error('[Gamification] Failed to track activity:', err);
            return null;
        }
    }

    /** Update the cached progress in localStorage */
    function updateLocalProgress(data) {
        const progress = JSON.parse(localStorage.getItem('student_progress') || '{}');
        if (data.totalXp !== undefined) progress.xp = data.totalXp;
        if (data.level !== undefined) progress.level = data.level;
        if (data.eggStage !== undefined) progress.eggStage = data.eggStage;
        if (data.streakDays !== undefined) progress.streak = data.streakDays;
        if (data.dailyXpEarned !== undefined) progress.dailyXpEarned = data.dailyXpEarned;
        if (data.maxDailyXp !== undefined) progress.maxDailyXp = data.maxDailyXp;
        // Track achievement unlocks so other pages see updated counts
        if (data.newAchievements && data.newAchievements.length > 0) {
            progress.achievements = (progress.achievements || 0) + data.newAchievements.length;
        }
        // Mark timestamp so pages can tell how fresh the cache is
        progress.updatedAt = Date.now();
        localStorage.setItem('student_progress', JSON.stringify(progress));
    }

    /** Update nav bar stats (XP, streak, level) if elements exist */
    function updateNavStats(data) {
        const xpEl = document.getElementById('xpPoints') || document.getElementById('navXp');
        const streakEl = document.getElementById('streakDays') || document.getElementById('navStreak');
        const levelEl = document.getElementById('navLevel');

        if (xpEl && data.totalXp !== undefined) {
            xpEl.textContent = formatXP(data.totalXp) + ' XP';
        }
        if (streakEl && data.streakDays !== undefined) {
            streakEl.textContent = data.streakDays + ' days';
        }
        if (levelEl && data.level !== undefined) {
            levelEl.textContent = 'Lv ' + data.level;
        }
    }

    // ── Notification UI ──

    function showXpNotification(data) {
        if (data.xpAwarded <= 0) return;

        const el = createNotification('xp');
        let html = `<strong>+${data.xpAwarded} XP</strong> earned!`;
        if (data.bonusDetails && data.bonusDetails.length > 0) {
            html += '<br><small>' + data.bonusDetails.join(' • ') + '</small>';
        }
        if (data.cappedOut) {
            html = '🔒 Daily XP cap reached. Come back tomorrow!';
        }
        el.innerHTML = html;
        showAndFade(el, 3500);
    }

    function showLevelUpNotification(level) {
        const el = createNotification('level-up');
        el.innerHTML = `<strong>🎉 Level Up!</strong> You are now <strong>Level ${level}</strong>!`;
        showAndFade(el, 5000);
    }

    function showEggEvolutionNotification(stage) {
        // Use team-aware names if PetSprites is available
        const progress = JSON.parse(localStorage.getItem('student_progress') || '{}');
        const team = progress.team || 'fire';
        let name;
        if (typeof PetSprites !== 'undefined') {
            name = PetSprites.stageName(team, stage);
        } else {
            const names = { 1: 'Egg', 2: 'Cracking Egg', 3: 'Hatchling', 4: 'Young Creature', 5: 'Mythical Guardian' };
            name = names[stage] || 'Unknown';
        }
        const teamEmojis = { fire: '🔥', water: '💧', grass: '🌿' };
        const icon = teamEmojis[team] || '🥚';
        const el = createNotification('egg-evolve');
        el.innerHTML = `<strong>${icon} Your companion evolved!</strong> It's now a <strong>${name}</strong>!`;
        showAndFade(el, 5000);
    }

    function showAchievementNotification(ach) {
        const el = createNotification('achievement');
        el.innerHTML = `<strong>${ach.icon} Achievement Unlocked!</strong><br>${ach.title} — +${ach.xpReward} XP`;
        showAndFade(el, 5000);
    }

    function createNotification(type) {
        // Inject styles once
        if (!document.getElementById('gamif-notif-style')) {
            const style = document.createElement('style');
            style.id = 'gamif-notif-style';
            style.textContent = `
                .gamif-notif {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    padding: 0.85rem 1.25rem;
                    border-radius: 10px;
                    color: #fff;
                    font-size: 0.9rem;
                    line-height: 1.4;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.25);
                    transform: translateX(120%);
                    transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s;
                    opacity: 0;
                    max-width: 360px;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                }
                .gamif-notif.show { transform: translateX(0); opacity: 1; }
                .gamif-notif.xp { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
                .gamif-notif.level-up { background: linear-gradient(135deg, #f59e0b, #d97706); }
                .gamif-notif.egg-evolve { background: linear-gradient(135deg, #10b981, #059669); }
                .gamif-notif.achievement { background: linear-gradient(135deg, #3b82f6, #2563eb); }
                .gamif-notif small { opacity: 0.85; }
            `;
            document.head.appendChild(style);
        }

        const el = document.createElement('div');
        el.className = 'gamif-notif ' + type;

        // Stack below existing notifications
        const existing = document.querySelectorAll('.gamif-notif');
        const offset = 20 + existing.length * 70;
        el.style.top = offset + 'px';

        document.body.appendChild(el);
        return el;
    }

    function showAndFade(el, duration) {
        requestAnimationFrame(() => {
            requestAnimationFrame(() => el.classList.add('show'));
        });
        setTimeout(() => {
            el.classList.remove('show');
            setTimeout(() => el.remove(), 400);
        }, duration);
    }

    function formatXP(num) {
        num = parseInt(num) || 0;
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(num >= 10000 ? 0 : 1) + 'K';
        return num.toLocaleString();
    }

    // ── Expose globally ──
    window.EduGamification = {
        trackActivity,
        updateNavStats,
        formatXP,
    };
})();
