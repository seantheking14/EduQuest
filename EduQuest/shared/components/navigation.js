// Navigation Component JavaScript

// ── Avatar map (shared with profile.js) ──
const NAV_AVATAR_MAP = {
    student: '🧑‍🎓', nerd: '🤓', cool: '😎', happy: '😊', star: '🌟',
    rocket: '🚀', fox: '🦊', cat: '🐱', dog: '🐶', panda: '🐼',
    wizard: '🧙', ninja: '🥷', astronaut: '🧑‍🚀', dragon: '🐉',
    unicorn: '🦄', phoenix: '🔥', crown: '👑', alien: '👾',
};

function resolveNavAvatar(avatarId) {
    return NAV_AVATAR_MAP[avatarId] || '🧑‍🎓';
}

document.addEventListener('DOMContentLoaded', function() {
    initNavigation();
    loadNavAvatar();
    initStudentNotifications();
});

/** Load saved avatar into the nav #navAvatar element */
function loadNavAvatar() {
    const el = document.getElementById('navAvatar');
    if (!el) return;
    try {
        const user = JSON.parse(localStorage.getItem('eduquest_user') || '{}');
        el.textContent = resolveNavAvatar(user.avatarId);
    } catch (_) {
        el.textContent = '🧑‍🎓';
    }
}

function initNavigation() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    const profileMenu = document.getElementById('profileMenu');
    const notificationBtn = document.getElementById('notificationBtn');
    
    // Mobile menu toggle
    if (navToggle && navMenu) {
        navToggle.setAttribute('aria-label', 'Toggle navigation menu');
        navToggle.setAttribute('aria-expanded', 'false');

        const closeMobileMenu = () => {
            navToggle.classList.remove('active');
            navToggle.setAttribute('aria-expanded', 'false');
            navMenu.classList.remove('active');
            document.body.classList.remove('nav-menu-open');
        };

        navToggle.addEventListener('click', function() {
            const isOpen = navMenu.classList.toggle('active');
            this.classList.toggle('active', isOpen);
            this.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            document.body.classList.toggle('nav-menu-open', isOpen);
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                closeMobileMenu();
            }
        });

        // Close menu when clicking a link
        navMenu.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeMobileMenu();
        });

        // Close if viewport expands past mobile breakpoint
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) closeMobileMenu();
        });
    }
    
    // Profile menu dropdown - close when clicking items
    if (profileMenu) {
        const dropdownItems = profileMenu.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', function() {
                // Dropdown will auto-close when mouse leaves due to CSS hover
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!profileMenu.contains(e.target)) {
                // Dropdown will auto-close when mouse leaves due to CSS hover
            }
        });
    }
    
    // Inject notification bell into .nav-actions if not already in HTML
    const navActions = document.querySelector('.nav-actions');
    if (navActions && !document.getElementById('notificationBtn')) {
        const bell = document.createElement('button');
        bell.id = 'notificationBtn';
        bell.className = 'nav-notif-bell';
        bell.setAttribute('aria-label', 'Notifications');
        bell.innerHTML = '&#128276;<span class="nav-notif-badge" id="navNotifBadge"></span>';
        const profileEl = navActions.querySelector('.nav-profile, .nav-avatar');
        if (profileEl) {
            navActions.insertBefore(bell, profileEl);
        } else {
            navActions.appendChild(bell);
        }
    }

    // Notification button
    const notificationBtnEl = document.getElementById('notificationBtn');
    if (notificationBtnEl) {
        notificationBtnEl.addEventListener('click', function(e) {
            e.stopPropagation();
            showNotificationsPanel();
        });
    }
    
    // Set active nav link based on current page
    setActiveNavLink();
}

