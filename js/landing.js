/**
 * SpendSmart - Landing Page JavaScript
 * Handles mobile menu toggle and smooth scrolling
 */
(function () {
    'use strict';

    // Mobile menu toggle
    var menuBtn = document.querySelector('.mobile-menu-btn');
    var mobileMenu = document.querySelector('.mobile-menu');

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', function () {
            var isOpen = mobileMenu.classList.contains('open');
            if (isOpen) {
                mobileMenu.classList.remove('open');
                mobileMenu.setAttribute('hidden', '');
                menuBtn.classList.remove('active');
                menuBtn.setAttribute('aria-expanded', 'false');
            } else {
                mobileMenu.classList.add('open');
                mobileMenu.removeAttribute('hidden');
                menuBtn.classList.add('active');
                menuBtn.setAttribute('aria-expanded', 'true');
            }
        });
    }

    // Close mobile menu when clicking a link
    var mobileLinks = document.querySelectorAll('.mobile-menu-link');
    mobileLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            if (mobileMenu) {
                mobileMenu.classList.remove('open');
                mobileMenu.setAttribute('hidden', '');
                menuBtn.classList.remove('active');
                menuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    });
})();
