<?php
$pageTitle = 'Create Account';
$bodyClass = 'auth-page';
$extraScripts = '<script src="js/auth.js"></script>';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$supportedCurrencies = [];
$currenciesPath = __DIR__ . '/data/currencies.json';
if (file_exists($currenciesPath)) {
    $raw = file_get_contents($currenciesPath);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $supportedCurrencies = $decoded;
    }
}

$defaultCurrency = 'EUR';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create Account - SpendSmart</title>
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
                        <rect width="32" height="32" rx="16" fill="url(#brandGradientRegister)"/>
                        <path d="M19.33 11.83H24.33V16.83" stroke="white" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M24.33 11.83L17.25 18.92L13.08 14.75L7.67 20.17" stroke="white" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                        <defs>
                            <linearGradient id="brandGradientRegister" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
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
                        <h1>Create your account</h1>
                        <p>Start tracking your expenses today</p>
                    </div>

                    <div id="registerAlert" class="alert" role="alert" aria-live="polite"></div>

                    <form id="register-form" novalidate>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="regFirstName" class="form-label">
                                    <svg class="form-label-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    First Name
                                </label>
                                <input type="text" id="regFirstName" class="form-input" placeholder="First name" required aria-required="true" />
                                <div class="field-error" id="regFirstNameError"></div>
                            </div>
                            <div class="form-group">
                                <label for="regLastName" class="form-label">
                                    <svg class="form-label-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    Last Name
                                </label>
                                <input type="text" id="regLastName" class="form-input" placeholder="Last name" required aria-required="true" />
                                <div class="field-error" id="regLastNameError"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="regEmail" class="form-label">
                                <svg class="form-label-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                                Email Address
                            </label>
                            <input type="email" id="regEmail" class="form-input" placeholder="Enter your email" required aria-required="true" autocomplete="email" />
                            <div class="field-error" id="regEmailError"></div>
                        </div>

                        <div class="form-group">
                            <label for="regDefaultCurrency" class="form-label">
                                <svg class="form-label-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M2 12h20"/>
                                    <path d="M12 2a15.3 15.3 0 0 1 0 20"/>
                                    <path d="M12 2a15.3 15.3 0 0 0 0 20"/>
                                </svg>
                                Default Currency
                            </label>
                            <select id="regDefaultCurrency" class="form-select" required aria-required="true">
                                <?php if (is_array($supportedCurrencies) && count($supportedCurrencies) > 0): ?>
                                    <?php foreach ($supportedCurrencies as $cur): ?>
                                        <?php
                                            if (!is_array($cur)) continue;
                                            $code = strtoupper(trim((string)($cur['code'] ?? '')));
                                            if ($code === '') continue;
                                            $name = (string)($cur['name'] ?? $code);
                                            $symbol = (string)($cur['symbol'] ?? $code);
                                            $selected = ($code === $defaultCurrency) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($code . ' (' . $symbol . ') - ' . $name, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="EUR" selected>EUR (€) - Euro</option>
                                    <option value="USD">USD ($) - US Dollar</option>
                                <?php endif; ?>
                            </select>
                            <div class="field-error" id="regCurrencyError"></div>
                        </div>

                        <div class="form-group">
                            <label for="regPassword" class="form-label">
                                <svg class="form-label-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                                Password
                            </label>
                            <div class="form-input-wrapper">
                                <input type="password" id="regPassword" class="form-input" placeholder="Min 6 chars, include letters (not numbers only)" required aria-required="true" autocomplete="new-password" />
                                <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="field-error" id="regPasswordError"></div>
                        </div>
                        <div class="form-group">
                            <label for="regConfirmPassword" class="form-label">
                                <svg class="form-label-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                                Confirm Password
                            </label>
                            <div class="form-input-wrapper">
                                <input type="password" id="regConfirmPassword" class="form-input" placeholder="Re-enter your password" required aria-required="true" autocomplete="new-password" />
                                <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="field-error" id="regConfirmPasswordError"></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg btn-block" id="registerBtn">Create Account &rarr;</button>
                    </form>

                    <div class="auth-divider">
                        <span>Already have an account?</span>
                    </div>

                    <a href="login.php" class="btn btn-outline btn-lg btn-block">Sign in instead &rarr;</a>

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
                <span class="auth-trust-badge auth-trust-encrypted">🔒 Encrypted</span>
                <span class="auth-trust-badge auth-trust-secure">🛡️ Secure</span>
                <span class="auth-trust-badge auth-trust-verified">✅ Verified</span>
            </div>

            <p class="auth-copyright">&copy; 2026 SpendSmart. All rights reserved.</p>
        </div>
    </main>

    <script src="js/auth.js"></script>
</body>
</html>
