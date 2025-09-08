import json
import os
import requests
from datetime import datetime
from flask import Flask, request, jsonify, render_template
from flask_cors import CORS
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

app = Flask(__name__)
CORS(app)

# Configuration
GEMINI_API_KEY = os.getenv('GEMINI_API_KEY')
GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent"
CHAT_HISTORY_FILE = "chat_history.json"

def load_chat_history():
    """Load chat history from JSON file"""
    try:
        if os.path.exists(CHAT_HISTORY_FILE):
            with open(CHAT_HISTORY_FILE, 'r', encoding='utf-8') as f:
                return json.load(f)
        return []
    except Exception as e:
        print(f"Error loading chat history: {e}")
        return []

def save_chat_history(history):
    """Save chat history to JSON file"""
    try:
        with open(CHAT_HISTORY_FILE, 'w', encoding='utf-8') as f:
            json.dump(history, f, indent=2, ensure_ascii=False)
    except Exception as e:
        print(f"Error saving chat history: {e}")

def call_gemini_api(message):
    """Call Gemini API with the user message"""
    headers = {
        'Content-Type': 'application/json',
        'X-goog-api-key': GEMINI_API_KEY
    }
    
    payload = {
        "contents": [
            {
                "parts": [
                    {
                        "text": message
                    }
                ]
            }
        ]
    }
    
    try:
        response = requests.post(GEMINI_API_URL, headers=headers, json=payload)
        response.raise_for_status()
        
        data = response.json()
        if 'candidates' in data and len(data['candidates']) > 0:
            return data['candidates'][0]['content']['parts'][0]['text']
        else:
            return "Sorry, I couldn't generate a response."
            
    except requests.exceptions.RequestException as e:
        print(f"API Error: {e}")
        return "Sorry, there was an error connecting to the AI service."
    except Exception as e:
        print(f"Unexpected error: {e}")
        return "Sorry, an unexpected error occurred."

@app.route('/')
def index():
    """Serve the chat interface"""
    return render_template('index.html')

@app.route('/api/chat', methods=['POST'])
def chat():
    """Handle chat messages"""
    try:
        data = request.get_json()
        user_message = data.get('message', '').strip()
        
        if not user_message:
            return jsonify({'error': 'Message cannot be empty'}), 400
        
        # Get AI response
        ai_response = call_gemini_api(user_message)
        
        # Load existing chat history
        chat_history = load_chat_history()
        
        # Create new conversation entry
        conversation = {
            'id': len(chat_history) + 1,
            'timestamp': datetime.now().isoformat(),
            'user_message': user_message,
            'ai_response': ai_response
        }
        
        # Add to history and save
        chat_history.append(conversation)
        save_chat_history(chat_history)
        
        return jsonify({
            'success': True,
            'response': ai_response,
            'conversation_id': conversation['id']
        })
        
    except Exception as e:
        print(f"Error in chat endpoint: {e}")
        return jsonify({'error': 'Internal server error'}), 500

@app.route('/api/history', methods=['GET'])
def get_history():
    """Get chat history"""
    try:
        history = load_chat_history()
        return jsonify({'success': True, 'history': history})
    except Exception as e:
        print(f"Error getting history: {e}")
        return jsonify({'error': 'Failed to load chat history'}), 500

@app.route('/api/clear-history', methods=['POST'])
def clear_history():
    """Clear chat history"""
    try:
        save_chat_history([])
        return jsonify({'success': True, 'message': 'Chat history cleared'})
    except Exception as e:
        print(f"Error clearing history: {e}")
        return jsonify({'error': 'Failed to clear chat history'}), 500

if __name__ == '__main__':
    # Create templates directory if it doesn't exist
    if not os.path.exists('templates'):
        os.makedirs('templates')
    
    # Create static directory if it doesn't exist
    if not os.path.exists('static'):
        os.makedirs('static')
    
    app.run(debug=True, host='0.0.0.0', port=5000)