function createProfileDropdown() {
    const user = JSON.parse(localStorage.getItem('eduquest_user') || '{}');
    const isTeacher = user.role === 'teacher';
    
    const dropdown = document.createElement('div');
    dropdown.className = 'nav-dropdown';
    dropdown.innerHTML = `
        <a href="#" class="dropdown-item">
            <span>⚙️</span>
            <span>Settings</span>
        </a>
        <a href="#" class="dropdown-item">
            <span>👤</span>
            <span>Profile</span>
        </a>
        ${isTeacher ? `
        <a href="#" class="dropdown-item">
            <span>📊</span>
            <span>Reports</span>
        </a>
        ` : `
        <a href="#" class="dropdown-item">
            <span>🏆</span>
            <span>Achievements</span>
        </a>
        `}
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item" onclick="logout(event)">
            <span>🚪</span>
            <span>Logout</span>
        </a>
    `;
    
    return dropdown;
}

// ── Notifications: real API integration ──────────────────────
const NOTIF_API_BASE = '../../EDUQUEST/api/notifications';
let _notifPollTimer  = null;
let _notifData       = [];

function _notifToken() {
    return localStorage.getItem('eq_token') || '';
}

function _notifHeaders() {
    return {
        'Content-Type' : 'application/json',
        'Authorization': 'Bearer ' + _notifToken()
    };
}

function _notifRelTime(rawStr) {
    if (!rawStr) return '';
    // Parse the GMT+8 timestamp (format: "2026-05-19 22:53:00")
    const then = new Date(rawStr.replace(' ', 'T'));
    
    // Current time: Get current UTC time, then convert to GMT+8 offset
    // GMT+8 is UTC+8 hours
    const nowUTC = Date.now();
    const gmtPlus8Offset = 8 * 60 * 60 * 1000; // 8 hours in milliseconds
    
    // Both times are in GMT+8 format, but since JavaScript treats naive timestamps as local time,
    // and we're comparing with local machine time, calculate the difference
    const diff = Math.floor((nowUTC - then.getTime()) / 1000);
    if (isNaN(diff) || diff < -300) return rawStr; // Allow 5 min tolerance for clock skew
    if (diff < 60)     return 'just now';
    if (diff < 3600)   return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400)  return Math.floor(diff / 3600) + ' hr ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return rawStr;
}

function _notifEsc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function _notifUpdateBadge(count) {
    const badge = document.getElementById('navNotifBadge');
    if (!badge) return;
    if (count > 0) {
        badge.textContent  = count > 99 ? '99+' : String(count);
        badge.style.display = 'inline-flex';
    } else {
        badge.style.display = 'none';
    }
}

function _notifRenderList(panel, items) {
    const list = panel.querySelector('.notifications-list');
    if (!list) return;
    if (!items || items.length === 0) {
        list.innerHTML = '<div class="notif-empty-msg">No notifications yet</div>';
        return;
    }
    list.innerHTML = items.map(function(n) {
        const cls = n.is_read ? 'notification-item' : 'notification-item unread';
        return '<div class="' + cls + '" data-id="' + n.id + '" data-link="' + _notifEsc(n.link || '') + '">'
            + '<div class="notification-icon">🔔</div>'
            + '<div class="notification-content">'
            + '<p class="notification-text">' + _notifEsc(n.message) + '</p>'
            + '<span class="notification-time">' + _notifRelTime(n.created_at_raw || n.created_at) + '</span>'
            + '</div></div>';
    }).join('');
    list.querySelectorAll('.notification-item').forEach(function(el) {
        el.addEventListener('click', function() {
            const id   = parseInt(el.dataset.id, 10);
            const link = el.dataset.link;
            el.classList.remove('unread');
            _notifMarkRead(id, function() { if (link) window.location.href = link; });
        });
    });
}

function _notifMarkRead(notifId, cb) {
    const body = notifId ? { notification_id: notifId } : {};
    fetch(NOTIF_API_BASE + '/mark-read.php', {
        method : 'POST',
        headers: _notifHeaders(),
        body   : JSON.stringify(body)
    }).then(function() {
        fetchStudentNotifications();
        if (cb) cb();
    }).catch(function() { if (cb) cb(); });
}

function fetchStudentNotifications() {
    if (!_notifToken()) return;
    fetch(NOTIF_API_BASE + '/fetch.php', {
        method : 'GET',
        headers: _notifHeaders()
    })
    .then(function(r) {
        if (r.status === 401) { _stopNotifPoll(); return null; }
        return r.json();
    })
    .then(function(data) {
        if (!data || !data.success) return;
        _notifData = data.notifications || [];
        _notifUpdateBadge(data.unread_count);
        // Re-render panel if open
        const panel = document.getElementById('notificationsPanel');
        if (panel) _notifRenderList(panel, _notifData);
    })
    .catch(function() {});
}

