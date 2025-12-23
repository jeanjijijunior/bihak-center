<?php
/**
 * AI Provider Configuration
 * Central configuration to easily switch between AI providers
 *
 * INSTRUCTIONS:
 * 1. Set AI_PROVIDER to your chosen provider
 * 2. Configure the appropriate config file for that provider
 * 3. All API endpoints will automatically use the correct provider
 */

// ============================================
// CHOOSE YOUR AI PROVIDER HERE
// ============================================
// Options: 'ollama', 'openai', 'claude'
define('AI_PROVIDER', 'ollama');  // Change this to switch providers
// ============================================

/**
 * Get the API endpoint URL for AI feedback
 */
function getAIFeedbackEndpoint() {
    switch (AI_PROVIDER) {
        case 'ollama':
            return '/api/incubation-interactive/ai-feedback-ollama.php';
        case 'openai':
            return '/api/incubation-interactive/ai-feedback-openai.php';
        case 'claude':
            return '/api/incubation-interactive/ai-feedback.php';
        default:
            return '/api/incubation-interactive/ai-feedback-ollama.php';
    }
}

/**
 * Get the API endpoint URL for AI chat
 */
function getAIChatEndpoint() {
    switch (AI_PROVIDER) {
        case 'ollama':
            return '/api/incubation-interactive/ai-chat-ollama.php';
        case 'openai':
            return '/api/incubation-interactive/ai-chat-openai.php';
        case 'claude':
            return '/api/incubation-interactive/ai-chat.php';
        default:
            return '/api/incubation-interactive/ai-chat-ollama.php';
    }
}

/**
 * Get the provider display name
 */
function getAIProviderName() {
    switch (AI_PROVIDER) {
        case 'ollama':
            return 'Ollama (FREE)';
        case 'openai':
            return 'OpenAI GPT';
        case 'claude':
            return 'Claude';
        default:
            return 'Unknown';
    }
}

/**
 * Check if provider is configured
 */
function isAIProviderConfigured() {
    switch (AI_PROVIDER) {
        case 'ollama':
            // Check if Ollama server is running
            if (!file_exists(__DIR__ . '/ai-config-ollama.php')) {
                return false;
            }
            require_once __DIR__ . '/ai-config-ollama.php';
            $ch = curl_init('http://' . OLLAMA_HOST . ':' . OLLAMA_PORT);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            $result = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ($http_code === 200 || $http_code === 404);

        case 'openai':
            // Check if OpenAI API key is configured
            if (!file_exists(__DIR__ . '/ai-config-openai.php')) {
                return false;
            }
            require_once __DIR__ . '/ai-config-openai.php';
            return defined('OPENAI_API_KEY') && OPENAI_API_KEY !== 'sk-your-openai-api-key-here';

        case 'claude':
            // Check if Claude API key is configured
            if (!file_exists(__DIR__ . '/ai-config.php')) {
                return false;
            }
            require_once __DIR__ . '/ai-config.php';
            return defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY !== 'sk-ant-your-key-here';

        default:
            return false;
    }
}

/**
 * Get provider configuration status message
 */
function getAIProviderStatus() {
    if (isAIProviderConfigured()) {
        return [
            'configured' => true,
            'message' => getAIProviderName() . ' is configured and ready.',
            'provider' => AI_PROVIDER
        ];
    } else {
        $setup_instructions = '';
        switch (AI_PROVIDER) {
            case 'ollama':
                $setup_instructions = 'Please install Ollama and run "ollama serve". See OLLAMA-SETUP-GUIDE.md for details.';
                break;
            case 'openai':
                $setup_instructions = 'Please configure your OpenAI API key in config/ai-config-openai.php';
                break;
            case 'claude':
                $setup_instructions = 'Please configure your Claude API key in config/ai-config.php';
                break;
        }
        return [
            'configured' => false,
            'message' => getAIProviderName() . ' is not configured. ' . $setup_instructions,
            'provider' => AI_PROVIDER
        ];
    }
}

/**
 * Export configuration to JavaScript
 * Use this in your HTML pages to automatically set the correct endpoints
 */
function exportAIConfigToJS() {
    $feedbackEndpoint = getAIFeedbackEndpoint();
    $chatEndpoint = getAIChatEndpoint();
    $providerName = getAIProviderName();

    echo <<<JAVASCRIPT
    <script>
    // AI Provider Configuration (auto-generated)
    window.AI_CONFIG = {
        provider: '{$providerName}',
        feedbackEndpoint: '{$feedbackEndpoint}',
        chatEndpoint: '{$chatEndpoint}'
    };
    console.log('AI Provider:', window.AI_CONFIG.provider);
    </script>
JAVASCRIPT;
}
?>
