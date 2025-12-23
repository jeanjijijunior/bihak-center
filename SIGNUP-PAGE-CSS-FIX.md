# Signup Page Layout Fix

## Issue
The signup page layout may have spacing issues with the fixed header overlapping content.

## Solution

Add these CSS rules to fix the layout in `assets/css/signup.css`:

### At the TOP of the file (after the header comment):

```css
/**
 * Signup Form Styles
 * Mobile-first responsive design
 */

/* Ensure proper spacing from fixed header */
body.signup-page {
    padding-top: 120px; /* Space for fixed header */
    background: #f5f7fa;
}

/* Container */
.signup-container {
    max-width: 900px;
    margin: 30px auto 60px;
    padding: 20px;
    background: #fff;
    min-height: calc(100vh - 200px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
}
```

### Alternative: Add to signup.php directly

If you prefer, add this inline in the `<head>` section of `public/signup.php`:

```html
<style>
body {
    padding-top: 120px;
    background: #f5f7fa;
}

.signup-container {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
}
</style>
```

### Additional Improvements (Optional)

For better visual hierarchy, you can also add:

```css
/* Improve form section cards */
.form-section {
    background: #ffffff;
    padding: 30px;
    margin-bottom: 25px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* Better input focus states */
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #1cabe2;
    box-shadow: 0 0 0 4px rgba(28, 171, 226, 0.1);
    transform: translateY(-1px);
}

/* Improve button spacing */
.form-actions {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #e5e7eb;
}

/* Better mobile spacing */
@media (max-width: 768px) {
    body {
        padding-top: 100px;
    }

    .signup-container {
        margin: 20px 10px;
        padding: 15px;
    }
}
```

## Quick Fix Commands

If you want to apply the fix via command line:

```bash
# Backup the original file
cp assets/css/signup.css assets/css/signup.css.backup

# Add the body padding fix at the beginning
sed -i '5a\
/* Ensure proper spacing from fixed header */\
body {\
    padding-top: 120px;\
    background: #f5f7fa;\
}\
' assets/css/signup.css
```

## Test After Applying

1. Open: http://localhost/bihak-center/public/signup.php
2. Check that:
   - Header doesn't overlap content
   - Form sections are well-spaced
   - Mobile view looks good
   - Form fields are easy to interact with

## Related Files

- Main CSS: [assets/css/signup.css](assets/css/signup.css)
- Signup Page: [public/signup.php](public/signup.php)
- Header CSS: [assets/css/header_new.css](assets/css/header_new.css)
