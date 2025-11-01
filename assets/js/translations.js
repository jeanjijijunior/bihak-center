/**
 * Bihak Center - Centralized Translation System
 * Supports English and French translations across all pages
 */

const bihakTranslations = {
    en: {
        // Navigation
        home: 'Home',
        about: 'About',
        work: 'Our Work',
        opportunities: 'Opportunities',
        contact: 'Contact',
        shareStory: 'Share Your Story',
        login: 'Login',
        logout: 'Logout',
        myAccount: 'My Account',
        myProfile: 'My Profile',
        admin: 'Admin',

        // Common Buttons
        submit: 'Submit',
        save: 'Save',
        cancel: 'Cancel',
        delete: 'Delete',
        edit: 'Edit',
        back: 'Back',
        next: 'Next',
        previous: 'Previous',
        search: 'Search',
        filter: 'Filter',
        sort: 'Sort',
        loadMore: 'Load More',
        viewMore: 'View More',
        apply: 'Apply',
        close: 'Close',

        // Forms
        name: 'Name',
        fullName: 'Full Name',
        email: 'Email',
        emailAddress: 'Email Address',
        password: 'Password',
        confirmPassword: 'Confirm Password',
        phone: 'Phone',
        message: 'Message',
        subject: 'Subject',
        description: 'Description',
        category: 'Category',
        date: 'Date',
        location: 'Location',
        country: 'Country',
        city: 'City',
        required: 'Required',
        optional: 'Optional',

        // Messages
        success: 'Success',
        error: 'Error',
        warning: 'Warning',
        info: 'Information',
        loading: 'Loading...',
        noResults: 'No results found',
        tryAgain: 'Please try again',

        // Status
        active: 'Active',
        inactive: 'Inactive',
        pending: 'Pending',
        approved: 'Approved',
        rejected: 'Rejected',
        published: 'Published',
        draft: 'Draft',

        // Time
        today: 'Today',
        yesterday: 'Yesterday',
        tomorrow: 'Tomorrow',
        thisWeek: 'This Week',
        thisMonth: 'This Month',
        thisYear: 'This Year',

        // Profile/Account
        profile: 'Profile',
        settings: 'Settings',
        preferences: 'Preferences',
        notifications: 'Notifications',
        privacy: 'Privacy',
        security: 'Security',
        changePassword: 'Change Password',
        updateProfile: 'Update Profile',
        deleteAccount: 'Delete Account',
        manageProfileSettings: 'Manage your profile and account settings',
        myProfileStatus: 'My Profile Status',
        profilePendingReview: 'Your profile is being reviewed by our team. We\'ll notify you soon!',
        profileLive: 'Your profile is live!',
        visibleOnWebsite: 'Visible on website.',
        awaitingPublication: 'Awaiting publication.',
        profileNotApproved: 'Your profile was not approved. Reason:',
        notSpecified: 'Not specified',
        title: 'Title',
        submitted: 'Submitted',
        views: 'Views',
        yes: 'Yes',
        no: 'No',
        viewPublicProfile: 'View My Public Profile',
        noProfileYet: 'No Profile Yet',
        shareYourStory: 'Share your story with the world! Create your profile to showcase your talents and achievements.',
        createMyProfile: 'Create My Profile',
        createProfile: 'Create Profile',
        recentActivity: 'Recent Activity',
        noActivityYet: 'No activity yet',
        accountInformation: 'Account Information',
        emailStatus: 'Email Status',
        verified: 'Verified',
        notVerified: 'Not Verified',
        quickActions: 'Quick Actions',
        browseOpportunities: 'Browse Opportunities',
        backToHome: 'Back to Home',
        needHelp: 'Need Help?',
        haveQuestions: 'Have questions or need assistance? We\'re here to help!',
        contactSupport: 'Contact Support',
        status: 'Status',

        // Stories
        stories: 'Stories',
        successStories: 'Success Stories',
        storiesSubtitle: 'Meet the inspiring young people we support and the incredible journeys they\'re on',
        totalStories: 'Total Stories',
        totalViews: 'Total Views',
        districts: 'Districts',
        allStories: 'All Stories',
        inspireByStories: 'Be inspired by the stories of young people making a difference',
        readStory: 'Read Story',
        noStoriesYet: 'No Stories Yet',
        beFirstToShare: 'Be the first to share your inspiring story!',
        ourPrograms: 'Discover Our Programs',

        // Opportunities
        deadline: 'Deadline',
        applicationUrl: 'Application URL',
        eligibility: 'Eligibility',
        benefits: 'Benefits',
        howToApply: 'How to Apply',
        viewDetails: 'View Details',
        applyNow: 'Apply Now',
        saveOpportunity: 'Save Opportunity',

        // Contact
        getInTouch: 'Get in Touch',
        sendMessage: 'Send Message',
        ourLocation: 'Our Location',
        emailUs: 'Email Us',
        callUs: 'Call Us',
        followUs: 'Follow Us',

        // Footer
        copyright: '© 2024 Bihak Center. All rights reserved.',
        termsOfService: 'Terms of Service',
        privacyPolicy: 'Privacy Policy',
        aboutUs: 'About Us',
        contactUs: 'Contact Us',

        // Errors
        errorOccurred: 'An error occurred',
        pageNotFound: 'Page not found',
        accessDenied: 'Access denied',
        sessionExpired: 'Your session has expired',
        invalidInput: 'Invalid input',
        requiredField: 'This field is required',
        invalidEmail: 'Please enter a valid email address',
        passwordTooShort: 'Password must be at least 8 characters',
        passwordMismatch: 'Passwords do not match'
    },
    fr: {
        // Navigation
        home: 'Accueil',
        about: 'À Propos',
        work: 'Notre Travail',
        opportunities: 'Opportunités',
        contact: 'Contact',
        shareStory: 'Partagez Votre Histoire',
        login: 'Connexion',
        logout: 'Déconnexion',
        myAccount: 'Mon Compte',
        myProfile: 'Mon Profil',
        admin: 'Admin',

        // Common Buttons
        submit: 'Soumettre',
        save: 'Enregistrer',
        cancel: 'Annuler',
        delete: 'Supprimer',
        edit: 'Modifier',
        back: 'Retour',
        next: 'Suivant',
        previous: 'Précédent',
        search: 'Rechercher',
        filter: 'Filtrer',
        sort: 'Trier',
        loadMore: 'Charger Plus',
        viewMore: 'Voir Plus',
        apply: 'Appliquer',
        close: 'Fermer',

        // Forms
        name: 'Nom',
        fullName: 'Nom Complet',
        email: 'Email',
        emailAddress: 'Adresse Email',
        password: 'Mot de Passe',
        confirmPassword: 'Confirmer le Mot de Passe',
        phone: 'Téléphone',
        message: 'Message',
        subject: 'Sujet',
        description: 'Description',
        category: 'Catégorie',
        date: 'Date',
        location: 'Lieu',
        country: 'Pays',
        city: 'Ville',
        required: 'Requis',
        optional: 'Facultatif',

        // Messages
        success: 'Succès',
        error: 'Erreur',
        warning: 'Avertissement',
        info: 'Information',
        loading: 'Chargement...',
        noResults: 'Aucun résultat trouvé',
        tryAgain: 'Veuillez réessayer',

        // Status
        active: 'Actif',
        inactive: 'Inactif',
        pending: 'En Attente',
        approved: 'Approuvé',
        rejected: 'Rejeté',
        published: 'Publié',
        draft: 'Brouillon',

        // Time
        today: "Aujourd'hui",
        yesterday: 'Hier',
        tomorrow: 'Demain',
        thisWeek: 'Cette Semaine',
        thisMonth: 'Ce Mois',
        thisYear: 'Cette Année',

        // Profile/Account
        profile: 'Profil',
        settings: 'Paramètres',
        preferences: 'Préférences',
        notifications: 'Notifications',
        privacy: 'Confidentialité',
        security: 'Sécurité',
        changePassword: 'Changer le Mot de Passe',
        updateProfile: 'Mettre à Jour le Profil',
        deleteAccount: 'Supprimer le Compte',
        manageProfileSettings: 'Gérez votre profil et les paramètres de votre compte',
        myProfileStatus: 'Statut de Mon Profil',
        profilePendingReview: 'Votre profil est en cours de révision par notre équipe. Nous vous informerons bientôt!',
        profileLive: 'Votre profil est en ligne!',
        visibleOnWebsite: 'Visible sur le site web.',
        awaitingPublication: 'En attente de publication.',
        profileNotApproved: 'Votre profil n\'a pas été approuvé. Raison:',
        notSpecified: 'Non spécifié',
        title: 'Titre',
        submitted: 'Soumis',
        views: 'Vues',
        yes: 'Oui',
        no: 'Non',
        viewPublicProfile: 'Voir Mon Profil Public',
        noProfileYet: 'Pas Encore de Profil',
        shareYourStory: 'Partagez votre histoire avec le monde! Créez votre profil pour présenter vos talents et réalisations.',
        createMyProfile: 'Créer Mon Profil',
        createProfile: 'Créer un Profil',
        recentActivity: 'Activité Récente',
        noActivityYet: 'Aucune activité pour le moment',
        accountInformation: 'Informations du Compte',
        emailStatus: 'Statut Email',
        verified: 'Vérifié',
        notVerified: 'Non Vérifié',
        quickActions: 'Actions Rapides',
        browseOpportunities: 'Parcourir les Opportunités',
        backToHome: 'Retour à l\'Accueil',
        needHelp: 'Besoin d\'Aide?',
        haveQuestions: 'Vous avez des questions ou besoin d\'assistance? Nous sommes là pour vous aider!',
        contactSupport: 'Contacter le Support',
        status: 'Statut',

        // Stories
        stories: 'Histoires',
        successStories: 'Histoires de Succès',
        storiesSubtitle: 'Rencontrez les jeunes inspirants que nous soutenons et les incroyables voyages qu\'ils entreprennent',
        totalStories: 'Total des Histoires',
        totalViews: 'Total des Vues',
        districts: 'Districts',
        allStories: 'Toutes les Histoires',
        inspireByStories: 'Laissez-vous inspirer par les histoires de jeunes qui font la différence',
        readStory: 'Lire l\'Histoire',
        noStoriesYet: 'Pas Encore d\'Histoires',
        beFirstToShare: 'Soyez le premier à partager votre histoire inspirante!',
        ourPrograms: 'Découvrez Nos Programmes',

        // Opportunities
        deadline: 'Date Limite',
        applicationUrl: "URL d'Application",
        eligibility: 'Éligibilité',
        benefits: 'Avantages',
        howToApply: 'Comment Postuler',
        viewDetails: 'Voir les Détails',
        applyNow: 'Postuler Maintenant',
        saveOpportunity: "Sauvegarder l'Opportunité",

        // Contact
        getInTouch: 'Contactez-Nous',
        sendMessage: 'Envoyer un Message',
        ourLocation: 'Notre Emplacement',
        emailUs: 'Envoyez-nous un Email',
        callUs: 'Appelez-Nous',
        followUs: 'Suivez-Nous',

        // Footer
        copyright: '© 2024 Bihak Center. Tous droits réservés.',
        termsOfService: "Conditions d'Utilisation",
        privacyPolicy: 'Politique de Confidentialité',
        aboutUs: 'À Propos de Nous',
        contactUs: 'Contactez-Nous',

        // Errors
        errorOccurred: "Une erreur s'est produite",
        pageNotFound: 'Page non trouvée',
        accessDenied: 'Accès refusé',
        sessionExpired: 'Votre session a expiré',
        invalidInput: 'Entrée invalide',
        requiredField: 'Ce champ est requis',
        invalidEmail: 'Veuillez entrer une adresse email valide',
        passwordTooShort: 'Le mot de passe doit contenir au moins 8 caractères',
        passwordMismatch: 'Les mots de passe ne correspondent pas'
    }
};

