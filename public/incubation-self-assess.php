<?php
/**
 * Self-Assessment Tool
 * Allows teams to self-evaluate their work before submitting
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';
$exercise_id = $_GET['exercise_id'] ?? 0;
$conn = getDatabaseConnection();

// Get exercise info
$exercise_query = "SELECT * FROM program_exercises WHERE id = ?";
$stmt = $conn->prepare($exercise_query);
$stmt->bind_param('i', $exercise_id);
$stmt->execute();
$exercise = $stmt->get_result()->fetch_assoc();

if (!$exercise) {
    die("Exercise not found");
}

// Define assessment criteria based on exercise type
$assessment_criteria = [
    'completeness' => [
        'title_en' => 'Completeness',
        'title_fr' => 'Complétude',
        'description_en' => 'Have you answered all parts of the exercise?',
        'description_fr' => 'Avez-vous répondu à toutes les parties de l\'exercice ?',
        'questions' => [
            'en' => [
                'Did you address all required components?',
                'Is your submission thorough and detailed?',
                'Did you provide examples where needed?'
            ],
            'fr' => [
                'Avez-vous traité tous les composants requis ?',
                'Votre soumission est-elle approfondie et détaillée ?',
                'Avez-vous fourni des exemples si nécessaire ?'
            ]
        ]
    ],
    'clarity' => [
        'title_en' => 'Clarity',
        'title_fr' => 'Clarté',
        'description_en' => 'Is your work clear and easy to understand?',
        'description_fr' => 'Votre travail est-il clair et facile à comprendre ?',
        'questions' => [
            'en' => [
                'Is your explanation clear and logical?',
                'Are your ideas well-organized?',
                'Would someone else understand your work?'
            ],
            'fr' => [
                'Votre explication est-elle claire et logique ?',
                'Vos idées sont-elles bien organisées ?',
                'Quelqu\'un d\'autre comprendrait-il votre travail ?'
            ]
        ]
    ],
    'relevance' => [
        'title_en' => 'Relevance',
        'title_fr' => 'Pertinence',
        'description_en' => 'Does your work directly address the exercise objectives?',
        'description_fr' => 'Votre travail répond-il directement aux objectifs de l\'exercice ?',
        'questions' => [
            'en' => [
                'Does your work relate to the problem statement?',
                'Is your solution appropriate for the target audience?',
                'Did you stay focused on the objectives?'
            ],
            'fr' => [
                'Votre travail est-il lié à l\'énoncé du problème ?',
                'Votre solution est-elle appropriée pour le public cible ?',
                'Êtes-vous resté concentré sur les objectifs ?'
            ]
        ]
    ],
    'creativity' => [
        'title_en' => 'Creativity & Innovation',
        'title_fr' => 'Créativité & Innovation',
        'description_en' => 'Did you think creatively and propose innovative ideas?',
        'description_fr' => 'Avez-vous pensé de manière créative et proposé des idées innovantes ?',
        'questions' => [
            'en' => [
                'Did you explore multiple perspectives?',
                'Are your ideas original and innovative?',
                'Did you challenge assumptions?'
            ],
            'fr' => [
                'Avez-vous exploré plusieurs perspectives ?',
                'Vos idées sont-elles originales et innovantes ?',
                'Avez-vous remis en question les hypothèses ?'
            ]
        ]
    ],
    'feasibility' => [
        'title_en' => 'Feasibility',
        'title_fr' => 'Faisabilité',
        'description_en' => 'Is your solution practical and achievable?',
        'description_fr' => 'Votre solution est-elle pratique et réalisable ?',
        'questions' => [
            'en' => [
                'Can this be implemented with available resources?',
                'Have you considered potential challenges?',
                'Is the timeline realistic?'
            ],
            'fr' => [
                'Cela peut-il être mis en œuvre avec les ressources disponibles ?',
                'Avez-vous considéré les défis potentiels ?',
                'Le calendrier est-il réaliste ?'
            ]
        ]
    ]
];

closeDatabaseConnection($conn);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'fr' ? 'Auto-évaluation' : 'Self-Assessment'; ?></title>
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

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .intro-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .intro-box h2 {
            color: #667eea;
            margin-bottom: 15px;
        }

        .intro-box p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .criteria-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .criteria-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .criteria-title {
            font-size: 1.3rem;
            color: #333;
        }

        .criteria-score {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }

        .criteria-description {
            color: #666;
            margin-bottom: 20px;
            font-style: italic;
        }

        .question-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .question-text {
            color: #333;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .rating-options {
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .rating-option {
            flex: 1;
            padding: 15px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .rating-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .rating-option.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }

        .rating-label {
            font-size: 0.85rem;
            margin-top: 5px;
        }

        .rating-emoji {
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .overall-results {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            margin-top: 30px;
        }

        .overall-score {
            font-size: 4rem;
            font-weight: 700;
            margin: 20px 0;
        }

        .score-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .score-item {
            background: rgba(255,255,255,0.2);
            padding: 20px;
            border-radius: 10px;
        }

        .score-item-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .score-item-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .recommendations {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .recommendations h3 {
            color: #667eea;
            margin-bottom: 20px;
        }

        .recommendation-item {
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #ffc107;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/incubation-header.php'; ?>

    <div class="container">
        <div class="header">
            <h1>🎯 <?php echo $lang === 'fr' ? 'Auto-évaluation' : 'Self-Assessment'; ?></h1>
            <p><?php echo $lang === 'fr' ? $exercise['exercise_title_fr'] : $exercise['exercise_title']; ?></p>
        </div>

        <div class="intro-box">
            <h2><?php echo $lang === 'fr' ? 'Avant de soumettre...' : 'Before You Submit...'; ?></h2>
            <p>
                <?php echo $lang === 'fr'
                    ? 'Prenez un moment pour évaluer votre travail. Cette auto-évaluation vous aidera à identifier les domaines à améliorer avant de soumettre votre exercice.'
                    : 'Take a moment to evaluate your work. This self-assessment will help you identify areas for improvement before submitting your exercise.'; ?>
            </p>
            <p>
                <?php echo $lang === 'fr'
                    ? 'Soyez honnête avec vous-même - cela vous aidera à grandir et à améliorer votre projet.'
                    : 'Be honest with yourself - this will help you grow and improve your project.'; ?>
            </p>
        </div>

        <div id="assessmentForm">
            <?php foreach ($assessment_criteria as $key => $criterion): ?>
                <div class="criteria-section" data-criterion="<?php echo $key; ?>">
                    <div class="criteria-header">
                        <div class="criteria-title">
                            <?php echo $lang === 'fr' ? $criterion['title_fr'] : $criterion['title_en']; ?>
                        </div>
                        <div class="criteria-score" data-score="0">
                            <span class="score-value">0</span>/15
                        </div>
                    </div>

                    <div class="criteria-description">
                        <?php echo $lang === 'fr' ? $criterion['description_fr'] : $criterion['description_en']; ?>
                    </div>

                    <?php
                    $questions = $lang === 'fr' ? $criterion['questions']['fr'] : $criterion['questions']['en'];
                    foreach ($questions as $index => $question):
                    ?>
                        <div class="question-item">
                            <div class="question-text">
                                <?php echo ($index + 1) . '. ' . $question; ?>
                            </div>
                            <div class="rating-options">
                                <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                                    <div class="rating-option" onclick="selectRating(this, '<?php echo $key; ?>', <?php echo $index; ?>, <?php echo $rating; ?>)">
                                        <div class="rating-emoji">
                                            <?php
                                            $emojis = ['😟', '😐', '🙂', '😊', '🤩'];
                                            echo $emojis[$rating - 1];
                                            ?>
                                        </div>
                                        <div class="rating-label"><?php echo $rating; ?></div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="overall-results" id="results" style="display: none;">
            <h2><?php echo $lang === 'fr' ? 'Votre Score Global' : 'Your Overall Score'; ?></h2>
            <div class="overall-score" id="overallScore">0/75</div>
            <p id="overallMessage"></p>

            <div class="score-breakdown" id="scoreBreakdown"></div>
        </div>

        <div class="recommendations" id="recommendations" style="display: none;">
            <h3>💡 <?php echo $lang === 'fr' ? 'Recommandations' : 'Recommendations'; ?></h3>
            <div id="recommendationsList"></div>
        </div>

        <div class="button-group">
            <button onclick="calculateResults()" class="btn btn-primary">
                <?php echo $lang === 'fr' ? '📊 Voir les résultats' : '📊 See Results'; ?>
            </button>
            <a href="incubation-exercise.php?id=<?php echo $exercise_id; ?>" class="btn btn-success">
                <?php echo $lang === 'fr' ? '✅ Continuer vers la soumission' : '✅ Continue to Submission'; ?>
            </a>
        </div>
    </div>

    <script>
        const scores = {};
        const lang = '<?php echo $lang; ?>';

        function selectRating(element, criterion, questionIndex, rating) {
            // Remove previous selection
            const parent = element.parentElement;
            parent.querySelectorAll('.rating-option').forEach(opt => {
                opt.classList.remove('selected');
            });

            // Add selection
            element.classList.add('selected');

            // Store score
            if (!scores[criterion]) {
                scores[criterion] = {};
            }
            scores[criterion][questionIndex] = rating;

            // Update criterion score
            updateCriterionScore(criterion);
        }

        function updateCriterionScore(criterion) {
            const criterionScores = scores[criterion];
            if (!criterionScores) return;

            const total = Object.values(criterionScores).reduce((sum, score) => sum + score, 0);
            const section = document.querySelector(`[data-criterion="${criterion}"]`);
            const scoreElement = section.querySelector('.score-value');
            scoreElement.textContent = total;
        }

        function calculateResults() {
            let totalScore = 0;
            let maxScore = 0;
            const criteriaScores = {};

            <?php foreach ($assessment_criteria as $key => $criterion): ?>
                <?php $questionCount = count($criterion['questions']['en']); ?>
                const <?php echo $key; ?>Score = scores['<?php echo $key; ?>']
                    ? Object.values(scores['<?php echo $key; ?>']).reduce((sum, score) => sum + score, 0)
                    : 0;
                criteriaScores['<?php echo $key; ?>'] = {
                    score: <?php echo $key; ?>Score,
                    max: <?php echo $questionCount * 5; ?>,
                    title: lang === 'fr' ? '<?php echo $criterion['title_fr']; ?>' : '<?php echo $criterion['title_en']; ?>'
                };
                totalScore += <?php echo $key; ?>Score;
                maxScore += <?php echo $questionCount * 5; ?>;
            <?php endforeach; ?>

            // Display results
            document.getElementById('results').style.display = 'block';
            document.getElementById('overallScore').textContent = `${totalScore}/${maxScore}`;

            // Overall message
            const percentage = (totalScore / maxScore) * 100;
            let message = '';
            if (lang === 'fr') {
                if (percentage >= 80) message = 'Excellent travail ! Votre soumission est bien préparée.';
                else if (percentage >= 60) message = 'Bon travail ! Quelques améliorations possibles.';
                else message = 'Prenez le temps d\'améliorer votre travail avant de soumettre.';
            } else {
                if (percentage >= 80) message = 'Excellent work! Your submission is well-prepared.';
                else if (percentage >= 60) message = 'Good work! Some improvements possible.';
                else message = 'Take time to improve your work before submitting.';
            }
            document.getElementById('overallMessage').textContent = message;

            // Score breakdown
            const breakdown = document.getElementById('scoreBreakdown');
            breakdown.innerHTML = '';
            for (const [key, data] of Object.entries(criteriaScores)) {
                const percent = Math.round((data.score / data.max) * 100);
                breakdown.innerHTML += `
                    <div class="score-item">
                        <div class="score-item-value">${percent}%</div>
                        <div class="score-item-label">${data.title}</div>
                    </div>
                `;
            }

            // Recommendations
            const recommendations = [];
            for (const [key, data] of Object.entries(criteriaScores)) {
                const percent = (data.score / data.max) * 100;
                if (percent < 60) {
                    if (lang === 'fr') {
                        recommendations.push(`Améliorez votre ${data.title.toLowerCase()} en ajoutant plus de détails et d'exemples.`);
                    } else {
                        recommendations.push(`Improve your ${data.title.toLowerCase()} by adding more details and examples.`);
                    }
                }
            }

            if (recommendations.length > 0) {
                document.getElementById('recommendations').style.display = 'block';
                const recList = document.getElementById('recommendationsList');
                recList.innerHTML = '';
                recommendations.forEach(rec => {
                    recList.innerHTML += `<div class="recommendation-item">${rec}</div>`;
                });
            }

            // Scroll to results
            document.getElementById('results').scrollIntoView({ behavior: 'smooth' });
        }
    </script>

    <!-- Chat Widget -->
    <?php include __DIR__ . '/../includes/chat_widget.php'; ?>
</body>
</html>
