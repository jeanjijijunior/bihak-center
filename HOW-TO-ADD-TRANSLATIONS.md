# Quick Guide: How to Add Translations to Any Page

## The Problem You Saw

✅ **Navigation translates** (because it's in the header)
❌ **Page content doesn't translate** (because pages need `data-translate` attributes)

## The Solution - 2 Simple Steps

### Step 1: Add Translation Keys (if needed)

If the text you want to translate isn't in the translation file yet, add it to **both English and French** sections:

**File:** `assets/js/translations-extended.js`

```javascript
en: {
    myNewText: 'My New Text',  // Add here
}

fr: {
    myNewText: 'Mon Nouveau Texte',  // And here
}
```

### Step 2: Add `data-translate` to HTML Elements

Open your page and add `data-translate="key"` to elements:

**Before:**
```html
<h1>Empowering Young People</h1>
<button>Submit</button>
```

**After:**
```html
<h1 data-translate="empoweringYoungPeople">Empowering Young People</h1>
<button data-translate="submit">Submit</button>
```

That's it! The language switcher now works for that element.

---

## Example: I Just Updated index.php

### What I Did:

**1. Added translation keys** to `translations-extended.js`:
```javascript
en: {
    empoweringYoungPeople: 'Empowering Young People',
    homeHeroText: 'Share your story. Get support...',
    viewStories: 'View Stories',
    youthChangingWorld: 'Youth Changing the World',
}

fr: {
    empoweringYoungPeople: 'Autonomiser les Jeunes',
    homeHeroText: 'Partagez votre histoire. Obtenez du soutien...',
    viewStories: 'Voir les Histoires',
    youthChangingWorld: 'Les Jeunes Qui Changent le Monde',
}
```

**2. Updated index.php HTML:**
```html
<!-- Before -->
<h1>Empowering Young People</h1>
<p>Share your story. Get support. Inspire others...</p>
<span>View Stories</span>

<!-- After -->
<h1 data-translate="empoweringYoungPeople">Empowering Young People</h1>
<p data-translate="homeHeroText">Share your story. Get support. Inspire others...</p>
<span data-translate="viewStories">View Stories</span>
```

**Result:** Now when you click FR, the home page content translates!

---

## Quick Reference: Common Patterns

### Headings
```html
<h1 data-translate="keyName">Text</h1>
<h2 data-translate="keyName">Text</h2>
```

### Paragraphs
```html
<p data-translate="keyName">Text here</p>
```

### Buttons
```html
<button data-translate="keyName">Click Me</button>
<a href="#" data-translate="keyName">Link Text</a>
```

### Spans (for inline text)
```html
<span data-translate="keyName">Text</span>
```

### Form Inputs (for placeholders)
```html
<input type="text" data-translate="keyName" placeholder="Enter name">
```

---

## Pages That Still Need Translation

### Priority 1 (Public Pages):
- ✅ index.php - **DONE!** (example)
- ❌ about.php
- ❌ contact.php
- ❌ opportunities.php
- ❌ work.php
- ❌ stories.php

### Priority 2 (User Pages):
- ❌ login.php
- ❌ signup.php
- ❌ my-account.php
- ❌ profile.php

### Priority 3 (Modules):
- ❌ Incubation pages
- ❌ Mentorship pages
- ❌ Messaging pages
- ❌ Admin pages

---

## Pro Tip: Use Existing Keys!

**400+ translation keys already exist!** Check `translations-extended.js` to see if the text you need is already translated.

Common ones:
- `submit`, `save`, `cancel`, `delete`, `edit`
- `name`, `email`, `password`, `phone`
- `loading`, `success`, `error`
- `home`, `about`, `contact`, `stories`

---

## Testing

1. Add `data-translate` attributes to your page
2. Save the file
3. Open page in browser
4. Click **FR** button in header
5. Content should translate instantly!
6. Click **EN** to switch back

---

## Need Help?

- **Full Guide:** `TRANSLATION-GUIDE.md`
- **Demo Page:** `public/test-translations.php`
- **All Keys:** `assets/js/translations-extended.js`

---

**Summary:** To translate any page content:
1. Make sure translation key exists in `translations-extended.js`
2. Add `data-translate="keyName"` to HTML element
3. Test with EN/FR switcher

That's it! 🎉
