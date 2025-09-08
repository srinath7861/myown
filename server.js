const express = require('express');
const cors = require('cors');
const axios = require('axios');
const fs = require('fs').promises;
const path = require('path');
const { v4: uuidv4 } = require('uuid');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('public'));

// Path to chat history file
const CHAT_HISTORY_FILE = path.join(__dirname, 'chat_history.json');

// Initialize chat history file if it doesn't exist
async function initializeChatHistory() {
    try {
        await fs.access(CHAT_HISTORY_FILE);
    } catch {
        await fs.writeFile(CHAT_HISTORY_FILE, JSON.stringify({ conversations: {} }, null, 2));
    }
}

// Load chat history
async function loadChatHistory() {
    try {
        const data = await fs.readFile(CHAT_HISTORY_FILE, 'utf8');
        return JSON.parse(data);
    } catch (error) {
        console.error('Error loading chat history:', error);
        return { conversations: {} };
    }
}

// Save chat history
async function saveChatHistory(history) {
    try {
        await fs.writeFile(CHAT_HISTORY_FILE, JSON.stringify(history, null, 2));
    } catch (error) {
        console.error('Error saving chat history:', error);
    }
}

// Call Gemini API
async function callGeminiAPI(message, conversationHistory = []) {
    const apiKey = process.env.GEMINI_API_KEY;
    
    if (!apiKey || apiKey === 'your_gemini_api_key_here') {
        throw new Error('Please set your GEMINI_API_KEY in the .env file');
    }

    const url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    
    // Build conversation context
    const contents = [];
    
    // Add previous messages for context
    conversationHistory.forEach(msg => {
        contents.push({
            parts: [{ text: `User: ${msg.user}` }]
        });
        if (msg.assistant) {
            contents.push({
                parts: [{ text: `Assistant: ${msg.assistant}` }]
            });
        }
    });
    
    // Add current message
    contents.push({
        parts: [{ text: message }]
    });

    try {
        const response = await axios.post(url, {
            contents: contents
        }, {
            headers: {
                'Content-Type': 'application/json',
                'X-goog-api-key': apiKey
            }
        });

        if (response.data.candidates && response.data.candidates[0]) {
            return response.data.candidates[0].content.parts[0].text;
        } else {
            throw new Error('Unexpected response format from Gemini API');
        }
    } catch (error) {
        console.error('Gemini API Error:', error.response?.data || error.message);
        throw error;
    }
}

// API Routes

// Get all conversations
app.get('/api/conversations', async (req, res) => {
    try {
        const history = await loadChatHistory();
        res.json(history.conversations);
    } catch (error) {
        res.status(500).json({ error: 'Failed to load conversations' });
    }
});

// Get specific conversation
app.get('/api/conversations/:id', async (req, res) => {
    try {
        const history = await loadChatHistory();
        const conversation = history.conversations[req.params.id];
        
        if (!conversation) {
            return res.status(404).json({ error: 'Conversation not found' });
        }
        
        res.json(conversation);
    } catch (error) {
        res.status(500).json({ error: 'Failed to load conversation' });
    }
});

// Create new conversation
app.post('/api/conversations', async (req, res) => {
    try {
        const history = await loadChatHistory();
        const conversationId = uuidv4();
        
        history.conversations[conversationId] = {
            id: conversationId,
            title: 'New Conversation',
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString(),
            messages: []
        };
        
        await saveChatHistory(history);
        res.json({ conversationId, conversation: history.conversations[conversationId] });
    } catch (error) {
        res.status(500).json({ error: 'Failed to create conversation' });
    }
});

// Delete conversation
app.delete('/api/conversations/:id', async (req, res) => {
    try {
        const history = await loadChatHistory();
        
        if (!history.conversations[req.params.id]) {
            return res.status(404).json({ error: 'Conversation not found' });
        }
        
        delete history.conversations[req.params.id];
        await saveChatHistory(history);
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ error: 'Failed to delete conversation' });
    }
});

// Send message and get response
app.post('/api/chat', async (req, res) => {
    try {
        const { message, conversationId } = req.body;
        
        if (!message) {
            return res.status(400).json({ error: 'Message is required' });
        }

        const history = await loadChatHistory();
        
        // Create new conversation if ID not provided
        let convId = conversationId;
        if (!convId || !history.conversations[convId]) {
            convId = uuidv4();
            history.conversations[convId] = {
                id: convId,
                title: message.substring(0, 50) + (message.length > 50 ? '...' : ''),
                createdAt: new Date().toISOString(),
                updatedAt: new Date().toISOString(),
                messages: []
            };
        }

        const conversation = history.conversations[convId];
        
        // Get response from Gemini API with conversation context
        const response = await callGeminiAPI(message, conversation.messages);
        
        // Create message entry
        const messageEntry = {
            id: uuidv4(),
            user: message,
            assistant: response,
            timestamp: new Date().toISOString()
        };
        
        // Add to conversation
        conversation.messages.push(messageEntry);
        conversation.updatedAt = new Date().toISOString();
        
        // Update title if it's the first message
        if (conversation.messages.length === 1) {
            conversation.title = message.substring(0, 50) + (message.length > 50 ? '...' : '');
        }
        
        // Save updated history
        await saveChatHistory(history);
        
        res.json({
            conversationId: convId,
            message: messageEntry,
            response: response
        });
    } catch (error) {
        console.error('Chat error:', error);
        res.status(500).json({ 
            error: error.message || 'Failed to process message',
            details: error.response?.data || undefined
        });
    }
});

// Initialize and start server
initializeChatHistory().then(() => {
    app.listen(PORT, () => {
        console.log(`Server running on http://localhost:${PORT}`);
        console.log('Make sure to set your GEMINI_API_KEY in the .env file');
    });
});