#!/bin/bash
# Database check script for MailLoop

echo "=== Checking MailLoop Database ==="
echo ""

cd /home/besttrust/kimito-link.com/_git/mailloop

echo "1. Users table:"
mysql -u besttrust_mail -ppass369code -h 175.28.4.206 besttrust_mail -e "SELECT id, provider, provider_sub, email, name FROM users;"

echo ""
echo "2. OAuth tokens table:"
mysql -u besttrust_mail -ppass369code -h 175.28.4.206 besttrust_mail -e "SELECT user_id, provider, expires_at FROM oauth_tokens;"

echo ""
echo "3. Recent error logs:"
tail -10 storage/app_error.log

echo ""
echo "=== Check Complete ==="
