<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

date_default_timezone_set('Asia/Tokyo');
session_name('mailloop_session');
session_start();

require_once __DIR__ . '/lib/crypto.php';
require_once __DIR__ . '/lib/http.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/router.php';

require_once __DIR__ . '/services/storage.php';
require_once __DIR__ . '/services/google_oauth.php';
require_once __DIR__ . '/services/gmail_send.php';

$storage = create_storage($config);
