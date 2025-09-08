// State management
let currentConversationId = null;
let conversations = {};

// DOM elements
const chatMessages = document.getElementById('chatMessages');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
const newChatBtn = document.getElementById('newChatBtn');
const conversationsList = document.getElementById('conversationsList');
const currentConversationTitle = document.getElementById('currentConversationTitle');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadConversations();
    setupEventListeners();
    adjustTextareaHeight();
});

// Event Listeners
function setupEventListeners() {
    sendBtn.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    messageInput.addEventListener('input', adjustTextareaHeight);
    newChatBtn.addEventListener('click', createNewConversation);
}

// Adjust textarea height based on content
function adjustTextareaHeight() {
    messageInput.style.height = 'auto';
    messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
}

// Load all conversations
async function loadConversations() {
    try {
        const response = await fetch('/api/conversations');
        conversations = await response.json();
        renderConversationsList();
        
        // Load the most recent conversation if exists
        const conversationIds = Object.keys(conversations);
        if (conversationIds.length > 0 && !currentConversationId) {
            const sortedConversations = conversationIds.sort((a, b) => 
                new Date(conversations[b].updatedAt) - new Date(conversations[a].updatedAt)
            );
            loadConversation(sortedConversations[0]);
        }
    } catch (error) {
        console.error('Error loading conversations:', error);
    }
}

// Render conversations list in sidebar
function renderConversationsList() {
    conversationsList.innerHTML = '';
    
    const sortedIds = Object.keys(conversations).sort((a, b) => 
        new Date(conversations[b].updatedAt) - new Date(conversations[a].updatedAt)
    );
    
    sortedIds.forEach(id => {
        const conv = conversations[id];
        const convElement = document.createElement('div');
        convElement.className = 'conversation-item';
        if (id === currentConversationId) {
            convElement.classList.add('active');
        }
        
        const date = new Date(conv.updatedAt);
        const dateStr = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        convElement.innerHTML = `
            <div class="conversation-title">${escapeHtml(conv.title)}</div>
            <div class="conversation-date">${dateStr}</div>
            <button class="delete-conversation" onclick="deleteConversation('${id}', event)">Delete</button>
        `;
        
        convElement.addEventListener('click', () => loadConversation(id));
        conversationsList.appendChild(convElement);
    });
}

