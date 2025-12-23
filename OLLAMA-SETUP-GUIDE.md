# Ollama Setup Guide - FREE Self-Hosted AI

**Complete guide to installing and using Ollama for the Bihak Center platform**

---

## What is Ollama?

Ollama allows you to run powerful AI models locally on your own server for **completely FREE**. No API costs, no usage limits, and your data stays private.

### Benefits:
- ✅ **100% FREE** - No costs ever
- ✅ **No usage limits** - Unlimited AI feedback and chat
- ✅ **Privacy** - Data never leaves your server
- ✅ **Offline** - Works without internet
- ✅ **French support** - Excellent French language capabilities
- ✅ **Control** - Full control over the AI

### Requirements:
- **RAM:** 8GB minimum (16GB recommended)
- **Disk:** 10-30GB free space
- **OS:** Windows, macOS, or Linux
- **Internet:** Only needed for initial download

---

## Installation

### Windows Installation

**Step 1: Download Ollama**
```
1. Visit: https://ollama.com/download/windows
2. Download OllamaSetup.exe
3. Run the installer
4. Follow installation wizard
```

**Step 2: Verify Installation**
```bash
# Open Command Prompt or PowerShell
ollama --version

# Should show: ollama version 0.x.x
```

**Step 3: Download AI Model**
```bash
# For Mistral 7B (Recommended - 4GB download)
ollama pull mistral

# OR for Mixtral 8x7B (Better quality - 26GB download)
ollama pull mixtral

# OR for Llama 3.1 (8GB download)
ollama pull llama3.1
```

**Step 4: Start Ollama Server**
```bash
# Start the server (runs in background)
ollama serve

# Server will run on: http://localhost:11434
```

To run Ollama as a service (auto-start on boot):
```bash
# Create a batch file: C:\ollama\start-ollama.bat
@echo off
ollama serve

# Add to Windows Startup folder:
# Press Win+R, type: shell:startup
# Create shortcut to start-ollama.bat
```

### Linux Installation

**Step 1: Install Ollama**
```bash
# One-line installation
curl -fsSL https://ollama.com/install.sh | sh
```

**Step 2: Download Model**
```bash
# Mistral 7B (Recommended)
ollama pull mistral

# OR Mixtral 8x7B (Better quality)
ollama pull mixtral
```

**Step 3: Start as Service**
```bash
# Ollama is automatically installed as a systemd service
sudo systemctl start ollama
sudo systemctl enable ollama  # Auto-start on boot

# Check status
sudo systemctl status ollama
```

### macOS Installation

**Step 1: Download Ollama**
```
1. Visit: https://ollama.com/download/mac
2. Download Ollama.dmg
3. Drag to Applications folder
4. Open Ollama from Applications
```

**Step 2: Download Model**
```bash
# Open Terminal
ollama pull mistral
```

**Step 3: Auto-Start (Optional)**
```
Ollama automatically starts on login on macOS
```

---

## Choosing the Right Model

### Mistral 7B (Recommended for Most Users)
```bash
ollama pull mistral
```
- **Size:** 4GB download, 8GB RAM needed
- **Speed:** Fast (2-5 seconds per response)
- **Quality:** Excellent
- **French:** Native French support
- **Best for:** Most production use cases

### Mixtral 8x7B (Best Quality)
```bash
ollama pull mixtral
```
- **Size:** 26GB download, 16GB RAM needed
- **Speed:** Slower (5-15 seconds per response)
- **Quality:** Outstanding
- **French:** Excellent French support
- **Best for:** High-quality feedback, complex analysis

### Llama 3.1 (Alternative)
```bash
ollama pull llama3.1
```
- **Size:** 8GB download, 8GB RAM needed
- **Speed:** Medium (3-7 seconds)
- **Quality:** Very good
- **French:** Good (not native)
- **Best for:** General purpose, English-heavy

---

## Configuration

### Step 1: Update Ollama Config

