#!/bin/bash

# Gemini AI Chatbot Startup Script

echo "🤖 Starting Gemini AI Chatbot..."
echo "================================="

# Check if virtual environment exists
if [ ! -d "venv" ]; then
    echo "❌ Virtual environment not found. Please run setup first."
    exit 1
fi

# Check if .env file exists and has API key
if [ ! -f ".env" ]; then
    echo "❌ .env file not found. Please create one with your GEMINI_API_KEY."
    exit 1
fi

# Check if API key is set
if grep -q "your_gemini_api_key_here" .env; then
    echo "❌ Please set your actual Gemini API key in the .env file."
    echo "   Edit .env and replace 'your_gemini_api_key_here' with your actual API key."
    exit 1
fi

# Activate virtual environment
source venv/bin/activate

# Start the Flask application
echo "✅ Starting Flask server..."
echo "🌐 Open your browser and go to: http://localhost:5000"
echo "🛑 Press Ctrl+C to stop the server"
echo ""

python app.py