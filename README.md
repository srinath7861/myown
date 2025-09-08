# Gemini Chatbot with Persistent JSON History

A simple, elegant chatbot application using Google's Gemini API with persistent conversation history stored in JSON format.

## Features

- 💬 **Real-time chat interface** with Gemini AI
- 💾 **Persistent conversation history** stored in JSON format
- 📚 **Multiple conversations** support with sidebar navigation
- 🔄 **Context-aware responses** using conversation history
- 🎨 **Modern, responsive UI** with gradient design
- 🗑️ **Conversation management** (create, delete, switch between conversations)
- ⚡ **Auto-save** all conversations to JSON file

## Prerequisites

- Node.js (v14 or higher)
- npm or yarn
- Google Gemini API key

## Installation

1. **Clone or download this repository**

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Set up your Gemini API key:**
   - Open the `.env` file
   - Replace `your_gemini_api_key_here` with your actual Gemini API key
   ```
   GEMINI_API_KEY=your_actual_api_key_here
   PORT=3000
   ```

   To get a Gemini API key:
   - Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
   - Sign in with your Google account
   - Click "Get API Key"
   - Copy the generated key

## Running the Application

1. **Start the server:**
   ```bash
   npm start
   ```
   
   Or for development with auto-reload:
   ```bash
   npm run dev
   ```

2. **Open your browser and navigate to:**
   ```
   http://localhost:3000
   ```

## How It Works

### Conversation Storage

- All conversations are stored in `chat_history.json` in the root directory
- The file is automatically created on first run
- Each conversation includes:
  - Unique ID
  - Title (based on first message)
  - Creation and update timestamps
  - Complete message history

### JSON Structure

```json
{
  "conversations": {
    "conversation-id": {
      "id": "unique-id",
      "title": "Conversation Title",
      "createdAt": "2024-01-01T00:00:00.000Z",
      "updatedAt": "2024-01-01T00:00:00.000Z",
      "messages": [
        {
          "id": "message-id",
          "user": "User message",
          "assistant": "AI response",
          "timestamp": "2024-01-01T00:00:00.000Z"
        }
      ]
    }
  }
}
```

## API Endpoints

- `GET /api/conversations` - Get all conversations
- `GET /api/conversations/:id` - Get specific conversation
- `POST /api/conversations` - Create new conversation
- `DELETE /api/conversations/:id` - Delete conversation
- `POST /api/chat` - Send message and get response

## Features in Detail

### Persistent Storage
- Conversations survive server restarts
- All data stored locally in JSON format
- No database required

### Context-Aware Responses
- Each message includes conversation history
- Gemini API receives context for better responses
- Maintains conversation continuity

### User Interface
- Clean, modern design with gradient accents
- Responsive layout
- Real-time typing indicators
- Message timestamps
- Auto-scrolling chat view

## Troubleshooting

### API Key Issues
If you see "Please set your GEMINI_API_KEY in the .env file":
1. Make sure you've added your API key to `.env`
2. Restart the server after adding the key
3. Verify your API key is valid and has proper permissions

### Port Already in Use
If port 3000 is already in use, change it in `.env`:
```
PORT=3001
```

### Chat History Not Saving
- Check file permissions for `chat_history.json`
- Ensure the application has write access to the directory

## Security Notes

- **Never commit your `.env` file** with your API key to version control
- Add `.env` to `.gitignore` if using Git
- Keep your API key secure and rotate it regularly
- The application stores all data locally - no external database required

## License

MIT