Edit: `c:\xampp\htdocs\bihak-center\config\ai-config-ollama.php`

```php
<?php
// Set your chosen model
define('OLLAMA_MODEL', 'mistral');  // or 'mixtral' or 'llama3.1'

// If Ollama is on a different server
define('OLLAMA_HOST', 'localhost');  // Change if needed
define('OLLAMA_PORT', 11434);        // Change if needed
?>
```

### Step 2: Update JavaScript to Use Ollama

Edit: `c:\xampp\htdocs\bihak-center\assets\js\incubation\ai-assistant.js`

Find the API URLs and change to Ollama versions:

```javascript
// Around line 50 - AI Feedback
async function getAIFeedback() {
    // Change this line:
    const response = await fetch('/api/incubation-interactive/ai-feedback-ollama.php', {
        // ... rest of code
    });
}

// Around line 150 - AI Chat
async function sendChatMessage() {
    // Change this line:
    const response = await fetch('/api/incubation-interactive/ai-chat-ollama.php', {
        // ... rest of code
    });
}
```

---

## Testing Ollama

### Test 1: Check Server is Running

```bash
# Windows/Linux/Mac
curl http://localhost:11434

# Should return: "Ollama is running"
```

### Test 2: Test Model Directly

```bash
# Test Mistral
ollama run mistral "Explain what a problem tree is in French"

# Should respond in French with explanation
```

### Test 3: Test via API

```bash
# Windows PowerShell
Invoke-RestMethod -Uri "http://localhost:11434/api/generate" -Method Post -Body '{"model":"mistral","prompt":"Hello, respond in French","stream":false}' -ContentType "application/json"

# Linux/Mac
curl -X POST http://localhost:11434/api/generate -d '{
  "model": "mistral",
  "prompt": "Hello, respond in French",
  "stream": false
}'
```

### Test 4: Test in Bihak Platform

1. Make sure Ollama is running: `ollama serve`
2. Log in to Bihak Center as a user
3. Go to Problem Tree exercise
4. Add some boxes
5. Click "Get AI Feedback"
6. Should receive feedback in 3-5 seconds

---

## Performance Optimization

### For Windows

**Increase Performance:**
```bash
# Set environment variable for better GPU usage
setx OLLAMA_NUM_GPU 1

# Use more CPU threads
setx OLLAMA_NUM_THREAD 8
```

### For Linux

**Edit service file:**
```bash
sudo nano /etc/systemd/system/ollama.service
```

Add environment variables:
```ini
[Service]
Environment="OLLAMA_NUM_GPU=1"
Environment="OLLAMA_NUM_THREAD=8"
Environment="OLLAMA_HOST=0.0.0.0"  # Allow remote access
```

Restart:
```bash
sudo systemctl daemon-reload
sudo systemctl restart ollama
```

### For macOS

Ollama automatically optimizes for Mac hardware (including M1/M2/M3 chips).

---

## Remote Server Setup

If you want to run Ollama on a separate server:

### On the Ollama Server:

```bash
# Linux - Allow remote connections
export OLLAMA_HOST=0.0.0.0:11434
ollama serve

# Or edit systemd service
sudo nano /etc/systemd/system/ollama.service
# Add: Environment="OLLAMA_HOST=0.0.0.0:11434"
```

### On the Bihak Platform Server:

Edit `config/ai-config-ollama.php`:
```php
define('OLLAMA_HOST', '192.168.1.100');  // IP of Ollama server
define('OLLAMA_PORT', 11434);
```

### Security Note:
If exposing Ollama to network, use firewall rules or VPN for security.

---

## Troubleshooting

### Issue: "Ollama is not running"

**Fix:**
```bash
# Check if running
curl http://localhost:11434

# Start it
ollama serve

# Windows: Check Task Manager for ollama.exe
# Linux: sudo systemctl status ollama
```

### Issue: "Model not found"

**Fix:**
```bash
# List installed models
ollama list

# Pull the model
ollama pull mistral
```

