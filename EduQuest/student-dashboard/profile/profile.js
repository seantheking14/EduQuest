// Student Profile Page JavaScript

// ── Avatar catalog ──
const AVATAR_CATALOG = {
    free: [
        { id: 'student', emoji: '🧑‍🎓', label: 'Student' },
        { id: 'nerd',    emoji: '🤓', label: 'Nerd' },
        { id: 'cool',    emoji: '😎', label: 'Cool' },
        { id: 'happy',   emoji: '😊', label: 'Happy' },
        { id: 'star',    emoji: '🌟', label: 'Star' },
        { id: 'rocket',  emoji: '🚀', label: 'Rocket' },
        { id: 'fox',     emoji: '🦊', label: 'Fox' },
        { id: 'cat',     emoji: '🐱', label: 'Cat' },
        { id: 'dog',     emoji: '🐶', label: 'Dog' },
        { id: 'panda',   emoji: '🐼', label: 'Panda' },
    ],
    locked: [
        { id: 'wizard',    emoji: '🧙', label: 'Wizard',    levelReq: 5 },
        { id: 'ninja',     emoji: '🥷', label: 'Ninja',     levelReq: 7 },
        { id: 'astronaut', emoji: '🧑‍🚀', label: 'Astronaut', levelReq: 10 },
        { id: 'dragon',    emoji: '🐉', label: 'Dragon',    levelReq: 12 },
        { id: 'unicorn',   emoji: '🦄', label: 'Unicorn',   levelReq: 15 },
        { id: 'phoenix',   emoji: '🔥', label: 'Phoenix',   levelReq: 18 },
        { id: 'crown',     emoji: '👑', label: 'Royal',     levelReq: 20 },
        { id: 'alien',     emoji: '👾', label: 'Alien',     levelReq: 25 },
    ],
};

// Resolve an avatar id to its emoji (fallback to default)
function getAvatarEmoji(avatarId) {
    if (!avatarId) return '🧑‍🎓';
    const all = [...AVATAR_CATALOG.free, ...AVATAR_CATALOG.locked];
    const match = all.find(a => a.id === avatarId);
    return match ? match.emoji : '🧑‍🎓';
}

let currentAvatarId = null;   // persisted avatar
let pendingAvatarId = null;   // selected but not saved yet
let currentLevel = 1;

document.addEventListener('DOMContentLoaded', function() {
    initializeProfile();
    setupEventListeners();
    setupAvatarPicker();
});

function initializeProfile() {
    // Load user data
    const user = Auth.getUser();
    if (!user) {
        window.location.href = '../../auth/login/login.html';
        return;
    }

    // Load saved avatar
    currentAvatarId = user.avatarId || 'student';
    applyAvatar(currentAvatarId);

    // Show cached data instantly while live data loads
    const cached = Storage.get('student_progress') || {};
    currentLevel = cached.level || 1;
    updateProfileDisplay(user, {
        level: cached.level || 1,
        xp: cached.xp || 0,
        streak: cached.streak || 0,
        completedQuests: cached.completedQuests || 0,
        achievements: cached.achievements || 0,
        coins: cached.coins || 0,
        questsInProgress: cached.questsInProgress || 0,
        avgCompletionTime: cached.avgCompletionTime || '--',
        classRank: cached.classRank || '--',
    });

    // Fetch live data from the API so XP/level/achievements are always current
    fetchLiveProgress(user);

    // Also fetch avatar from server in case it was changed on another device
    fetchStudentAvatar();
}

