# Website Improvements Implementation Plan

## Status: IN PROGRESS

This document outlines the requested improvements and tracks their implementation status.

---

## 1. Multiple Image Uploads with Descriptions ✅ PARTIAL

### Status: Frontend Complete, Backend Pending

### What's Done:
- ✅ Updated `signup.php` form to accept multiple images (`profile_images[]`)
- ✅ Created JavaScript handlers for multiple image previews with remove buttons
- ✅ Added dynamic description fields for each uploaded image
- ✅ Updated validation to support up to 5 images (5MB each)
- ✅ Added CSS styles for image preview grid
- ✅ User can now select multiple images and add optional descriptions

### What's Needed:
- ⏳ Update `process_signup.php` to handle multiple image uploads
- ⏳ Save all images to uploads folder
- ⏳ Store image descriptions in `profile_media` table (`caption` field)
- ⏳ Link images to profile using `profile_id` and `display_order`

### Files Modified:
- [signup.php](public/signup.php) - Line 193-206 (form fields)
- [signup-validation.js](assets/js/signup-validation.js) - Complete rewrite for multiple images
- [signup.css](assets/css/signup.css) - Added lines 375-395 (preview grid styles)

### Files to Modify:
- [process_signup.php](public/process_signup.php) - Needs update to handle `profile_images[]` and `image_descriptions[]`

---

## 2. Capitalization Standardization (Sentence Case) ⏳ PENDING

### Requirement:
Use "Sentence case" throughout the entire website (only capitalize first letter of first word).

### Examples:
- ❌ "Share Your Story" → ✅ "Share your story"
- ❌ "Profile Media" → ✅ "Profile media"
- ❌ "Get Involved" → ✅ "Get involved"

### Files to Update:
**Headers/Navigation:**
- [header_new.php](includes/header_new.php) - All navigation buttons and menu items
- [header_new.css](assets/css/header_new.css) - Button text

**Forms:**
- [signup.php](public/signup.php) - All section headings (`<h2>`) and labels
- [get-involved.php](public/get-involved.php) - Form labels and headings
- [login.php](public/login.php) - Form labels

**Admin Dashboard:**
- [admin-sidebar.php](public/admin/includes/admin-sidebar.php) - All menu items
- [admin-dashboard.css](assets/css/admin-dashboard.css) - Headings

**Public Pages:**
- [index.php](public/index.php) - All headings
- [about.php](public/about.php) - All headings
- [contact.php](public/contact.php) - All headings
- [work.php](public/work.php) - All headings
- [opportunities.php](public/opportunities.php) - All headings
- [donation-impact.php](public/donation-impact.php) - All headings

### Partial Completion:
- ✅ Already updated in signup.php: "Profile media" (line 193)

---

## 3. French-English Language Switching ⏳ PENDING

### Requirement:
The language switcher should work for ALL content on the website, not just partial content.

### Current Status:
- Language switcher exists in header
- Translation system partially implemented
- Need to expand to cover entire website

### Implementation Strategy:

#### Option 1: JavaScript-Based (Recommended for Quick Implementation)
- Use existing [translations.js](assets/js/translations.js)
- Add comprehensive translation dictionary for all text
- Use `data-translate` attributes on all text elements
- Pros: No server reload, fast switching
- Cons: Larger JavaScript file

#### Option 2: PHP Session-Based (Better for SEO)
- Store language preference in session
- Create PHP translation function
- Separate translation files (en.php, fr.php)
- Reload page on language change
- Pros: Better SEO, smaller page size
- Cons: Requires page reload

### Files to Create/Update:
- [translations-complete.js](assets/js/translations-complete.js) - Full translation dictionary
- Or: [lang/en.php](lang/en.php) + [lang/fr.php](lang/fr.php) - PHP approach
- Update all pages to use translation system

### Translation Scope:
- Navigation menus
- Page headings
- Form labels
- Button text
- Footer content
- Success/error messages
- Opportunity cards
- Profile descriptions

---

## 4. Change "Share Your Story" Button to Green ⏳ PENDING

### Requirement:
Make the "Share Your Story" button green (matching the "Get Involved" button style).

### Current State:
- "Get Involved" button: Green gradient (`#10b981` to `#059669`)
- "Share Your Story" button: Blue gradient (needs to change)