// Get current language from localStorage or default to English
let currentLanguage = localStorage.getItem('language') || 'en';

/**
 * Get translation for a key
 * @param {string} key - The translation key
 * @param {string} lang - Optional language override
 * @returns {string} The translated text
 */
function t(key, lang = null) {
    const language = lang || currentLanguage;
    return bihakTranslations[language]?.[key] || key;
}

/**
 * Get all translations for current language
 * @param {string} lang - Optional language override
 * @returns {object} All translations for the language
 */
function getTranslations(lang = null) {
    const language = lang || currentLanguage;
    return bihakTranslations[language] || bihakTranslations.en;
}

/**
 * Switch language
 * @param {string} lang - Language code (en/fr)
 */
function switchLanguage(lang) {
    if (!bihakTranslations[lang]) {
        console.error(`Language '${lang}' not supported`);
        return;
    }

    currentLanguage = lang;
    localStorage.setItem('language', lang);

    // Update active button in language switcher
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    const langBtn = document.getElementById('lang-' + lang);
    if (langBtn) langBtn.classList.add('active');

    // Update page content
    updatePageLanguage(lang);

    // Set HTML lang attribute
    document.documentElement.lang = lang;

    // Dispatch custom event for page-specific translations
    document.dispatchEvent(new CustomEvent('languageChanged', {
        detail: {
            language: lang,
            translations: getTranslations(lang),
            t: (key) => t(key, lang)
        }
    }));
}

