<?php
return [
  'APP_URL' => 'https://example.com',
  'APP_KEY' => 'CHANGE_ME_TO_A_LONG_RANDOM_STRING',
  'STORAGE_DRIVER' => 'mysql', // mysql|file

  'DB_HOST' => 'localhost',
  'DB_NAME' => 'dbname',
  'DB_USER' => 'dbuser',
  'DB_PASS' => 'dbpass',
  'DB_CHARSET' => 'utf8mb4',

  'GOOGLE_CLIENT_ID' => 'YOUR_CLIENT_ID',
  'GOOGLE_CLIENT_SECRET' => 'YOUR_CLIENT_SECRET',
  'GOOGLE_REDIRECT_URI' => 'https://example.com/auth/callback',

  'GMAIL_SCOPE' => 'https://www.googleapis.com/auth/gmail.send',

  'WARN_BCC_1' => 50,
  'WARN_BCC_2' => 100,
  'DUPLICATE_SEND_HOURS' => 6,
];
