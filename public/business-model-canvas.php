<?php
/**
 * Business Model Canvas Tool
 * Interactive 9-block canvas for final project deliverable
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

// Get user's team
$team_query = "
    SELECT t.*, tm.role
    FROM incubation_teams t
    JOIN team_members tm ON t.id = tm.team_id
    WHERE tm.user_id = ? AND tm.is_active = TRUE
    LIMIT 1
";
$stmt = $conn->prepare($team_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$team = $stmt->get_result()->fetch_assoc();

if (!$team) {
    header('Location: incubation-team-create.php');
    exit;
}

$team_id = $team['id'];

// Get existing canvas or create new
$canvas_query = "SELECT * FROM business_model_canvas WHERE team_id = ? ORDER BY version DESC LIMIT 1";
$stmt = $conn->prepare($canvas_query);
$stmt->bind_param('i', $team_id);
$stmt->execute();
$canvas = $stmt->get_result()->fetch_assoc();

$success_message = '';
$error_message = '';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key_partners = trim($_POST['key_partners'] ?? '');
    $key_activities = trim($_POST['key_activities'] ?? '');
    $key_resources = trim($_POST['key_resources'] ?? '');
    $value_propositions = trim($_POST['value_propositions'] ?? '');
    $customer_relationships = trim($_POST['customer_relationships'] ?? '');
    $channels = trim($_POST['channels'] ?? '');
    $customer_segments = trim($_POST['customer_segments'] ?? '');
    $cost_structure = trim($_POST['cost_structure'] ?? '');
    $revenue_streams = trim($_POST['revenue_streams'] ?? '');
    $social_impact = trim($_POST['social_impact'] ?? '');
    $environmental_impact = trim($_POST['environmental_impact'] ?? '');
    $status = $_POST['status'] ?? 'draft';

    if (empty($value_propositions)) {
        $error_message = $lang === 'fr' ? 'La proposition de valeur est requise.' : 'Value Proposition is required.';
    } else {
        $version = ($canvas['version'] ?? 0) + 1;

        $insert_query = "
            INSERT INTO business_model_canvas
            (team_id, key_partners, key_activities, key_resources, value_propositions,
             customer_relationships, channels, customer_segments, cost_structure, revenue_streams,
             social_impact, environmental_impact, version, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param('issssssssssiis', $team_id, $key_partners, $key_activities, $key_resources,
            $value_propositions, $customer_relationships, $channels, $customer_segments,
            $cost_structure, $revenue_streams, $social_impact, $environmental_impact, $version, $status);

        if ($stmt->execute()) {
            // Log activity
            $log_query = "
                INSERT INTO team_activity_log (team_id, user_id, activity_type, description)
                VALUES (?, ?, 'canvas_updated', ?)
            ";
            $stmt = $conn->prepare($log_query);
            $description = ($status === 'completed') ? "Business Model Canvas completed" : "Business Model Canvas saved as draft";
            $stmt->bind_param('iis', $team_id, $user_id, $description);
            $stmt->execute();

            $success_message = ($status === 'completed')
                ? ($lang === 'fr' ? 'Canvas complété avec succès !' : 'Canvas completed successfully!')
                : ($lang === 'fr' ? 'Canvas sauvegardé.' : 'Canvas saved.');

            // Reload canvas
            $stmt = $conn->prepare($canvas_query);
            $stmt->bind_param('i', $team_id);
            $stmt->execute();
            $canvas = $stmt->get_result()->fetch_assoc();
        } else {
            $error_message = $lang === 'fr' ? 'Erreur lors de la sauvegarde.' : 'Error saving canvas.';
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
    <title><?php echo $lang === 'fr' ? 'Business Model Canvas' : 'Business Model Canvas'; ?> - <?php echo htmlspecialchars($team['team_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.95;
        }

        .breadcrumb {
            margin-bottom: 10px;
        }

        .breadcrumb a {
            color: white;
            text-decoration: none;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
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

        .canvas-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-template-rows: repeat(3, auto);
            gap: 15px;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .canvas-block {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            background: #fafafa;
            transition: all 0.3s;
        }

        .canvas-block:hover {
            border-color: #1cabe2;
            background: white;
        }

        .block-title {
            font-weight: 700;
            color: #1cabe2;
            margin-bottom: 15px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .block-title::before {
            content: '';
            width: 4px;
            height: 20px;
            background: #1cabe2;
            border-radius: 2px;
        }

        textarea {
            width: 100%;
            min-height: 150px;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            resize: vertical;
            background: white;
        }

        textarea:focus {
            outline: none;
            border-color: #1cabe2;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .block-help {
            font-size: 0.85rem;
            color: #666;
            margin-top: 10px;
            font-style: italic;
        }

        /* Grid positioning */
        .block-key-partners {
            grid-column: 1;
            grid-row: 1 / 3;
        }

        .block-key-activities {
            grid-column: 2;
            grid-row: 1;
        }

        .block-value-propositions {
            grid-column: 3;
            grid-row: 1 / 3;
            border-color: #f5576c;
        }

        .block-value-propositions .block-title {
            color: #f5576c;
        }

        .block-value-propositions .block-title::before {
            background: #f5576c;
        }

        .block-customer-relationships {
            grid-column: 4;
            grid-row: 1;
        }

        .block-customer-segments {
            grid-column: 5;
            grid-row: 1 / 3;
        }

        .block-key-resources {
            grid-column: 2;
            grid-row: 2;
        }

        .block-channels {
            grid-column: 4;
            grid-row: 2;
        }

        .block-cost-structure {
            grid-column: 1 / 3;
            grid-row: 3;
        }

        .block-revenue-streams {
            grid-column: 3 / 6;
            grid-row: 3;
        }

        .extra-blocks {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }

        .extra-block {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #1cabe2;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        @media (max-width: 1400px) {
            .canvas-grid {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
            }

            .block-key-partners,
            .block-key-activities,
            .block-value-propositions,
            .block-customer-relationships,
            .block-customer-segments,
            .block-key-resources,
            .block-channels,
            .block-cost-structure,
            .block-revenue-streams {
                grid-column: 1;
                grid-row: auto;
            }
        }

        @media (max-width: 768px) {
            .extra-blocks {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="breadcrumb">
                <a href="incubation-dashboard-v2.php">← <?php echo $lang === 'fr' ? 'Retour au tableau de bord' : 'Back to Dashboard'; ?></a>
            </div>
            <h1>📊 <?php echo $lang === 'fr' ? 'Business Model Canvas' : 'Business Model Canvas'; ?></h1>
            <p><?php echo htmlspecialchars($team['team_name']); ?></p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" id="canvasForm">
            <!-- Main Canvas Grid -->
            <div class="canvas-grid">
                <!-- Block 1: Key Partners -->
                <div class="canvas-block block-key-partners">
                    <div class="block-title">
                        🤝 <?php echo $lang === 'fr' ? 'Partenaires Clés' : 'Key Partners'; ?>
                    </div>
                    <textarea name="key_partners" placeholder="<?php echo $lang === 'fr' ? 'Qui sont vos partenaires et fournisseurs clés ?' : 'Who are your key partners and suppliers?'; ?>"><?php echo htmlspecialchars($canvas['key_partners'] ?? ''); ?></textarea>
                    <div class="block-help">
                        <?php echo $lang === 'fr' ? 'Ex: ONG, gouvernement, entreprises partenaires' : 'e.g., NGOs, government, partner companies'; ?>
                    </div>
                </div>

                <!-- Block 2: Key Activities -->
                <div class="canvas-block block-key-activities">
                    <div class="block-title">
                        ⚙️ <?php echo $lang === 'fr' ? 'Activités Clés' : 'Key Activities'; ?>
                    </div>
                    <textarea name="key_activities" placeholder="<?php echo $lang === 'fr' ? 'Quelles sont les activités essentielles ?' : 'What are the essential activities?'; ?>"><?php echo htmlspecialchars($canvas['key_activities'] ?? ''); ?></textarea>
                    <div class="block-help">
                        <?php echo $lang === 'fr' ? 'Ex: Production, formation, distribution' : 'e.g., Production, training, distribution'; ?>
                    </div>
                </div>

                <!-- Block 3: Value Propositions (CENTER - REQUIRED) -->
                <div class="canvas-block block-value-propositions">
                    <div class="block-title">
                        💎 <?php echo $lang === 'fr' ? 'Propositions de Valeur' : 'Value Propositions'; ?> *
                    </div>
                    <textarea name="value_propositions" required placeholder="<?php echo $lang === 'fr' ? 'Quelle valeur apportez-vous à vos clients ?' : 'What value do you deliver to your customers?'; ?>"><?php echo htmlspecialchars($canvas['value_propositions'] ?? ''); ?></textarea>
                    <div class="block-help">
                        <?php echo $lang === 'fr' ? 'Ex: Accès à l\'eau propre, éducation de qualité' : 'e.g., Access to clean water, quality education'; ?>
                    </div>
                </div>

                <!-- Block 4: Customer Relationships -->
                <div class="canvas-block block-customer-relationships">
                    <div class="block-title">
                        💬 <?php echo $lang === 'fr' ? 'Relations Clients' : 'Customer Relationships'; ?>
                    </div>
                    <textarea name="customer_relationships" placeholder="<?php echo $lang === 'fr' ? 'Comment interagissez-vous avec vos clients ?' : 'How do you interact with your customers?'; ?>"><?php echo htmlspecialchars($canvas['customer_relationships'] ?? ''); ?></textarea>
                    <div class="block-help">
                        <?php echo $lang === 'fr' ? 'Ex: Support personnel, communauté' : 'e.g., Personal assistance, community'; ?>
                    </div>
                </div>

                <!-- Block 5: Customer Segments -->
                <div class="canvas-block block-customer-segments">
                    <div class="block-title">
                        👥 <?php echo $lang === 'fr' ? 'Segments Clients' : 'Customer Segments'; ?>
                    </div>
                    <textarea name="customer_segments" placeholder="<?php echo $lang === 'fr' ? 'Pour qui créez-vous de la valeur ?' : 'For whom are you creating value?'; ?>"><?php echo htmlspecialchars($canvas['customer_segments'] ?? ''); ?></textarea>
                    <div class="block-help">
                        <?php echo $lang === 'fr' ? 'Ex: Jeunes, communautés rurales' : 'e.g., Youth, rural communities'; ?>
                    </div>
                </div>

                <!-- Block 6: Key Resources -->
                <div class="canvas-block block-key-resources">
                    <div class="block-title">
                        🔧 <?php echo $lang === 'fr' ? 'Ressources Clés' : 'Key Resources'; ?>
                    </div>
                    <textarea name="key_resources" placeholder="<?php echo $lang === 'fr' ? 'Quelles ressources sont essentielles ?' : 'What resources are essential?'; ?>"><?php echo htmlspecialchars($canvas['key_resources'] ?? ''); ?></textarea>
                    <div class="block-help">
                        <?php echo $lang === 'fr' ? 'Ex: Équipement, expertise, financement' : 'e.g., Equipment, expertise, funding'; ?>
                    </div>
                </div>

                <!-- Block 7: Channels -->
                <div class="canvas-block block-channels">
                    <div class="block-title">
                        📡 <?php echo $lang === 'fr' ? 'Canaux' : 'Channels'; ?>
                    </div>
                    <textarea name="channels" placeholder="<?php echo $lang === 'fr' ? 'Comment atteignez-vous vos clients ?' : 'How do you reach your customers?'; ?>"><?php echo htmlspecialchars($canvas['channels'] ?? ''); ?></textarea>
                    <div class="block-help">
                        <?php echo $lang === 'fr' ? 'Ex: Réseaux sociaux, partenaires locaux' : 'e.g., Social media, local partners'; ?>
                    </div>
                </div>

                <!-- Block 8: Cost Structure -->
                <div class="canvas-block block-cost-structure">
                    <div class="block-title">
                        💰 <?php echo $lang === 'fr' ? 'Structure de Coûts' : 'Cost Structure'; ?>
                    </div>
                    <textarea name="cost_structure" placeholder="<?php echo $lang === 'fr' ? 'Quels sont vos coûts principaux ?' : 'What are your main costs?'; ?>"><?php echo htmlspecialchars($canvas['cost_structure'] ?? ''); ?></textarea>
                    <div class="block-help">
                        <?php echo $lang === 'fr' ? 'Ex: Salaires, matériaux, transport' : 'e.g., Salaries, materials, transport'; ?>
                    </div>
                </div>

                <!-- Block 9: Revenue Streams -->
                <div class="canvas-block block-revenue-streams">
                    <div class="block-title">
                        💵 <?php echo $lang === 'fr' ? 'Sources de Revenus' : 'Revenue Streams'; ?>
                    </div>
                    <textarea name="revenue_streams" placeholder="<?php echo $lang === 'fr' ? 'Comment générez-vous des revenus ?' : 'How do you generate revenue?'; ?>"><?php echo htmlspecialchars($canvas['revenue_streams'] ?? ''); ?></textarea>
                    <div class="block-help">
                        <?php echo $lang === 'fr' ? 'Ex: Ventes, subventions, donations' : 'e.g., Sales, grants, donations'; ?>
                    </div>
                </div>
            </div>

            <!-- Extra Blocks: Social & Environmental Impact -->
            <div class="extra-blocks">
                <div class="extra-block">
                    <div class="block-title">
                        🌍 <?php echo $lang === 'fr' ? 'Impact Social' : 'Social Impact'; ?>
                    </div>
                    <textarea name="social_impact" placeholder="<?php echo $lang === 'fr' ? 'Quel est votre impact sur la société ?' : 'What is your impact on society?'; ?>"><?php echo htmlspecialchars($canvas['social_impact'] ?? ''); ?></textarea>
                    <div class="block-help">
                        <?php echo $lang === 'fr' ? 'Ex: Emplois créés, vies améliorées' : 'e.g., Jobs created, lives improved'; ?>
                    </div>
                </div>

                <div class="extra-block">
                    <div class="block-title">
                        🌱 <?php echo $lang === 'fr' ? 'Impact Environnemental' : 'Environmental Impact'; ?>
                    </div>
                    <textarea name="environmental_impact" placeholder="<?php echo $lang === 'fr' ? 'Quel est votre impact sur l\'environnement ?' : 'What is your impact on the environment?'; ?>"><?php echo htmlspecialchars($canvas['environmental_impact'] ?? ''); ?></textarea>
                    <div class="block-help">
                        <?php echo $lang === 'fr' ? 'Ex: Réduction déchets, énergie renouvelable' : 'e.g., Waste reduction, renewable energy'; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="button-group">
                <button type="submit" name="status" value="draft" class="btn btn-secondary">
                    <?php echo $lang === 'fr' ? '💾 Sauvegarder Brouillon' : '💾 Save Draft'; ?>
                </button>
                <button type="submit" name="status" value="completed" class="btn btn-success">
                    <?php echo $lang === 'fr' ? '✅ Compléter Canvas' : '✅ Complete Canvas'; ?>
                </button>
            </div>
        </form>
    </div>

    <script>
        // Auto-save to localStorage
        const form = document.getElementById('canvasForm');
        const inputs = form.querySelectorAll('textarea');

        inputs.forEach(input => {
            // Load saved data
            const saved = localStorage.getItem('canvas_' + input.name);
            if (saved && !input.value) {
                input.value = saved;
            }

            // Save on change
            input.addEventListener('input', () => {
                localStorage.setItem('canvas_' + input.name, input.value);
            });
        });

        // Clear localStorage on successful submit
        form.addEventListener('submit', (e) => {
            if (form.checkValidity()) {
                inputs.forEach(input => {
                    localStorage.removeItem('canvas_' + input.name);
                });
            }
        });

        // Warn before leaving with unsaved changes
        let formChanged = false;
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        form.addEventListener('submit', () => {
            formChanged = false;
        });
    </script>
</body>
</html>
