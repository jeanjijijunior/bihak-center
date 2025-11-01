# Translation System Guide

## Overview
Bihak Center uses a centralized translation system that supports English (EN) and French (FR) across all pages.

## How It Works

### Automatic Translation
The translation system is automatically loaded with `header_new.php`. Any page that includes the header will have translation support.

### Available Languages
- **English (EN)** - Default
- **French (FR)**

## Using Translations in Your Pages

### Method 1: Data Attributes (Recommended)
Add `data-translate` attribute to any element you want to translate:

```html
<h1 data-translate="home">Home</h1>
<button data-translate="submit">Submit</button>
<input type="text" placeholder="Search" data-translate="search">
```

The system will automatically translate these elements when the language changes.

### Method 2: JavaScript Function
Use the `t()` function to get translations in your JavaScript:

```javascript
// Get translation for current language
const homeText = t('home'); // Returns "Home" or "Accueil"

// Get translation for specific language
const frenchHome = t('home', 'fr'); // Returns "Accueil"
```

### Method 3: Listen to Language Change Event
For complex page-specific translations:

```javascript
document.addEventListener('languageChanged', function(e) {
    const lang = e.detail.language;
    const translations = e.detail.translations;
    const t = e.detail.t; // Translation function

    // Update your page elements
    document.getElementById('title').textContent = translations.myCustomKey;
    document.getElementById('button').textContent = t('submit');
});
```

## Available Translation Keys

### Navigation
- `home`, `about`, `work`, `opportunities`, `contact`
- `shareStory`, `login`, `logout`, `myAccount`, `myProfile`, `admin`

### Common Buttons
- `submit`, `save`, `cancel`, `delete`, `edit`, `back`, `next`, `previous`
- `search`, `filter`, `sort`, `loadMore`, `viewMore`, `apply`, `close`

### Forms
- `name`, `fullName`, `email`, `emailAddress`, `password`, `confirmPassword`
- `phone`, `message`, `subject`, `description`, `category`, `date`
- `location`, `country`, `city`, `required`, `optional`

### Messages
- `success`, `error`, `warning`, `info`, `loading`, `noResults`, `tryAgain`

### Status
- `active`, `inactive`, `pending`, `approved`, `rejected`, `published`, `draft`

### Time
- `today`, `yesterday`, `tomorrow`, `thisWeek`, `thisMonth`, `thisYear`

### Profile/Account
- `profile`, `settings`, `preferences`, `notifications`, `privacy`, `security`
- `changePassword`, `updateProfile`, `deleteAccount`

### Opportunities
- `deadline`, `applicationUrl`, `eligibility`, `benefits`, `howToApply`
- `viewDetails`, `applyNow`, `saveOpportunity`

### Contact
- `getInTouch`, `sendMessage`, `ourLocation`, `emailUs`, `callUs`, `followUs`

### Footer
- `copyright`, `termsOfService`, `privacyPolicy`, `aboutUs`, `contactUs`

### Errors
- `errorOccurred`, `pageNotFound`, `accessDenied`, `sessionExpired`
- `invalidInput`, `requiredField`, `invalidEmail`, `passwordTooShort`, `passwordMismatch`

## Adding New Translations

To add new translation keys:

1. Open `assets/js/translations.js`
2. Add your key to both `en` and `fr` objects:

```javascript
const bihakTranslations = {
    en: {
        // ... existing translations
        myNewKey: 'My English Text',
        anotherKey: 'Another Text'
    },
    fr: {
        // ... existing translations
        myNewKey: 'Mon Texte Français',
        anotherKey: 'Autre Texte'
    }
};
```

3. Use the key in your HTML:

```html
<h1 data-translate="myNewKey">My English Text</h1>
```

Or in JavaScript:

```javascript
const text = t('myNewKey');
```

## Example: Creating a New Page with Translations

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My New Page - Bihak Center</title>
    <link rel="stylesheet" href="../assets/css/header_new.css">
</head>
<body>
    <?php include '../includes/header_new.php'; ?>

    <div class="container">
        <h1 data-translate="myPageTitle">My Page Title</h1>
        <p data-translate="myPageDescription">This is my page description</p>

        <button id="myButton" data-translate="submit">Submit</button>
    </div>

    <script>
        // Listen for language changes to update custom elements
        document.addEventListener('languageChanged', function(e) {
            const translations = e.detail.translations;

            // Update any custom elements not covered by data-translate
            console.log('Language changed to: ' + e.detail.language);
        });
    </script>
</body>
</html>
```

## Best Practices

1. **Always use translation keys** - Don't hardcode text in multiple languages
2. **Add data-translate attributes** - Let the system handle automatic translation
3. **Keep keys descriptive** - Use clear names like `submitButton` not `btn1`
4. **Test both languages** - Switch between EN/FR to ensure all text translates
5. **Add new keys to translations.js** - Keep all translations centralized

## Language Persistence

The user's language choice is saved in `localStorage` and will persist across:
- Page refreshes
- Navigation between pages
- Browser sessions

The language is automatically restored when the user returns to the site.

## Troubleshooting

**Problem:** Translation not working on my page
- **Solution:** Make sure you include `header_new.php` which loads the translation system

**Problem:** My custom text isn't translating
- **Solution:** Add `data-translate="yourKey"` attribute or use the `t()` function

**Problem:** I need a translation key that doesn't exist
- **Solution:** Add it to `assets/js/translations.js` in both `en` and `fr` objects

**Problem:** Translation works but some elements don't update
- **Solution:** Listen to the `languageChanged` event and update them manually

## Support

For questions or issues with the translation system, refer to:
- Translation file: `assets/js/translations.js`
- Header file: `includes/header_new.php`
- This guide: `TRANSLATION-GUIDE.md`
