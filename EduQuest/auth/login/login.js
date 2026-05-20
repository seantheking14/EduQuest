/**
 * Login Form Handler
 * Unified login for both teachers and students
 */

class LoginForm {
    constructor() {
        this.selectedRole = 'student'; // Default role
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.checkRememberMe();
        this.checkURLParams();
    }

    setupEventListeners() {
        // Role buttons
        const roleButtons = document.querySelectorAll('.role-btn');
        roleButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.selectRole(button.getAttribute('data-role'));
            });
        });

        // Toggle password visibility
        document.getElementById('togglePassword')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.togglePasswordVisibility();
        });

        // Form submission
        document.getElementById('loginForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitLogin();
        });

        // Forgot password link
        document.querySelector('.forgot-link')?.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = '../forgot-password.html';
        });
    }

    checkURLParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const role = urlParams.get('role');
        if (role === 'teacher' || role === 'student') {
            this.selectRole(role);
        }
    }

    checkRememberMe() {
        // Check if there's a remember token in the cookie
        const rememberCookie = document.cookie
            .split('; ')
            .find(row => row.startsWith('eduquest_remember='));

        if (rememberCookie && this.shouldAutoLogin()) {
            this.autoLoginWithRemember();
        }
    }

    shouldAutoLogin() {
        // Only auto-login if user specifically enabled remember me
        return localStorage.getItem('eduquest_remember_me') === 'true';
    }

    async autoLoginWithRemember() {
        const loginButton = document.querySelector('.btn-login');
        loginButton.disabled = true;
        loginButton.innerHTML = '<span class="btn-loader">Logging you in...</span>';

        try {
            const response = await fetch('../../EDUQUEST/api/auth/remember.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
            });

            const result = await response.json();

            if (!result.success) {
                // Remember token invalid/expired — let user log in manually
                localStorage.removeItem('eduquest_remember_me');
                loginButton.disabled = false;
                loginButton.innerHTML = '<span class="btn-text">Sign In</span>';
                return;
            }

            // Store auth data exactly like a normal login
            localStorage.setItem('eq_token', result.data.token);
            localStorage.setItem('eduquest_user', JSON.stringify(result.data.user));

            const role = result.data.user.role;
            if ((role === 'teacher' || role === 'admin') && result.data.teacher) {
                localStorage.setItem('eq_teacher', JSON.stringify(result.data.teacher));
                localStorage.removeItem('eq_student');
            } else if (role === 'student' && result.data.student) {
                localStorage.setItem('eq_student', JSON.stringify(result.data.student));
                localStorage.removeItem('eq_teacher');
            }

            this.showSuccess('Welcome back! Logging you in...');

            setTimeout(() => {
                window.location.href = result.data.redirectUrl || '../../EDUQUEST/teacher-dashboard/dashboard.php';
            }, 800);

        } catch (error) {
            console.error('Auto-login failed:', error);
            localStorage.removeItem('eduquest_remember_me');
            loginButton.disabled = false;
            loginButton.innerHTML = '<span class="btn-text">Sign In</span>';
        }
    }

    selectRole(role) {
        this.selectedRole = role;

        // Update button states
        document.querySelectorAll('.role-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-role') === role);
        });
    }

    togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const toggle = document.getElementById('togglePassword');
        const eyeIcon = toggle.querySelector('.eye-icon');

        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        eyeIcon.textContent = isPassword ? '👁️‍🗨️' : '👁️';
    }

    async submitLogin() {
        // Get form data
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const rememberMe = document.getElementById('remember')?.checked || false;

        // Validate
        if (!email || !password) {
            this.showError('Email and password are required');
            return;
        }

        if (!this.isValidEmail(email)) {
            this.showError('Please enter a valid email address');
            return;
        }

        // Disable submit button
        const submitButton = document.querySelector('.btn-login');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="btn-loader">Signing you in...</span>';

        try {
            // Use path relative to this HTML file
            const loginUrl = '../../EDUQUEST/api/auth/login.php';
            
            // Send login request
            const bodyData = {
                email: email,
                password: password,
                role: this.selectedRole,
                rememberMe: rememberMe,
            };
            const response = await fetch(loginUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include', // Include cookies
                body: JSON.stringify(bodyData),
            });

            const result = await response.json();

            if (!result.success) {
                this.showError(result.message || 'Login failed');
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                return;
            }

            // Save remember me preference
            if (rememberMe) {
                localStorage.setItem('eduquest_remember_me', 'true');
            } else {
                localStorage.removeItem('eduquest_remember_me');
            }

            // ── Unified auth storage ──────────────────────────────
            // eq_token   : Bearer token used by EDUQUEST/pages/ (auth-guard.js)
            // eduquest_user : canonical user object used by dashboards & nav
            // eq_teacher / eq_student : role profile used by auth-guard.js
            localStorage.setItem('eq_token', result.data.token);
            localStorage.setItem('eduquest_user', JSON.stringify(result.data.user));

            const role = result.data.user.role;
            if ((role === 'teacher' || role === 'admin') && result.data.teacher) {
                localStorage.setItem('eq_teacher', JSON.stringify(result.data.teacher));
                localStorage.removeItem('eq_student');
            } else if (role === 'student' && result.data.student) {
                localStorage.setItem('eq_student', JSON.stringify(result.data.student));
                localStorage.removeItem('eq_teacher');
            }

            // Clear legacy sessionStorage entries
            sessionStorage.removeItem('eduquest_session_token');
            sessionStorage.removeItem('user_id');
            sessionStorage.removeItem('user_role');
            // ─────────────────────────────────────────────────────

            // Show success message
            this.showSuccess('Login successful!');

            // Redirect to appropriate dashboard
            setTimeout(() => {
                window.location.href = result.data.redirectUrl || '../../EDUQUEST/teacher-dashboard/dashboard.php';
            }, 1000);

        } catch (error) {
            console.error('Login error:', error);
            this.showError(error.message || 'An error occurred. Please try again.');
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    }

    isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    showError(message) {
        let errorDiv = document.getElementById('loginError');

        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'loginError';
            errorDiv.className = 'form-error';
            const form = document.getElementById('loginForm');
            form.insertBefore(errorDiv, form.firstChild);
        }

        errorDiv.textContent = message;
        errorDiv.style.display = 'block';

        // Auto-hide after 5 seconds
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }

    showSuccess(message) {
        let successDiv = document.getElementById('loginSuccess');

        if (!successDiv) {
            successDiv = document.createElement('div');
            successDiv.id = 'loginSuccess';
            successDiv.className = 'form-success';
            const form = document.getElementById('loginForm');
            form.insertBefore(successDiv, form.firstChild);
        }

        successDiv.textContent = message;
        successDiv.style.display = 'block';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    new LoginForm();
});