async function fetchLiveProgress(user) {
    const token = localStorage.getItem('eq_token');
    if (!token) return;
    try {
        const [profileRes, achievementsRes] = await Promise.all([
            fetch('../../EDUQUEST/api/gamification/profile.php', {
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            }),
            fetch('../../EDUQUEST/api/gamification/achievements.php', {
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            }),
        ]);
        const profileJson = await profileRes.json();
        const achievementsJson = await achievementsRes.json();

        if (profileJson.success && profileJson.data) {
            const p = profileJson.data.profile;
            const unlocked = achievementsJson.success ? (achievementsJson.data.unlocked || 0) : 0;
            const questsCompleted = profileJson.data.recentXp
                ? profileJson.data.recentXp.filter(r => r.sourceType === 'quest' || r.sourceType === 'activity').length
                : 0;

            const liveProgress = {
                level: p.level || 1,
                xp: p.totalXp || 0,
                streak: p.streakDays || 0,
                completedQuests: questsCompleted,
                achievements: unlocked,
                coins: p.coins || 0,
                questsInProgress: 0,
                avgCompletionTime: '--',
                classRank: '--',
            };

            updateProfileDisplay(user, liveProgress);
            currentLevel = liveProgress.level;

            // Update localStorage cache so other pages benefit
            const cached = Storage.get('student_progress') || {};
            cached.xp = liveProgress.xp;
            cached.level = liveProgress.level;
            cached.streak = liveProgress.streak;
            cached.achievements = liveProgress.achievements;
            Storage.set('student_progress', cached);
        }
    } catch (_) {
        // Keep showing cached data on error
    }
}

function updateProfileDisplay(user, progress) {
    // Update name
    const displayName = (user.firstName && user.lastName)
        ? user.firstName + ' ' + user.lastName
        : user.name || 'Student';
    document.getElementById('displayName').textContent = displayName;
    
    // Update form fields from user object (overwritten by fetchPersonalInfo if API succeeds)
    document.getElementById('firstName').value = user.firstName || (user.name || 'Demo').split(' ')[0];
    document.getElementById('lastName').value = user.lastName || (user.name || 'Student').split(' ').slice(1).join(' ') || 'Student';
    document.getElementById('email').value = user.email || '';
    
    // Load personal info from the server (grade, bio, and authoritative name)
    fetchPersonalInfo();
    
    // Update level and XP
    const requiredXP = progress.level * 400;
    const xpProgress = Math.min(100, (progress.xp / requiredXP) * 100);
    
    document.getElementById('profileLevel').textContent = progress.level;
    document.getElementById('currentXP').textContent = progress.xp.toLocaleString();
    document.getElementById('requiredXP').textContent = requiredXP.toLocaleString();
    document.getElementById('xpBar').style.width = xpProgress + '%';
    document.getElementById('xpToNext').textContent = Math.max(0, requiredXP - progress.xp).toLocaleString();
    document.getElementById('nextLevel').textContent = progress.level + 1;
    
    // Update nav bar stats
    const navLevel = document.getElementById('navLevel');
    const navXp = document.getElementById('navXp') || document.getElementById('xpPoints');
    const navStreak = document.getElementById('navStreak');
    if (navLevel) navLevel.textContent = 'Lv ' + progress.level;
    if (navXp) navXp.textContent = progress.xp.toLocaleString() + ' XP';
    if (navStreak) navStreak.textContent = progress.streak + ' days';
    
    // Update student title based on level
    const title = getStudentTitle(progress.level);
    document.getElementById('studentTitle').textContent = title;
    
    // Update badges
    document.getElementById('streakDays').textContent = progress.streak;
    document.getElementById('completedQuests').textContent = progress.completedQuests;
    document.getElementById('unlockedAchievements').textContent = progress.achievements;
    document.getElementById('totalCoins').textContent = progress.coins;
    
    // Update stats
    document.getElementById('totalXP').textContent = progress.xp.toLocaleString();
    document.getElementById('questsInProgress').textContent = progress.questsInProgress;
    document.getElementById('avgCompletionTime').textContent = progress.avgCompletionTime;
    document.getElementById('classRank').textContent = progress.classRank === '--' ? '--' : '#' + progress.classRank;
}

