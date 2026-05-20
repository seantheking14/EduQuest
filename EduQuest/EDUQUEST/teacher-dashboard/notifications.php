<?php
/**
 * Notification Bell — reusable PHP include
 *
 * Drop this inside any teacher-dashboard page-header:
 *   <?php require_once 'notifications.php'; ?>
 *
 * Requires auth-guard.js to have been loaded on the page (sets window.EQ.token).
 * API paths below are relative to pages in EDUQUEST/teacher-dashboard/.
 *
 * For a page at a different depth, define NOTIF_API_BASE before including:
 *   define('NOTIF_API_BASE', '../api/notifications');
 *   require_once 'notifications.php';
 */

$_notifApiBase = defined('NOTIF_API_BASE') ? NOTIF_API_BASE : '../api/notifications';
?>
<style>
/* ── Notification Bell component ───────────────────────────── */
.notif-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
}
.notif-bell-btn {
  background: none;
  border: 1px solid #e2e8f0;
  cursor: pointer;
  font-size: 1.2rem;
  padding: .4rem .6rem;
  border-radius: 8px;
  position: relative;
  color: #475569;
  display: flex;
  align-items: center;
  gap: .25rem;
  line-height: 1;
  transition: background .15s, border-color .15s;
}
.notif-bell-btn:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}
.notif-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  background: #ef4444;
  color: #fff;
  font-size: .6rem;
  font-weight: 700;
  line-height: 1;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  border-radius: 999px;
  display: none;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}