function _stopNotifPoll() {
    if (_notifPollTimer) { clearInterval(_notifPollTimer); _notifPollTimer = null; }
}

function initStudentNotifications() {
    if (!_notifToken()) return; // not logged in, skip
    fetchStudentNotifications();
    _notifPollTimer = setInterval(fetchStudentNotifications, 30000);

    // Inject bell button styles
    if (!document.getElementById('notifBellStyles')) {
        const s = document.createElement('style');
        s.id = 'notifBellStyles';
        s.textContent = [
            '.nav-notif-bell{position:relative;background:none;border:1px solid rgba(255,255,255,.3);',
            'border-radius:8px;padding:.35rem .5rem;cursor:pointer;font-size:1.1rem;color:inherit;',
            'display:inline-flex;align-items:center;transition:background .15s;}',
            '.nav-notif-bell:hover{background:rgba(255,255,255,.15);}',
            '.nav-notif-badge{position:absolute;top:2px;right:2px;background:#ef4444;color:#fff;',
            'font-size:.58rem;font-weight:700;min-width:15px;height:15px;padding:0 3px;',
            'border-radius:999px;display:none;align-items:center;justify-content:center;line-height:1;pointer-events:none;}',
            '.notif-empty-msg{padding:2rem 1rem;text-align:center;color:var(--text-light,#94a3b8);font-size:.84rem;}'
        ].join('');
        document.head.appendChild(s);
    }
}

