# Gemini AI Chatbot

A simple, elegant chatbot web application that uses Google's Gemini API and stores conversation history in JSON format. The chat history persists across browser refreshes and sessions.

## Features

- 🤖 **Gemini AI Integration**: Powered by Google's Gemini 2.0 Flash model
- 💾 **Persistent Chat History**: All conversations are stored in JSON format
- 🔄 **Session Persistence**: Chat history remains after page refreshes
- 🎨 **Modern UI**: Beautiful, responsive design with smooth animations
- 🧹 **History Management**: Clear chat history with a single click
- 📱 **Mobile Friendly**: Fully responsive design for all devices

## Setup Instructions

### Prerequisites

- Python 3.7 or higher
- A Google Gemini API key

### Installation

1. **Clone or download the project files**

2. **Install dependencies:**
   ```bash
   pip install -r requirements.txt
   ```

3. **Set up your Gemini API key:**
   - Edit the `.env` file
   - Replace `your_gemini_api_key_here` with your actual Gemini API key
   
   ```
   GEMINI_API_KEY=your_actual_api_key_here
   ```

4. **Run the application:**
   ```bash
   python app.py
   ```

5. **Open your browser and go to:**
   ```
   http://localhost:5000
   ```

### Getting a Gemini API Key

1. Go to [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Sign in with your Google account
3. Create a new API key
4. Copy the key and paste it in your `.env` file

## Project Structure

```
chatbot/
├── app.py                 # Flask backend server
├── requirements.txt       # Python dependencies
├── .env                  # Environment variables (API key)
├── chat_history.json     # Stored conversations (auto-created)
├── templates/
│   └── index.html        # Main chat interface
└── static/
    ├── style.css         # Styling and animations
    └── script.js         # Frontend JavaScript logic
```

## API Endpoints

- `GET /` - Serve the chat interface
- `POST /api/chat` - Send a message to the AI
- `GET /api/history` - Retrieve chat history
- `POST /api/clear-history` - Clear all chat history

## Chat History Format

The chat history is stored in `chat_history.json` with the following structure:

```json
[
  {
    "id": 1,
    "timestamp": "2024-01-15T10:30:00.123456",
    "user_message": "Hello, how are you?",
    "ai_response": "Hello! I'm doing well, thank you for asking. How can I help you today?"
  }
]
```

## Features in Detail

### Persistent Storage
- All conversations are automatically saved to `chat_history.json`
- History loads automatically when you refresh the page
- Each conversation has a unique ID and timestamp

### Modern UI
- Gradient backgrounds and smooth animations
- Message bubbles with user and AI avatars
- Auto-resizing text input
- Loading indicators during API calls
- Responsive design for mobile devices

### Error Handling
- Graceful handling of API errors
- Network error recovery
- User-friendly error messages
- Input validation

## Customization

### Styling
Edit `static/style.css` to customize:
- Colors and gradients
- Font sizes and families
- Animation speeds
- Layout dimensions

### AI Behavior
Modify the `call_gemini_api()` function in `app.py` to:
- Change the AI model
- Add system prompts
- Adjust response parameters

### Storage
The JSON storage can be easily extended to:
- Add user sessions
- Store additional metadata
- Implement conversation threading

## Troubleshooting

### Common Issues

1. **"API Key not found" error:**
   - Make sure your `.env` file contains the correct API key
   - Verify the key is valid and has proper permissions

2. **"Module not found" errors:**
   - Run `pip install -r requirements.txt` to install dependencies

3. **Chat history not persisting:**
   - Check if the application has write permissions in the directory
   - Ensure `chat_history.json` is not corrupted

4. **Port already in use:**
   - Change the port in `app.py`: `app.run(debug=True, host='0.0.0.0', port=5001)`

## License

This project is open source and available under the MIT License.