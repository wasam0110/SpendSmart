<?php
$pageTitle = 'Login';
$bodyClass = 'auth-page';
$extraScripts = '<script src="js/auth.js"></script>';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - SpendSmart</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
</head>
<body class="auth-page">

    <main id="main-content" class="auth-standalone">
        <a href="index.php" class="auth-back-link">&larr; Back</a>

        <div class="auth-standalone-inner">
            <div class="auth-logo">
                <div class="auth-logo-icon">
                    <svg width="40" height="40" viewBox="0 0 32 32" fill="none">
                        <rect width="32" height="32" rx="16" fill="url(#brandGradientLogin)"/>
                        <path d="M19.33 11.83H24.33V16.83" stroke="white" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M24.33 11.83L17.25 18.92L13.08 14.75L7.67 20.17" stroke="white" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                        <defs>
                            <linearGradient id="brandGradientLogin" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#2463EB"/>
                                <stop offset="1" stop-color="#10B77F"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <span class="auth-logo-text">SpendSmart</span>
            </div>

            <div class="auth-card">
                <div class="auth-accent"></div>
                <div class="auth-body">
                    <div class="auth-header">
                        <h1>Sign in to your account</h1>
                        <p>Access your dashboard and manage your finances</p>
                    </div>

                    <div id="loginAlert" class="alert" role="alert" aria-live="polite"></div>

                    <form id="login-form" novalidate>
                        <div class="form-group">
                            <label for="loginEmail" class="form-label">
                                <svg class="form-label-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                                Email Address
                            </label>
                            <input type="email" id="loginEmail" class="form-input" placeholder="Enter your email" required aria-required="true" autocomplete="email" />
                            <div class="field-error" id="loginEmailError"></div>
                        </div>
                        <div class="form-group">
                            <div class="form-label-row">
                                <label for="loginPassword" class="form-label">
                                    <svg class="form-label-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                    Password
                                </label>
                                <span class="auth-forgot-link">Forgot?</span>
                            </div>
                            <div class="form-input-wrapper">
                                <input type="password" id="loginPassword" class="form-input" placeholder="Enter your password" required aria-required="true" autocomplete="current-password" />
                                <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="field-error" id="loginPasswordError"></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg btn-block" id="loginBtn">Sign In &rarr;</button>
                    </form>

                    <div class="auth-divider">
                        <span>New user?</span>
                    </div>

                    <a href="register.php" class="btn btn-outline btn-lg btn-block">Create new account &rarr;</a>

                    <div class="auth-footer-links auth-card-footer-links">
                        <a href="#">Terms</a>
                        <a href="#">Privacy</a>
                        <a href="#">Contact</a>
                    </div>
                </div>
            </div>

            <div class="auth-status">
                <span class="auth-status-dot"></span> All systems operational
            </div>

            <div class="auth-trust-badges">
                <span class="auth-trust-badge auth-trust-encrypted">🔒 Encrypted Encrypted</span>
                <span class="auth-trust-badge auth-trust-secure">🛡️ Secure</span>
                <span class="auth-trust-badge auth-trust-verified">✅ Verified</span>
            </div>

            <p class="auth-copyright">&copy; 2026 SpendSmart. All rights reserved.</p>
        </div>
    </main>

    <script src="js/auth.js"></script>
</body>
</html>
