# Bihak Center - Complete Translation System Guide

**Making ALL Content Bilingual (English/French)**

---

## Quick Start - 3 Steps

### Step 1: Page Already Has Translation Script
```php
<?php include __DIR__ . '/../includes/header_new.php'; ?>
```
✅ Translation script is auto-loaded in header!

### Step 2: Add `data-translate` Attribute
```html
<!-- Before -->
<h1>Welcome</h1>
<button>Submit</button>

<!-- After - Add data-translate="key" -->
<h1 data-translate="home">Welcome</h1>
<button data-translate="submit">Submit</button>
```

### Step 3: Test!
Click EN/FR buttons in header → Content translates instantly!

---

## Available Translation Keys (400+)

### Navigation & Common
```
home, about, stories, work, opportunities, contact
submit, save, cancel, delete, edit, back, next
search, filter, loading, success, error
```

### Forms
```
name, email, password, phone, message, subject
required, optional, firstName, lastName
```

### Incubation Module
```
incubationProgram, myTeam, exercises, progress
feedback, aiFeedback, submitForReview
problemTree, businessModelCanvas
```

### Mentorship
```
mentorship, myMentor, findMentor, scheduleSession
expertise, availability
```

### Admin Panel
```
adminDashboard, users, profiles, analytics
totalUsers, manage, addNew, exportData
```

### Messaging
```
messages, inbox, newMessage, reply
markAsRead, attachments, online
```

**Full list:** See [translations-extended.js](assets/js/translations-extended.js)

---

## Usage Examples

### Example 1: Simple Page
```html
<div class="container">
    <h1 data-translate="myAccount">My Account</h1>
    <p data-translate="manageProfileSettings">Manage your settings</p>
    <button data-translate="save">Save</button>
    <button data-translate="cancel">Cancel</button>
</div>
```

### Example 2: Form
```html
<form>
    <label data-translate="yourName">Your Name</label>
    <input type="text" data-translate="name" placeholder="Name">

    <label data-translate="yourEmail">Your Email</label>
    <input type="email" data-translate="email" placeholder="Email">

    <button data-translate="submit">Submit</button>
</form>
```

### Example 3: Table
```html
<table>
    <thead>
        <tr>
            <th data-translate="name">Name</th>
            <th data-translate="email">Email</th>
            <th data-translate="status">Status</th>
        </tr>
    </thead>
</table>
```

### Example 4: Status Messages
```html
<div class="alert alert-success" data-translate="successfullySaved">
    Successfully saved
</div>

<div class="loading" data-translate="loading">
    Loading...
</div>
```

### Example 5: JavaScript
```javascript
// Get translation
const text = translate('submit'); // or t('submit')
console.log(text); // "Submit" or "Soumettre"

// Listen for language change
document.addEventListener('languageChanged', function(e) {
    const { language, t } = e.detail;
    updateMyContent(t);
});
```

---

## Common Patterns

### Dashboard Card
```html
<div class="card">
    <h3 data-translate="recentActivity">Recent Activity</h3>
    <p class="count">25</p>
    <button data-translate="viewAll">View All</button>
</div>
```

### Empty State
```html
<div class="empty-state">
    <p data-translate="noResults">No results found</p>
    <button data-translate="tryAgain">Try Again</button>
</div>
```

### Action Buttons
```html
<div class="actions">
    <button data-translate="edit">Edit</button>
    <button data-translate="delete">Delete</button>
    <button data-translate="download">Download</button>
</div>
```

---

## Adding New Translations

Edit `assets/js/translations-extended.js`:

```javascript
const bihakExtendedTranslations = {
    en: {
        // Add your key here
        myNewKey: 'My English Text'
    },
    fr: {
        // Add French translation
        myNewKey: 'Mon Texte Français'
    }
};
```

Use it:
```html
<span data-translate="myNewKey">My English Text</span>
```

---

## Module Examples

### Incubation Dashboard
```html
<h1 data-translate="incubationProgram">Incubation Program</h1>
<h2 data-translate="myTeam">My Team</h2>
<button data-translate="startExercise">Start Exercise</button>
<button data-translate="getAIFeedback">Get AI Feedback</button>
<span data-translate="progress">Progress</span>: 75%
```

### Mentorship Page
```html
<h1 data-translate="mentorship">Mentorship</h1>
<h3 data-translate="myMentor">My Mentor</h3>
<button data-translate="scheduleSession">Schedule Session</button>
<p><span data-translate="expertise">Expertise</span>: Business</p>
```

### Admin Panel
```html
<h1 data-translate="adminDashboard">Admin Dashboard</h1>
<h3 data-translate="totalUsers">Total Users</h3>
<button data-translate="addNew">Add New</button>
<button data-translate="exportData">Export Data</button>
```

---

## Testing

1. Open any page
2. Click FR button in header
3. All `data-translate` elements should translate
4. Click EN to switch back
5. Refresh page - language persists

### Quick Test
Create `test.php`:
```php
<?php include 'includes/header_new.php'; ?>
<div class="container">
    <h1 data-translate="home">Home</h1>
    <button data-translate="submit">Submit</button>
    <p data-translate="loading">Loading...</p>
</div>
<?php include 'includes/footer_new.php'; ?>
```

---

## Troubleshooting

**Translation not working?**
- Check if `data-translate` attribute is correct
- Verify key exists in translations-extended.js
- Open browser console (F12) for errors

**Language not persisting?**
- Check if localStorage is enabled
- Clear browser cache

**Dynamic content not translating?**
```javascript
// Manually translate after adding elements
element.textContent = t('myKey');
```

---

## Best Practices

✅ **DO:**
- Use `data-translate` for all visible text
- Test in both languages
- Use meaningful key names

❌ **DON'T:**
- Hard-code French text
- Create duplicate elements for each language
- Forget to translate error messages

---

## Summary

- ✅ 400+ translation keys ready
- ✅ Covers ALL modules
- ✅ Simple `data-translate` attribute
- ✅ Automatic language switching
- ✅ Persistent preferences

**To translate any page:**
1. Add `data-translate="key"` to elements
2. Use existing keys from translations-extended.js
3. Test with EN/FR switcher

---

**Files:**
- [assets/js/translations-extended.js](assets/js/translations-extended.js) - All translations
- [includes/header_new.php](includes/header_new.php) - Loads translation script

**Next:** Start adding `data-translate` to your pages!

*Last updated: November 30, 2025*
