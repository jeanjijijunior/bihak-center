<?php
/**
 * Mentor Preferences Page
 * Allows mentors to set their mentoring preferences and availability
 */

session_start();
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in as sponsor/mentor
if (!isset($_SESSION['sponsor_id'])) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$conn = getDatabaseConnection();
$mentor_id = $_SESSION['sponsor_id'];

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preferred_sectors = isset($_POST['sectors']) ? json_encode($_POST['sectors']) : null;
    $preferred_skills = isset($_POST['skills']) ? json_encode(array_filter(array_map('trim', explode(',', $_POST['skills'])))) : null;
    $max_mentees = intval($_POST['max_mentees']);
    $availability_hours = intval($_POST['availability_hours']);
    $preferred_languages = isset($_POST['languages']) ? json_encode($_POST['languages']) : null;

    // Check if preferences exist
    $stmt = $conn->prepare("SELECT id FROM mentor_preferences WHERE mentor_id = ?");
    $stmt->bind_param('i', $mentor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->fetch_assoc();

    if ($exists) {
        // Update existing preferences
        $stmt = $conn->prepare("
            UPDATE mentor_preferences
            SET preferred_sectors = ?, preferred_skills = ?, max_mentees = ?,
                availability_hours = ?, preferred_languages = ?, updated_at = NOW()
            WHERE mentor_id = ?
        ");
        $stmt->bind_param('ssiisi', $preferred_sectors, $preferred_skills, $max_mentees,
                         $availability_hours, $preferred_languages, $mentor_id);
    } else {
        // Insert new preferences
        $stmt = $conn->prepare("
            INSERT INTO mentor_preferences
            (mentor_id, preferred_sectors, preferred_skills, max_mentees, availability_hours, preferred_languages)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('ississ', $mentor_id, $preferred_sectors, $preferred_skills,
                         $max_mentees, $availability_hours, $preferred_languages);
    }

    if ($stmt->execute()) {
        // Redirect to dashboard with success message
        header('Location: dashboard.php?preferences_saved=1');
        exit;
    } else {
        $error_message = 'Error saving preferences. Please try again.';
    }
}

// Load current preferences
$stmt = $conn->prepare("SELECT * FROM mentor_preferences WHERE mentor_id = ?");
$stmt->bind_param('i', $mentor_id);
$stmt->execute();
$result = $stmt->get_result();
$preferences = $result->fetch_assoc();

$sectors = $preferences ? json_decode($preferences['preferred_sectors'], true) : [];
$skills = $preferences ? implode(', ', json_decode($preferences['preferred_skills'], true) ?: []) : '';
$max_mentees = $preferences ? $preferences['max_mentees'] : 3;
$availability_hours = $preferences ? $preferences['availability_hours'] : 5;
$languages = $preferences ? json_decode($preferences['preferred_languages'], true) : [];

closeDatabaseConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Preferences - Bihak Center</title>
    <link rel="stylesheet" href="../../assets/css/header_new.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding-top: 80px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 1.1rem;
            color: #7f8c8d;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .form-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .form-group label .required {
            color: #ef4444;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #10b981;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-item label {
            margin: 0;
            font-weight: 400;
            cursor: pointer;
        }

        .help-text {
            font-size: 0.9rem;
            color: #64748b;
            margin-top: 5px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
        }

        .btn {
            flex: 1;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #10b981;
            color: white;
        }

        .btn-primary:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #64748b;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        @media (max-width: 768px) {
            .form-card {
                padding: 25px;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .checkbox-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/header_new.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>⚙️ Mentor Preferences</h1>
            <p>Set your mentoring preferences to help us match you with the right mentees</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                ✅ <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                ❌ <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="form-card">
            <div class="form-group">
                <label>
                    Maximum Number of Mentees <span class="required">*</span>
                </label>
                <input
                    type="number"
                    name="max_mentees"
                    value="<?php echo htmlspecialchars($max_mentees); ?>"
                    min="1"
                    max="10"
                    required
                >
                <div class="help-text">How many mentees can you actively mentor at once?</div>
            </div>

            <div class="form-group">
                <label>
                    Availability (Hours per Month) <span class="required">*</span>
                </label>
                <input
                    type="number"
                    name="availability_hours"
                    value="<?php echo htmlspecialchars($availability_hours); ?>"
                    min="1"
                    max="100"
                    required
                >
                <div class="help-text">How many hours per month can you dedicate to mentoring?</div>
            </div>

            <div class="form-group">
                <label>Preferred Sectors</label>
                <div class="checkbox-group">
                    <?php
                    $sector_options = [
                        'Technology', 'Business', 'Healthcare', 'Education',
                        'Agriculture', 'Finance', 'Marketing', 'Engineering',
                        'Arts & Design', 'Social Impact', 'Environment', 'Other'
                    ];
                    foreach ($sector_options as $sector):
                        $checked = in_array($sector, $sectors) ? 'checked' : '';
                    ?>
                        <div class="checkbox-item">
                            <input
                                type="checkbox"
                                name="sectors[]"
                                value="<?php echo $sector; ?>"
                                id="sector_<?php echo str_replace(' ', '_', $sector); ?>"
                                <?php echo $checked; ?>
                            >
                            <label for="sector_<?php echo str_replace(' ', '_', $sector); ?>">
                                <?php echo $sector; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="help-text">Select the industries you can provide guidance in</div>
            </div>

            <div class="form-group">
                <label>Skills You Can Mentor</label>
                <textarea
                    name="skills"
                    rows="4"
                    placeholder="e.g., Leadership, Project Management, Python, Marketing Strategy, Public Speaking"
                ><?php echo htmlspecialchars($skills); ?></textarea>
                <div class="help-text">Separate skills with commas</div>
            </div>

            <div class="form-group">
                <label>Preferred Languages</label>
                <div class="checkbox-group">
                    <?php
                    $language_options = ['English', 'French', 'Kinyarwanda', 'Swahili'];
                    foreach ($language_options as $language):
                        $checked = in_array($language, $languages) ? 'checked' : '';
                    ?>
                        <div class="checkbox-item">
                            <input
                                type="checkbox"
                                name="languages[]"
                                value="<?php echo $language; ?>"
                                id="lang_<?php echo $language; ?>"
                                <?php echo $checked; ?>
                            >
                            <label for="lang_<?php echo $language; ?>">
                                <?php echo $language; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="help-text">Languages you're comfortable mentoring in</div>
            </div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">💾 Save Preferences</button>
            </div>
        </form>
    </div>

    <?php include __DIR__ . '/../../includes/chat_widget.php'; ?>
</body>
</html>