// Fetch personal info from the student profile API (authoritative source)
async function fetchPersonalInfo() {
    const token = localStorage.getItem('eq_token');
    if (!token) return;
    try {
        const res = await fetch('../../EDUQUEST/api/students/profile.php', {
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        });
        const json = await res.json();
        if (json.success && json.data) {
            const d = json.data;
            document.getElementById('firstName').value = d.firstName || '';
            document.getElementById('lastName').value  = d.lastName  || '';
            document.getElementById('email').value     = d.email     || '';
            document.getElementById('bio').value       = d.bio       || '';
            document.getElementById('displayName').textContent = (d.firstName + ' ' + d.lastName).trim() || 'Student';
        }
    } catch (_) { /* keep showing cached data */ }
}

function getStudentTitle(level) {
    if (level >= 20) return 'Legendary Master';
    if (level >= 15) return 'Epic Champion';
    if (level >= 10) return 'Skilled Adventurer';
    if (level >= 5) return 'Quest Explorer';
    return 'Novice Learner';
}

function setupEventListeners() {
    // Edit Profile Button
    document.getElementById('editProfileBtn').addEventListener('click', enableEditMode);
    
    // Save Profile Button
    document.getElementById('saveProfileBtn').addEventListener('click', saveProfile);
    
    // Cancel Edit Button
    document.getElementById('cancelEditBtn').addEventListener('click', cancelEdit);
    
    // Open avatar picker
    document.getElementById('openAvatarPicker').addEventListener('click', openAvatarPicker);

    // Avatar modal buttons
    document.getElementById('cancelAvatarBtn').addEventListener('click', closeAvatarPicker);
    document.getElementById('saveAvatarBtn').addEventListener('click', saveAvatarSelection);
    
    // Change Password Button
    document.getElementById('changePasswordBtn').addEventListener('click', openPasswordModal);
    
    // Password Modal Buttons
    document.getElementById('cancelPasswordBtn').addEventListener('click', closePasswordModal);
    document.getElementById('savePasswordBtn').addEventListener('click', savePassword);

    // Also re-validate when currentPassword is typed (enables Send OTP button)
    document.getElementById('currentPassword').addEventListener('input', validatePasswordLive);
    
    // Modal Close Buttons (both password and avatar modals)
    document.querySelectorAll('.modal-close, .modal-overlay').forEach(element => {
        element.addEventListener('click', () => {
            closePasswordModal();
            closeAvatarPicker();
        });
    });
    
    // Settings Toggle Switches
    setupSettingsListeners();
}

function enableEditMode() {
    // Enable form inputs
    const inputs = document.querySelectorAll('#personalInfoForm input, #personalInfoForm textarea');
    inputs.forEach(input => {
        if (input.id !== 'email') { // Email should not be editable
            input.disabled = false;
        }
    });
    
    // Show/hide buttons
    document.getElementById('editProfileBtn').style.display = 'none';
    document.getElementById('saveProfileBtn').style.display = 'inline-flex';
    document.getElementById('cancelEditBtn').style.display = 'inline-flex';
    
    // Show notification
    showNotification('Edit mode enabled. Make your changes and click Save.', 'info');
}

function cancelEdit() {
    // Reload page to reset all changes
    location.reload();
}

function saveProfile() {
    // Get form values
    const profileData = {
        firstName: document.getElementById('firstName').value.trim(),
        lastName: document.getElementById('lastName').value.trim(),
        bio: document.getElementById('bio').value.trim()
    };
    
    // Validate data
    if (!profileData.firstName || !profileData.lastName) {
        showNotification('First name and last name are required', 'error');
        return;
    }

    const token = localStorage.getItem('eq_token');
    if (!token) {
        showNotification('Session expired. Please log in again.', 'error');
        return;
    }

    // Disable save button while request is in flight
    const saveBtn = document.getElementById('saveProfileBtn');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving…';

    fetch('../../EDUQUEST/api/students/profile.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token,
        },
        body: JSON.stringify(profileData),
    })
    .then(res => res.json())
    .then(json => {
        if (!json.success) {
            showNotification(json.message || 'Failed to save profile.', 'error');
            return;
        }

        // Update localStorage so other pages reflect the change
        const user = Auth.getUser();
        user.firstName = profileData.firstName;
        user.lastName = profileData.lastName;
        user.name = profileData.firstName + ' ' + profileData.lastName;
        user.profile = profileData;
        Storage.set('eduquest_user', user);

        // Update display
        document.getElementById('displayName').textContent = user.name;

        // Disable form inputs
        const inputs = document.querySelectorAll('#personalInfoForm input, #personalInfoForm textarea');
        inputs.forEach(input => input.disabled = true);

        // Show/hide buttons
        document.getElementById('editProfileBtn').style.display = 'inline-flex';
        document.getElementById('saveProfileBtn').style.display = 'none';
        document.getElementById('cancelEditBtn').style.display = 'none';

        showNotification('Profile updated successfully!', 'success');
    })
    .catch(() => {
        showNotification('Network error. Please try again.', 'error');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<span class="btn-icon">💾</span> Save Changes';
    });
}

