<?php
$pageTitle = 'Track Your Expenses Smartly';
$bodyClass = 'landing-page';
$extraScripts = '<script src="js/landing.js"></script>';
require_once 'includes/header.php';
?>

    <main id="main-content">
        <!-- Hero Section -->
        <section class="hero">
            <div class="container hero-content">
                <div class="hero-text">
                    <span class="hero-badge"><span class="hero-badge-dot"></span> Smarter way to manage money</span>
                    <h1 class="hero-title">Track Your Expenses <span class="text-blue">Smartly</span></h1>
                    <p class="hero-description">Take control of your finances. Categorize your spending, view detailed reports, and save more for the things that matter most.</p>
                    <div class="hero-buttons">
                        <a href="app/guest.php" class="btn btn-primary btn-lg">View Demo</a>
                        <a href="register.php" class="btn btn-outline btn-lg">Get Started</a>
                    </div>
                    <div class="hero-trust">
                        <span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10B77F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="9 12 12 15 15 9"/>
                            </svg>
                            No credit card required
                        </span>
                        <span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10B77F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="9 12 12 15 15 9"/>
                            </svg>
                            Setup in 2 mins
                        </span>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="hero-image-wrapper">
                        <div class="hero-image-backdrop" aria-hidden="true"></div>
                        <div class="hero-float-card">
                            <div class="hero-float-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#00A63E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                    <polyline points="17 6 23 6 23 12"/>
                                </svg>
                            </div>
                            <div class="hero-float-text">
                                <span class="hero-float-label">Monthly Savings</span>
                                <span class="hero-float-amount">+$450.00</span>
                            </div>
                        </div>
                        <img src="img/homepage.png" alt="SpendSmart dashboard preview showing expense tracking interface" width="560" height="400" loading="eager" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features" id="features">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Everything you need to manage your money</h2>
                    <p class="section-subtitle">Simple, intuitive, and powerful tools to help you understand your spending habits.</p>
                </div>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon feature-icon-analytics">
                            <svg width="36" height="36" viewBox="0 0 56 56" fill="none" aria-hidden="true">
                                <path d="M38.4999 27.9999C39.1439 27.9999 39.6724 27.4761 39.6083 26.8356C39.3393 24.1571 38.1524 21.6541 36.2487 19.7508C34.345 17.8475 31.8416 16.6611 29.1631 16.3927C28.5214 16.3286 27.9988 16.8571 27.9988 17.5011V26.8344C27.9988 27.1438 28.1217 27.4406 28.3405 27.6594C28.5593 27.8782 28.856 28.0011 29.1654 28.0011L38.4999 27.9999Z" stroke="#2B7FFF" stroke-width="2.33333" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M38.745 32.5384C38.0028 34.2937 36.8419 35.8403 35.3639 37.0433C33.8858 38.2462 32.1356 39.0688 30.2662 39.439C28.3968 39.8092 26.4652 39.7159 24.6402 39.1672C22.8152 38.6184 21.1524 37.631 19.7972 36.2912C18.442 34.9514 17.4356 33.3 16.8661 31.4814C16.2966 29.6628 16.1812 27.7323 16.5301 25.8588C16.8789 23.9853 17.6814 22.2258 18.8674 20.7341C20.0534 19.2424 21.5867 18.064 23.3334 17.3018" stroke="#2B7FFF" stroke-width="2.33333" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="feature-title">Smart Analytics</h3>
                        <p class="feature-desc">Visualize your spending patterns with beautiful, interactive charts and graphs.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon purple">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                                <line x1="7" y1="7" x2="7.01" y2="7"/>
                            </svg>
                        </div>
                        <h3 class="feature-title">Custom Categories</h3>
                        <p class="feature-desc">Organize expenses your way. Create custom categories and track exactly where your money goes.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon green">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                <line x1="12" y1="18" x2="12.01" y2="18"/>
                            </svg>
                        </div>
                        <h3 class="feature-title">Accessible Anywhere</h3>
                        <p class="feature-desc">Access your dashboard from any device. Fully responsive design for desktop and mobile.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="how-it-works" id="how-it-works">
            <div class="container">
                <h2 class="section-title">How SpendSmart Works</h2>
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Add Transactions</h3>
                        <p class="step-desc">Log your daily expenses and income in seconds with our simple form.</p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Categorize Spending</h3>
                        <p class="step-desc">Assign categories to see exactly where your money is going.</p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h3 class="step-title">View Reports</h3>
                        <p class="step-desc">Get insights into your financial health with detailed analytics.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php require_once 'includes/footer.php'; ?>
