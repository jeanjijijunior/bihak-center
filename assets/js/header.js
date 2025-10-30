/**
 * Header Functionality
 * Mobile menu toggle, scroll effects, and active language indicator
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const navbar = document.getElementById('main-navbar');
    const header = document.getElementById('main-header');

    if (mobileMenuToggle && navbar) {
        mobileMenuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            navbar.classList.toggle('active');
        });

        // Close menu when clicking nav links
        const navLinks = navbar.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenuToggle.classList.remove('active');
                navbar.classList.remove('active');
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInsideMenu = navbar.contains(event.target);
            const isClickOnToggle = mobileMenuToggle.contains(event.target);

            if (!isClickInsideMenu && !isClickOnToggle && navbar.classList.contains('active')) {
                mobileMenuToggle.classList.remove('active');
                navbar.classList.remove('active');
            }
        });
    }

    // Scroll Effect - Add shadow to header on scroll
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // Update active language indicator
    updateActiveLanguage();
});

/**
 * Update active language button styling
 */
function updateActiveLanguage() {
    // Get current language from cookie or default to 'en'
    const currentLang = getCookie('googtrans') || '/en/en';
    const langCode = currentLang.split('/')[2] || 'en';

    // Update active class
    document.querySelectorAll('.language-switcher a').forEach(link => {
        link.classList.remove('active');
    });

    const activeLink = document.getElementById('lang-' + langCode);
    if (activeLink) {
        activeLink.classList.add('active');
    }
}

/**
 * Get cookie value by name
 */
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

/**
 * Enhanced changeLanguage function
 * Updates both Google Translate and active indicator
 */
function changeLanguage(lang) {
    // Load Google Translate if not loaded
    if (!window.google || !window.google.translate) {
        loadGoogleTranslate();
    }

    // Wait for Google Translate to be ready
    const interval = setInterval(() => {
        const select = document.querySelector(".goog-te-combo");
        if (select) {
            select.value = lang;
            select.dispatchEvent(new Event("change"));
            clearInterval(interval);

            // Update active language indicator after a short delay
            setTimeout(() => {
                updateActiveLanguage();
            }, 500);
        }
    }, 500);

    // Update UI immediately for better UX
    document.querySelectorAll('.language-switcher a').forEach(link => {
        link.classList.remove('active');
    });
    const activeLink = document.getElementById('lang-' + lang);
    if (activeLink) {
        activeLink.classList.add('active');
    }
}

/**
 * Smooth scroll to top when clicking logo
 */
document.addEventListener('DOMContentLoaded', function() {
    const logo = document.querySelector('.logo a');
    if (logo) {
        logo.addEventListener('click', function(e) {
            // Only prevent default and smooth scroll if already on homepage
            const currentPage = window.location.pathname.split('/').pop();
            if (currentPage === 'index_new.php' || currentPage === 'index.php' || currentPage === '') {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                // Update URL without reload
                history.pushState(null, '', 'index_new.php');
            }
        });
    }
});

/**
 * Highlight active menu item based on current page
 */
function highlightActiveMenuItem() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-links a');

    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        if (linkPage === currentPage || (currentPage === '' && linkPage === 'index_new.php')) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// Run on page load
document.addEventListener('DOMContentLoaded', highlightActiveMenuItem);
