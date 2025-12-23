<?php
/**
 * Create Incubation Team
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';
$conn = getDatabaseConnection();

// Get active program
$program_query = "SELECT * FROM incubation_programs WHERE is_active = TRUE LIMIT 1";
$program_result = $conn->query($program_query);
$program = $program_result->fetch_assoc();

if (!$program) {
    die("No active program found.");
}

// Check if user already has a team
$existing_team_query = "
    SELECT t.id FROM incubation_teams t
    JOIN team_members tm ON t.id = tm.team_id
    WHERE tm.user_id = ? AND tm.is_active = TRUE
    AND t.program_id = ?
";
$stmt = $conn->prepare($existing_team_query);
$stmt->bind_param('ii', $user_id, $program['id']);
$stmt->execute();
$existing_team = $stmt->get_result()->fetch_assoc();

if ($existing_team) {
    header('Location: incubation-dashboard.php');
    exit;
}

// Get user info
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = trim($_POST['team_name'] ?? '');
    $team_description = trim($_POST['team_description'] ?? '');
    $member_emails = array_filter(array_map('trim', $_POST['member_emails'] ?? []));

    if (empty($team_name)) {
        $error_message = $lang === 'fr' ? 'Le nom de l\'équipe est requis.' : 'Team name is required.';
    } else {
        // Create team
        $conn->begin_transaction();

        try {
            // Insert team
            $insert_team = "
                INSERT INTO incubation_teams (program_id, team_name, team_description, status)
                VALUES (?, ?, ?, 'forming')
            ";
            $stmt = $conn->prepare($insert_team);
            $stmt->bind_param('iss', $program['id'], $team_name, $team_description);
            $stmt->execute();
            $team_id = $conn->insert_id;

            // Add creator as leader
            $add_leader = "
                INSERT INTO team_members (team_id, user_id, role)
                VALUES (?, ?, 'leader')
            ";
            $stmt = $conn->prepare($add_leader);
            $stmt->bind_param('ii', $team_id, $user_id);
            $stmt->execute();

            // Log activity
            $log_activity = "
                INSERT INTO team_activity_log (team_id, user_id, activity_type, description)
                VALUES (?, ?, 'team_created', ?)
            ";
            $stmt = $conn->prepare($log_activity);
            $description = "Team '$team_name' created";
            $stmt->bind_param('iis', $team_id, $user_id, $description);
            $stmt->execute();

            // Send invitations
            foreach ($member_emails as $email) {
                if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));

                    // Check if user exists
                    $check_user = "SELECT id FROM users WHERE email = ?";
                    $stmt = $conn->prepare($check_user);
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $invitee = $stmt->get_result()->fetch_assoc();
                    $invitee_id = $invitee['id'] ?? null;

                    $invite_query = "
                        INSERT INTO team_invitations
                        (team_id, inviter_user_id, invitee_email, invitee_user_id, invitation_token, expires_at)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ";
                    $stmt = $conn->prepare($invite_query);
                    $stmt->bind_param('iisiss', $team_id, $user_id, $email, $invitee_id, $token, $expires_at);
                    $stmt->execute();
                }
            }

            $conn->commit();

            header('Location: incubation-dashboard.php?team_created=1');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $lang === 'fr' ? 'Erreur lors de la création de l\'équipe.' : 'Error creating team.';
        }
    }
}

closeDatabaseConnection($conn);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'fr' ? 'Créer une équipe' : 'Create a Team'; ?> - Bihak Center</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2rem;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .required {
            color: #e74c3c;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .help-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        .member-invites {
            margin-top: 20px;
        }

        .email-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .email-input-group input {
            flex: 1;
        }

        .btn-remove {
            padding: 12px 20px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-add {
            display: inline-block;
            padding: 10px 20px;
            background: #f0f0f0;
            color: #333;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-add:hover {
            background: #e0e0e0;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 15px;
            font-size: 1.1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
        }

        .info-box strong {
            color: #1976D2;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/incubation-header.php'; ?>

    <div class="container">
        <h1><?php echo $lang === 'fr' ? 'Créer votre équipe' : 'Create Your Team'; ?></h1>
        <p class="subtitle">
            <?php echo $lang === 'fr'
                ? 'Formez une équipe de 3 à 5 membres pour commencer le programme d\'incubation.'
                : 'Form a team of 3-5 members to start the incubation program.'; ?>
        </p>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="info-box">
            <strong><?php echo $lang === 'fr' ? 'Remarque :' : 'Note:'; ?></strong>
            <?php echo $lang === 'fr'
                ? 'Vous serez le chef d\'équipe. Vous pouvez inviter d\'autres membres maintenant ou plus tard.'
                : 'You will be the team leader. You can invite other members now or later.'; ?>
        </div>

        <form method="POST" id="createTeamForm">
            <div class="form-group">
                <label for="team_name">
                    <?php echo $lang === 'fr' ? 'Nom de l\'équipe' : 'Team Name'; ?>
                    <span class="required">*</span>
                </label>
                <input type="text" id="team_name" name="team_name" required
                       placeholder="<?php echo $lang === 'fr' ? 'ex: Innovateurs Bihak' : 'e.g., Bihak Innovators'; ?>">
            </div>

            <div class="form-group">
                <label for="team_description">
                    <?php echo $lang === 'fr' ? 'Description de l\'équipe' : 'Team Description'; ?>
                </label>
                <textarea id="team_description" name="team_description"
                          placeholder="<?php echo $lang === 'fr'
                              ? 'Décrivez votre équipe et votre idée de projet...'
                              : 'Describe your team and project idea...'; ?>"></textarea>
            </div>

            <div class="form-group">
                <label>
                    <?php echo $lang === 'fr' ? 'Inviter des membres (optionnel)' : 'Invite Members (Optional)'; ?>
                </label>
                <p class="help-text">
                    <?php echo $lang === 'fr'
                        ? 'Ajoutez les emails des personnes que vous souhaitez inviter à rejoindre votre équipe.'
                        : 'Add email addresses of people you want to invite to join your team.'; ?>
                </p>

                <div class="member-invites" id="memberInvites">
                    <div class="email-input-group">
                        <input type="email" name="member_emails[]"
                               placeholder="<?php echo $lang === 'fr' ? 'Email du membre' : 'Member email'; ?>">
                        <button type="button" class="btn-remove" onclick="removeEmailField(this)">×</button>
                    </div>
                </div>

                <button type="button" class="btn-add" onclick="addEmailField()">
                    + <?php echo $lang === 'fr' ? 'Ajouter un autre membre' : 'Add Another Member'; ?>
                </button>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">
                    <?php echo $lang === 'fr' ? 'Créer l\'équipe' : 'Create Team'; ?>
                </button>
                <a href="incubation-program.php" class="btn btn-secondary" style="text-align: center; text-decoration: none; line-height: 1.5;">
                    <?php echo $lang === 'fr' ? 'Annuler' : 'Cancel'; ?>
                </a>
            </div>
        </form>
    </div>

    <script>
        function addEmailField() {
            const container = document.getElementById('memberInvites');
            const div = document.createElement('div');
            div.className = 'email-input-group';
            div.innerHTML = `
                <input type="email" name="member_emails[]"
                       placeholder="<?php echo $lang === 'fr' ? 'Email du membre' : 'Member email'; ?>">
                <button type="button" class="btn-remove" onclick="removeEmailField(this)">×</button>
            `;
            container.appendChild(div);
        }

        function removeEmailField(btn) {
            const container = document.getElementById('memberInvites');
            if (container.children.length > 1) {
                btn.parentElement.remove();
            }
        }
    </script>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../includes/chat_widget.php'; ?>
</body>
</html>