// ── Avatar picker functions ──

function setupAvatarPicker() {
    const freeGrid = document.getElementById('freeAvatarGrid');
    const lockedGrid = document.getElementById('lockedAvatarGrid');

    // Render free avatars
    AVATAR_CATALOG.free.forEach(avatar => {
        freeGrid.appendChild(createAvatarOption(avatar, false));
    });

    // Render locked avatars
    AVATAR_CATALOG.locked.forEach(avatar => {
        lockedGrid.appendChild(createAvatarOption(avatar, true));
    });
}

function createAvatarOption(avatar, isLockable) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'avatar-option';
    el.setAttribute('data-avatar-id', avatar.id);
    el.setAttribute('aria-label', avatar.label);
    el.textContent = avatar.emoji;

    if (isLockable) {
        const isUnlocked = currentLevel >= avatar.levelReq;
        if (!isUnlocked) {
            el.classList.add('locked');
            el.title = `Unlock at Level ${avatar.levelReq}`;
            const badge = document.createElement('span');
            badge.className = 'avatar-lock-badge';
            badge.textContent = `Lv ${avatar.levelReq}`;
            el.appendChild(badge);
        }
    }

    // Mark currently active
    if (avatar.id === currentAvatarId) {
        el.classList.add('selected');
    }

    el.addEventListener('click', () => {
        if (el.classList.contains('locked')) return;
        selectAvatarOption(avatar.id);
    });

    return el;
}

function selectAvatarOption(avatarId) {
    pendingAvatarId = avatarId;

    // Update selection UI
    document.querySelectorAll('.avatar-option').forEach(opt => {
        opt.classList.toggle('selected', opt.getAttribute('data-avatar-id') === avatarId);
    });

    // Update preview
    const emoji = getAvatarEmoji(avatarId);
    document.getElementById('previewAvatar').textContent = emoji;

    // Enable save button (only if different from current)
    document.getElementById('saveAvatarBtn').disabled = (avatarId === currentAvatarId);
}

function openAvatarPicker() {
    pendingAvatarId = currentAvatarId;

    // Refresh locked state based on current level
    document.querySelectorAll('#lockedAvatarGrid .avatar-option').forEach(opt => {
        const id = opt.getAttribute('data-avatar-id');
        const avatarDef = AVATAR_CATALOG.locked.find(a => a.id === id);
        if (!avatarDef) return;

        const isUnlocked = currentLevel >= avatarDef.levelReq;
        opt.classList.toggle('locked', !isUnlocked);

        // Update or remove lock badge
        let badge = opt.querySelector('.avatar-lock-badge');
        if (!isUnlocked) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'avatar-lock-badge';
                opt.appendChild(badge);
            }
            badge.textContent = `Lv ${avatarDef.levelReq}`;
            opt.title = `Unlock at Level ${avatarDef.levelReq}`;
        } else {
            if (badge) badge.remove();
            opt.title = avatarDef.label;
        }
    });

    // Highlight current selection
    document.querySelectorAll('.avatar-option').forEach(opt => {
        opt.classList.toggle('selected', opt.getAttribute('data-avatar-id') === currentAvatarId);
    });

    // Reset preview
    document.getElementById('previewAvatar').textContent = getAvatarEmoji(currentAvatarId);
    document.getElementById('saveAvatarBtn').disabled = true;

    document.getElementById('avatarModal').classList.add('active');
}

