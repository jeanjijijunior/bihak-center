<?php
/**
 * OpenAI Configuration
 * Alternative to Claude API
 */

define('OPENAI_API_KEY', 'sk-your-openai-api-key-here');
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
define('OPENAI_MODEL', 'gpt-4o'); // or 'gpt-4o-mini' for cheaper option

// Cost estimates:
// gpt-4o: $2.50 per 1M input tokens, $10 per 1M output tokens
// gpt-4o-mini: $0.150 per 1M input tokens, $0.600 per 1M output tokens
// Average cost per feedback: ~$0.002-0.01
?>
