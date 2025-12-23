# Bihak Center - Youth Opportunity Platform

A bilingual (English/French) platform connecting African youth with opportunities, mentorship, and incubation programs.

## Features

- **Opportunity Aggregation**: Scholarships, jobs, internships, and grants scraped from multiple sources
- **Profile Management**: User profiles with skills, education, and experience tracking
- **Mentorship System**: Connect youth with mentors and sponsors
- **Incubation Program**: Team-based entrepreneurship program with exercises and progress tracking
- **AI Assistant**: Powered by Ollama for personalized guidance
- **Messaging System**: Real-time chat between users, mentors, and admins
- **Admin Panel**: Comprehensive dashboard for content and user management
- **Bilingual Support**: Full English and French language support

## Tech Stack

- **Backend**: PHP 8.x
- **Database**: MySQL 8.x
- **Frontend**: Vanilla JavaScript, CSS3
- **AI**: Ollama (local LLM)
- **Server**: Apache (XAMPP)

## Setup Instructions

### Prerequisites

1. Install XAMPP (PHP 8.x, MySQL 8.x, Apache)
2. Install Git
3. (Optional) Install Ollama for AI features

### Installation

1. **Clone the repository**
   ```bash
   cd C:\xampp\htdocs
   git clone <your-repo-url> bihak-center
   cd bihak-center
   ```

2. **Create database**
   ```bash
   # Start MySQL in XAMPP
   # Open phpMyAdmin (http://localhost/phpmyadmin)
   # Create database: bihak (utf8mb4_unicode_ci)
   ```

3. **Import database schema**
   ```bash
   # Option 1: Via phpMyAdmin
   # - Select 'bihak' database
   # - Click Import
   # - Choose database-schema.sql
   
   # Option 2: Via command line
   mysql -u root bihak < database-schema.sql
   ```

4. **Configure database connection**
   ```bash
   # Copy example config
   cp config/database.example.php config/database.php
   
   # Edit config/database.php with your credentials
   ```

5. **Start the server**
   - Start Apache and MySQL in XAMPP Control Panel
   - Visit: http://localhost/bihak-center/public/

6. **Default admin login**
   - URL: http://localhost/bihak-center/public/admin/
   - Username: admin@bihak.com
   - Password: admin123

## Project Structure

```
bihak-center/
├── api/                    # API endpoints
│   ├── incubation-interactive/
│   ├── mentorship/
│   └── messaging/
├── assets/                 # Static assets
│   ├── css/
│   ├── js/
│   ├── images/
│   └── uploads/
├── config/                 # Configuration files
├── includes/              # Shared PHP includes
├── public/                # Web-accessible files
│   ├── admin/            # Admin panel
│   ├── mentorship/
│   └── messages/
├── scrapers/             # Opportunity scrapers
└── websocket/            # WebSocket server (optional)
```

## Key Components

### Scrapers
Located in `/scrapers/`. Run via admin panel or cron job:
- ScholarshipScraper.php
- JobScraper.php
- InternshipScraper.php
- GrantScraper.php

### Incubation Platform
Multi-phase entrepreneurship program with:
- Team management
- Exercise submissions
- Progress tracking
- Admin review system

### Messaging System
Real-time messaging with:
- Direct messages
- Conversation management
- File attachments
- Read receipts

## Development

### Working Across Computers

1. **Push changes from Computer A**
   ```bash
   git add .
   git commit -m "Description of changes"
   git push origin main
   ```

2. **Pull changes on Computer B**
   ```bash
   git pull origin main
   ```

3. **Export/Import Database Changes**
   ```bash
   # Export only structure changes
   mysqldump -u root --no-data bihak > database-schema.sql
   
   # Or export with sample data
   mysqldump -u root bihak > bihak-backup.sql
   ```

### Git Workflow

- Always pull before starting work: `git pull`
- Commit frequently with clear messages
- Don't commit sensitive files (already in .gitignore)
- Database schema changes should be committed

## Troubleshooting

### Database connection errors
- Check MySQL is running in XAMPP
- Verify credentials in `config/database.php`

### Scraper not working
- Check internet connection
- Verify source websites are accessible
- Check PHP curl extension is enabled

### AI assistant not responding
- Verify Ollama is installed and running
- Check `config/ai-provider.php` settings

## Documentation

See additional documentation files:
- IMPLEMENTATION-COMPLETE-SUMMARY.md
- INCUBATION-PLATFORM-SUMMARY.md
- MENTORSHIP-SYSTEM-COMPLETE.md
- Various feature-specific .md files in root

## License

Proprietary - Bihak Center

## Support

For issues or questions, contact the development team.
