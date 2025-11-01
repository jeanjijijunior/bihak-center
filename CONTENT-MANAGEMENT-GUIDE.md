# Content Management System - User Guide

**Date:** October 31, 2025
**Feature:** Static Content Management for Non-Technical Users

---

## Overview

The Content Management System allows administrators to edit website content without touching code. All content can be edited through a user-friendly interface in the admin dashboard.

---

## Features

### ✅ What You Can Edit
- **Page Content**: Text, headings, paragraphs on all main pages
- **Bilingual Support**: Edit content in both English and French
- **Rich Text Formatting**: Bold, italic, lists, links, images
- **Content Types**: Text, HTML, headings, paragraphs, image URLs, link URLs

### ✅ Supported Pages
1. **Home** - Hero section, welcome messages, calls to action
2. **About** - Mission, vision, team information
3. **Work** - Program descriptions, impact stories
4. **Contact** - Contact information, office hours
5. **Opportunities** - Page descriptions, filters
6. **Stories** - Introductory text, section headings

---

## Getting Started

### Step 1: Run Database Setup

Execute the SQL file to create content tables:

```bash
"C:\xampp\mysql\bin\mysql.exe" -u root bihak < includes/page_content_tables.sql
```

Or run manually in phpMyAdmin:
1. Open phpMyAdmin
2. Select `bihak` database
3. Go to SQL tab
4. Copy/paste contents of `includes/page_content_tables.sql`
5. Click "Go"

### Step 2: Access Content Manager

1. Login to admin dashboard: `/public/admin/login.php`
2. Look for **Content Management** section in sidebar
3. Click **Edit Page Content**

---

## How to Use

### Editing Content

1. **Select a Page**: Click on the page tab at the top (Home, About, Work, etc.)
2. **Choose Language**: Click EN or FR tabs for each content item
3. **Edit Content**:
   - For text: Type directly in the input field
   - For HTML: Use the rich text editor with formatting toolbar
4. **Save Changes**: Click "Save All Changes" button at bottom

### Content Types

Each content item has a type badge:

- **TEXT** - Plain text, no formatting
- **HTML** - Rich text with formatting (bold, lists, links)
- **HEADING** - Page headings
- **PARAGRAPH** - Body paragraphs
- **IMAGE_URL** - URLs to images
- **LINK_URL** - URLs to other pages

### Rich Text Editor Features

The HTML editor (TinyMCE) supports:
- **Text Formatting**: Bold, italic, underline
- **Lists**: Numbered and bulleted lists
- **Links**: Insert/edit hyperlinks
- **Images**: Insert images from URLs
- **Tables**: Create data tables
- **Code**: View/edit HTML source
- **Preview**: Preview before saving

---

## Usage in Pages

### Loading Content in PHP

To display content from the database in your pages:

```php
<?php
require_once '../config/page_content.php';

// Set language (based on user preference)
PageContent::setLanguage($lang); // 'en' or 'fr'

// Get single content item
$heroTitle = PageContent::get('home', 'hero_title', 'Welcome to BIHAK Center');

// Get all content for a page
$allContent = PageContent::getAll('about');
foreach ($allContent as $key => $content) {
    echo "<p>{$content}</p>";
}
?>
```

### Example Integration

**Before (hardcoded):**
```php
<h1>Welcome to BIHAK Center</h1>
<p>Empowering young people through education</p>
```

**After (dynamic):**
```php
<?php
PageContent::setLanguage($_SESSION['language'] ?? 'en');
?>
<h1><?= PageContent::get('home', 'hero_title', 'Welcome to BIHAK Center') ?></h1>
<p><?= PageContent::get('home', 'hero_subtitle', 'Empowering young people') ?></p>
```

---

## Database Structure

### page_contents Table

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Auto-increment primary key |
| page_name | VARCHAR(50) | Page identifier (home, about, work, etc.) |
| section_key | VARCHAR(100) | Content section key (hero_title, mission, etc.) |
| content_type | ENUM | Type of content (text, html, heading, etc.) |
| content_en | TEXT | English content |
| content_fr | TEXT | French content |
| display_order | INT | Display order (0-100) |
| is_active | BOOLEAN | Whether content is active |
| last_updated | TIMESTAMP | Last modification timestamp |
| updated_by | INT | Admin ID who last updated |

### Indexes
- Primary key on `id`
- Unique key on (`page_name`, `section_key`)
- Index on `page_name`
- Index on `is_active`

---

## Pre-Populated Content

The system comes with default content for all pages:

### Home Page (17 items)
- Hero section: title, subtitle, description
- CTA buttons and links
- Profile section headings
- Impact statistics

