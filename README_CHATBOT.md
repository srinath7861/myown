# Adriana Chatbot - Conversation Storage System

## Overview
This chatbot system stores user information and conversations in a JSON file, allowing users to continue their conversations from where they left off when they return.

## Files Structure

```
/workspace/
├── chatbot.html          # Main chatbot HTML file with JavaScript
├── config.php           # PHP backend for handling data storage
├── chatbot_data.json    # Auto-created JSON file for storing data
└── chatbot_data_sample.json # Sample data structure
```

## Features

### 🔄 Conversation Continuity
- **New Users**: First-time visitors provide name, email, and mobile number
- **Returning Users**: System recognizes returning users by email + name combination
- **Previous Conversations**: Automatically loads and displays previous chat history
- **Seamless Experience**: Users can continue chatting from where they left off

### 💾 Data Storage
- **JSON File**: All data stored in `chatbot_data.json`
- **User Information**: Name, email, mobile, timestamps
- **Message History**: Complete conversation threads with timestamps
- **Automatic Creation**: JSON file created automatically if it doesn't exist

### 🛡️ Data Structure

#### Users Array
```json
{
  "id": "unique_user_id",
  "name": "Full Name",
  "email": "email@example.com", 
  "mobile": "+1-555-123-4567",
  "created_at": "2024-01-15 10:30:00",
  "last_login": "2024-01-22 14:45:00"
}
```

#### Conversations Array
```json
{
  "user_id": "unique_user_id",
  "messages": [
    {
      "id": "unique_message_id",
      "sender": "user|bot",
      "message": "Message content with HTML formatting",
      "timestamp": "2024-01-15 10:35:00"
    }
  ],
  "created_at": "2024-01-15 10:35:00",
  "updated_at": "2024-01-15 10:39:30"
}
```

## PHP Backend Functions

### `save_user`
- Saves new user or updates existing user
- Returns user ID and whether user is returning
- Updates last_login timestamp

### `save_message`
- Stores individual messages in conversations
- Links messages to users via user_id
- Creates new conversation thread if needed

### `get_conversation`
- Retrieves complete conversation history for a user
- Returns all messages in chronological order

### `check_returning_user`
- Checks if user exists based on email + name
- Returns user data and conversation if found
- Used before form submission to determine user status

## User Experience Flow

### First-Time Users
1. User clicks chatbot widget
2. Fills out contact form (name, email, mobile)
3. System creates new user record
4. Chat area opens with welcome message
5. Conversation begins and gets stored

### Returning Users
1. User clicks chatbot widget
2. Fills out contact form with same email + name
3. System recognizes returning user
4. Shows loading indicator while fetching data
5. Displays previous conversation history
6. Shows "Welcome back" message
7. User can continue from where they left off

## Installation & Setup

### 1. Server Requirements
- PHP 7.0 or higher
- Write permissions for JSON file creation
- Web server (Apache, Nginx, etc.)

### 2. File Placement
```
your-website/
├── chatbot.html     # Place in your website directory
├── config.php      # Place in same directory as HTML
└── (chatbot_data.json will be auto-created)
```

### 3. Configuration
- Update `GEMINI_API_KEY` in JavaScript if using AI responses
- Ensure PHP has write permissions for JSON file creation
- Test with sample users to verify functionality

### 4. Security Considerations
- Set appropriate file permissions on `chatbot_data.json`
- Consider moving JSON file outside web root for security
- Implement input validation and sanitization
- Add rate limiting for API calls

## API Integration

The system includes integration with Google's Gemini AI for intelligent responses:
- Knowledge base provides instant answers for common questions
- AI handles complex queries when knowledge base doesn't match
- Conversation context maintained for natural dialogue

## Customization

### Styling
- All CSS is scoped with `.adriana-` prefix to prevent conflicts
- Mobile-responsive design included
- Easy to customize colors and branding

### Knowledge Base
- Comprehensive knowledge base in JavaScript
- Easy to update services, pricing, and contact information
- Supports HTML formatting in responses

### Quick Replies
- Pre-defined quick reply buttons for common questions
- Dynamic follow-up suggestions based on conversation context
- Customizable button text and actions

## Testing

### Test Scenarios
1. **New User Registration**
   - Fill form with new email/name
   - Verify user created in JSON
   - Check welcome message appears

2. **Returning User Recognition**
   - Use same email/name as before
   - Verify previous conversation loads
   - Check welcome back message

3. **Message Storage**
   - Send various message types
   - Verify all messages saved with timestamps
   - Check conversation continuity

4. **Error Handling**
   - Test with invalid form data
   - Test with corrupted JSON file
   - Verify graceful fallbacks

## Maintenance

### Regular Tasks
- Monitor JSON file size and consider archiving old conversations
- Backup conversation data regularly
- Update knowledge base with new information
- Review and update AI prompts as needed

### Troubleshooting
- Check PHP error logs for backend issues
- Verify JSON file permissions and structure
- Test API connections and rate limits
- Monitor browser console for JavaScript errors

## Support

For questions or issues:
- Check browser console for JavaScript errors
- Review PHP error logs for backend problems
- Verify file permissions and server configuration
- Test with different browsers and devices