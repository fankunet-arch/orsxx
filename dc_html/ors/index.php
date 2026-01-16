<?php
/**
 * ORS Mobile Capture Entry Point
 * URL: /ors/
 *
 * This is the mobile-first interface for quick task/purchase capture.
 * Features: Quick Task, Quick Purchase, Today's Records, Search
 */

// Load bootstrap
require_once dirname(dirname(__DIR__)) . '/app/ors/bootstrap.php';

use ORS\Auth;

// Get action from URL
$action = $_GET['action'] ?? 'home';

// Login handling
if ($action === 'login') {
    include __DIR__ . '/views/login.php';
    exit;
}

if ($action === 'doLogin') {
    // Redirect to API
    header('Location: /app/ors/api/auth.php?action=login');
    exit;
}

if ($action === 'logout') {
    Auth::logout();
    header('Location: /ors/?action=login');
    exit;
}

// Require authentication for all other actions
if (!Auth::isLoggedIn()) {
    header('Location: /ors/?action=login');
    exit;
}

// Route to appropriate view
switch ($action) {
    case 'home':
        include __DIR__ . '/views/home.php';
        break;

    case 'quickTask':
        include __DIR__ . '/views/quick_task.php';
        break;

    case 'quickPurchase':
        include __DIR__ . '/views/quick_purchase.php';
        break;

    case 'today':
        include __DIR__ . '/views/today.php';
        break;

    case 'search':
        include __DIR__ . '/views/search.php';
        break;

    default:
        include __DIR__ . '/views/home.php';
        break;
}
