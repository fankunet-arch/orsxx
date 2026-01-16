<?php
/**
 * ORS Environment Configuration
 *
 * IMPORTANT: This file contains sensitive information.
 * - DO NOT commit this file to version control
 * - Copy from env_ors.example.php and modify for your environment
 */

// Database Configuration
define('ORS_DB_HOST', 'localhost');
define('ORS_DB_PORT', '3306');
define('ORS_DB_NAME', 'ors_db');
define('ORS_DB_USER', 'ors_user');
define('ORS_DB_PASS', 'your_password_here');
define('ORS_DB_CHARSET', 'utf8mb4');

// Application Configuration
define('ORS_DEBUG', true);  // Set to false in production
define('ORS_TIMEZONE', 'Asia/Shanghai');

// Session Configuration
define('ORS_SESSION_NAME', 'ors_session');
define('ORS_SESSION_LIFETIME', 86400 * 7);  // 7 days for "remember me"
define('ORS_SESSION_PATH', '/');

// Security
define('ORS_CSRF_TOKEN_NAME', 'ors_csrf_token');

// Default Currency
define('ORS_DEFAULT_CURRENCY', 'EUR');
define('ORS_DEFAULT_FX_CNY_EUR', 0.13);
define('ORS_DEFAULT_FX_USD_EUR', 0.92);

// File paths (relative to PROJECT_ROOT)
define('ORS_APP_PATH', dirname(__DIR__));  // app/ors/
define('ORS_LIB_PATH', ORS_APP_PATH . '/lib');
define('ORS_VIEW_PATH', ORS_APP_PATH . '/views');
define('ORS_API_PATH', ORS_APP_PATH . '/api');
