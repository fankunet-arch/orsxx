<?php
/**
 * Authentication API
 * Handles login/logout operations
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use ORS\Auth;
use ORS\Response;
use ORS\Validator;

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'status';

switch ($action) {
    case 'login':
        if ($method !== 'POST') Response::error('Method not allowed', 405);
        handleLogin();
        break;

    case 'logout':
        handleLogout();
        break;

    case 'status':
        handleStatus();
        break;

    default:
        Response::notFound('Action not found');
}

function handleLogin(): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $validator = Validator::make($input)
        ->required('username', 'Username is required')
        ->required('password', 'Password is required');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $username = $validator->getString('username');
    $password = $validator->getString('password');
    $remember = $validator->getBool('remember', false);

    if (!Auth::attempt($username, $password, $remember)) {
        Response::error('Invalid username or password', 401);
    }

    $user = Auth::getCurrentUser();
    Response::success([
        'user' => $user,
        'redirect' => $user['role'] === 'admin' ? '/ors/ap/' : '/ors/'
    ], 'Login successful');
}

function handleLogout(): void
{
    Auth::logout();
    Response::success([], 'Logout successful');
}

function handleStatus(): void
{
    if (Auth::isLoggedIn()) {
        Response::success([
            'logged_in' => true,
            'user' => Auth::getCurrentUser()
        ]);
    } else {
        Response::success([
            'logged_in' => false,
            'user' => null
        ]);
    }
}