### Issue: "Out of memory"

**Fix:**
```bash
# Use smaller model
ollama pull mistral  # Instead of mixtral

# Or close other applications
# Or add more RAM
```

### Issue: Slow responses

**Solutions:**
1. Use smaller model (Mistral instead of Mixtral)
2. Reduce `OLLAMA_MAX_TOKENS` in config
3. Close other applications
4. Use GPU if available
5. Increase RAM allocation

### Issue: Connection timeout

**Fix:**
```php
// In config/ai-config-ollama.php
define('OLLAMA_TIMEOUT', 60);  // Increase from 30 to 60 seconds
```

### Issue: French responses are poor

**Fix:**
```bash
# Use Mistral or Mixtral (best French support)
ollama pull mistral

# Update config
define('OLLAMA_MODEL', 'mistral');
```

---

## Monitoring and Logs

### View Ollama Logs

**Windows:**
```bash
# Logs in console where you ran "ollama serve"
```

**Linux:**
```bash
sudo journalctl -u ollama -f
```

**macOS:**
```bash
# View in Console app
# Or check: ~/Library/Logs/Ollama/
```

### Monitor Resource Usage

**Check RAM usage:**
```bash
# Windows
tasklist | findstr ollama

# Linux
top -p $(pgrep ollama)

# macOS
top | grep ollama
```

---

## Model Management

### List Installed Models
```bash
ollama list
```

### Remove a Model
```bash
ollama rm mixtral
```

### Update a Model
```bash
ollama pull mistral  # Downloads latest version
```

### Switch Models

Just update the config:
```php
define('OLLAMA_MODEL', 'mixtral');  // Change to different model
```

---

## Cost Comparison

### Ollama (Self-Hosted)
- **Setup:** 1 hour
- **Cost:** $0/month
- **Usage:** Unlimited
- **Privacy:** 100% private

### OpenAI
- **Setup:** 10 minutes
- **Cost:** $5-10/month
- **Usage:** ~1000 feedbacks
- **Privacy:** Cloud-based

### Claude
- **Setup:** 10 minutes
- **Cost:** $10-20/month
- **Usage:** ~1000 feedbacks
- **Privacy:** Cloud-based

---

## Advanced: Multiple Models

You can install multiple models and switch between them:

```bash
# Install all three
ollama pull mistral
ollama pull mixtral
ollama pull llama3.1

# Switch by updating config
define('OLLAMA_MODEL', 'mistral');  # Fast
define('OLLAMA_MODEL', 'mixtral');  # Quality
define('OLLAMA_MODEL', 'llama3.1'); # Alternative
```

---

## Production Deployment

### Recommended Setup:

1. **Dedicated Server:**
   - 16GB RAM minimum
   - 50GB disk space
   - Ubuntu 22.04 LTS

2. **Model Choice:**
   - Mistral 7B for production
   - Mixtral 8x7B for premium quality

3. **Auto-Start:**
   - Configure as systemd service (Linux)
   - Configure as Windows Service

4. **Monitoring:**
   - Set up health checks
   - Monitor RAM usage
   - Log response times

---

## Getting Help

### Ollama Resources:
- **Documentation:** https://github.com/ollama/ollama
- **Models:** https://ollama.com/library
- **Discord:** https://discord.gg/ollama
- **Issues:** https://github.com/ollama/ollama/issues

### Bihak Platform Integration:
- Check logs in browser console (F12)
- Check PHP error logs
- Verify API endpoints are accessible
- Test with curl commands above

---

## Summary

✅ **FREE forever** - No costs
✅ **Easy setup** - 15-30 minutes
✅ **Great quality** - Excellent French support
✅ **Private** - Data stays on your server
✅ **Unlimited** - No usage restrictions

**Next Steps:**
1. Install Ollama
2. Download Mistral model
3. Update config files
4. Test the integration
5. Enjoy unlimited FREE AI! 🎉

---

**Questions?** Check the troubleshooting section or Ollama documentation.