function closeAvatarPicker() {
    document.getElementById('avatarModal').classList.remove('active');
    pendingAvatarId = null;
}

async function saveAvatarSelection() {
    if (!pendingAvatarId || pendingAvatarId === currentAvatarId) return;

    const saveBtn = document.getElementById('saveAvatarBtn');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving…';

    try {
        // Save to server
        const token = localStorage.getItem('eq_token');
        const response = await fetch('../../EDUQUEST/api/students/profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token,
            },
            body: JSON.stringify({ avatarId: pendingAvatarId }),
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to save avatar');
        }

        // Update local state
        currentAvatarId = pendingAvatarId;
        applyAvatar(currentAvatarId);

        // Persist to localStorage user object
        const user = Auth.getUser();
        user.avatarId = currentAvatarId;
        Storage.set('eduquest_user', user);

        closeAvatarPicker();
        showNotification('Avatar updated! Looking great! 🎉', 'success');
    } catch (err) {
        console.error('Avatar save error:', err);
        showNotification(err.message || 'Could not save avatar. Try again.', 'error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<span class="btn-icon">💾</span> Save Avatar';
    }
}

async function fetchStudentAvatar() {
    const token = localStorage.getItem('eq_token');
    if (!token) return;

    try {
        const res = await fetch('../../EDUQUEST/api/students/profile.php', {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token,
            },
        });
        const json = await res.json();
        if (json.success && json.data && json.data.avatarId) {
            currentAvatarId = json.data.avatarId;
            applyAvatar(currentAvatarId);

            // Also update localStorage
            const user = Auth.getUser();
            user.avatarId = currentAvatarId;
            Storage.set('eduquest_user', user);
        }
    } catch (_) {
        // Silently use cached avatar
    }
}

function applyAvatar(avatarId) {
    const emoji = getAvatarEmoji(avatarId);
    const profileAvatar = document.getElementById('profileAvatar');
    const navAvatar = document.getElementById('navAvatar');
    if (profileAvatar) profileAvatar.textContent = emoji;
    if (navAvatar) navAvatar.textContent = emoji;
}

// ── Password Modal OTP State ──────────────────────────────
let _pwdOtpStep = 1; // 1 = password fields, 2 = OTP entry
let _countdownTimer = null;
let _storedCurrentPw = '';
let _storedNewPw = '';

function openPasswordModal() {
    _resetPasswordModal();
    document.getElementById('passwordModal').classList.add('active');
}

function _resetPasswordModal() {
    _pwdOtpStep = 1;
    _storedCurrentPw = '';
    _storedNewPw = '';
    clearInterval(_countdownTimer);

    // Reset form
    document.getElementById('passwordForm').reset();
    document.getElementById('pwdAlert').classList.add('hidden');
    document.getElementById('strengthFill').style.width = '0';
    document.getElementById('strengthFill').className = 'pwd-strength-fill';
    document.getElementById('strengthText').textContent = '';
    document.getElementById('matchText').textContent = '';

    // Reset password requirements
    document.querySelectorAll('#pwdRequirements li').forEach(li => {
        li.classList.remove('met');
        li.querySelector('.pwd-check').textContent = '○';
    });

    // Show step 1, hide step 2
    document.getElementById('passwordForm').classList.remove('hidden');
    document.getElementById('pwdOtpStep').classList.add('hidden');

    // Reset button
    const btn = document.getElementById('savePasswordBtn');
    btn.textContent = 'Send OTP to Email';
    btn.disabled = true;
    btn.dataset.step = '1';

    // Reset OTP fields
    document.getElementById('otpCode').value = '';
    document.getElementById('otpEmailDisplay').textContent = '';
    document.getElementById('otpCountdown').textContent = '';
    document.getElementById('otpCountdown').classList.remove('expiring-soon');
    document.getElementById('resendOtpBtn').classList.add('hidden');

    // Wire up toggle visibility buttons
    document.querySelectorAll('.pwd-toggle-btn').forEach(btn => {
        btn.onclick = () => {
            const input = document.getElementById(btn.dataset.target);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.querySelector('.eye-open').classList.toggle('hidden', isHidden);
            btn.querySelector('.eye-closed').classList.toggle('hidden', !isHidden);
        };
    });

    // Wire up real-time validation
    const newPwdInput = document.getElementById('newPassword');
    const confirmPwdInput = document.getElementById('confirmPassword');
    const curPwdInput = document.getElementById('currentPassword');
    newPwdInput.removeEventListener('input', validatePasswordLive);
    confirmPwdInput.removeEventListener('input', validatePasswordLive);
    curPwdInput.removeEventListener('input', validatePasswordLive);
    newPwdInput.addEventListener('input', validatePasswordLive);
    confirmPwdInput.addEventListener('input', validatePasswordLive);
    curPwdInput.addEventListener('input', validatePasswordLive);
}

