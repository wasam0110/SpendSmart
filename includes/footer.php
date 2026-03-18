    <footer class="footer">
        <div class="container footer-content">
            <div class="footer-brand">
                <a href="index.php" class="logo" aria-label="SpendSmart Home">
                    <div class="logo-icon" aria-hidden="true">
                       <svg width="24" height="24" viewBox="0 0 32 32" fill="none">

    <!-- White background -->
    <circle cx="16" cy="16" r="16" fill="white"/>

    <!-- Arrow -->
    <path d="M9.5 18.5L14.2 13.8L17.4 17L22.5 11.9"
    stroke="#2463EB"
    stroke-width="2.4"
    stroke-linecap="round"
    stroke-linejoin="round"/>

    <path d="M18.5 11.9H22.5V15.9"
    stroke="#2463EB"
    stroke-width="2.4"
    stroke-linecap="round"
    stroke-linejoin="round"/>

</svg>
                    </div>
                    <span class="logo-text">SpendSmart</span>
                </a>
            </div>
            <p class="footer-copy">&copy; <?php echo date('Y'); ?> SpendSmart. All rights reserved.</p>
        </div>
    </footer>

    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
