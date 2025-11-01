/**
 * Universal Language Switcher
 * Works on ALL pages - header, footer, and content
 */

// Store current language
let currentLanguage = localStorage.getItem('language') || 'en';

// Universal translations for common elements
const universalTranslations = {
    en: {
        // Navigation
        'nav-home': 'Home',
        'nav-about': 'About',
        'nav-work': 'Our Work',
        'nav-stories': 'Stories',
        'nav-opportunities': 'Opportunities',
        'nav-contact': 'Contact',

        // Common buttons
        'back-to-stories': '← Back to All Stories',
        'read-more': 'Read More',
        'learn-more': 'Learn More',
        'apply-now': 'Apply Now',
        'save': 'Save',
        'share': 'Share',
        'contact-us': 'Contact Us',

        // Common labels
        'search': 'Search',
        'filter': 'Filter',
        'sort-by': 'Sort By',
        'loading': 'Loading...',
        'no-results': 'No results found',

        // Footer
        'footer-about-title': 'About Bihak Center',
        'footer-about-text': 'Empowering young people across Africa through education, mentorship, and opportunities. We showcase talent, provide information, and connect youth with life-changing opportunities.',
        'footer-links-title': 'Quick Links',
        'footer-link-home': 'Home',
        'footer-link-about': 'About Us',
        'footer-link-work': 'Our Work',
        'footer-link-opportunities': 'Opportunities',
        'footer-link-contact': 'Contact',
        'footer-link-signup': 'Share Your Story',
        'footer-users-title': 'For Young People',
        'footer-link-login': 'Login',
        'footer-link-register': 'Register',
        'footer-link-account': 'My Account',
        'footer-link-scholarships': 'Scholarships',
        'footer-link-jobs': 'Jobs',
        'footer-link-internships': 'Internships',
        'footer-contact-title': 'Contact Us',
        'footer-copyright': `© ${new Date().getFullYear()} Bihak Center. All rights reserved.`,
        'footer-privacy': 'Privacy Policy',
        'footer-terms': 'Terms of Service'
    },
    fr: {
        // Navigation
        'nav-home': 'Accueil',
        'nav-about': 'À Propos',
        'nav-work': 'Notre Travail',
        'nav-stories': 'Histoires',
        'nav-opportunities': 'Opportunités',
        'nav-contact': 'Contact',

        // Common buttons
        'back-to-stories': '← Retour aux Histoires',
        'read-more': 'Lire Plus',
        'learn-more': 'En Savoir Plus',
        'apply-now': 'Postuler',
        'save': 'Sauvegarder',
        'share': 'Partager',
        'contact-us': 'Contactez-nous',

        // Common labels
        'search': 'Rechercher',
        'filter': 'Filtrer',
        'sort-by': 'Trier Par',
        'loading': 'Chargement...',
        'no-results': 'Aucun résultat trouvé',

        // Footer
        'footer-about-title': 'À Propos de Bihak Center',
        'footer-about-text': 'Autonomiser les jeunes à travers l\'Afrique par l\'éducation, le mentorat et les opportunités. Nous mettons en valeur les talents, fournissons des informations et connectons les jeunes à des opportunités qui changent la vie.',
        'footer-links-title': 'Liens Rapides',
        'footer-link-home': 'Accueil',
        'footer-link-about': 'À Propos',
        'footer-link-work': 'Notre Travail',
        'footer-link-opportunities': 'Opportunités',
        'footer-link-contact': 'Contact',
        'footer-link-signup': 'Partagez Votre Histoire',
        'footer-users-title': 'Pour les Jeunes',
        'footer-link-login': 'Connexion',
        'footer-link-register': 'S\'inscrire',
        'footer-link-account': 'Mon Compte',
        'footer-link-scholarships': 'Bourses',
        'footer-link-jobs': 'Emplois',
        'footer-link-internships': 'Stages',
        'footer-contact-title': 'Contactez-Nous',
        'footer-copyright': `© ${new Date().getFullYear()} Bihak Center. Tous droits réservés.`,
        'footer-privacy': 'Politique de Confidentialité',
        'footer-terms': 'Conditions d\'Utilisation'
    }
};

/**
 * Switch language
 */
function switchLanguage(lang) {
    currentLanguage = lang;
    localStorage.setItem('language', lang);

    // Update active button
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    const activeBtn = document.getElementById('lang-' + lang);
    if (activeBtn) {
        activeBtn.classList.add('active');
    }

    // Update page content
    updatePageLanguage(lang);

    // Set HTML lang attribute
    document.documentElement.lang = lang;

    // Dispatch custom event for page-specific translations
    document.dispatchEvent(new CustomEvent('languageChanged', {
        detail: { language: lang }
    }));
}

/**
 * Update all translatable elements
 */
function updatePageLanguage(lang) {
    const translations = universalTranslations[lang];

    if (!translations) return;

    // Update all elements with data-translate attribute
    document.querySelectorAll('[data-translate]').forEach(element => {
        const key = element.getAttribute('data-translate');
        if (translations[key]) {
            // Handle different element types
            if (element.tagName === 'INPUT' && (element.type === 'text' || element.type === 'search')) {
                element.placeholder = translations[key];
            } else if (element.tagName === 'TEXTAREA') {
                element.placeholder = translations[key];
            } else {
                element.textContent = translations[key];
            }
        }
    });

    // Update all elements with ID that matches translation key
    Object.keys(translations).forEach(key => {
        const element = document.getElementById(key);
        if (element) {
            element.textContent = translations[key];
        }
    });
}

/**
 * Initialize language switcher
 */
function initLanguageSwitcher() {
    // Set initial language
    updatePageLanguage(currentLanguage);

    // Update active button
    const activeBtn = document.getElementById('lang-' + currentLanguage);
    if (activeBtn) {
        activeBtn.classList.add('active');
    }

    // Add event listeners to language buttons
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const lang = this.getAttribute('data-lang');
            if (lang) {
                switchLanguage(lang);
            }
        });
    });
}

// Initialize when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLanguageSwitcher);
} else {
    // DOM already loaded
    initLanguageSwitcher();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { switchLanguage, updatePageLanguage, universalTranslations };
}
