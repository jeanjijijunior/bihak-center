<?php
/**
 * Content Manager - Edit Static Page Content
 * Allows non-technical admins to edit page content without touching code
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

// Require authentication
Auth::requireAuth();

$admin = Auth::user();
$conn = getDatabaseConnection();

$success = '';
$error = '';

// Get selected page
$selected_page = $_GET['page'] ?? 'home';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $updates = $_POST['content'] ?? [];
        $success_count = 0;

        foreach ($updates as $id => $content) {
            $content_en = trim($content['en'] ?? '');
            $content_fr = trim($content['fr'] ?? '');

            $stmt = $conn->prepare("
                UPDATE page_contents
                SET content_en = ?, content_fr = ?, updated_by = ?
                WHERE id = ?
            ");
            $stmt->bind_param('ssii', $content_en, $content_fr, $admin['id'], $id);

            if ($stmt->execute()) {
                $success_count++;
            }
        }

        if ($success_count > 0) {
            Auth::logActivity($admin['id'], 'content_updated', 'page_content', 0, "Updated {$success_count} content items on {$selected_page} page");
            $success = "Successfully updated {$success_count} content item(s)!";
        } else {
            $error = 'No changes were made.';
        }
    }
}

// Get all pages
$pages_query = "SELECT DISTINCT page_name FROM page_contents WHERE is_active = TRUE ORDER BY page_name";
$pages_result = $conn->query($pages_query);
$pages = [];
while ($row = $pages_result->fetch_assoc()) {
    $pages[] = $row['page_name'];
}

// Get content for selected page
$stmt = $conn->prepare("
    SELECT * FROM page_contents
    WHERE page_name = ? AND is_active = TRUE
    ORDER BY display_order, section_key
");
$stmt->bind_param('s', $selected_page);
$stmt->execute();
$contents_result = $stmt->get_result();
$contents = [];
while ($row = $contents_result->fetch_assoc()) {
    $contents[] = $row;
}

$csrf_token = Security::generateCSRFToken();

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Manager - Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/admin-dashboard.css">
    <script src="https://cdn.tiny.mce.com/1/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        .content-manager {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }

        .page-header h1 {
            color: #1cabe2;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #6b7280;
            font-size: 1.05rem;
        }

        .page-selector {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .page-selector h3 {
            color: #374151;
            font-size: 1.1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .page-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .page-tab {
            padding: 10px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-decoration: none;
            color: #6b7280;
            font-weight: 600;
            transition: all 0.3s;
            text-transform: capitalize;
        }

        .page-tab:hover {
            border-color: #1cabe2;
            color: #1cabe2;
            background: #f0f9ff;
        }

        .page-tab.active {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            border-color: #1cabe2;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .content-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .content-item {
            margin-bottom: 35px;
            padding: 25px;
            background: #f9fafb;
            border-radius: 10px;
            border-left: 4px solid #1cabe2;
        }

        .content-item:last-child {
            margin-bottom: 0;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .content-header h3 {
            color: #1cabe2;
            font-size: 1.2rem;
            margin: 0;
        }

        .content-type-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            background: #e5e7eb;
            color: #6b7280;
        }

        .content-type-badge.heading {
            background: #dbeafe;
            color: #1e40af;
        }

        .content-type-badge.paragraph {
            background: #d1fae5;
            color: #065f46;
        }

        .content-type-badge.html {
            background: #fce7f3;
            color: #9f1239;
        }

        .language-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .lang-tab {
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px 8px 0 0;
            background: white;
            cursor: pointer;
            font-weight: 600;
            color: #6b7280;
            transition: all 0.3s;
        }

        .lang-tab.active {
            border-color: #1cabe2;
            border-bottom-color: white;
            color: #1cabe2;
            background: white;
        }

        .lang-content {
            display: none;
        }

        .lang-content.active {
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #1cabe2;
            box-shadow: 0 0 0 3px rgba(28, 171, 226, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #e5e7eb;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(28, 171, 226, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .page-tabs {
                flex-direction: column;
            }

            .page-tab {
                text-align: center;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="content-manager">
        <div class="page-header">
            <h1>📝 Content Manager</h1>
            <p>Edit static page content without touching code. Changes are reflected immediately on the website.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Page Selector -->
        <div class="page-selector">
            <h3>
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>
                </svg>
                Select Page to Edit
            </h3>
            <div class="page-tabs">
                <?php foreach ($pages as $page): ?>
                    <a href="?page=<?php echo urlencode($page); ?>"
                       class="page-tab <?php echo $page === $selected_page ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars(ucfirst($page)); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Content Form -->
        <?php if (count($contents) > 0): ?>
            <form method="POST" class="content-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <?php foreach ($contents as $content): ?>
                    <div class="content-item">
                        <div class="content-header">
                            <h3><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $content['section_key']))); ?></h3>
                            <span class="content-type-badge <?php echo $content['content_type']; ?>">
                                <?php echo htmlspecialchars($content['content_type']); ?>
                            </span>
                        </div>

                        <div class="language-tabs">
                            <button type="button" class="lang-tab active" onclick="switchLang(event, 'en-<?php echo $content['id']; ?>')">
                                🇬🇧 English
                            </button>
                            <button type="button" class="lang-tab" onclick="switchLang(event, 'fr-<?php echo $content['id']; ?>')">
                                🇫🇷 French
                            </button>
                        </div>

                        <!-- English Content -->
                        <div id="en-<?php echo $content['id']; ?>" class="lang-content active">
                            <?php if ($content['content_type'] === 'html'): ?>
                                <textarea name="content[<?php echo $content['id']; ?>][en]"
                                          class="form-control html-editor"
                                          rows="6"><?php echo htmlspecialchars($content['content_en']); ?></textarea>
                            <?php elseif ($content['content_type'] === 'paragraph'): ?>
                                <textarea name="content[<?php echo $content['id']; ?>][en]"
                                          class="form-control"
                                          rows="4"><?php echo htmlspecialchars($content['content_en']); ?></textarea>
                            <?php else: ?>
                                <input type="text"
                                       name="content[<?php echo $content['id']; ?>][en]"
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($content['content_en']); ?>">
                            <?php endif; ?>
                        </div>

                        <!-- French Content -->
                        <div id="fr-<?php echo $content['id']; ?>" class="lang-content">
                            <?php if ($content['content_type'] === 'html'): ?>
                                <textarea name="content[<?php echo $content['id']; ?>][fr]"
                                          class="form-control html-editor"
                                          rows="6"><?php echo htmlspecialchars($content['content_fr']); ?></textarea>
                            <?php elseif ($content['content_type'] === 'paragraph'): ?>
                                <textarea name="content[<?php echo $content['id']; ?>][fr]"
                                          class="form-control"
                                          rows="4"><?php echo htmlspecialchars($content['content_fr']); ?></textarea>
                            <?php else: ?>
                                <input type="text"
                                       name="content[<?php echo $content['id']; ?>][fr]"
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($content['content_fr']); ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="btn-group">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                        </svg>
                        Back to Dashboard
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                        </svg>
                        Save All Changes
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                </svg>
                <h3>No Content Found</h3>
                <p>No editable content available for this page yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Initialize TinyMCE for HTML content
        tinymce.init({
            selector: '.html-editor',
            height: 300,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
                'searchreplace', 'visualblocks', 'code',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright | bullist numlist | link image media | code',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size:16px; line-height:1.6 }'
        });

        // Language tab switcher
        function switchLang(event, contentId) {
            event.preventDefault();

            // Get parent content item
            const contentItem = event.target.closest('.content-item');

            // Hide all language contents in this item
            const allContents = contentItem.querySelectorAll('.lang-content');
            allContents.forEach(content => content.classList.remove('active'));

            // Remove active class from all tabs in this item
            const allTabs = contentItem.querySelectorAll('.lang-tab');
            allTabs.forEach(tab => tab.classList.remove('active'));

            // Show selected content
            document.getElementById(contentId).classList.add('active');

            // Activate clicked tab
            event.target.classList.add('active');
        }

        // Prevent accidental navigation away with unsaved changes
        let formChanged = false;
        const form = document.querySelector('.content-form');

        if (form) {
            form.addEventListener('input', () => {
                formChanged = true;
            });

            form.addEventListener('submit', () => {
                formChanged = false;
            });

            window.addEventListener('beforeunload', (e) => {
                if (formChanged) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        }
    </script>
</body>
</html>
