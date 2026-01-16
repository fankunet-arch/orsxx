<?php
/**
 * ORS Environment Configuration - EXAMPLE FILE
 *
 * Copy this file to: app/ors/config_ors/env_ors.php
 * Then modify the values for your environment.
 *
 * IMPORTANT: Never commit the actual env_ors.php file to version control!
 */

// Database Configuration
define('ORS_DB_HOST', 'localhost');
define('ORS_DB_PORT', '3306');
define('ORS_DB_NAME', 'ors_db');          // Your database name
define('ORS_DB_USER', 'your_db_user');    // Your database username
define('ORS_DB_PASS', 'your_db_password'); // Your database password
define('ORS_DB_CHARSET', 'utf8mb4');

// Application Configuration
define('ORS_DEBUG', false);  // Set to true for development, false for production
define('ORS_TIMEZONE', 'Asia/Shanghai');

// Session Configuration
define('ORS_SESSION_NAME', 'ors_session');
define('ORS_SESSION_LIFETIME', 86400 * 7);  // 7 days - allows "remember me" functionality
define('ORS_SESSION_PATH', '/');

// Security
define('ORS_CSRF_TOKEN_NAME', 'ors_csrf_token');

// Default Currency Settings
define('ORS_DEFAULT_CURRENCY', 'EUR');
define('ORS_DEFAULT_FX_CNY_EUR', 0.13);  // CNY to EUR default rate
define('ORS_DEFAULT_FX_USD_EUR', 0.92);  // USD to EUR default rate

// File paths (these are auto-calculated, usually no need to change)
define('ORS_APP_PATH', dirname(__DIR__));  // app/ors/
define('ORS_LIB_PATH', ORS_APP_PATH . '/lib');
define('ORS_VIEW_PATH', ORS_APP_PATH . '/views');
define('ORS_API_PATH', ORS_APP_PATH . '/api');
