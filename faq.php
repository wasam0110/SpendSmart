<?php
$pageTitle = 'Frequently Asked Questions';
$bodyClass = 'faq-page';
$extraScripts = '<script src="js/landing.js"></script>';
require_once 'includes/header.php';
?>

    <main id="main-content">
        <section class="faq-hero">
            <div class="container">
                <h1 class="faq-main-title">Frequently Asked Questions</h1>
                <p class="faq-main-subtitle">Find answers to common questions about SpendSmart. Can't find what you're looking for? <a href="mailto:support@spendsmart.com" class="text-link">Contact us</a></p>
            </div>
        </section>

        <section class="faq-section">
            <div class="container faq-container">

                <!-- Getting Started -->
                <div class="faq-category">
                    <h2 class="faq-category-title">Getting Started</h2>
                    <div class="faq-list">
                        <details class="faq-item">
                            <summary class="faq-question">What is SpendSmart?</summary>
                            <div class="faq-answer">
                                <p>SpendSmart is an expense tracking web application that helps you manage your personal finances. You can track income, categorize expenses, view detailed reports, and make informed financial decisions.</p>
                            </div>
                        </details>
                        <details class="faq-item">
                            <summary class="faq-question">Do I need to create an account?</summary>
                            <div class="faq-answer">
                                <p>No, you can try SpendSmart using Guest Mode without creating an account. However, guest data is temporary and will not be saved. We recommend creating a free account to save and access your data anytime.</p>
                            </div>
                        </details>
                        <details class="faq-item">
                            <summary class="faq-question">What's the difference between Guest Mode and registered accounts?</summary>
                            <div class="faq-answer">
                                <p>Guest Mode lets you explore the app with sample data, but nothing is saved when you leave. Registered accounts save all your transactions, categories, and preferences so you can access them anytime.</p>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Features -->
                <div class="faq-category">
                    <h2 class="faq-category-title">Features</h2>
                    <div class="faq-list">
                        <details class="faq-item">
                            <summary class="faq-question">Can I add multiple transactions?</summary>
                            <div class="faq-answer">
                                <p>Yes, you can add as many transactions as you need. Each transaction can be categorized, dated, and assigned a currency. You can also edit or delete transactions at any time.</p>
                            </div>
                        </details>
                        <details class="faq-item">
                            <summary class="faq-question">How do I view my spending reports?</summary>
                            <div class="faq-answer">
                                <p>Navigate to the Reports tab in your dashboard. You'll see charts showing your income vs expenses over time, category breakdowns, and a detailed transaction list. You can also filter by date range, type, and category.</p>
                            </div>
                        </details>
                        <details class="faq-item">
                            <summary class="faq-question">Can I create custom categories?</summary>
                            <div class="faq-answer">
                                <p>Yes! Go to the Categories tab and click "Add Category". You can choose a name, icon, and color for each category. You can also edit or delete existing categories.</p>
                            </div>
                        </details>
                        <details class="faq-item">
                            <summary class="faq-question">Can I edit or delete transactions?</summary>
                            <div class="faq-answer">
                                <p>Absolutely. In the Transactions tab, each transaction has Edit and Delete buttons. You can modify any field or remove a transaction entirely.</p>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Data & Privacy -->
                <div class="faq-category">
                    <h2 class="faq-category-title">Data &amp; Privacy</h2>
                    <div class="faq-list">
                        <details class="faq-item">
                            <summary class="faq-question">Is my data safe?</summary>
                            <div class="faq-answer">
                                <p>Your data is stored securely on the server. Passwords are hashed using industry-standard algorithms. We never share your personal information with third parties.</p>
                            </div>
                        </details>
                        <details class="faq-item">
                            <summary class="faq-question">What happens to my Guest Mode data when I close the app?</summary>
                            <div class="faq-answer">
                                <p>Guest Mode data is temporary and exists only for the duration of your session. Once you close the browser or your session expires, the sample data is reset.</p>
                            </div>
                        </details>
                        <details class="faq-item">
                            <summary class="faq-question">Can I export my data?</summary>
                            <div class="faq-answer">
                                <p>Currently, data export is not available but is planned for a future update. Your data remains accessible in your account at any time.</p>
                            </div>
                        </details>
                        <details class="faq-item">
                            <summary class="faq-question">How is my personal information used?</summary>
                            <div class="faq-answer">
                                <p>Your personal information (name, email) is used solely for account management. We do not use your data for advertising or share it with external services.</p>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Account & Billing -->
                <div class="faq-category">
                    <h2 class="faq-category-title">Account &amp; Billing</h2>
                    <div class="faq-list">
                        <details class="faq-item">
                            <summary class="faq-question">Is SpendSmart free?</summary>
                            <div class="faq-answer">
                                <p>Yes, SpendSmart is completely free to use. There are no hidden costs, subscriptions, or premium tiers. All features are available to every user.</p>
                            </div>
                        </details>
                        <details class="faq-item">
                            <summary class="faq-question">What happens if I forget my password?</summary>
                            <div class="faq-answer">
                                <p>Please contact support to reset your password. Password recovery via email is planned for a future update.</p>
                            </div>
                        </details>
                        <details class="faq-item">
                            <summary class="faq-question">Can I delete my account?</summary>
                            <div class="faq-answer">
                                <p>Please contact support to request account deletion. All your data will be permanently removed from our servers.</p>
                            </div>
                        </details>
                        <details class="faq-item">
                            <summary class="faq-question">Can I have multiple accounts?</summary>
                            <div class="faq-answer">
                                <p>Each email address can only be associated with one account. If you need separate accounts for different purposes, you can register with different email addresses.</p>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Troubleshooting -->
                <div class="faq-category">
                    <h2 class="faq-category-title">Troubleshooting</h2>
                    <div class="faq-list">
                        <details class="faq-item">
                            <summary class="faq-question">The app is loading slowly, what should I do?</summary>
                            <div class="faq-answer">
                                <p>Try clearing your browser cache and refreshing the page. If the problem persists, check your internet connection or try a different browser.</p>
                            </div>
                        </details>
                        <details class="faq-item">
                            <summary class="faq-question">My transaction didn't save, what happened?</summary>
                            <div class="faq-answer">
                                <p>Ensure all required fields are filled in correctly. If you're in Guest Mode, data is not permanently saved. Check your internet connection and try again.</p>
                            </div>
                        </details>
                        <details class="faq-item">
                            <summary class="faq-question">How do I contact support?</summary>
                            <div class="faq-answer">
                                <p>You can reach our support team by emailing support@spendsmart.com. We aim to respond within 24 hours.</p>
                            </div>
                        </details>
                    </div>
                </div>

            </div>
        </section>

        <!-- CTA Section -->
        <section class="faq-cta">
            <div class="container">
                <div class="faq-cta-card custom-cta-gradient">
                    <h2 class="faq-cta-title">Still have questions?</h2>
                    <p class="faq-cta-text">Get in touch with our support team or start using SpendSmart today.</p>
                    <div class="faq-cta-buttons">
                        <a href="app/guest.php" class="btn custom-cta-btn custom-cta-btn-secondary">Try Guest Mode</a>
                        <a href="register.php" class="btn custom-cta-btn custom-cta-btn-primary">Create Account</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php require_once 'includes/footer.php'; ?>
