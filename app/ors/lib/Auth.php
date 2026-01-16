<?php
namespace ORS;

/**
 * Authentication Manager
 */
class Auth
{
    private const SESSION_USER_KEY = 'ors_user';
    private const COOKIE_REMEMBER = 'ors_remember';

    /**
     * Attempt to login user
     */
    public static function attempt(string $username, string $password, bool $remember = false): bool
    {
        $user = Database::fetchOne(
            'SELECT id, username, password_hash, role, display_name FROM ors_user WHERE username = ?',
            [$username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Update last login
        Database::update('ors_user', ['last_login_at' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

        // Store user in session
        unset($user['password_hash']);
        $_SESSION[self::SESSION_USER_KEY] = $user;

        // Handle remember me
        if ($remember) {
            self::setRememberToken($user['id']);
        }

        return true;
    }

    /**
     * Set remember token
     */
    private static function setRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + ORS_SESSION_LIFETIME);

        Database::update('ors_user', [
            'remember_token' => $token,
            'token_expires_at' => $expires
        ], 'id = ?', [$userId]);

        setcookie(self::COOKIE_REMEMBER, $token, [
            'expires' => time() + ORS_SESSION_LIFETIME,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    /**
     * Try to login from remember token
     */
    public static function loginFromRemember(): bool
    {
        if (!isset($_COOKIE[self::COOKIE_REMEMBER])) {
            return false;
        }

        $token = $_COOKIE[self::COOKIE_REMEMBER];
        $user = Database::fetchOne(
            'SELECT id, username, role, display_name FROM ors_user
             WHERE remember_token = ? AND token_expires_at > NOW()',
            [$token]
        );

        if (!$user) {
            self::clearRememberCookie();
            return false;
        }

        $_SESSION[self::SESSION_USER_KEY] = $user;
        return true;
    }

    /**
     * Clear remember cookie
     */
    private static function clearRememberCookie(): void
    {
        setcookie(self::COOKIE_REMEMBER, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    /**
     * Logout user
     */
    public static function logout(): void
    {
        $user = self::getCurrentUser();
        if ($user) {
            Database::update('ors_user', [
                'remember_token' => null,
                'token_expires_at' => null
            ], 'id = ?', [$user['id']]);
        }

        unset($_SESSION[self::SESSION_USER_KEY]);
        self::clearRememberCookie();

        session_destroy();
    }

    /**
     * Get current logged in user
     */
    public static function getCurrentUser(): ?array
    {
        if (isset($_SESSION[self::SESSION_USER_KEY])) {
            return $_SESSION[self::SESSION_USER_KEY];
        }

        // Try remember me
        if (self::loginFromRemember()) {
            return $_SESSION[self::SESSION_USER_KEY];
        }

        return null;
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        return self::getCurrentUser() !== null;
    }

    /**
     * Check if user has specific role
     */
    public static function hasRole(string $role): bool
    {
        $user = self::getCurrentUser();
        return $user && $user['role'] === $role;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }

    /**
     * Require authentication (redirect if not logged in)
     */
    public static function requireAuth(string $redirectUrl = '/ors/'): void
    {
        if (!self::isLoggedIn()) {
            if (self::isAjaxRequest()) {
                Response::json(['success' => false, 'error' => 'Unauthorized'], 401);
            }
            header('Location: ' . $redirectUrl . '?action=login');
            exit;
        }
    }

    /**
     * Require admin role
     */
    public static function requireAdmin(string $redirectUrl = '/ors/ap'): void
    {
        self::requireAuth($redirectUrl);

        if (!self::isAdmin()) {
            if (self::isAjaxRequest()) {
                Response::json(['success' => false, 'error' => 'Forbidden'], 403);
            }
            header('Location: ' . $redirectUrl . '?action=forbidden');
            exit;
        }
    }

    /**
     * Check if request is AJAX
     */
    private static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Hash password
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
