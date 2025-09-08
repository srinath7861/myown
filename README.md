# Adriana Chatbot - Complete Solution with Conversation Storage

A sophisticated chatbot widget with complete backend integration for storing and retrieving user conversations. The system automatically recognizes returning users and displays their conversation history.

## 🚀 Features

- **User Registration**: Collects name, email, and mobile number
- **Automatic User Recognition**: Identifies returning users by email/name
- **Conversation Persistence**: All chats stored in JSON format
- **History Display**: Shows previous conversations for returning users
- **Seamless Continuation**: Users can continue from where they left off
- **Admin Dashboard**: View all users and conversations
- **Data Export**: Export all data in JSON format
- **Automatic Backups**: Maintains last 10 backups automatically
- **Search Functionality**: Search users and conversations in admin panel
- **Real-time Statistics**: Track active users and message counts

## 📁 File Structure

```
/
├── chatbot_widget.html      # Main chatbot widget (can be embedded in any page)
├── chatbot_backend.php      # Backend API for data management
├── admin_dashboard.php      # Admin panel for viewing data
├── test_chatbot.html        # Test page with instructions
├── chatbot_data.json        # Data storage (auto-created)
└── backups/                 # Automatic backups directory (auto-created)
```

## 🔧 Installation

### 1. Basic Setup

1. Copy all files to your web server with PHP support
2. Ensure PHP 7.0+ is installed and enabled
3. Set proper file permissions:
```bash
chmod 755 .
chmod 666 chatbot_data.json  # After first run
chmod 755 backups/            # After first run
```

### 2. Configuration

#### Update Backend URL (chatbot_widget.html)
```javascript
// Line 1564
const BACKEND_URL = 'chatbot_backend.php'; // Update if backend is in different location
```

#### Add Gemini API Key (chatbot_widget.html)
```javascript
// Line 1565
const GEMINI_API_KEY = 'YOUR_ACTUAL_API_KEY_HERE'; // Get from Google AI Studio
```

#### Change Admin Password (admin_dashboard.php)
```php
// Line 5
$ADMIN_PASSWORD = 'your-secure-password-here'; // Change immediately!
```

## 💻 Usage

### For Website Visitors

1. Click the chat widget in the bottom-right corner
2. Enter name, email, and mobile number
3. Start chatting with Adriana
4. On return visits with same email/name, see previous conversations
5. Continue chatting with full context preserved

### For Administrators

1. Navigate to `admin_dashboard.php`
2. Login with admin password
3. View statistics, users, and conversations
4. Export data as needed
5. Search through users and conversations

### For Developers

#### Embed in Your Website
```html
<!-- Add to your page where you want the chatbot -->
<iframe src="chatbot_widget.html" style="position:fixed; bottom:0; right:0; border:none; width:100%; height:100%; z-index:9999;"></iframe>
```

#### Or Include Directly
```html
<!-- Copy the entire content of chatbot_widget.html into your page -->
```

## 📊 Data Structure

The system stores data in JSON format:

```json
{
  "users": [{
    "id": "user_xxx",
    "name": "John Doe",
    "email": "john@example.com",
    "mobile": "+1234567890",
    "created_at": "2024-01-15 10:30:00",
    "last_login": "2024-01-15 10:30:00",
    "login_count": 1
  }],
  "conversations": [{
    "id": "conv_xxx",
    "user_id": "user_xxx",
    "date": "2024-01-15",
    "started_at": "2024-01-15 10:30:00",
    "messages": [{
      "id": "msg_xxx",
      "sender": "user|bot",
      "message": "Message content",
      "timestamp": "2024-01-15 10:30:00"
    }]
  }],
  "metadata": {
    "created_at": "2024-01-15 10:00:00",
    "last_updated": "2024-01-15 10:30:00",
    "version": "1.0"
  }
}
```

## 🔒 Security Considerations

1. **Change Default Password**: Update admin password immediately
2. **Use HTTPS**: Implement SSL for production
3. **Add Authentication**: Implement proper user authentication for production
4. **Database Migration**: Consider MySQL/PostgreSQL for large-scale deployments
5. **Rate Limiting**: Add rate limiting to prevent abuse
6. **Input Sanitization**: All inputs are sanitized, but review for your use case
7. **File Permissions**: Ensure proper file permissions on server

## 🛠️ API Endpoints

### Save User
```javascript
POST chatbot_backend.php
{
  "action": "save_user",
  "name": "John Doe",
  "email": "john@example.com",
  "mobile": "+1234567890"
}
```

### Check User
```javascript
POST chatbot_backend.php
{
  "action": "check_user",
  "email": "john@example.com",
  "name": "John Doe"  // Optional
}
```

### Save Message
```javascript
POST chatbot_backend.php
{
  "action": "save_message",
  "user_id": "user_xxx",
  "message": "Hello",
  "sender": "user|bot"
}
```

### Get History
```javascript
POST/GET chatbot_backend.php
{
  "action": "get_history",
  "user_id": "user_xxx"
}
```

### Get Statistics
```javascript
GET chatbot_backend.php?action=get_statistics
```

### Export Data
```javascript
GET chatbot_backend.php?action=export_data
```

## 🐛 Troubleshooting

### Chatbot not saving data
- Check PHP is enabled on server
- Verify file permissions (backend needs write access)
- Check browser console for errors

### Admin panel not loading
- Ensure `chatbot_data.json` exists
- Check file permissions
- Verify PHP is working

### AI responses not working
- Add valid Gemini API key
- Check API key permissions
- Verify internet connectivity

### Widget not appearing
- Check browser console for JavaScript errors
- Ensure all files are loaded correctly
- Verify no CSS conflicts

## 📈 Performance Tips

1. **Caching**: Implement caching for frequently accessed data
2. **Pagination**: Add pagination for large conversation lists
3. **Compression**: Enable GZIP compression on server
4. **CDN**: Serve static assets via CDN
5. **Database**: Migrate to database for better performance at scale

## 🤝 Support

For issues or questions:
1. Check the test page at `test_chatbot.html`
2. Review browser console for errors
3. Check PHP error logs
4. Verify all configurations are correct

## 📄 License

This chatbot system is provided as-is for integration into your website. Customize as needed for your specific requirements.

## 🎯 Quick Start Checklist

- [ ] Upload all files to PHP-enabled server
- [ ] Update `BACKEND_URL` if needed
- [ ] Add your Gemini API key
- [ ] Change admin password
- [ ] Test chatbot functionality
- [ ] Access admin dashboard
- [ ] Configure for production use
- [ ] Implement additional security measures

---

**Note**: This is a complete, working solution. The chatbot will start working immediately after proper configuration. All conversation data is automatically stored and retrieved for returning users.