function closePasswordModal() {
    clearInterval(_countdownTimer);
    document.getElementById('passwordModal').classList.remove('active');
    document.getElementById('passwordForm').reset();
}

function validatePasswordLive() {
    const pw = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmPassword').value;

    const rules = {
        length:    pw.length >= 8,
        uppercase: /[A-Z]/.test(pw),
        number:    /[0-9]/.test(pw),
        special:   /[!@#$%^&*()_\-+=\[\]{};:'".,<>?\/\\|`~]/.test(pw),
    };

    let metCount = 0;
    Object.entries(rules).forEach(([rule, met]) => {
        const li = document.querySelector(`#pwdRequirements li[data-rule="${rule}"]`);
        if (li) {
            li.classList.toggle('met', met);
            li.querySelector('.pwd-check').textContent = met ? '✓' : '○';
        }
        if (met) metCount++;
    });

    // Strength bar
    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');
    const pct = (metCount / 4) * 100;
    fill.style.width = pct + '%';
    fill.className = 'pwd-strength-fill';

    if (pw.length === 0) {
        text.textContent = '';
    } else if (metCount <= 1) {
        fill.classList.add('strength-weak');
        text.textContent = 'Weak';
        text.style.color = '#ef4444';
    } else if (metCount <= 2) {
        fill.classList.add('strength-fair');
        text.textContent = 'Fair';
        text.style.color = '#f59e0b';
    } else if (metCount <= 3) {
        fill.classList.add('strength-good');
        text.textContent = 'Good';
        text.style.color = '#3b82f6';
    } else {
        fill.classList.add('strength-strong');
        text.textContent = 'Strong';
        text.style.color = '#22c55e';
    }

    // Match check
    const matchEl = document.getElementById('matchText');
    if (confirm.length > 0) {
        if (pw === confirm) {
            matchEl.textContent = '✓ Passwords match';
            matchEl.style.color = '#22c55e';
        } else {
            matchEl.textContent = '✗ Passwords do not match';
            matchEl.style.color = '#ef4444';
        }
    } else {
        matchEl.textContent = '';
    }

    // Enable send-OTP button only when everything is valid (step 1 only)
    if (_pwdOtpStep === 1) {
        const allMet = metCount === 4 && pw === confirm && confirm.length > 0
            && document.getElementById('currentPassword').value.length > 0;
        document.getElementById('savePasswordBtn').disabled = !allMet;
    }
}

// ── Main button dispatcher ─────────────────────────────────
function savePassword() {
    const step = document.getElementById('savePasswordBtn').dataset.step || '1';
    if (step === '1') {
        requestOtp();
    } else {
        submitPasswordChange();
    }
}

// ── Step 1: Request OTP ────────────────────────────────────
async function requestOtp() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword     = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (!currentPassword || !newPassword || !confirmPassword) {
        showPwdAlert('All fields are required.', 'error');
        return;
    }
    if (newPassword !== confirmPassword) {
        showPwdAlert('New passwords do not match.', 'error');
        return;
    }

    const btn = document.getElementById('savePasswordBtn');
    btn.disabled = true;
    btn.textContent = 'Sending…';

    try {
        const token = Auth.getToken();
        const res = await fetch('../../EDUQUEST/api/students/send-otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token,
            },
            body: JSON.stringify({ action: 'send_otp' }),
        });

        const data = await res.json();

        if (!data.success) {
            showPwdAlert(data.message || 'Failed to send OTP. Please try again.', 'error');
            btn.disabled = false;
            btn.textContent = 'Send OTP to Email';
            return;
        }

        // Transition to step 2 — store passwords in state, hide the form
        _pwdOtpStep = 2;
        _storedCurrentPw = currentPassword;
        _storedNewPw = newPassword;

        document.getElementById('pwdAlert').classList.add('hidden');
        document.getElementById('passwordForm').classList.add('hidden');
        document.getElementById('pwdOtpStep').classList.remove('hidden');
        document.getElementById('otpEmailDisplay').textContent = data.maskedEmail || 'your email';

        // Update button
        btn.textContent = 'Verify & Update Password';
        btn.disabled = true;
        btn.dataset.step = '2';

        // Wire OTP input
        const otpInput = document.getElementById('otpCode');
        otpInput.value = '';
        otpInput.removeEventListener('input', _onOtpInput);
        otpInput.addEventListener('input', _onOtpInput);
        setTimeout(() => otpInput.focus(), 100);

        // Start countdown (10 minutes = 600 seconds)
        _startCountdown(600);

        // Wire resend button
        const resendBtn = document.getElementById('resendOtpBtn');
        resendBtn.onclick = () => {
            _resetPasswordModal();
            // Re-fill password fields so user doesn't need to retype
            document.getElementById('currentPassword').value = currentPassword;
            document.getElementById('newPassword').value = newPassword;
            document.getElementById('confirmPassword').value = confirmPassword;
            validatePasswordLive();
        };

    } catch (err) {
        showPwdAlert('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.textContent = 'Send OTP to Email';
    }
}

