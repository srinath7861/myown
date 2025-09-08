#!/bin/bash

# Gemini AI Chatbot Setup Script

echo "🚀 Setting up Gemini AI Chatbot..."
echo "=================================="

# Create virtual environment if it doesn't exist
if [ ! -d "venv" ]; then
    echo "📦 Creating virtual environment..."
    python3 -m venv venv
fi

# Activate virtual environment
echo "🔄 Activating virtual environment..."
source venv/bin/activate

# Install dependencies
echo "📥 Installing dependencies..."
pip install -r requirements.txt

echo ""
echo "✅ Setup complete!"
echo ""
echo "Next steps:"
echo "1. Edit the .env file and add your Gemini API key"
echo "2. Run ./start_chatbot.sh to start the application"
echo ""
echo "To get a Gemini API key:"
echo "- Visit: https://makersuite.google.com/app/apikey"
echo "- Sign in with your Google account"
echo "- Create a new API key"
echo "- Copy it to your .env file"