### About Page (13 items)
- Hero title and description
- Mission and vision statements
- Values and approach
- Team introduction

### Work Page (16 items)
- Hero and intro content
- Program descriptions (4 programs)
- Impact stories
- CTA sections

### Contact Page (10 items)
- Hero content
- Contact information
- Office hours
- Social media prompts

### Opportunities Page (8 items)
- Hero section
- Filter descriptions
- Application guidelines

---

## Security Features

### ✅ CSRF Protection
- All forms use CSRF tokens
- Token validation on submission

### ✅ Input Sanitization
- HTML content sanitized on save
- XSS prevention on output
- SQL injection prevention (prepared statements)

### ✅ Activity Logging
- All content changes logged
- Admin ID tracked
- Timestamp recorded
- Action type logged

### ✅ Access Control
- Only logged-in admins can access
- Role-based permissions
- Session validation

---

## Caching

The system includes built-in caching for performance:

```php
// Content is cached in memory during page load
PageContent::get('home', 'hero_title'); // Database query
PageContent::get('home', 'hero_title'); // Cached (no query)

// Clear cache after updates
PageContent::clearCache();
```

Cache automatically clears when:
- Content is updated via admin panel
- New page load starts

---

## Bilingual Support

### Language Handling

1. **Setting Language**:
```php
PageContent::setLanguage('fr'); // Switch to French
```

2. **Automatic Fallback**:
- If French content is empty, English is used automatically
- No need to check for empty content

3. **User Session**:
```php
// Set language from user session
$lang = $_SESSION['language'] ?? 'en';
PageContent::setLanguage($lang);
```

---

## Troubleshooting

### Content Not Displaying
1. Check database connection in [config/db.php](config/db.php)
2. Verify table exists: `SHOW TABLES LIKE 'page_contents';`
3. Check content is active: `SELECT * FROM page_contents WHERE is_active=TRUE;`
4. Clear cache: `PageContent::clearCache();`

### Editor Not Loading
1. Check TinyMCE CDN is accessible
2. Verify JavaScript console for errors
3. Check admin session is valid

### Changes Not Saving
1. Check CSRF token is present
2. Verify admin has write permissions
3. Check database error logs
4. Ensure content_type matches field type

---

## Best Practices

### 1. Content Organization
- Use descriptive section keys (e.g., `hero_title`, `mission_statement`)
- Keep content_type accurate for proper rendering
- Use display_order for consistent ordering

### 2. Bilingual Content
- Always provide English content (fallback)
- Add French translations when available
- Keep content length similar in both languages

### 3. HTML Content
- Use semantic HTML (headings, paragraphs, lists)
- Avoid inline styles (use CSS classes)
- Test content in rich text editor preview

### 4. Performance
- Don't call `get()` multiple times for same content
- Use `getAll()` for loading multiple items
- Cache is automatic, don't worry about it

---

## Future Enhancements

### Possible Additions
- [ ] Image upload directly in editor
- [ ] Content versioning/history
- [ ] Bulk import/export
- [ ] Content scheduling (publish later)
- [ ] Preview changes before saving
- [ ] Multi-user editing with locking
- [ ] Content approval workflow

---

## Files Reference

### Core Files
- [config/page_content.php](config/page_content.php) - Helper class
- [public/admin/content-manager.php](public/admin/content-manager.php) - Admin interface
- [includes/page_content_tables.sql](includes/page_content_tables.sql) - Database schema

### Integration Points
- [public/index.php](public/index.php) - Homepage (ready for integration)
- [public/about.php](public/about.php) - About page (ready for integration)
- [public/work.php](public/work.php) - Work page (ready for integration)
- [public/contact.php](public/contact.php) - Contact page (ready for integration)

---

## Support

### Admin Account
- Username: `admin`
- Email: `admin@bihakcenter.org`

### Database
- Host: `localhost`
- Database: `bihak`
- Table: `page_contents`

### Error Logs
- PHP errors: `c:\xampp\apache\logs\error.log`
- Database logs: Check phpMyAdmin

---

## Summary

The Content Management System provides:
- ✅ User-friendly interface for non-technical admins
- ✅ Bilingual content editing (EN/FR)
- ✅ Rich text formatting with TinyMCE
- ✅ Security with CSRF protection and activity logging
- ✅ Performance with built-in caching
- ✅ Pre-populated content for all main pages

**Ready to use!** Just run the SQL setup and start editing content through the admin dashboard.

---

**Created:** October 31, 2025
**Version:** 1.0
**Status:** Production Ready
