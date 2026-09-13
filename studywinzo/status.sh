#!/data/data/com.termux/files/usr/bin/bash
echo ""
echo "═══ StudyWinzo Status ═══"
echo ""
if pgrep -f "php -S" > /dev/null; then
    echo "✅ PHP server: RUNNING (PID $(pgrep -f 'php -S'))"
else
    echo "❌ PHP server: STOPPED"
fi

if pgrep -f ngrok > /dev/null; then
    echo "✅ ngrok:      RUNNING (PID $(pgrep -f ngrok))"
else
    echo "❌ ngrok:      STOPPED"
fi

echo ""
echo "🌍 URL: https://scoreless-trousers-aviation.ngrok-free.dev"
echo ""
