/**
 * AI Assistant Module
 * Handles AI feedback and chat functionality
 */

function initEventListeners() {
    // Get AI Feedback button
    document.getElementById('get-ai-feedback-btn')?.addEventListener('click', async function() {
        await getAIFeedback();
    });

    // Chat functionality
    document.getElementById('send-chat-btn')?.addEventListener('click', function() {
        sendChatMessage();
    });

    document.getElementById('chat-input')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendChatMessage();
        }
    });

    // Submit button
    document.getElementById('submit-btn')?.addEventListener('click', async function() {
        await submitForReview();
    });
}

async function getAIFeedback() {
    const loadingSpinner = document.getElementById('loading-spinner');
    const feedbackBtn = document.getElementById('get-ai-feedback-btn');

    loadingSpinner.classList.add('active');
    feedbackBtn.disabled = true;

    try {
        // Get current work data
        let data;
        if (exerciseTemplate === 'problem_tree') {
            data = exportProblemTreeData();
        } else if (exerciseTemplate === 'business_model_canvas') {
            data = exportBMCData();
        } else if (exerciseTemplate === 'persona') {
            data = exportPersonaData();
        }

        // Request AI feedback (uses configured provider)
        const feedbackEndpoint = window.AI_CONFIG ? window.AI_CONFIG.feedbackEndpoint : '/api/incubation-interactive/ai-feedback-ollama.php';
        const response = await fetch(feedbackEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                team_id: teamId,
                exercise_id: exerciseId,
                exercise_template: exerciseTemplate,
                data: data
            })
        });

        const result = await response.json();

        if (result.success) {
            displayFeedback(result.feedback);

            // Update checklist based on feedback
            if (result.feedback.completeness_score >= 80) {
                document.querySelectorAll('.checkbox').forEach(cb => {
                    cb.classList.add('checked');
                    cb.innerHTML = '✓';
                });
            }
        } else {
            alert('Failed to get AI feedback: ' + result.message);
        }
    } catch (error) {
        console.error('AI Feedback error:', error);
        alert('Failed to get AI feedback: ' + error.message);
    } finally {
        loadingSpinner.classList.remove('active');
        feedbackBtn.disabled = false;
    }
}

function displayFeedback(feedback) {
    // Create feedback card
    const feedbackSection = document.querySelector('.ai-section');
    const existingCards = feedbackSection.querySelectorAll('.feedback-card');

    // Remove old cards if more than 3
    if (existingCards.length >= 3) {
        existingCards[existingCards.length - 1].remove();
    }

    const feedbackCard = document.createElement('div');
    feedbackCard.className = 'feedback-card';
    feedbackCard.innerHTML = `
        <div class="feedback-score">
            <span class="score-badge">${feedback.completeness_score}%</span>
            <span style="font-size: 0.75rem; color: #6b7280;">
                Just now
            </span>
        </div>
        <div class="feedback-text" style="margin-bottom: 0.75rem;">
            ${feedback.feedback_text.substring(0, 200)}...
            <a href="#" onclick="showFullFeedback(${feedback.id}); return false;" style="color: #667eea; font-weight: 600;">Read more</a>
        </div>
        ${feedback.strengths ? `
        <div style="margin-bottom: 0.5rem;">
            <strong style="color: #10b981;">✓ Strengths:</strong>
            <ul style="margin: 0.25rem 0 0 1.5rem; font-size: 0.875rem;">
                ${feedback.strengths.map(s => `<li>${s}</li>`).join('')}
            </ul>
        </div>
        ` : ''}
        ${feedback.improvements ? `
        <div>
            <strong style="color: #f59e0b;">→ Improvements:</strong>
            <ul style="margin: 0.25rem 0 0 1.5rem; font-size: 0.875rem;">
                ${feedback.improvements.map(i => `<li>${i}</li>`).join('')}
            </ul>
        </div>
        ` : ''}
    `;

    // Insert at the beginning
    feedbackSection.insertBefore(feedbackCard, feedbackSection.firstChild.nextSibling);

    // Update progress bar
    const progressFill = document.querySelector('.progress-fill');
    if (progressFill) {
        progressFill.style.width = feedback.completeness_score + '%';
    }

    // Show notification
    showNotification(`AI Feedback received! Score: ${feedback.completeness_score}%`, 'success');
}