/**
 * Update page content with translations
 * @param {string} lang - Language code
 */
function updatePageLanguage(lang) {
    const translations = getTranslations(lang);

    // Update navigation links
    const navLinks = document.querySelectorAll('.nav-links a');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href.includes('index')) link.textContent = translations.home;
        else if (href.includes('about')) link.textContent = translations.about;
        else if (href.includes('stories')) link.textContent = translations.stories;
        else if (href.includes('work')) link.textContent = translations.work;
        else if (href.includes('opportunities')) link.textContent = translations.opportunities;
        else if (href.includes('contact')) link.textContent = translations.contact;
    });

    // Update buttons
    const shareBtn = document.querySelector('.btn-share-story span');
    if (shareBtn) shareBtn.textContent = translations.shareStory;

    const loginBtn = document.querySelector('.btn-login span');
    if (loginBtn) loginBtn.textContent = translations.login;

    const adminBtn = document.querySelector('.btn-admin span');
    if (adminBtn) adminBtn.textContent = translations.admin;

    // Update user dropdown
    const dropdownLinks = document.querySelectorAll('.user-dropdown a');
    dropdownLinks.forEach(link => {
        const href = link.getAttribute('href');
        const textNodes = Array.from(link.childNodes).filter(node => node.nodeType === Node.TEXT_NODE);
        if (href.includes('my-account') && textNodes.length > 0) {
            textNodes[textNodes.length - 1].textContent = ' ' + translations.myAccount;
        } else if (href.includes('profile') && textNodes.length > 0) {
            textNodes[textNodes.length - 1].textContent = ' ' + translations.myProfile;
        } else if (href.includes('logout') && textNodes.length > 0) {
            textNodes[textNodes.length - 1].textContent = ' ' + translations.logout;
        }
    });

    // Update elements with data-translate attribute
    document.querySelectorAll('[data-translate]').forEach(element => {
        const key = element.getAttribute('data-translate');
        if (translations[key]) {
            if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
                element.placeholder = translations[key];
            } else {
                element.textContent = translations[key];
            }
        }
    });
}

// Initialize language on page load
document.addEventListener('DOMContentLoaded', function() {
    switchLanguage(currentLanguage);
});
