/**
 * SpendSmart - Authentication JavaScript
 * Handles login and registration form submission
 * Client-side validation before sending to PHP backend
 */
(function () {
    'use strict';

    // --- Password Toggle ---
    var toggleBtns = document.querySelectorAll('.password-toggle');
    toggleBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.parentElement.querySelector('input');
            if (input.type === 'password') {
                input.type = 'text';
                btn.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                btn.setAttribute('aria-label', 'Show password');
            }
        });
    });

    // --- Helper: Show Alert ---
    function showAlert(elementId, message, type) {
        var alert = document.getElementById(elementId);
        if (!alert) return;
        alert.textContent = message;
        alert.className = 'alert show alert-' + type;
    }

    function hideAlert(elementId) {
        var alert = document.getElementById(elementId);
        if (alert) alert.className = 'alert';
    }

    // --- Helper: Show field error ---
    function showFieldError(errorId, message) {
        var el = document.getElementById(errorId);
        if (el) {
            el.textContent = message;
            el.classList.add('show');
        }
    }

    function clearFieldErrors() {
        var errors = document.querySelectorAll('.field-error');
        errors.forEach(function (e) { e.classList.remove('show'); e.textContent = ''; });
        var inputs = document.querySelectorAll('.form-input.error');
        inputs.forEach(function (i) { i.classList.remove('error'); });
    }

    // --- Helper: Email validation ---
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // --- Helper: Password strength validation ---
    function validatePasswordStrength(password) {
        if (!password) return 'Password is required';
        if (/\s/.test(password)) return 'Password must not contain spaces';
        if (password.length < 6) return 'Password is too weak. Use at least 6 characters and include letters (not numbers only).';
        if (!/[A-Za-z]/.test(password)) {
            return 'Password is too weak. Use at least 6 characters and include letters (not numbers only).';
        }
        if (/^[0-9]+$/.test(password)) {
            return 'Password is too weak. Use at least 6 characters and include letters (not numbers only).';
        }
        return '';
    }

    // ==================== LOGIN FORM ====================
    var loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            clearFieldErrors();
            hideAlert('loginAlert');

            var email = document.getElementById('loginEmail').value.trim();
            var password = document.getElementById('loginPassword').value;
            var valid = true;

            // Validate email
            if (!email) {
                showFieldError('loginEmailError', 'Email is required');
                document.getElementById('loginEmail').classList.add('error');
                valid = false;
            } else if (!isValidEmail(email)) {
                showFieldError('loginEmailError', 'Please enter a valid email address');
                document.getElementById('loginEmail').classList.add('error');
                valid = false;
            }

            // Validate password
            if (!password) {
                showFieldError('loginPasswordError', 'Password is required');
                document.getElementById('loginPassword').classList.add('error');
                valid = false;
            }

            if (!valid) return;

            // Disable button and show loading
            var btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Signing in...';

            // Send login request to PHP backend
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'app/login.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function () {
                btn.disabled = false;
                btn.innerHTML = 'Sign In <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 8h10M9 4l4 4-4 4"/></svg>';

                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showAlert('loginAlert', 'Login successful! Redirecting...', 'success');
                        setTimeout(function () {
                            try {
                                if (window.sessionStorage) sessionStorage.setItem('spendsmart.activeTab', 'overview');
                            } catch (e) {
                                // ignore storage errors
                            }
                            window.location.href = 'dashboard.php#overview';
                        }, 500);
                    } else {
                        showAlert('loginAlert', response.message || 'Login failed', 'error');
                    }
                } catch (err) {
                    showAlert('loginAlert', 'An error occurred. Please try again.', 'error');
                }
            };

            xhr.onerror = function () {
                btn.disabled = false;
                btn.innerHTML = 'Sign In <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 8h10M9 4l4 4-4 4"/></svg>';
                showAlert('loginAlert', 'Network error. Please check your connection.', 'error');
            };

            xhr.send(JSON.stringify({ email: email, password: password }));
        });
    }

    // ==================== REGISTER FORM ====================
    var registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            e.preventDefault();
            clearFieldErrors();
            hideAlert('registerAlert');

            var firstName = document.getElementById('regFirstName').value.trim();
            var lastName = document.getElementById('regLastName').value.trim();
            var email = document.getElementById('regEmail').value.trim();
            var defaultCurrencyEl = document.getElementById('regDefaultCurrency');
            var defaultCurrency = defaultCurrencyEl ? String(defaultCurrencyEl.value || '').trim() : '';
            var password = document.getElementById('regPassword').value;
            var confirmPassword = document.getElementById('regConfirmPassword').value;
            var valid = true;

            if (!firstName) {
                showFieldError('regFirstNameError', 'First name is required');
                document.getElementById('regFirstName').classList.add('error');
                valid = false;
            }
            if (!lastName) {
                showFieldError('regLastNameError', 'Last name is required');
                document.getElementById('regLastName').classList.add('error');
                valid = false;
            }
            if (!email) {
                showFieldError('regEmailError', 'Email is required');
                document.getElementById('regEmail').classList.add('error');
                valid = false;
            } else if (!isValidEmail(email)) {
                showFieldError('regEmailError', 'Please enter a valid email address');
                document.getElementById('regEmail').classList.add('error');
                valid = false;
            }
            if (!password) {
                showFieldError('regPasswordError', 'Password is required');
                document.getElementById('regPassword').classList.add('error');
                valid = false;
            } else {
                var pwMessage = validatePasswordStrength(password);
                if (pwMessage) {
                    showFieldError('regPasswordError', pwMessage);
                    document.getElementById('regPassword').classList.add('error');
                    valid = false;
                }
            }
            if (password !== confirmPassword) {
                showFieldError('regConfirmPasswordError', 'Passwords do not match');
                document.getElementById('regConfirmPassword').classList.add('error');
                valid = false;
            }

            if (!defaultCurrency) {
                showFieldError('regCurrencyError', 'Default currency is required');
                if (defaultCurrencyEl) defaultCurrencyEl.classList.add('error');
                valid = false;
            }

            if (!valid) return;

            var btn = document.getElementById('registerBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Creating account...';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'app/register.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function () {
                btn.disabled = false;
                btn.innerHTML = 'Create Account <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 8h10M9 4l4 4-4 4"/></svg>';

                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showAlert('registerAlert', 'Account created! Redirecting to dashboard...', 'success');
                        setTimeout(function () {
                            try {
                                if (window.sessionStorage) sessionStorage.setItem('spendsmart.activeTab', 'overview');
                            } catch (e) {
                                // ignore storage errors
                            }
                            window.location.href = 'dashboard.php#overview';
                        }, 500);
                    } else {
                        showAlert('registerAlert', response.message || 'Registration failed', 'error');
                    }
                } catch (err) {
                    showAlert('registerAlert', 'An error occurred. Please try again.', 'error');
                }
            };

            xhr.onerror = function () {
                btn.disabled = false;
                btn.innerHTML = 'Create Account <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 8h10M9 4l4 4-4 4"/></svg>';
                showAlert('registerAlert', 'Network error. Please check your connection.', 'error');
            };

            xhr.send(JSON.stringify({
                firstName: firstName,
                lastName: lastName,
                email: email,
                password: password,
                defaultCurrency: defaultCurrency
            }));
        });
    }
})();
