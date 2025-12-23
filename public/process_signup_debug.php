<?php
/**
 * Debug version of signup processor - shows all errors
 */

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>Signup Debug Mode</h2>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;} pre{background:white;padding:15px;border-radius:5px;overflow:auto;} .error{color:red;} .success{color:green;}</style>";

echo "<h3>Request Information:</h3>";
echo "<pre>";
echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n";
echo "</pre>";

echo "<h3>POST Data:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>FILES Data:</h3>";
echo "<pre>";
print_r($_FILES);
echo "</pre>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr><h3>Processing Signup...</h3>";

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/security.php';

    echo "<p class='success'>✓ Config files loaded</p>";

    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Not a POST request');
    }

    echo "<p class='success'>✓ POST request confirmed</p>";

    // Check CSRF token
    if (!isset($_POST['csrf_token'])) {
        throw new Exception('No CSRF token in POST data');
    }

    echo "<p class='success'>✓ CSRF token present: " . substr($_POST['csrf_token'], 0, 20) . "...</p>";

    if (!Security::validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception('CSRF token validation failed');
    }

    echo "<p class='success'>✓ CSRF token valid</p>";

    // Check required fields
    $required = ['full_name', 'email', 'password', 'password_confirm', 'date_of_birth', 'city', 'district', 'education_level', 'title', 'short_description', 'full_story'];

    echo "<p>Checking required fields:</p><ul>";
    foreach ($required as $field) {
        $value = $_POST[$field] ?? '';
        $status = !empty($value) ? "✓" : "✗";
        $class = !empty($value) ? "success" : "error";
        echo "<li class='$class'>$status $field: " . (empty($value) ? "MISSING" : "present (" . strlen($value) . " chars)") . "</li>";
    }
    echo "</ul>";

    // Check email
    $email = $_POST['email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email: $email");
    }
    echo "<p class='success'>✓ Email valid: $email</p>";

    // Check passwords
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 8) {
        throw new Exception("Password too short: " . strlen($password) . " characters");
    }
    echo "<p class='success'>✓ Password length OK: " . strlen($password) . " characters</p>";

    if ($password !== $password_confirm) {
        throw new Exception("Passwords don't match");
    }
    echo "<p class='success'>✓ Passwords match</p>";

    // Check database connection
    $conn = getDatabaseConnection();
    echo "<p class='success'>✓ Database connected</p>";

    // Check if email exists
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$checkEmail) {
        throw new Exception("Failed to prepare email check: " . $conn->error);
    }

    $checkEmail->bind_param('s', $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        throw new Exception("Email already registered: $email");
    }
    $checkEmail->close();

    echo "<p class='success'>✓ Email available</p>";

    // Check images
    if (!isset($_FILES['profile_images']) || empty($_FILES['profile_images']['name'][0])) {
        throw new Exception("No images uploaded");
    }

    $imageCount = count($_FILES['profile_images']['name']);
    echo "<p class='success'>✓ Images uploaded: $imageCount files</p>";

    echo "<p>Image details:</p><ul>";
    for ($i = 0; $i < $imageCount; $i++) {
        $size = $_FILES['profile_images']['size'][$i];
        $type = $_FILES['profile_images']['type'][$i];
        $error = $_FILES['profile_images']['error'][$i];
        $name = $_FILES['profile_images']['name'][$i];

        echo "<li>Image $i: $name<br>";
        echo "Size: " . round($size / 1024 / 1024, 2) . " MB<br>";
        echo "Type: $type<br>";
        echo "Error: $error (" . getUploadErrorMessage($error) . ")</li>";
    }
    echo "</ul>";

    echo "<h3 class='success'>All Validations Passed!</h3>";
    echo "<p>The form data looks good. The actual signup should work.</p>";

    closeDatabaseConnection($conn);

} catch (Exception $e) {
    echo "<h3 class='error'>Error: " . $e->getMessage() . "</h3>";
    echo "<pre class='error'>" . $e->getTraceAsString() . "</pre>";
}

function getUploadErrorMessage($code) {
    switch ($code) {
        case UPLOAD_ERR_OK:
            return 'Success';
        case UPLOAD_ERR_INI_SIZE:
            return 'File too large (php.ini)';
        case UPLOAD_ERR_FORM_SIZE:
            return 'File too large (form)';
        case UPLOAD_ERR_PARTIAL:
            return 'Partial upload';
        case UPLOAD_ERR_NO_FILE:
            return 'No file';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'No temp directory';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Cannot write to disk';
        default:
            return 'Unknown error';
    }
}

echo "<hr>";
echo "<p><a href='signup.php'>Back to Signup Form</a></p>";
?>