function _onOtpInput(e) {
    // Only allow digits
    e.target.value = e.target.value.replace(/[^0-9]/g, '');
    const btn = document.getElementById('savePasswordBtn');
    btn.disabled = e.target.value.length !== 6;
}

function _startCountdown(seconds) {
    clearInterval(_countdownTimer);
    const countdownEl = document.getElementById('otpCountdown');
    const resendBtn   = document.getElementById('resendOtpBtn');

    let remaining = seconds;

    const tick = () => {
        const m = Math.floor(remaining / 60);
        const s = remaining % 60;
        countdownEl.textContent = `Expires in ${m}:${String(s).padStart(2, '0')}`;
        countdownEl.classList.toggle('expiring-soon', remaining <= 60);

        if (remaining <= 0) {
            clearInterval(_countdownTimer);
            countdownEl.textContent = 'Code expired.';
            countdownEl.classList.add('expiring-soon');
            document.getElementById('savePasswordBtn').disabled = true;
            resendBtn.classList.remove('hidden');
        }
        remaining--;
    };

    tick();
    _countdownTimer = setInterval(tick, 1000);

    // Show resend link after 60 seconds
    setTimeout(() => resendBtn.classList.remove('hidden'), 60000);
}

// ── Step 2: Submit password change with OTP ────────────────
async function submitPasswordChange() {
    const otpCode = document.getElementById('otpCode').value.trim();
    const btn     = document.getElementById('savePasswordBtn');

    if (otpCode.length !== 6) {
        showPwdAlert('Please enter the 6-digit code.', 'error');
        return;
    }

    if (!_storedCurrentPw || !_storedNewPw) {
        showPwdAlert('Session expired. Please start over.', 'error');
        _resetPasswordModal();
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Verifying…';

    try {
        const token = Auth.getToken();
        const res = await fetch('../../EDUQUEST/api/students/profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token,
            },
            body: JSON.stringify({
                action: 'change_password',
                current_password: _storedCurrentPw,
                new_password: _storedNewPw,
                otp_code: otpCode,
            }),
        });

        const data = await res.json();

        if (data.success) {
            clearInterval(_countdownTimer);
            showNotification('Password updated successfully! 🎉', 'success');
            closePasswordModal();
        } else {
            showPwdAlert(data.message || 'Failed to change password.', 'error');
            // Clear the input so the user can type the correct code fresh
            const otpInput = document.getElementById('otpCode');
            otpInput.value = '';
            btn.disabled = true;        // disabled until they type 6 digits again
            btn.textContent = 'Verify & Update Password';
            setTimeout(() => otpInput.focus(), 50);
        }
    } catch (err) {
        showPwdAlert('Network error. Please try again.', 'error');
        const otpInput = document.getElementById('otpCode');
        otpInput.value = '';
        btn.disabled = true;
        btn.textContent = 'Verify & Update Password';
        setTimeout(() => otpInput.focus(), 50);
    }
}

