<?php
/**
 * AI Assistant for Incubation Program
 * Provides guidance and answers questions during exercises
 * NOTE: This is a frontend interface. Backend API integration required.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'ask') {
        $question = trim($_POST['question'] ?? '');
        $exercise_context = $_POST['exercise_context'] ?? '';
        $phase_context = $_POST['phase_context'] ?? '';

        if (empty($question)) {
            echo json_encode(['error' => 'Question is required']);
            exit;
        }

        // TODO: Integrate with OpenAI/Claude API
        // For now, return mock response with helpful templates

        $response = generateMockAIResponse($question, $exercise_context, $phase_context, $lang);

        echo json_encode([
            'success' => true,
            'answer' => $response,
            'timestamp' => time()
        ]);
        exit;
    }

    if ($action === 'get_suggestions') {
        $exercise_type = $_POST['exercise_type'] ?? '';
        $suggestions = getSuggestionsForExercise($exercise_type, $lang);

        echo json_encode([
            'success' => true,
            'suggestions' => $suggestions
        ]);
        exit;
    }
}

/**
 * Generate mock AI response (replace with actual API call)
 */
function generateMockAIResponse($question, $exercise_context, $phase_context, $lang) {
    // This is a mock function. In production, integrate with:
    // - OpenAI API (GPT-4)
    // - Anthropic Claude API
    // - Or custom AI model

    $templates = [
        'en' => [
            'problem_tree' => "When creating a problem tree, start by identifying the core problem at the center. Then branch out to show causes (roots) below and effects (branches) above. Focus on 'why' for causes and 'what happens' for effects.",
            '5_whys' => "The 5 Whys technique helps you dig deeper into root causes. Ask 'why' 5 times, each answer becoming the next question. Stop when you reach a root cause you can address.",
            'personas' => "A strong persona includes: demographics (age, location, occupation), goals and motivations, pain points and frustrations, and typical behaviors. Give your persona a name and make them feel real.",
            'brainstorming' => "During brainstorming: 1) Don't judge ideas initially, 2) Aim for quantity over quality first, 3) Build on others' ideas, 4) Welcome wild and creative thoughts. Evaluate later.",
            'prototyping' => "Start with low-fidelity prototypes (paper, sketches). Focus on testing one key feature at a time. Get feedback early and often. Don't be attached to your first version.",
            'default' => "Great question! When working on design thinking exercises, remember to: stay user-focused, iterate based on feedback, collaborate with your team, and document your process clearly."
        ],
        'fr' => [
            'problem_tree' => "Lors de la création d'un arbre à problèmes, commencez par identifier le problème central. Ensuite, ramifiez pour montrer les causes (racines) en dessous et les effets (branches) au-dessus. Concentrez-vous sur 'pourquoi' pour les causes et 'que se passe-t-il' pour les effets.",
            '5_whys' => "La technique des 5 Pourquoi vous aide à creuser plus profondément dans les causes profondes. Demandez 'pourquoi' 5 fois, chaque réponse devenant la prochaine question. Arrêtez lorsque vous atteignez une cause profonde que vous pouvez aborder.",
            'personas' => "Un persona fort inclut : données démographiques (âge, lieu, profession), objectifs et motivations, points de douleur et frustrations, et comportements typiques. Donnez un nom à votre persona et rendez-le réel.",
            'brainstorming' => "Pendant le brainstorming : 1) Ne jugez pas les idées au début, 2) Visez la quantité plutôt que la qualité d'abord, 3) Construisez sur les idées des autres, 4) Accueillez les pensées folles et créatives. Évaluez plus tard.",
            'prototyping' => "Commencez par des prototypes basse fidélité (papier, croquis). Concentrez-vous sur le test d'une fonctionnalité clé à la fois. Obtenez des retours tôt et souvent. Ne soyez pas attaché à votre première version.",
            'default' => "Excellente question ! Lorsque vous travaillez sur des exercices de design thinking, rappelez-vous de : rester concentré sur l'utilisateur, itérer en fonction des retours, collaborer avec votre équipe et documenter clairement votre processus."
        ]
    ];

    $lang_templates = $templates[$lang] ?? $templates['en'];

    // Simple keyword matching for demo
    $question_lower = strtolower($question);

    if (strpos($question_lower, 'problem tree') !== false || strpos($question_lower, 'arbre') !== false) {
        return $lang_templates['problem_tree'];
    } elseif (strpos($question_lower, '5 why') !== false || strpos($question_lower, 'pourquoi') !== false) {
        return $lang_templates['5_whys'];
    } elseif (strpos($question_lower, 'persona') !== false) {
        return $lang_templates['personas'];
    } elseif (strpos($question_lower, 'brainstorm') !== false) {
        return $lang_templates['brainstorming'];
    } elseif (strpos($question_lower, 'prototype') !== false || strpos($question_lower, 'prototyp') !== false) {
        return $lang_templates['prototyping'];
    } else {
        return $lang_templates['default'];
    }
}

