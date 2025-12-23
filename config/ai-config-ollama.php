<?php
/**
 * Ollama AI Configuration
 * Self-hosted, FREE AI models
 *
 * Setup Instructions:
 * 1. Install Ollama from https://ollama.com
 * 2. Run: ollama pull mistral (or mixtral)
 * 3. Start server: ollama serve
 * 4. Server runs on http://localhost:11434
 */

// Ollama server URL (default local installation)
define('OLLAMA_API_URL', 'http://localhost:11434/api/generate');
define('OLLAMA_CHAT_URL', 'http://localhost:11434/api/chat');

// Model selection
// Options:
// - 'mistral' (7B params, 4GB RAM, fast, excellent French)
// - 'mixtral' (8x7B params, 26GB RAM, slower, best quality)
// - 'llama3.1' (8B params, 8GB RAM, good general purpose)
define('OLLAMA_MODEL', 'mistral');

// Generation parameters
define('OLLAMA_TEMPERATURE', 0.7);
define('OLLAMA_MAX_TOKENS', 1000);

// Server configuration
define('OLLAMA_TIMEOUT', 30); // seconds
define('OLLAMA_HOST', 'localhost');
define('OLLAMA_PORT', 11434);

/**
 * Cost: $0 (completely FREE!)
 *
 * Server Requirements:
 * - For Mistral 7B: 8GB RAM minimum
 * - For Mixtral: 16GB RAM minimum
 * - Disk: 10-30GB depending on model
 *
 * Performance:
 * - Mistral: ~2-5 seconds per response
 * - Mixtral: ~5-15 seconds per response
 *
 * Benefits:
 * - No API costs
 * - No usage limits
 * - Complete data privacy
 * - Works offline
 * - Excellent French support
 */
?>
