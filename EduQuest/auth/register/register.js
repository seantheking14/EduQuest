/**
 * Registration Form Handler
 * Manages role selection, form validation, and API communication
 */

class RegistrationForm {
    constructor() {
        this.selectedRole = null;
        this.passwordTouched = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupPasswordValidation();
        this.setupPasswordToggles();
        this.setupEmailValidation();
    }

    setupEventListeners() {
        // Role selector buttons
        document.querySelectorAll('.role-button').forEach(button => {
            button.addEventListener('click', () => this.selectRole(button));
        });

        // Back button
        document.getElementById('backButton').addEventListener('click', () => {
            this.goBackToRoleSelector();
        });

        // Form submission
        document.getElementById('registerForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitForm();
        });
    }

    // ── Password toggle (show/hide) for both fields ──
    setupPasswordToggles() {
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = btn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = btn.querySelector('.eye-icon');
                if (!input) return;

                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.textContent = isPassword ? '👁️‍🗨️' : '👁️';
            });
        });
    }

    // ── Real-time email validation ──
    setupEmailValidation() {
        const emailInput = document.getElementById('email');
        const emailHint = document.getElementById('emailHint');
        if (!emailInput || !emailHint) return;

        emailInput.addEventListener('input', () => {
            const val = emailInput.value.trim();
            if (!val) {
                emailHint.textContent = '';
                emailHint.className = 'field-hint';
                emailInput.classList.remove('valid', 'invalid');
                return;
            }

            if (this.isValidEmail(val)) {
                emailHint.textContent = '✓ Looks good!';
                emailHint.className = 'field-hint success';
                emailInput.classList.add('valid');
                emailInput.classList.remove('invalid');
            } else {
                emailHint.textContent = 'Enter a valid email address';
                emailHint.className = 'field-hint error';
                emailInput.classList.add('invalid');
                emailInput.classList.remove('valid');
            }
        });

        // Clear on blur if empty
        emailInput.addEventListener('blur', () => {
            if (!emailInput.value.trim()) {
                emailHint.textContent = '';
                emailHint.className = 'field-hint';
                emailInput.classList.remove('valid', 'invalid');
            }
        });
    }

    // ── Password validation + strength meter ──
    setupPasswordValidation() {
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('passwordConfirm');

        passwordInput.addEventListener('input', (e) => {
            this.passwordTouched = true;
            this.validatePassword(e.target.value);
            this.updateStrengthMeter(e.target.value);
            // Re-check match if confirm already has content
            if (confirmInput.value) {
                this.validatePasswordMatch();
            }
        });

        confirmInput.addEventListener('input', () => {
            this.validatePasswordMatch();
        });
    }

    // ── Strength meter ──
    updateStrengthMeter(password) {
        const container = document.getElementById('passwordStrength');
        const fill = document.getElementById('strengthFill');
        const label = document.getElementById('strengthLabel');

        if (!password) {
            container.classList.remove('visible');
            return;
        }

        container.classList.add('visible');

        let score = 0;
        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[!@#$%^&*()_\-+=\[\]{};:'"<>?\/\\|`~]/.test(password)) score++;

        // Remove old classes
        fill.className = 'strength-fill';
        label.className = 'strength-label';

        if (score <= 1) {
            fill.classList.add('weak');
            label.classList.add('weak');
            label.textContent = 'Weak';
        } else if (score === 2) {
            fill.classList.add('fair');
            label.classList.add('fair');
            label.textContent = 'Fair';
        } else if (score === 3) {
            fill.classList.add('good');
            label.classList.add('good');
            label.textContent = 'Good';
        } else {
            fill.classList.add('strong');
            label.classList.add('strong');
            label.textContent = 'Strong';
        }
    }

    selectRole(button) {
        // Remove active state from all buttons
        document.querySelectorAll('.role-button').forEach(b => {
            b.classList.remove('active');
        });

        // Add active state to clicked button
        button.classList.add('active');

        // Store selected role
        this.selectedRole = button.getAttribute('data-role');

        // Update role label in form
        const roleLabel = document.getElementById('roleLabel');
        const roleTitle = button.querySelector('.role-title').textContent;
        roleLabel.textContent = `Registering as a ${roleTitle}`;

        // Show registration form after a short delay
        setTimeout(() => {
            this.showRegistrationForm();
        }, 300);
    }

    showRegistrationForm() {
        const roleSelector = document.getElementById('roleSelector');
        const registrationForm = document.getElementById('registrationForm');

        roleSelector.classList.remove('active');
        registrationForm.classList.add('active');

        // Focus on first name field
        document.getElementById('firstName').focus();
    }

    goBackToRoleSelector() {
        const roleSelector = document.getElementById('roleSelector');
        const registrationForm = document.getElementById('registrationForm');

        registrationForm.classList.remove('active');
        roleSelector.classList.add('active');

        // Reset form
        document.getElementById('registerForm').reset();
        this.clearErrors();
        this.passwordTouched = false;

        // Reset strength meter
        const strengthContainer = document.getElementById('passwordStrength');
        if (strengthContainer) strengthContainer.classList.remove('visible');

        // Reset all input border colors
        document.querySelectorAll('.form-group input').forEach(input => {
            input.classList.remove('valid', 'invalid');
        });

        // Reset hints
        document.querySelectorAll('.field-hint').forEach(hint => {
            hint.textContent = '';
            hint.className = 'field-hint';
        });

        // Reset requirements
        document.querySelectorAll('.requirement').forEach(req => {
            req.classList.remove('met', 'unmet');
            const icon = req.querySelector('.requirement-icon');
            if (icon) icon.textContent = '○';
        });

        // Reset role selection
        document.querySelectorAll('.role-button').forEach(b => {
            b.classList.remove('active');
        });

        this.selectedRole = null;
    }

    validatePassword(password) {
        const requirements = {
            charRequirement: password.length >= 8,
            upperRequirement: /[A-Z]/.test(password),
            numberRequirement: /[0-9]/.test(password),
            specialRequirement: /[!@#$%^&*()_\-+=\[\]{};:'"<>?\/\\|`~]/.test(password),
        };

        const passwordInput = document.getElementById('password');

        Object.entries(requirements).forEach(([id, isMet]) => {
            const element = document.getElementById(id);
            const icon = element.querySelector('.requirement-icon');

            // Reset classes
            element.classList.remove('met', 'unmet');

            if (isMet) {
                element.classList.add('met');
                icon.textContent = '✓';
            } else if (this.passwordTouched && password.length > 0) {
                // Only show red if user has started typing
                element.classList.add('unmet');
                icon.textContent = '✗';
            } else {
                icon.textContent = '○';
            }
        });

        const allMet = Object.values(requirements).every(v => v === true);

        // Color the input border
        if (password.length === 0) {
            passwordInput.classList.remove('valid', 'invalid');
        } else if (allMet) {
            passwordInput.classList.add('valid');
            passwordInput.classList.remove('invalid');
        } else {
            passwordInput.classList.add('invalid');
            passwordInput.classList.remove('valid');
        }

        return allMet;
    }

    validatePasswordMatch() {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('passwordConfirm').value;
        const confirmInput = document.getElementById('passwordConfirm');
        const error = document.getElementById('passwordConfirmError');
        const matchHint = document.getElementById('matchHint');

        if (!confirm) {
            error.textContent = '';
            error.classList.remove('show');
            confirmInput.classList.remove('valid', 'invalid');
            if (matchHint) {
                matchHint.textContent = '';
                matchHint.className = 'field-hint';
            }
            return true;
        }

        if (password !== confirm) {
            error.textContent = 'Passwords do not match';
            error.classList.add('show');
            confirmInput.classList.add('invalid');
            confirmInput.classList.remove('valid');
            if (matchHint) {
                matchHint.textContent = '✗ Passwords don\'t match';
                matchHint.className = 'field-hint error';
            }
            return false;
        } else {
            error.textContent = '';
            error.classList.remove('show');
            confirmInput.classList.add('valid');
            confirmInput.classList.remove('invalid');
            if (matchHint) {
                matchHint.textContent = '✓ Passwords match!';
                matchHint.className = 'field-hint success';
            }
            return true;
        }
    }

    async submitForm() {
        // Clear previous errors
        this.clearErrors();

        // Validate form
        const formData = this.getFormData();
        if (!this.validateForm(formData)) {
            return;
        }

        // Disable submit button
        const submitButton = document.getElementById('submitButton');
        const btnText = submitButton.querySelector('.btn-text');
        const btnLoader = submitButton.querySelector('.btn-loader');
        submitButton.disabled = true;
        if (btnText) btnText.style.display = 'none';
        if (btnLoader) btnLoader.style.display = 'inline-flex';

        try {
            // Prepare registration payload
            const payload = formData;

            // Send registration request — use path relative to this HTML file
            const registerUrl = '../../EDUQUEST/api/auth/register.php';
            const response = await fetch(registerUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            // Check response content-type
            const contentType = response.headers.get('content-type');
            
            // Read response as text first
            const responseText = await response.text();
            
            if (!contentType || !contentType.includes('application/json')) {
                console.error('Invalid content-type:', contentType);
                console.error('Response text:', responseText);
                throw new Error(`Server returned ${contentType || 'unknown'} instead of JSON. Response: ${responseText.substring(0, 100)}`);
            }

            // Parse JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text:', responseText);
                throw new Error(`Failed to parse JSON response: ${e.message}. Response: ${responseText.substring(0, 100)}`);
            }

            if (!result.success) {
                this.showFormErrors(result.errors);
                submitButton.disabled = false;
                if (btnText) btnText.style.display = '';
                if (btnLoader) btnLoader.style.display = 'none';
                return;
            }

            // Show success message
            this.showSuccessMessage(result);

            // Redirect after delay
            setTimeout(() => {
                window.location.href = `../verify-email.html?email=${encodeURIComponent(formData.email)}`;
            }, 3000);

        } catch (error) {
            console.error('Registration error:', error);
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.textContent = error.message || 'An error occurred. Please try again.';
            errorMessage.style.display = 'block';
            submitButton.disabled = false;
            if (btnText) btnText.style.display = '';
            if (btnLoader) btnLoader.style.display = 'none';
        }
    }

    getFormData() {
        return {
            firstName: document.getElementById('firstName').value.trim(),
            lastName: document.getElementById('lastName').value.trim(),
            email: document.getElementById('email').value.trim(),
            password: document.getElementById('password').value,
            passwordConfirm: document.getElementById('passwordConfirm').value,
            role: this.selectedRole,
        };
    }

    validateForm(formData) {
        const errors = {};

        // First Name
        if (!formData.firstName) {
            errors.firstName = 'First name is required';
        } else if (formData.firstName.length < 2) {
            errors.firstName = 'First name must be at least 2 characters';
        }

        // Last Name
        if (!formData.lastName) {
            errors.lastName = 'Last name is required';
        } else if (formData.lastName.length < 2) {
            errors.lastName = 'Last name must be at least 2 characters';
        }

        // Email
        if (!formData.email) {
            errors.email = 'Email is required';
        } else if (!this.isValidEmail(formData.email)) {
            errors.email = 'Please enter a valid email address';
        }

        // Password
        if (!formData.password) {
            errors.password = 'Password is required';
        } else if (!this.validatePassword(formData.password)) {
            errors.password = 'Password does not meet security requirements';
        }

        // Password Confirm
        if (formData.password !== formData.passwordConfirm) {
            errors.passwordConfirm = 'Passwords do not match';
        }

        // Terms
        if (!document.getElementById('agreeTerms').checked) {
            errors.agreeTerms = 'You must agree to the terms and conditions';
        }

        if (Object.keys(errors).length > 0) {
            this.showFormErrors(errors);
            return false;
        }

        return true;
    }

    isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    showFormErrors(errors) {
        Object.entries(errors || {}).forEach(([field, message]) => {
            const errorElement = document.getElementById(`${this.camelToSnake(field)}Error`);
            if (errorElement) {
                errorElement.textContent = Array.isArray(message) ? message[0] : message;
                errorElement.classList.add('show');
            }

            // Also mark the input as invalid
            const input = document.getElementById(this.camelToSnake(field));
            if (input) {
                input.classList.add('invalid');
                input.classList.remove('valid');
            }
        });
    }

    clearErrors() {
        document.querySelectorAll('.error-message').forEach(el => {
            el.textContent = '';
            el.classList.remove('show');
        });
        const errorBanner = document.getElementById('errorMessage');
        if (errorBanner) errorBanner.style.display = 'none';
    }

    showSuccessMessage(result) {
        const successMessage = document.getElementById('successMessage');
        successMessage.style.display = 'block';

        const messageContent = successMessage.querySelector('.message-content');
        messageContent.innerHTML = `
            <strong>✓ Account Created!</strong>
            <p>Check your email at <strong>${result.data.email}</strong> for a verification link.</p>
            <p style="margin-top: 8px; font-size: 0.85rem;">Redirecting to verification page...</p>
        `;
    }

    camelToSnake(camelCase) {
        return camelCase.replace(/[A-Z]/g, letter => letter.toLowerCase());
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    new RegistrationForm();
});
