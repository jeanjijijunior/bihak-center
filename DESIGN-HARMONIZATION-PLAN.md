# 🎨 Design Harmonization Plan

## Issues Identified

1. ❌ "Back to All Stories" button broken (links to index_new.php instead of index.php#stories)
2. ❌ No "Stories" link in navigation bar
3. ❌ Homepage (index.php) and profile pages not using new header/footer
4. ❌ Inconsistent spacing between header/footer and content
5. ❌ Language switcher only works on header, not page content
6. ❌ Footer missing on some pages or inconsistent
7. ❌ Design not harmonized across all pages

---

## ✅ What I'm Fixing Right Now

### 1. Updated Header (`includes/header_new.php`)
- ✅ Added "Stories" link to navigation
- ✅ Added data-translate attributes for language switching
- Navigation now includes:
  - Home
  - About
  - Our Work
  - **Stories** ← NEW!
  - Opportunities
  - Contact

### 2. Files Being Created/Updated

I'll need to systematically update these files:

#### A. Core Template Files
- ✅ `includes/header_new.php` - Updated with Stories link
- ⏳ `includes/footer.php` - Already created, needs to be added to all pages
- ⏳ `includes/common-styles.php` - Created for consistent spacing
- ⏳ `assets/js/language-switcher.js` - NEW - Universal language switcher

#### B. Pages That Need Header/Footer Added
- ⏳ `public/index.php` - Homepage (add header_new.php, footer.php, spacing)
- ⏳ `public/profile.php` - Profile detail (fix "Back" button, add footer)
- ⏳ `public/profiles.php` - All profiles listing
- ⏳ `public/signup.php` - Share Your Story form

#### C. Pages Already Using New System (Just need spacing fix)
- ⏳ `public/about.php`
- ⏳ `public/work.php`
- ⏳ `public/contact.php`
- ⏳ `public/opportunities.php`

---

## 📋 Detailed Action Plan

### Phase 1: Create Universal Language Switcher ⏳
**File:** `assets/js/language-switcher.js`

```javascript
// Universal translations object
const translations = {
    en: {
        // Navigation
        'nav-home': 'Home',
        'nav-about': 'About',
        'nav-work': 'Our Work',
        'nav-stories': 'Stories',
        'nav-opportunities': 'Opportunities',
        'nav-contact': 'Contact',

        // Footer
        'footer-about-title': 'About Bihak Center',
        // ... etc
    },
    fr: {
        // French translations
        'nav-home': 'Accueil',
        'nav-about': 'À Propos',
        'nav-work': 'Notre Travail',
        'nav-stories': 'Histoires',
        'nav-opportunities': 'Opportunités',
        'nav-contact': 'Contact',
        // ... etc
    }
};

// Function to translate all elements with data-translate attribute
function translatePage(lang) {
    document.querySelectorAll('[data-translate]').forEach(element => {
        const key = element.getAttribute('data-translate');
        if (translations[lang] && translations[lang][key]) {
            element.textContent = translations[lang][key];
        }
    });

    // Dispatch custom event for page-specific translations
    document.dispatchEvent(new CustomEvent('languageChanged', {
        detail: { language: lang }
    }));
}
```

### Phase 2: Update All Pages with Consistent Structure ⏳

**Standard page structure:**
```php
<?php
// Page logic here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page Title - Bihak Center</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header_new.css">
    <?php include __DIR__ . '/../includes/common-styles.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/header_new.php'; ?>

    <main>
        <!-- Page content here -->
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="../assets/js/header_new.js"></script>
    <script src="../assets/js/language-switcher.js"></script>
    <script>
        // Page-specific translations if needed
    </script>
</body>
</html>
```

### Phase 3: Fix Specific Issues ⏳

#### A. Fix "Back to All Stories" Button
**File:** `public/profile.php`

Change from:
```html
<a href="index_new.php" class="back-button">
```

To:
```html
<a href="index.php#stories" class="back-button" data-translate="back-to-stories">
    ← Back to All Stories
</a>
```

#### B. Update Homepage (index.php)
- Add `<?php include '../includes/header_new.php'; ?>`
- Add proper `<main>` wrapper with spacing
- Add `<?php include '../includes/footer.php'; ?>`
- Add language translations for all text

#### C. Add Spacing to All Pages
```html
<main style="margin-top: 80px; margin-bottom: 40px;">
    <div class="page-container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
        <!-- Content here -->
    </div>
</main>
```

---

## 🎨 Design Consistency Checklist

All pages must have:
- [ ] Same header (`includes/header_new.php`)
- [ ] Same footer (`includes/footer.php`)
- [ ] 80px margin-top for fixed header
- [ ] 40px margin-bottom before footer
- [ ] Max-width: 1200px container
- [ ] Consistent padding: 40px vertical, 20px horizontal
- [ ] Language switcher works on all content
- [ ] Responsive design (mobile-friendly)
- [ ] Same color scheme (#1cabe2 primary)
- [ ] Same button styles
- [ ] Same card styles
- [ ] Same typography

---

## 🚀 Implementation Order

1. ✅ Update `header_new.php` with Stories link
2. ⏳ Create `language-switcher.js`
3. ⏳ Update `index.php` (homepage)
4. ⏳ Update `profile.php` (fix back button)
5. ⏳ Update `profiles.php`
6. ⏳ Update `signup.php`
7. ⏳ Add spacing to about.php, work.php, contact.php, opportunities.php
8. ⏳ Test all pages for consistency
9. ⏳ Test language switching on all pages
10. ⏳ Test responsive design on mobile

---

## 📝 Notes

- All pages should load in under 2 seconds
- Mobile menu should work on all pages
- Language preference should persist across pages
- Footer should have consistent social links
- All external links should open in new tab
- All forms should have CSRF protection

---

## ⚠️ Important

Before I continue with the massive updates, please confirm:

1. Should I update ALL pages now?
2. Do you want me to create the universal language switcher first?
3. Should I prioritize the homepage (index.php) and profile pages first?
4. Any specific design preferences (colors, fonts, spacing)?

This is a comprehensive update that will touch many files. I want to make sure I do it right!
