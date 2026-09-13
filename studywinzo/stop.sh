#!/data/data/com.termux/files/usr/bin/bash
echo "🛑 Stopping StudyWinzo..."
pkill -9 php 2>/dev/null
pkill -9 ngrok 2>/dev/null
sleep 1
echo "✅ Stopped"