// Create new conversation
async function createNewConversation() {
    try {
        const response = await fetch('/api/conversations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        
        const data = await response.json();
        conversations[data.conversationId] = data.conversation;
        currentConversationId = data.conversationId;
        
        renderConversationsList();
        clearChatMessages();
        currentConversationTitle.textContent = 'New Conversation';
        messageInput.focus();
    } catch (error) {
        console.error('Error creating conversation:', error);
        showError('Failed to create new conversation');
    }
}

// Load specific conversation
async function loadConversation(conversationId) {
    try {
        const response = await fetch(`/api/conversations/${conversationId}`);
        if (!response.ok) throw new Error('Conversation not found');
        
        const conversation = await response.json();
        conversations[conversationId] = conversation;
        currentConversationId = conversationId;
        
        renderConversationsList();
        displayConversation(conversation);
        currentConversationTitle.textContent = conversation.title;
    } catch (error) {
        console.error('Error loading conversation:', error);
        showError('Failed to load conversation');
    }
}

// Display conversation messages
function displayConversation(conversation) {
    clearChatMessages();
    
    if (!conversation.messages || conversation.messages.length === 0) {
        chatMessages.innerHTML = `
            <div class="welcome-message">
                <h2>Start a new conversation</h2>
                <p>Type a message below to begin chatting with Gemini AI.</p>
            </div>
        `;
        return;
    }
    
    conversation.messages.forEach(msg => {
        addMessageToChat('user', msg.user, msg.timestamp);
        if (msg.assistant) {
            addMessageToChat('assistant', msg.assistant, msg.timestamp);
        }
    });
    
    scrollToBottom();
}

// Delete conversation
async function deleteConversation(conversationId, event) {
    event.stopPropagation();
    
    if (!confirm('Are you sure you want to delete this conversation?')) {
        return;
    }
    
    try {
        const response = await fetch(`/api/conversations/${conversationId}`, {
            method: 'DELETE'
        });
        
        if (!response.ok) throw new Error('Failed to delete');
        
        delete conversations[conversationId];
        
        if (currentConversationId === conversationId) {
            currentConversationId = null;
            clearChatMessages();
            currentConversationTitle.textContent = '';
            
            // Load another conversation if available
            const remainingIds = Object.keys(conversations);
            if (remainingIds.length > 0) {
                loadConversation(remainingIds[0]);
            }
        }
        
        renderConversationsList();
    } catch (error) {
        console.error('Error deleting conversation:', error);
        showError('Failed to delete conversation');
    }
}

// Send message
async function sendMessage() {
    const message = messageInput.value.trim();
    if (!message) return;
    
    // Disable input while sending
    messageInput.disabled = true;
    sendBtn.disabled = true;
    
    // Clear welcome message if present
    const welcomeMsg = chatMessages.querySelector('.welcome-message');
    if (welcomeMsg) {
        welcomeMsg.remove();
    }
    
    // Add user message to chat
    addMessageToChat('user', message);
    
    // Clear input
    messageInput.value = '';
    adjustTextareaHeight();
    
    // Show typing indicator
    showTypingIndicator();
    
    try {
        const response = await fetch('/api/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: message,
                conversationId: currentConversationId
            })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Failed to get response');
        }
        
        // Update current conversation ID if new
        if (!currentConversationId || currentConversationId !== data.conversationId) {
            currentConversationId = data.conversationId;
            // Reload conversations to get the new one
            await loadConversations();
        }
        
        // Remove typing indicator
        removeTypingIndicator();
        
        // Add assistant response
        addMessageToChat('assistant', data.response, data.message.timestamp);
        
        // Update conversation in state
        if (conversations[currentConversationId]) {
            if (!conversations[currentConversationId].messages) {
                conversations[currentConversationId].messages = [];
            }
            conversations[currentConversationId].messages.push(data.message);
            conversations[currentConversationId].updatedAt = new Date().toISOString();
            
            // Update title if it was the first message
            if (conversations[currentConversationId].messages.length === 1) {
                conversations[currentConversationId].title = message.substring(0, 50) + (message.length > 50 ? '...' : '');
                currentConversationTitle.textContent = conversations[currentConversationId].title;
            }
            
            renderConversationsList();
        }
        
    } catch (error) {
        console.error('Error sending message:', error);
        removeTypingIndicator();
        
        if (error.message.includes('GEMINI_API_KEY')) {
            showError('Please set your GEMINI_API_KEY in the .env file');
        } else {
            showError(error.message || 'Failed to send message. Please try again.');
        }
    } finally {
        // Re-enable input
        messageInput.disabled = false;
        sendBtn.disabled = false;
        messageInput.focus();
    }
}

// Add message to chat display
function addMessageToChat(type, content, timestamp = null) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    
    const timeStr = timestamp ? 
        new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 
        new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    messageDiv.innerHTML = `
        <div class="message-content">${escapeHtml(content)}</div>
        <div class="message-time">${timeStr}</div>
    `;
    
    chatMessages.appendChild(messageDiv);
    scrollToBottom();
}

// Show typing indicator
function showTypingIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'message assistant';
    indicator.id = 'typingIndicator';
    indicator.innerHTML = `
        <div class="typing-indicator">
            <span></span>
            <span></span>
            <span></span>
        </div>
    `;
    chatMessages.appendChild(indicator);
    scrollToBottom();
}

// Remove typing indicator
function removeTypingIndicator() {
    const indicator = document.getElementById('typingIndicator');
    if (indicator) {
        indicator.remove();
    }
}

// Clear chat messages
function clearChatMessages() {
    chatMessages.innerHTML = `
        <div class="welcome-message">
            <h2>Welcome to Gemini Chatbot!</h2>
            <p>Start a conversation or select one from the sidebar.</p>
        </div>
    `;
}

// Show error message
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    chatMessages.appendChild(errorDiv);
    scrollToBottom();
    
    // Remove error after 5 seconds
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

// Scroll to bottom of chat
function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}