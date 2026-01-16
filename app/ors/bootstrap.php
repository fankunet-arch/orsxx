<?php
/**
 * ORS Bootstrap File
 *
 * This file initializes the ORS application:
 * - Loads environment configuration
 * - Sets up error handling
 * - Initializes database connection
 * - Starts session management
 * - Loads core libraries
 *
 * Both entry points (/ors/ and /ors/ap) must include this file.
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) === 'bootstrap.php') {
    http_response_code(403);
    exit('Direct access not allowed');
}

// Load environment configuration
$configPath = __DIR__ . '/config_ors/env_ors.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    exit('Configuration file not found. Please copy env_ors.example.php to config_ors/env_ors.php');
}
require_once $configPath;

// Set timezone
date_default_timezone_set(ORS_TIMEZONE);

// Error handling based on debug mode
if (ORS_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_name(ORS_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => ORS_SESSION_LIFETIME,
        'path' => ORS_SESSION_PATH,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Load core libraries
require_once ORS_LIB_PATH . '/Database.php';
require_once ORS_LIB_PATH . '/Auth.php';
require_once ORS_LIB_PATH . '/Response.php';
require_once ORS_LIB_PATH . '/Validator.php';
require_once ORS_LIB_PATH . '/Model.php';

// Initialize database connection
try {
    ORS\Database::init([
        'host' => ORS_DB_HOST,
        'port' => ORS_DB_PORT,
        'dbname' => ORS_DB_NAME,
        'username' => ORS_DB_USER,
        'password' => ORS_DB_PASS,
        'charset' => ORS_DB_CHARSET
    ]);
} catch (Exception $e) {
    if (ORS_DEBUG) {
        http_response_code(500);
        exit('Database connection failed: ' . $e->getMessage());
    } else {
        http_response_code(500);
        exit('Service temporarily unavailable');
    }
}

// CSRF Token management
if (empty($_SESSION[ORS_CSRF_TOKEN_NAME])) {
    $_SESSION[ORS_CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

/**
 * Get CSRF token for forms
 */
function ors_csrf_token(): string
{
    return $_SESSION[ORS_CSRF_TOKEN_NAME] ?? '';
}

/**
 * Verify CSRF token
 */
function ors_verify_csrf(string $token): bool
{
    return hash_equals($_SESSION[ORS_CSRF_TOKEN_NAME] ?? '', $token);
}

/**
 * Output CSRF hidden input field
 */
function ors_csrf_field(): string
{
    return '<input type="hidden" name="' . ORS_CSRF_TOKEN_NAME . '" value="' . htmlspecialchars(ors_csrf_token()) . '">';
}

/**
 * Escape HTML output
 */
function ors_e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Get current user info
 */
function ors_current_user(): ?array
{
    return ORS\Auth::getCurrentUser();
}

/**
 * Check if user is logged in
 */
function ors_is_logged_in(): bool
{
    return ORS\Auth::isLoggedIn();
}

/**
 * Check if current user has role
 */
function ors_has_role(string $role): bool
{
    return ORS\Auth::hasRole($role);
}

/**
 * Redirect helper
 */
function ors_redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * JSON response helper
 */
function ors_json(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Format currency
 */
function ors_format_currency(float $amount, string $currency = 'EUR', int $decimals = 2): string
{
    return number_format($amount, $decimals, '.', ',') . ' ' . $currency;
}

/**
 * Calculate EUR equivalent
 */
function ors_to_eur(float $amount, string $currency, ?float $fxRate = null): float
{
    if ($currency === 'EUR') {
        return $amount;
    }

    if ($fxRate === null) {
        if ($currency === 'CNY') {
            $fxRate = ORS_DEFAULT_FX_CNY_EUR;
        } elseif ($currency === 'USD') {
            $fxRate = ORS_DEFAULT_FX_USD_EUR;
        } else {
            $fxRate = 1.0;
        }
    }

    return round($amount * $fxRate, 2);
}
