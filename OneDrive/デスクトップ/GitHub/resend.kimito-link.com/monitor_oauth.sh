#!/bin/bash
# OAuth Flow Diagnostic Script
# このスクリプトを実行しながらログインをテストしてください

echo "=== OAuth Flow Monitoring ==="
echo "Press Ctrl+C to stop"
echo ""
echo "Now monitoring logs..."
echo "Please try to login at: https://resend.kimito-link.com/"
echo ""

cd /home/besttrust/kimito-link.com/_git/mailloop

# Clear old logs
> storage/oauth_trace.log

# Monitor logs in real-time
tail -f storage/app_error.log | grep --line-buffered -E '(LOGIN|CALLBACK|getUser|session_id|user_id|Location:)' | while read line; do
    echo "[$(date '+%H:%M:%S')] $line"
    echo "[$(date '+%H:%M:%S')] $line" >> storage/oauth_trace.log
done
