class ChatBot {
    constructor() {
        this.messageInput = document.getElementById('message-input');
        this.sendBtn = document.getElementById('send-btn');
        this.chatMessages = document.getElementById('chat-messages');
        this.clearHistoryBtn = document.getElementById('clear-history-btn');
        this.loadingIndicator = document.getElementById('loading-indicator');
        
        this.initializeEventListeners();
        this.loadChatHistory();
        this.adjustTextareaHeight();
    }

    initializeEventListeners() {
        // Send button click
        this.sendBtn.addEventListener('click', () => this.sendMessage());
        
        // Enter key to send message
        this.messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Auto-resize textarea
        this.messageInput.addEventListener('input', () => {
            this.adjustTextareaHeight();
            this.toggleSendButton();
        });
        
        // Clear history button
        this.clearHistoryBtn.addEventListener('click', () => this.clearHistory());
        
        // Initial button state
        this.toggleSendButton();
    }

    adjustTextareaHeight() {
        this.messageInput.style.height = 'auto';
        this.messageInput.style.height = Math.min(this.messageInput.scrollHeight, 120) + 'px';
    }

    toggleSendButton() {
        const hasText = this.messageInput.value.trim().length > 0;
        this.sendBtn.disabled = !hasText;
    }

    async sendMessage() {
        const message = this.messageInput.value.trim();
        if (!message) return;

        // Clear input and reset height
        this.messageInput.value = '';
        this.adjustTextareaHeight();
        this.toggleSendButton();

        // Add user message to chat
        this.addMessage(message, 'user');

        // Show loading indicator
        this.showLoading(true);

        try {
            const response = await fetch('/api/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message }),
            });

            const data = await response.json();

            if (data.success) {
                this.addMessage(data.response, 'ai');
            } else {
                this.addMessage('Sorry, there was an error processing your request.', 'ai');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            this.addMessage('Sorry, there was a network error. Please try again.', 'ai');
        } finally {
            this.showLoading(false);
        }
    }

    addMessage(content, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message`;

        const avatarDiv = document.createElement('div');
        avatarDiv.className = `message-avatar ${sender}-avatar`;
        avatarDiv.innerHTML = sender === 'user' ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';

        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        // Format the content (basic markdown-like formatting)
        const formattedContent = this.formatMessage(content);
        contentDiv.innerHTML = formattedContent;

        const timeDiv = document.createElement('div');
        timeDiv.className = 'message-time';
        timeDiv.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        contentDiv.appendChild(timeDiv);
        messageDiv.appendChild(avatarDiv);
        messageDiv.appendChild(contentDiv);

        this.chatMessages.appendChild(messageDiv);
        this.scrollToBottom();
    }

    formatMessage(content) {
        // Basic formatting for better readability
        return content
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') // Bold
            .replace(/\*(.*?)\*/g, '<em>$1</em>') // Italic
            .replace(/`(.*?)`/g, '<code>$1</code>') // Inline code
            .replace(/\n/g, '<br>'); // Line breaks
    }

    showLoading(show) {
        if (show) {
            this.loadingIndicator.classList.add('show');
        } else {
            this.loadingIndicator.classList.remove('show');
        }
        this.scrollToBottom();
    }

    scrollToBottom() {
        setTimeout(() => {
            this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
        }, 100);
    }

    async loadChatHistory() {
        try {
            const response = await fetch('/api/history');
            const data = await response.json();

            if (data.success && data.history.length > 0) {
                // Clear welcome message if there's history
                const welcomeMessage = this.chatMessages.querySelector('.welcome-message');
                if (welcomeMessage) {
                    welcomeMessage.style.display = 'none';
                }

                // Load all previous conversations
                data.history.forEach(conversation => {
                    this.addMessage(conversation.user_message, 'user');
                    this.addMessage(conversation.ai_response, 'ai');
                });
            }
        } catch (error) {
            console.error('Error loading chat history:', error);
        }
    }

    async clearHistory() {
        if (!confirm('Are you sure you want to clear all chat history? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch('/api/clear-history', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                // Clear chat messages
                this.chatMessages.innerHTML = `
                    <div class="welcome-message">
                        <div class="ai-message">
                            <div class="message-avatar ai-avatar">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="message-content">
                                <p>Hello! I'm your AI assistant powered by Gemini. How can I help you today?</p>
                            </div>
                        </div>
                    </div>
                `;
                
                // Show success message temporarily
                this.showTemporaryMessage('Chat history cleared successfully!', 'success');
            } else {
                this.showTemporaryMessage('Failed to clear chat history.', 'error');
            }
        } catch (error) {
            console.error('Error clearing history:', error);
            this.showTemporaryMessage('Network error while clearing history.', 'error');
        }
    }

    showTemporaryMessage(message, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `temporary-message ${type}`;
        messageDiv.textContent = message;
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        `;

        document.body.appendChild(messageDiv);

        setTimeout(() => {
            messageDiv.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                document.body.removeChild(messageDiv);
            }, 300);
        }, 3000);
    }
}

// Add CSS for temporary messages
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
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
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize the chatbot when the page loads
document.addEventListener('DOMContentLoaded', () => {
    new ChatBot();
});