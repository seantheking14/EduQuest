/**
 * profile.js – Teacher Profile Page
 * Loads profile from API, handles save and password change.
 */
(function () {
  'use strict';

  // ── DOM refs ──────────────────────────────────────────────
  const alertBox     = document.getElementById('alertBox');
  const firstNameEl  = document.getElementById('firstName');
  const lastNameEl   = document.getElementById('lastName');
  const emailEl      = document.getElementById('email');
  const departmentEl = document.getElementById('department');
  const schoolEl     = document.getElementById('schoolName');
  const saveBtn      = document.getElementById('saveProfileBtn');
  const pwModal      = document.getElementById('passwordModal');
  const pwAlert      = document.getElementById('pwModalAlert');

  // ── Load profile ──────────────────────────────────────────
  async function loadProfile() {
    try {
      const res  = await fetch('../api/teachers/profile.php', {
        headers: window.EQ.authHeaders(),
      });
      const data = await res.json();

      if (!data.success) {
        showAlert(data.message || 'Failed to load profile.', 'error');
        return;
      }

      const p = data.data;

      // Header card
      document.getElementById('avatarInitials').textContent =
        (p.firstName[0] + p.lastName[0]).toUpperCase();
      document.getElementById('displayName').innerHTML =
        `${p.firstName} ${p.lastName}<span class="badge">${capitalize(p.role)}</span>`;
      document.getElementById('displayEmail').textContent  = p.email;
      document.getElementById('displaySchool').textContent = p.schoolName || '';
      document.getElementById('displayDept').textContent   = p.department  || '';
      document.getElementById('memberSince').textContent   =
        p.memberSince ? 'Member since ' + formatDate(p.memberSince) : '';

      // Form fields
      firstNameEl.value  = p.firstName  || '';
      lastNameEl.value   = p.lastName   || '';
      emailEl.value      = p.email      || '';
      departmentEl.value = p.department || '';
      schoolEl.value     = p.schoolName || '';

    } catch (err) {
      showAlert('Network error. Please try again.', 'error');
    }
  }

  // ── Save profile ──────────────────────────────────────────
  saveBtn.addEventListener('click', async () => {
    const firstName  = firstNameEl.value.trim();
    const lastName   = lastNameEl.value.trim();
    const schoolName = schoolEl.value.trim();
    const department = departmentEl.value.trim();

    if (!firstName || !lastName) {
      showAlert('First and last name are required.', 'error');
      return;
    }

    saveBtn.disabled    = true;
    saveBtn.textContent = 'Saving…';

    try {
      const res  = await fetch('../api/teachers/profile.php', {
        method:  'POST',
        headers: window.EQ.authHeaders(),
        body:    JSON.stringify({ action: 'update_profile', firstName, lastName, schoolName, department }),
      });
      const data = await res.json();

      if (data.success) {
        showAlert('Profile updated successfully.', 'success');

        // Sync local eduquest_user so the sidebar name refreshes
        const stored = JSON.parse(localStorage.getItem('eduquest_user') || '{}');
        stored.firstName = firstName;
        stored.lastName  = lastName;
        localStorage.setItem('eduquest_user', JSON.stringify(stored));

        // Sync eq_teacher
        const teacher = JSON.parse(localStorage.getItem('eq_teacher') || '{}');
        teacher.first_name = firstName;
        teacher.last_name  = lastName;
        localStorage.setItem('eq_teacher', JSON.stringify(teacher));

        // Update sidebar name
        const nameEl = document.getElementById('teacherName');
        if (nameEl) nameEl.textContent = `${firstName} ${lastName}`;

        // Refresh header card
        document.getElementById('avatarInitials').textContent =
          (firstName[0] + lastName[0]).toUpperCase();
        document.getElementById('displayName').innerHTML =
          `${firstName} ${lastName}<span class="badge" id="roleBadge">${document.getElementById('roleBadge')?.textContent || 'Teacher'}</span>`;
        document.getElementById('displaySchool').textContent = schoolName;
        document.getElementById('displayDept').textContent   = department;
      } else {
        showAlert(data.message || 'Failed to save.', 'error');
      }
    } catch {
      showAlert('Network error. Please try again.', 'error');
    } finally {
      saveBtn.disabled    = false;
      saveBtn.textContent = 'Save Changes';
    }
  });

  // ── Password modal ─────────────────────────────────────────
  document.getElementById('changePasswordBtn').addEventListener('click', () => {
    pwModal.classList.remove('hidden');
    pwAlert.classList.add('hidden');
    document.getElementById('currentPassword').value = '';
    document.getElementById('newPassword').value     = '';
    document.getElementById('confirmPassword').value = '';
  });

  document.getElementById('cancelPasswordBtn').addEventListener('click', () => {
    pwModal.classList.add('hidden');
  });

  pwModal.addEventListener('click', e => {
    if (e.target === pwModal) pwModal.classList.add('hidden');
  });

  document.getElementById('submitPasswordBtn').addEventListener('click', async () => {
    const current = document.getElementById('currentPassword').value;
    const next    = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmPassword').value;

    if (!current || !next || !confirm) {
      showPwAlert('All fields are required.', 'error');
      return;
    }
    if (next !== confirm) {
      showPwAlert('New passwords do not match.', 'error');
      return;
    }
    if (next.length < 8) {
      showPwAlert('Password must be at least 8 characters.', 'error');
      return;
    }

    const btn = document.getElementById('submitPasswordBtn');
    btn.disabled    = true;
    btn.textContent = 'Updating…';

    try {
      const res  = await fetch('../api/teachers/profile.php', {
        method:  'POST',
        headers: window.EQ.authHeaders(),
        body:    JSON.stringify({ action: 'change_password', current_password: current, new_password: next }),
      });
      const data = await res.json();

      if (data.success) {
        pwModal.classList.add('hidden');
        showAlert('Password changed successfully.', 'success');
      } else {
        showPwAlert(data.message || 'Failed to change password.', 'error');
      }
    } catch {
      showPwAlert('Network error. Please try again.', 'error');
    } finally {
      btn.disabled    = false;
      btn.textContent = 'Update Password';
    }
  });

  // ── Helpers ───────────────────────────────────────────────
  function showAlert(msg, type) {
    alertBox.textContent = msg;
    alertBox.className   = `alert alert-${type}`;
    alertBox.classList.remove('hidden');
    setTimeout(() => alertBox.classList.add('hidden'), 5000);
  }

  function showPwAlert(msg, type) {
    pwAlert.textContent = msg;
    pwAlert.className   = `alert alert-${type}`;
    pwAlert.classList.remove('hidden');
  }

  function capitalize(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
  }

  function formatDate(iso) {
    return new Date(iso).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  }

  // ── Init ──────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', loadProfile);
})();