/**
 * Get suggestions for specific exercise types
 */
function getSuggestionsForExercise($exercise_type, $lang) {
    $suggestions = [
        'en' => [
            'problem_analysis' => [
                "What are the root causes of this problem?",
                "Who is most affected by this problem?",
                "What happens if this problem is not solved?",
                "How can I visualize the problem and its impacts?"
            ],
            'user_research' => [
                "What questions should I ask during user interviews?",
                "How do I identify the real needs of my target audience?",
                "What observation techniques are most effective?",
                "How can I validate my assumptions?"
            ],
            'ideation' => [
                "How can I generate more creative ideas?",
                "What brainstorming techniques work best?",
                "How do I evaluate and prioritize ideas?",
                "How can I combine different ideas?"
            ],
            'prototyping' => [
                "What prototyping method should I use?",
                "How detailed should my prototype be?",
                "What should I test first?",
                "How do I gather user feedback effectively?"
            ],
            'business_model' => [
                "How do I identify my key partners?",
                "What makes a strong value proposition?",
                "How can I determine my revenue streams?",
                "What costs should I consider?"
            ]
        ],
        'fr' => [
            'problem_analysis' => [
                "Quelles sont les causes profondes de ce problème ?",
                "Qui est le plus affecté par ce problème ?",
                "Que se passe-t-il si ce problème n'est pas résolu ?",
                "Comment puis-je visualiser le problème et ses impacts ?"
            ],
            'user_research' => [
                "Quelles questions devrais-je poser lors des entretiens utilisateurs ?",
                "Comment identifier les vrais besoins de mon public cible ?",
                "Quelles techniques d'observation sont les plus efficaces ?",
                "Comment puis-je valider mes hypothèses ?"
            ],
            'ideation' => [
                "Comment puis-je générer des idées plus créatives ?",
                "Quelles techniques de brainstorming fonctionnent le mieux ?",
                "Comment évaluer et prioriser les idées ?",
                "Comment puis-je combiner différentes idées ?"
            ],
            'prototyping' => [
                "Quelle méthode de prototypage devrais-je utiliser ?",
                "À quel point mon prototype doit-il être détaillé ?",
                "Que devrais-je tester en premier ?",
                "Comment recueillir efficacement les retours des utilisateurs ?"
            ],
            'business_model' => [
                "Comment identifier mes partenaires clés ?",
                "Qu'est-ce qui fait une proposition de valeur forte ?",
                "Comment puis-je déterminer mes sources de revenus ?",
                "Quels coûts devrais-je considérer ?"
            ]
        ]
    ];

    $lang_suggestions = $suggestions[$lang] ?? $suggestions['en'];

    // Map exercise types to categories
    $category_map = [
        '1.1' => 'problem_analysis',
        '1.2' => 'problem_analysis',
        '1.3' => 'user_research',
        '1.4' => 'user_research',
        '1.5' => 'user_research',
        '2.1' => 'user_research',
        '2.2' => 'ideation',
        '2.3' => 'ideation',
        '2.4' => 'ideation',
        '2.5' => 'ideation',
        '2.6' => 'user_research',
        '3.1' => 'ideation',
        '3.2' => 'prototyping',
        '3.3' => 'prototyping',
        '3.4' => 'prototyping',
        '4.1' => 'business_model',
        '4.2' => 'business_model',
        '4.3' => 'business_model',
        '4.4' => 'ideation'
    ];

    $category = $category_map[$exercise_type] ?? 'ideation';

    return $lang_suggestions[$category] ?? $lang_suggestions['ideation'];
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'fr' ? 'Assistant IA' : 'AI Assistant'; ?></title>
    <style>
        .ai-assistant-widget {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .ai-assistant-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            border: none;
            color: white;
            font-size: 1.8rem;
            cursor: pointer;
            box-shadow: 0 5px 25px rgba(102, 126, 234, 0.4);
            transition: all 0.3s;
        }

        .ai-assistant-button:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.6);
        }

        .ai-assistant-panel {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 400px;
            height: 600px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 50px rgba(0,0,0,0.2);
            display: none;
            flex-direction: column;
            z-index: 1001;
        }

        .ai-assistant-panel.active {
            display: flex;
        }

        .ai-panel-header {
            background: linear-gradient(135deg, #1cabe2 0%, #147ba5 100%);
            color: white;
            padding: 20px;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ai-panel-header h3 {
            font-size: 1.2rem;
        }

        .ai-panel-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
        }

        .ai-panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .ai-suggestions {
            margin-bottom: 20px;
        }

        .ai-suggestions h4 {
            color: #1cabe2;
            margin-bottom: 15px;
            font-size: 1rem;
        }

        .suggestion-btn {
            background: #f8f9ff;
            border: 1px solid #e0e0e0;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            text-align: left;
            width: 100%;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .suggestion-btn:hover {
            background: #1cabe2;
            color: white;
            border-color: #1cabe2;
        }

        .ai-chat {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .ai-message {
            padding: 15px;
            border-radius: 15px;
            max-width: 85%;
        }

        .ai-message.user {
            background: #1cabe2;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }

        .ai-message.assistant {
            background: #f8f9fa;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }

        .ai-message.assistant::before {
            content: '🤖 ';
        }

        .ai-panel-footer {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .ai-input-group {
            display: flex;
            gap: 10px;
        }

        .ai-input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.95rem;
        }

        .ai-input:focus {
            outline: none;
            border-color: #1cabe2;
        }

        .ai-send-btn {
            padding: 12px 20px;
            background: #1cabe2;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .ai-send-btn:hover {
            background: #5568d3;
        }

        .ai-send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .ai-typing {
            display: none;
            padding: 15px;
            color: #666;
            font-style: italic;
        }

        .ai-typing.active {
            display: block;
        }

        @media (max-width: 480px) {
            .ai-assistant-panel {
                width: calc(100% - 20px);
                right: 10px;
                bottom: 90px;
                height: 500px;
            }
        }
    </style>
</head>
<body>
    <!-- AI Assistant Widget -->
    <div class="ai-assistant-widget">
        <button class="ai-assistant-button" onclick="toggleAIPanel()" title="<?php echo $lang === 'fr' ? 'Assistant IA' : 'AI Assistant'; ?>">
            🤖
        </button>

        <div class="ai-assistant-panel" id="aiPanel">
            <div class="ai-panel-header">
                <h3><?php echo $lang === 'fr' ? '🤖 Assistant IA' : '🤖 AI Assistant'; ?></h3>
                <button class="ai-panel-close" onclick="toggleAIPanel()">×</button>
            </div>

            <div class="ai-panel-body">
                <div class="ai-suggestions">
                    <h4><?php echo $lang === 'fr' ? 'Questions Suggérées :' : 'Suggested Questions:'; ?></h4>
                    <div id="suggestionsList"></div>
                </div>

                <div class="ai-chat" id="aiChat"></div>

                <div class="ai-typing" id="aiTyping">
                    <?php echo $lang === 'fr' ? 'L\'assistant réfléchit...' : 'Assistant is thinking...'; ?>
                </div>
            </div>

            <div class="ai-panel-footer">
                <div class="ai-input-group">
                    <input type="text"
                           class="ai-input"
                           id="aiInput"
                           placeholder="<?php echo $lang === 'fr' ? 'Posez votre question...' : 'Ask your question...'; ?>"
                           onkeypress="if(event.key==='Enter') sendAIQuestion()">
                    <button class="ai-send-btn" id="aiSendBtn" onclick="sendAIQuestion()">
                        <?php echo $lang === 'fr' ? 'Envoyer' : 'Send'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let aiPanelOpen = false;
        const lang = '<?php echo $lang; ?>';

        function toggleAIPanel() {
            aiPanelOpen = !aiPanelOpen;
            const panel = document.getElementById('aiPanel');
            panel.classList.toggle('active');

            if (aiPanelOpen && document.getElementById('suggestionsList').innerHTML === '') {
                loadSuggestions();
            }
        }

        function loadSuggestions() {
            // Get exercise context from page if available
            const exerciseType = document.querySelector('[data-exercise-type]')?.dataset.exerciseType || '2.4';

            fetch('ai-assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_suggestions&exercise_type=${exerciseType}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('suggestionsList');
                    container.innerHTML = '';
                    data.suggestions.forEach(suggestion => {
                        const btn = document.createElement('button');
                        btn.className = 'suggestion-btn';
                        btn.textContent = suggestion;
                        btn.onclick = () => askSuggestion(suggestion);
                        container.appendChild(btn);
                    });
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function askSuggestion(question) {
            document.getElementById('aiInput').value = question;
            sendAIQuestion();
        }

        function sendAIQuestion() {
            const input = document.getElementById('aiInput');
            const question = input.value.trim();

            if (!question) return;

            // Add user message to chat
            addMessage(question, 'user');

            // Clear input
            input.value = '';

            // Show typing indicator
            document.getElementById('aiTyping').classList.add('active');
            document.getElementById('aiSendBtn').disabled = true;

            // Get context
            const exerciseContext = document.querySelector('[data-exercise-context]')?.dataset.exerciseContext || '';
            const phaseContext = document.querySelector('[data-phase-context]')?.dataset.phaseContext || '';

            // Send request
            fetch('ai-assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=ask&question=${encodeURIComponent(question)}&exercise_context=${encodeURIComponent(exerciseContext)}&phase_context=${encodeURIComponent(phaseContext)}`
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('aiTyping').classList.remove('active');
                document.getElementById('aiSendBtn').disabled = false;

                if (data.success) {
                    addMessage(data.answer, 'assistant');
                } else {
                    addMessage(lang === 'fr' ? 'Désolé, une erreur s\'est produite.' : 'Sorry, an error occurred.', 'assistant');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('aiTyping').classList.remove('active');
                document.getElementById('aiSendBtn').disabled = false;
                addMessage(lang === 'fr' ? 'Erreur de connexion.' : 'Connection error.', 'assistant');
            });
        }

        function addMessage(text, type) {
            const chat = document.getElementById('aiChat');
            const message = document.createElement('div');
            message.className = `ai-message ${type}`;
            message.textContent = text;
            chat.appendChild(message);

            // Scroll to bottom
            chat.parentElement.scrollTop = chat.parentElement.scrollHeight;
        }

        // Initialize when exercise page loads
        document.addEventListener('DOMContentLoaded', () => {
            if (document.querySelector('[data-exercise-type]')) {
                // Show hint about AI assistant
                setTimeout(() => {
                    if (!localStorage.getItem('ai_assistant_hint_shown')) {
                        const button = document.querySelector('.ai-assistant-button');
                        button.style.animation = 'pulse 1s infinite';
                        localStorage.setItem('ai_assistant_hint_shown', 'true');

                        setTimeout(() => {
                            button.style.animation = '';
                        }, 5000);
                    }
                }, 2000);
            }
        });
    </script>

    <style>
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }
    </style>
</body>
</html>
