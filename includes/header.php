<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$isGuest = isset($_SESSION['guest_mode']) && $_SESSION['guest_mode'] === true;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - SpendSmart' : 'SpendSmart - Track Your Expenses Smartly'; ?></title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet" />
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body<?php echo isset($bodyClass) ? ' class="' . htmlspecialchars($bodyClass) . '"' : ''; ?>>
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <header class="navbar" role="banner">
        <div class="container navbar-content">
            <a href="index.php" class="logo" aria-label="SpendSmart Home">
                <div class="logo-icon" aria-hidden="true">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <rect width="32" height="32" rx="16" fill="url(#brandGradientHeader)"/>
                        <path d="M19.33 11.83H24.33V16.83" stroke="white" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M24.33 11.83L17.25 18.92L13.08 14.75L7.67 20.17" stroke="white" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                        <defs>
                            <linearGradient id="brandGradientHeader" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#2463EB"/>
                                <stop offset="1" stop-color="#10B77F"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <span class="logo-text">SpendSmart</span>
            </a>
            <nav class="nav-links" role="navigation" aria-label="Main navigation">
                <a href="index.php" class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">Home</a>
                <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
                <a href="faq.php" class="nav-link <?php echo $currentPage === 'faq' ? 'active' : ''; ?>">FAQs</a>
            </nav>
            <div class="nav-actions">
                <?php if ($isLoggedIn && !$isGuest): ?>
                    <a href="app/logout.php" class="btn btn-outline" role="button">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline" id="navLoginBtn" role="button">Login</a>
                    <a href="register.php" class="btn btn-primary" id="navSignupBtn" role="button">Sign Up</a>
                <?php endif; ?>
            </div>
            <button class="mobile-menu-btn" aria-label="Toggle menu" aria-expanded="false">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
        <div class="mobile-menu" role="navigation" aria-label="Mobile navigation" hidden>
            <a href="index.php" class="mobile-menu-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">Home</a>
            <a href="dashboard.php" class="mobile-menu-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
            <a href="faq.php" class="mobile-menu-link <?php echo $currentPage === 'faq' ? 'active' : ''; ?>">FAQs</a>
            <div class="mobile-menu-actions">
                <?php if ($isLoggedIn && !$isGuest): ?>
                    <a href="app/logout.php" class="btn btn-outline btn-block">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline btn-block">Login</a>
                    <a href="register.php" class="btn btn-primary btn-block">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