### Implementation:
**File:** [header_new.css](assets/css/header_new.css)

**Find:**
```css
.btn-share-story {
    /* Currently blue gradient */
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}
```

**Change to:**
```css
.btn-share-story {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: 1.5px solid rgba(255, 255, 255, 0.3);
}

.btn-share-story:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.5);
}
```

**Estimated Time:** 2 minutes

---

## 5. Improve Opportunity Scraper ⏳ PENDING

### Requirements:
1. Generate well-informed opportunities leading to **working websites**
2. Prefer **quality over quantity** (few real opportunities vs many broken ones)
3. **Key Criteria:** Eligibility for African youth, mainly Sub-Saharan Africa

### Current Scrapers:
- [ScholarshipScraper.php](scrapers/ScholarshipScraper.php)
- [JobScraper.php](scrapers/JobScraper.php)
- [InternshipScraper.php](scrapers/InternshipScraper.php)
- [GrantScraper.php](scrapers/GrantScraper.php)
- [BaseScraper.php](scrapers/BaseScraper.php)

### Issues to Fix:
1. **URL Validation:** Verify URLs are working before saving
2. **Eligibility Filtering:** Check for "Africa", "African", "Sub-Saharan", "Rwanda", etc.
3. **Quality Control:** Remove opportunities without application URLs
4. **Duplication:** Prevent duplicate opportunities

### Recommended Improvements:

#### A. Add URL Validation
```php
function validateOpportunityUrl($url) {
    // Check if URL is accessible
    $headers = @get_headers($url);
    return $headers && strpos($headers[0], '200') !== false;
}
```

#### B. Add Africa Eligibility Check
```php
function isEligibleForAfrica($description, $eligibility) {
    $africanKeywords = [
        'africa', 'african', 'sub-saharan', 'rwanda', 'kenya',
        'uganda', 'tanzania', 'burundi', 'congo', 'worldwide',
        'international', 'all countries', 'developing countries'
    ];

    $text = strtolower($description . ' ' . $eligibility);
    foreach ($africanKeywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            return true;
        }
    }
    return false;
}
```

#### C. Add Quality Filters
- Require `application_url` to be non-empty
- Require minimum description length (100 characters)
- Check deadline is in the future
- Verify organization name exists

#### D. Recommended Sources for African Youth:
- **Scholarships:**
  - African Union Scholarships
  - MasterCard Foundation Scholars
  - DAAD Scholarships for Africa
  - Commonwealth Scholarships

- **Jobs/Internships:**
  - UN Jobs Africa
  - African Development Bank Careers
  - NGO jobs in Africa portals

- **Grants:**
  - African youth entrepreneurship grants
  - Innovation prizes for Africa

### Implementation Plan:
1. Add eligibility filter to `BaseScraper.php`
2. Add URL validation before saving
3. Update each scraper to use quality filters
4. Test with small batch first
5. Monitor results and adjust filters

---

## Priority Order

### High Priority (Do First):
1. ✅ **Multiple Image Uploads** - Finish backend (process_signup.php)
2. 🟢 **Green Button** - Quick 2-minute change
3. 🟡 **Capitalization** - Important for consistency (1-2 hours)

### Medium Priority (Do Next):
4. 🔵 **Scraper Quality** - Critical for user value (2-3 hours)
5. 🟣 **Language Switching** - Important but time-consuming (4-6 hours)

---

## Implementation Time Estimates

| Task | Estimated Time | Complexity |
|------|---------------|------------|
| Multiple images backend | 30 minutes | Medium |
| Green button | 2 minutes | Easy |
| Capitalization | 1-2 hours | Easy but tedious |
| Scraper improvements | 2-3 hours | Medium |
| Full language switching | 4-6 hours | High |
| **TOTAL** | **8-12 hours** | Mixed |

---

## Next Steps

1. **Immediate:** Finish multiple image upload backend
2. **Quick Win:** Change Share Your Story button to green
3. **Systematic:** Update capitalization across all pages
4. **Quality:** Improve scrapers with Africa focus
5. **Feature:** Implement comprehensive language switching

---

## Notes

- All changes should be tested on localhost before going live
- Database backups recommended before major changes
- Consider creating a staging environment for testing

---

**Created:** 2025-10-31
**Last Updated:** 2025-10-31
**Status:** Active Development
