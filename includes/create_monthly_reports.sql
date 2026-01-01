-- Migration: Create monthly scraper reports table
-- Date: 2026-01-01
-- Description: Track monthly statistics for scraped opportunities

-- Create monthly reports table
CREATE TABLE IF NOT EXISTS monthly_scraper_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_month DATE NOT NULL,
    scraper_type ENUM('scholarship', 'job', 'internship', 'grant', 'competition', 'all') NOT NULL,
    total_opportunities_scraped INT DEFAULT 0,
    opportunities_added INT DEFAULT 0,
    opportunities_updated INT DEFAULT 0,
    opportunities_expired INT DEFAULT 0,
    opportunities_rejected INT DEFAULT 0,
    active_opportunities INT DEFAULT 0,
    total_runs INT DEFAULT 0,
    successful_runs INT DEFAULT 0,
    failed_runs INT DEFAULT 0,
    report_generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_month_type (report_month, scraper_type),
    INDEX idx_report_month (report_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create archived opportunities table
CREATE TABLE IF NOT EXISTS archived_opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_opportunity_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('scholarship', 'job', 'internship', 'grant', 'competition') NOT NULL,
    organization VARCHAR(255),
    location VARCHAR(255),
    country VARCHAR(100),
    deadline DATE,
    application_url VARCHAR(500),
    amount VARCHAR(100),
    currency VARCHAR(10),
    source_name VARCHAR(100),
    archived_reason ENUM('expired', 'inactive', 'invalid') DEFAULT 'expired',
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    original_created_at TIMESTAMP NULL,
    INDEX idx_archived_at (archived_at),
    INDEX idx_type (type),
    INDEX idx_deadline (deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
