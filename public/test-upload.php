<?php
/**
 * Upload Diagnostics Page
 * Shows PHP upload configuration and tests upload
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Diagnostics</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #1cabe2;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .value {
            color: #333;
        }
        .good {
            color: #10b981;
            font-weight: bold;
        }
        .bad {
            color: #ef4444;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>🔍 Upload Diagnostics</h1>

    <div class="section">
        <h2>PHP Upload Settings</h2>
        <?php
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        $max_files = ini_get('max_file_uploads');
        $memory_limit = ini_get('memory_limit');

        function bytes_to_mb($val) {
            $val = trim($val);
            $last = strtolower($val[strlen($val)-1]);
            $val = (int) $val;
            switch($last) {
                case 'g': $val *= 1024;
                case 'm': $val *= 1024;
                case 'k': $val *= 1024;
            }
            return round($val / 1048576, 2);
        }

        $upload_mb = bytes_to_mb($upload_max);
        $post_mb = bytes_to_mb($post_max);
        ?>

        <div class="info-row">
            <span class="label">upload_max_filesize:</span>
            <span class="value <?php echo $upload_mb >= 5 ? 'good' : 'bad'; ?>">
                <?php echo $upload_max; ?> (<?php echo $upload_mb; ?> MB)
            </span>
        </div>

        <div class="info-row">
            <span class="label">post_max_size:</span>
            <span class="value <?php echo $post_mb >= 10 ? 'good' : 'bad'; ?>">
                <?php echo $post_max; ?> (<?php echo $post_mb; ?> MB)
            </span>
        </div>

        <div class="info-row">
            <span class="label">max_file_uploads:</span>
            <span class="value <?php echo $max_files >= 5 ? 'good' : 'bad'; ?>">
                <?php echo $max_files; ?>
            </span>
        </div>

        <div class="info-row">
            <span class="label">memory_limit:</span>
            <span class="value"><?php echo $memory_limit; ?></span>
        </div>
    </div>

    <div class="section">
        <h2>Upload Directory Status</h2>
        <?php
        $uploadDir = __DIR__ . '/../assets/uploads/profiles/';
        ?>

        <div class="info-row">
            <span class="label">Upload Path:</span>
            <span class="value"><?php echo $uploadDir; ?></span>
        </div>

        <div class="info-row">
            <span class="label">Directory Exists:</span>
            <span class="value <?php echo file_exists($uploadDir) ? 'good' : 'bad'; ?>">
                <?php echo file_exists($uploadDir) ? 'YES' : 'NO'; ?>
            </span>
        </div>

        <div class="info-row">
            <span class="label">Is Writable:</span>
            <span class="value <?php echo is_writable($uploadDir) ? 'good' : 'bad'; ?>">
                <?php echo is_writable($uploadDir) ? 'YES' : 'NO'; ?>
            </span>
        </div>

        <div class="info-row">
            <span class="label">Permissions:</span>
            <span class="value">
                <?php echo file_exists($uploadDir) ? substr(sprintf('%o', fileperms($uploadDir)), -4) : 'N/A'; ?>
            </span>
        </div>

        <div class="info-row">
            <span class="label">Owner:</span>
            <span class="value">
                <?php
                if (file_exists($uploadDir)) {
                    $owner = posix_getpwuid(fileowner($uploadDir));
                    echo $owner['name'] . ' (UID: ' . fileowner($uploadDir) . ')';
                } else {
                    echo 'N/A';
                }
                ?>
            </span>
        </div>

        <div class="info-row">
            <span class="label">Free Space:</span>
            <span class="value">
                <?php echo round(disk_free_space($uploadDir) / 1073741824, 2); ?> GB
            </span>
        </div>
    </div>

    <div class="section">
        <h2>PHP Temporary Directory</h2>
        <?php
        $tmpDir = sys_get_temp_dir();
        ?>

        <div class="info-row">
            <span class="label">Temp Directory:</span>
            <span class="value"><?php echo $tmpDir; ?></span>
        </div>

        <div class="info-row">
            <span class="label">Is Writable:</span>
            <span class="value <?php echo is_writable($tmpDir) ? 'good' : 'bad'; ?>">
                <?php echo is_writable($tmpDir) ? 'YES' : 'NO'; ?>
            </span>
        </div>
    </div>

    <div class="section">
        <h2>Recommendations</h2>
        <?php
        $issues = [];

        if ($upload_mb < 5) {
            $issues[] = "⚠️ upload_max_filesize is too small. Should be at least 5M for image uploads.";
        }

        if ($post_mb < 10) {
            $issues[] = "⚠️ post_max_size is too small. Should be at least 10M for multiple image uploads.";
        }

        if ($max_files < 5) {
            $issues[] = "⚠️ max_file_uploads is too small. Should be at least 5.";
        }

        if (!file_exists($uploadDir)) {
            $issues[] = "❌ Upload directory does not exist!";
        } elseif (!is_writable($uploadDir)) {
            $issues[] = "❌ Upload directory is not writable!";
        }

        if (!is_writable($tmpDir)) {
            $issues[] = "❌ Temporary directory is not writable!";
        }

        if (empty($issues)) {
            echo '<p class="good">✅ All checks passed! Upload should work.</p>';
        } else {
            echo '<ul style="color: #ef4444;">';
            foreach ($issues as $issue) {
                echo "<li>$issue</li>";
            }
            echo '</ul>';
        }
        ?>
    </div>

    <p style="text-align: center; color: #888; margin-top: 40px;">
        <a href="signup.php" style="color: #1cabe2;">← Back to Signup</a>
    </p>
</body>
</html>
