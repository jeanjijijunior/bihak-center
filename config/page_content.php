<?php
/**
 * Page Content Helper Functions
 * Load dynamic content from database for static pages
 */

class PageContent {
    private static $cache = [];
    private static $language = 'en';

    /**
     * Set the current language
     */
    public static function setLanguage($lang) {
        self::$language = ($lang === 'fr') ? 'fr' : 'en';
    }

    /**
     * Get content by page name and section key
     */
    public static function get($page_name, $section_key, $default = '') {
        // Check cache first
        $cache_key = "{$page_name}_{$section_key}_" . self::$language;
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        try {
            $conn = getDatabaseConnection();

            $stmt = $conn->prepare("
                SELECT content_en, content_fr, content_type
                FROM page_contents
                WHERE page_name = ? AND section_key = ? AND is_active = TRUE
                LIMIT 1
            ");
            $stmt->bind_param('ss', $page_name, $section_key);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $content = (self::$language === 'fr') ? $row['content_fr'] : $row['content_en'];

                // If French content is empty, fallback to English
                if (empty($content) && self::$language === 'fr') {
                    $content = $row['content_en'];
                }

                // Cache the result
                self::$cache[$cache_key] = $content;

                closeDatabaseConnection($conn);
                return $content;
            }

            closeDatabaseConnection($conn);
        } catch (Exception $e) {
            error_log('PageContent Error: ' . $e->getMessage());
        }

        return $default;
    }

    /**
     * Get all content for a page
     */
    public static function getAll($page_name) {
        try {
            $conn = getDatabaseConnection();

            $stmt = $conn->prepare("
                SELECT section_key, content_en, content_fr, content_type, display_order
                FROM page_contents
                WHERE page_name = ? AND is_active = TRUE
                ORDER BY display_order, section_key
            ");
            $stmt->bind_param('s', $page_name);
            $stmt->execute();
            $result = $stmt->get_result();

            $contents = [];
            while ($row = $result->fetch_assoc()) {
                $content = (self::$language === 'fr') ? $row['content_fr'] : $row['content_en'];

                // If French content is empty, fallback to English
                if (empty($content) && self::$language === 'fr') {
                    $content = $row['content_en'];
                }

                $contents[$row['section_key']] = $content;
            }

            closeDatabaseConnection($conn);
            return $contents;
        } catch (Exception $e) {
            error_log('PageContent Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if content management table exists
     */
    public static function isAvailable() {
        try {
            $conn = getDatabaseConnection();
            $result = $conn->query("SHOW TABLES LIKE 'page_contents'");
            $exists = $result->num_rows > 0;
            closeDatabaseConnection($conn);
            return $exists;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Clear cache (useful after content updates)
     */
    public static function clearCache() {
        self::$cache = [];
    }
}