function showFullFeedback(feedbackId) {
    // Create modal with full feedback
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;

    modal.innerHTML = `
        <div style="background: white; padding: 2rem; border-radius: 0.5rem; max-width: 600px; max-height: 80vh; overflow-y: auto;">
            <h2 style="margin-bottom: 1rem;">AI Feedback Details</h2>
            <div id="full-feedback-content">Loading...</div>
            <button onclick="this.parentElement.parentElement.remove()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: #667eea; color: white; border: none; border-radius: 0.375rem; cursor: pointer;">
                Close
            </button>
        </div>
    `;

    document.body.appendChild(modal);

    // Fetch full feedback
    fetch(`/api/incubation-interactive/get-feedback.php?id=${feedbackId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('full-feedback-content').innerHTML = data.feedback.feedback_text.replace(/\n/g, '<br>');
            }
        });
}

async function sendChatMessage() {
    const input = document.getElementById('chat-input');
    const chatBox = document.getElementById('ai-chat-box');
    const message = input.value.trim();

    if (!message) return;

    // Add user message to chat
    appendChatMessage('user', message);
    input.value = '';

    // Show typing indicator
    const typingIndicator = document.createElement('div');
    typingIndicator.className = 'chat-message ai';
    typingIndicator.innerHTML = '<em>AI is typing...</em>';
    typingIndicator.id = 'typing-indicator';
    chatBox.appendChild(typingIndicator);
    chatBox.scrollTop = chatBox.scrollHeight;

    try {
        // Send to AI (uses configured provider)
        const chatEndpoint = window.AI_CONFIG ? window.AI_CONFIG.chatEndpoint : '/api/incubation-interactive/ai-chat-ollama.php';
        const response = await fetch(chatEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                team_id: teamId,
                exercise_id: exerciseId,
                exercise_template: exerciseTemplate,
                message: message
            })
        });

        const result = await response.json();

        // Remove typing indicator
        document.getElementById('typing-indicator')?.remove();

        if (result.success) {
            appendChatMessage('ai', result.response);
        } else {
            appendChatMessage('ai', 'Sorry, I encountered an error. Please try again.');
        }
    } catch (error) {
        console.error('Chat error:', error);
        document.getElementById('typing-indicator')?.remove();
        appendChatMessage('ai', 'Sorry, I encountered an error. Please try again.');
    }
}

function appendChatMessage(type, text) {
    const chatBox = document.getElementById('ai-chat-box');
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${type}`;
    messageDiv.innerHTML = text.replace(/\n/g, '<br>');
    chatBox.appendChild(messageDiv);
    chatBox.scrollTop = chatBox.scrollHeight;
}

async function submitForReview() {
    // Check completeness
    const checkboxes = document.querySelectorAll('.checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.classList.contains('checked'));

    if (!allChecked) {
        const confirm = window.confirm(
            'Your work may not be complete. Some checklist items are not yet checked.\n\n' +
            'Would you like to get AI feedback before submitting?'
        );

        if (confirm) {
            await getAIFeedback();
            return;
        }
    }

    const finalConfirm = window.confirm(
        'Are you sure you want to submit this exercise for admin review?\n\n' +
        'You can still make changes after submission, but admins will be notified.'
    );

    if (!finalConfirm) return;

    const loadingSpinner = document.getElementById('loading-spinner');
    loadingSpinner.classList.add('active');

    try {
        // Get current work data
        let data;
        if (exerciseTemplate === 'problem_tree') {
            data = exportProblemTreeData();
        } else if (exerciseTemplate === 'business_model_canvas') {
            data = exportBMCData();
        } else if (exerciseTemplate === 'persona') {
            data = exportPersonaData();
        }

        // Submit
        const response = await fetch('/api/incubation-interactive/submit-exercise.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                team_id: teamId,
                exercise_id: exerciseId,
                data_type: exerciseTemplate,
                data_json: data
            })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Exercise submitted for review!', 'success');
            setTimeout(() => {
                window.location.href = 'incubation-dashboard.php';
            }, 2000);
        } else {
            alert('Failed to submit: ' + result.message);
        }
    } catch (error) {
        console.error('Submit error:', error);
        alert('Failed to submit: ' + error.message);
    } finally {
        loadingSpinner.classList.remove('active');
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 0.5rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
