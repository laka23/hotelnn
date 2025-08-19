<?php
// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'pos_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// App configuration
define('APP_NAME', 'Mobile Shop POS');
define('BASE_URL', rtrim((isset($_SERVER['BASE_URI']) ? $_SERVER['BASE_URI'] : ''), '/'));

// Session settings
ini_set('session.use_strict_mode', 1);
session_name('pos_session');

// Timezone
date_default_timezone_set(getenv('APP_TZ') ?: 'UTC');