function showNotificationsPanel() {
    // Toggle: remove if already open
    let panel = document.getElementById('notificationsPanel');
    if (panel) {
        panel.remove();
        document.removeEventListener('click', closeNotificationsOnOutsideClick);
        return;
    }

    // Mark all as read when panel opens
    _notifMarkRead(null, null);
    _notifUpdateBadge(0);

    // Create panel
    panel = document.createElement('div');
    panel.id = 'notificationsPanel';
    panel.className = 'notifications-panel';
    panel.innerHTML = [
        '<div class="notifications-header">',
        '<h3>🔔 Notifications</h3>',
        '<button class="close-btn" id="notifCloseBtn">✕</button>',
        '</div>',
        '<div class="notifications-list"><div class="notif-empty-msg">Loading&hellip;</div></div>',
        '<div class="notifications-footer">',
        '<button id="notifMarkAllBtn">Mark all as read</button>',
        '</div>'
    ].join('');
    document.body.appendChild(panel);

    panel.querySelector('#notifCloseBtn').addEventListener('click', function() {
        panel.remove();
        document.removeEventListener('click', closeNotificationsOnOutsideClick);
    });
    panel.querySelector('#notifMarkAllBtn').addEventListener('click', function() {
        _notifMarkRead(null, function() { _notifUpdateBadge(0); });
    });

    _notifRenderList(panel, _notifData);

    // Add styles if not already added
    if (!document.getElementById('notificationPanelStyles')) {
        const style = document.createElement('style');
        style.id = 'notificationPanelStyles';
        style.textContent = `
            .notifications-panel {
                position: fixed;
                top: 80px;
                right: 20px;
                width: 360px;
                max-height: 500px;
                background: white;
                border-radius: var(--border-radius-lg);
                box-shadow: var(--shadow-xl);
                z-index: var(--z-dropdown);
                animation: slideInRight 0.3s ease;
                display: flex;
                flex-direction: column;
            }
            
            .notifications-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: var(--spacing-lg);
                border-bottom: 1px solid var(--border-color);
            }
            
            .notifications-header h3 {
                font-size: var(--font-lg);
                font-weight: var(--font-bold);
                color: var(--text-primary);
            }
            
            .close-btn {
                background: none;
                border: none;
                font-size: var(--font-xl);
                cursor: pointer;
                color: var(--text-secondary);
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: var(--border-radius-sm);
                transition: all var(--transition-base);
            }
            
            .close-btn:hover {
                background: var(--gray-100);
                color: var(--text-primary);
            }
            
            .notifications-list {
                overflow-y: auto;
                max-height: 400px;
            }
            
            .notification-item {
                display: flex;
                gap: var(--spacing-md);
                padding: var(--spacing-lg);
                border-bottom: 1px solid var(--border-color);
                cursor: pointer;
                transition: background var(--transition-base);
            }
            
            .notification-item:hover {
                background: var(--gray-50);
            }
            
            .notification-item.unread {
                background: #667eea08;
            }
            
            .notification-icon {
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--gray-100);
                border-radius: var(--border-radius-full);
                flex-shrink: 0;
                font-size: var(--font-xl);
            }
            
            .notification-content {
                flex: 1;
            }
            
            .notification-text {
                font-size: var(--font-sm);
                color: var(--text-primary);
                margin-bottom: var(--spacing-xs);
                line-height: 1.4;
            }
            
            .notification-time {
                font-size: var(--font-xs);
                color: var(--text-light);
            }
            
            .notifications-footer {
                padding: var(--spacing-md);
                border-top: 1px solid var(--border-color);
            }
            
            .notifications-footer button {
                width: 100%;
                padding: var(--spacing-sm);
                background: var(--gray-100);
                border: none;
                border-radius: var(--border-radius-sm);
                color: var(--text-primary);
                font-weight: var(--font-medium);
                cursor: pointer;
                transition: all var(--transition-base);
            }
            
            .notifications-footer button:hover {
                background: var(--gray-200);
            }
            
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
            
            @media (max-width: 480px) {
                .notifications-panel {
                    right: 10px;
                    left: 10px;
                    width: auto;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Close when clicking outside
    setTimeout(() => {
        document.addEventListener('click', closeNotificationsOnOutsideClick);
    }, 0);
}

function closeNotificationsOnOutsideClick(e) {
    const panel = document.getElementById('notificationsPanel');
    const notificationBtn = document.getElementById('notificationBtn');
    
    if (panel && !panel.contains(e.target) && !notificationBtn?.contains(e.target)) {
        panel.remove();
        document.removeEventListener('click', closeNotificationsOnOutsideClick);
    }
}

function setActiveNavLink() {
    const currentFile = window.location.pathname.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('.nav-menu .nav-link');

    navLinks.forEach(link => {
        const linkFile = link.getAttribute('href').split('/').pop().split('?')[0];
        if (linkFile === currentFile) {
            link.classList.add('active');
            link.setAttribute('aria-current', 'page');
        } else {
            link.classList.remove('active');
            link.removeAttribute('aria-current');
        }
    });
}

async function logout(event) {
    if (event) event.preventDefault();

    // Call server-side logout to invalidate the session token
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

    // Show brief logout message then redirect
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 2rem;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-xl);
        text-align: center;
        z-index: 10000;
    `;
    notification.innerHTML = `
        <h2 style="margin-bottom: 1rem;">👋 Logging out...</h2>
        <p>See you next time!</p>
    `;
    document.body.appendChild(notification);

    setTimeout(() => {
        window.location.href = '../../auth/login/login.html';
    }, 1000);
}

// Export functions
window.logout = logout;
window.handleLogout = logout;
window.showNotificationsPanel = showNotificationsPanel;

/* ═══════════════════════════════════════════════════════
   MOBILE BOTTOM NAVIGATION
   Reads nav-menu links from the page and creates a
   bottom nav bar for mobile devices (≤768px).
   ═══════════════════════════════════════════════════════ */
function initMobileBottomNav() {
    if (window.innerWidth > 768) return;

    const navLinks = Array.from(document.querySelectorAll('.nav-menu .nav-link'));
    if (!navLinks.length) return;

    // Determine split: first N in bar, rest in "More"
    const MAX_BAR = 5;
    const barLinks  = navLinks.slice(0, MAX_BAR);
    const moreLinks = navLinks.slice(MAX_BAR);

    // Current page for active state
    const currentFile = window.location.pathname.split('/').pop() || '';

    function isActive(href) {
        return href && href.split('/').pop().split('?')[0] === currentFile;
    }

    function buildMbnItem(link, isBtn) {
        const icon  = link.querySelector('.nav-icon')  ? link.querySelector('.nav-icon').textContent.trim()  : '🔗';
        const label = link.querySelector('span:not(.nav-icon)') ? link.querySelector('span:not(.nav-icon)').textContent.trim() : '';
        const href  = link.getAttribute('href') || '#';
        const active = isActive(href);
        const shortLabel = label.replace('My ', '').replace(' Progress', 'Progress').substring(0, 9);

        if (isBtn) {
            return '<button class="mbn-item' + (active ? ' active' : '') + '" data-href="' + href + '">'
                 + '<span class="mbn-icon">' + icon + '</span>'
                 + '<span class="mbn-label">' + shortLabel + '</span>'
                 + '</button>';
        }
        return '<a href="' + href + '" class="mbn-item' + (active ? ' active' : '') + '">'
             + '<span class="mbn-icon">' + icon + '</span>'
             + '<span class="mbn-label">' + shortLabel + '</span>'
             + '</a>';
    }

    // Build bar items HTML
    let barHTML = barLinks.map(function(l) { return buildMbnItem(l, false); }).join('');

    // If there are overflow links, add "More" button
    let moreHTML = '';
    if (moreLinks.length) {
        const anyMoreActive = moreLinks.some(function(l) { return isActive(l.getAttribute('href')); });
        barHTML += '<button class="mbn-item' + (anyMoreActive ? ' active' : '') + '" id="mbnMoreBtn">'
                 + '<span class="mbn-icon">⋯</span>'
                 + '<span class="mbn-label">More</span>'
                 + '</button>';

        moreHTML = '<div class="mbn-more-overlay" id="mbnOverlay"></div>'
                 + '<div class="mbn-more-panel" id="mbnMorePanel">'
                 + '<div class="mbn-more-handle"></div>'
                 + '<nav class="mbn-more-list">'
                 + moreLinks.map(function(l) {
                     const icon  = l.querySelector('.nav-icon') ? l.querySelector('.nav-icon').textContent.trim() : '🔗';
                     const label = l.querySelector('span:not(.nav-icon)') ? l.querySelector('span:not(.nav-icon)').textContent.trim() : '';
                     const href  = l.getAttribute('href') || '#';
                     const active = isActive(href);
                     return '<a href="' + href + '" class="mbn-more-link' + (active ? ' active' : '') + '">'
                          + '<span class="mbn-more-icon">' + icon + '</span>'
                          + '<span class="mbn-more-text">' + label + '</span>'
                          + '</a>';
                 }).join('')
                 + '</nav></div>';
    }

    // Create and append the bottom nav
    const nav = document.createElement('div');
    nav.id        = 'mobileBottomNav';
    nav.className = 'mobile-bottom-nav';
    nav.innerHTML = '<nav class="mbn-list" role="navigation" aria-label="Main navigation">' + barHTML + '</nav>';
    document.body.appendChild(nav);
    document.body.classList.add('has-bottom-nav');

    // Append more panel elements if needed
    if (moreHTML) {
        document.body.insertAdjacentHTML('beforeend', moreHTML);
        const moreBtn     = document.getElementById('mbnMoreBtn');
        const morePanel   = document.getElementById('mbnMorePanel');
        const moreOverlay = document.getElementById('mbnOverlay');

        function openMore() {
            morePanel.classList.add('open');
            moreOverlay.classList.add('open');
            moreBtn && moreBtn.setAttribute('aria-expanded', 'true');
        }
        function closeMore() {
            morePanel.classList.remove('open');
            moreOverlay.classList.remove('open');
            moreBtn && moreBtn.setAttribute('aria-expanded', 'false');
        }

        moreBtn  && moreBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            morePanel.classList.contains('open') ? closeMore() : openMore();
        });
        moreOverlay && moreOverlay.addEventListener('click', closeMore);
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeMore(); });
    }
}

// Initialise mobile bottom nav on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    // Delay slightly so nav-menu links are rendered first
    requestAnimationFrame(initMobileBottomNav);
});