.notif-dropdown {
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  width: 340px;
  max-height: 430px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, .14);
  border: 1px solid #e2e8f0;
  display: none;
  flex-direction: column;
  overflow: hidden;
  z-index: 9999;
}
.notif-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .7rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  flex-shrink: 0;
}
.notif-panel-header span {
  font-weight: 600;
  font-size: .88rem;
  color: #1e2a3b;
}
.notif-mark-all-btn {
  background: none;
  border: none;
  font-size: .76rem;
  color: #3b82f6;
  cursor: pointer;
  padding: 0;
  font-family: inherit;
}
.notif-mark-all-btn:hover { text-decoration: underline; }
.notif-list {
  overflow-y: auto;
  flex: 1;
}
.notif-item {
  padding: .7rem 1rem .7rem 1.1rem;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: background .1s;
  border-left: 3px solid transparent;
  user-select: none;
}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover  { background: #f8fafc; }
.notif-item.unread { border-left-color: #3b82f6; background: #eff6ff; }
.notif-item.unread:hover { background: #dbeafe; }
.notif-item-msg {
  font-size: .84rem;
  color: #1e293b;
  line-height: 1.45;
  margin-bottom: .2rem;
}
.notif-item-time {
  font-size: .71rem;
  color: #94a3b8;
}
.notif-empty {
  padding: 2.2rem 1rem;
  text-align: center;
  color: #94a3b8;
  font-size: .84rem;
}
</style>

<div class="notif-wrapper" id="notifWrapper">
  <button class="notif-bell-btn" id="notifBellBtn" aria-label="Notifications" title="Notifications">
    &#128276;
    <span class="notif-badge" id="notifBadge"></span>
  </button>

  <div class="notif-dropdown" id="notifDropdown">
    <div class="notif-panel-header">
      <span>Notifications</span>
      <button class="notif-mark-all-btn" id="notifMarkAll">Mark all as read</button>
    </div>
    <div class="notif-list" id="notifList">
      <div class="notif-empty">Loading&hellip;</div>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  var API_FETCH = '<?= htmlspecialchars($_notifApiBase, ENT_QUOTES) ?>/fetch.php';
  var API_MARK  = '<?= htmlspecialchars($_notifApiBase, ENT_QUOTES) ?>/mark-read.php';
  var POLL_MS   = 30000; // 30 seconds

  var pollTimer = null;
  var isOpen    = false;

  // ── Auth token (set by auth-guard.js before DOMContentLoaded) ──
  function getToken() {
    return (window.EQ && window.EQ.token) ? window.EQ.token : null;
  }

  function authHeaders() {
    var t = getToken();
    return {
      'Content-Type'  : 'application/json',
      'Authorization' : t ? 'Bearer ' + t : ''
    };
  }

  // ── Relative time ────────────────────────────────────────────
  function parseNotificationTime(rawStr) {
    if (!rawStr) return null;
    // DB timestamps are stored in UTC; append Z when timezone is missing.
    var normalized = String(rawStr).trim().replace(' ', 'T');
    if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/.test(normalized)) {
      normalized += 'Z';
    }
    var d = new Date(normalized);
    return isNaN(d.getTime()) ? null : d;
  }

  function formatLocalDateTime(rawStr) {
    var d = parseNotificationTime(rawStr);
    if (!d) return String(rawStr || '');
    return d.toLocaleString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit'
    });
  }

  function relativeTime(rawStr) {
    var then = parseNotificationTime(rawStr);
    if (!then) return String(rawStr || '');
    var diff = Math.floor((Date.now() - then.getTime()) / 1000);
    if (diff < -300) return formatLocalDateTime(rawStr); // 5 min tolerance for clock skew
    if (diff < 60)     return 'just now';
    if (diff < 3600)   return Math.floor(diff / 60)   + ' min ago';
    if (diff < 86400)  return Math.floor(diff / 3600) + ' hr ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return formatLocalDateTime(rawStr);
  }

  // ── Escape HTML ───────────────────────────────────────────────
  function esc(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // ── Update badge ──────────────────────────────────────────────
  function updateBadge(count) {
    var badge = document.getElementById('notifBadge');
    if (count > 0) {
      badge.textContent  = count > 99 ? '99+' : String(count);
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  }

  // ── Render notification list ──────────────────────────────────
  function renderList(notifications) {
    var list = document.getElementById('notifList');
    if (!notifications || notifications.length === 0) {
      list.innerHTML = '<div class="notif-empty">No notifications yet</div>';
      return;
    }

    list.innerHTML = notifications.map(function (n) {
      var cls  = n.is_read ? 'notif-item' : 'notif-item unread';
      var time = relativeTime(n.created_at_raw || n.created_at);
      return '<div class="' + cls + '" data-id="' + n.id + '" data-link="' + esc(n.link || '') + '">'
        + '<div class="notif-item-msg">' + esc(n.message) + '</div>'
        + '<div class="notif-item-time">' + time + '</div>'
        + '</div>';
    }).join('');

    // Per-item click: mark read then redirect
    list.querySelectorAll('.notif-item').forEach(function (el) {
      el.addEventListener('click', function () {
        var id   = parseInt(el.dataset.id, 10);
        var link = el.dataset.link;
        el.classList.remove('unread');
        markRead(id, function () {
          if (link) window.location.href = link;
        });
      });
    });
  }

  // ── Fetch from server ─────────────────────────────────────────
  function fetchNotifications() {
    if (!getToken()) return;

    fetch(API_FETCH, {
      method      : 'GET',
      headers     : authHeaders(),
      credentials : 'same-origin'
    })
    .then(function (r) {
      if (r.status === 401) { stopPolling(); return null; }
      return r.json();
    })
    .then(function (data) {
      if (!data || !data.success) return;
      updateBadge(data.unread_count);
      renderList(data.notifications);
    })
    .catch(function () { /* network error — fail silently */ });
  }

  // ── Mark read (single or all) ─────────────────────────────────
  function markRead(notificationId, callback) {
    var body = notificationId ? { notification_id: notificationId } : {};

    fetch(API_MARK, {
      method      : 'POST',
      headers     : authHeaders(),
      credentials : 'same-origin',
      body        : JSON.stringify(body)
    })
    .then(function () {
      fetchNotifications();
      if (callback) callback();
    })
    .catch(function () {
      if (callback) callback();
    });
  }

  // ── Polling ───────────────────────────────────────────────────
  function startPolling() {
    fetchNotifications();
    pollTimer = setInterval(fetchNotifications, POLL_MS);
  }

  function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
  }

  // ── Dropdown open / close ─────────────────────────────────────
  function openDropdown() {
    document.getElementById('notifDropdown').style.display = 'flex';
    isOpen = true;
    // Mark all as read when the panel is opened
    markRead(null, function () { updateBadge(0); });
  }

  function closeDropdown() {
    document.getElementById('notifDropdown').style.display = 'none';
    isOpen = false;
  }

  // ── Initialise ────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('notifBellBtn').addEventListener('click', function (e) {
      e.stopPropagation();
      isOpen ? closeDropdown() : openDropdown();
    });

    document.getElementById('notifMarkAll').addEventListener('click', function (e) {
      e.stopPropagation();
      markRead(null, function () { updateBadge(0); });
    });

    // Close when clicking outside the wrapper
    document.addEventListener('click', function (e) {
      if (isOpen && !document.getElementById('notifWrapper').contains(e.target)) {
        closeDropdown();
      }
    });

    startPolling();
  });
}());
</script>