function showPwdAlert(msg, type) {
    const el = document.getElementById('pwdAlert');
    el.textContent = msg;
    el.className = `pwd-alert pwd-alert-${type}`;
    el.classList.remove('hidden');
}

function setupSettingsListeners() {
    // Quest Notifications
    document.getElementById('questNotifications').addEventListener('change', function(e) {
        const enabled = e.target.checked;
        savePreference('questNotifications', enabled);
        showNotification(`Quest notifications ${enabled ? 'enabled' : 'disabled'}`, 'info');
    });

    // Achievement Pop-ups — restore saved state, then listen for changes
    const achievementToggle = document.getElementById('achievementPopups');
    const user = JSON.parse(localStorage.getItem('eduquest_user') || '{}');
    const savedPref = user && user.preferences && user.preferences.achievementPopups;
    if (savedPref === false) achievementToggle.checked = false;
    achievementToggle.addEventListener('change', function(e) {
        const enabled = e.target.checked;
        savePreference('achievementPopups', enabled);
        showNotification(`Achievement pop-ups ${enabled ? 'enabled' : 'disabled'}`, 'info');
    });
}

function savePreference(key, value) {
    const user = Auth.getUser();
    if (!user.preferences) {
        user.preferences = {};
    }
    user.preferences[key] = value;
    Storage.set('eduquest_user', user);
}

function awardXP(amount) {
    // Use server-backed gamification if available
    if (window.EduGamification) {
        EduGamification.trackActivity({
            activityType: 'activity',
            title: 'Profile update',
            score: 100,
            maxScore: 100,
            attempts: 1,
        });
        return;
    }

    // Fallback to localStorage-only
    const progress = Storage.get('student_progress');
    if (!progress) return;
    
    progress.xp += amount;
    
    // Check for level up
    const requiredXP = progress.level * 400;
    if (progress.xp >= requiredXP) {
        progress.level++;
        progress.xp -= requiredXP;
        showNotification(`🎉 Level Up! You are now Level ${progress.level}!`, 'success');
    }
    
    Storage.set('student_progress', progress);
    
    // Refresh display
    const user = Auth.getUser();
    updateProfileDisplay(user, progress);
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${getNotificationIcon(type)}</span>
            <span class="notification-message">${message}</span>
        </div>
        <button class="notification-close">&times;</button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto dismiss after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
    
    // Close button
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
}

function getNotificationIcon(type) {
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    return icons[type] || icons.info;
}

// Helper objects (if not loaded from helpers.js)
if (typeof Storage === 'undefined') {
    window.Storage = {
        get: (key) => {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : null;
        },
        set: (key, value) => {
            localStorage.setItem(key, JSON.stringify(value));
        }
    };
}

if (typeof Auth === 'undefined') {
    window.Auth = {
        getUser: () => Storage.get('eduquest_user'),
        isAuthenticated: () => !!Storage.get('eduquest_user')
    };
}
