<?php
/**
 * ORS Control Panel (Organize) Entry Point
 * URL: /ors/ap/
 *
 * This is the desktop-first interface for organizing and managing.
 * Features: Dashboard, Kanban, Projects, Templates, Vendors, Reports
 */

// Load bootstrap
require_once dirname(dirname(dirname(__DIR__))) . '/app/ors/bootstrap.php';

use ORS\Auth;

// Get action from URL
$action = $_GET['action'] ?? 'dashboard';

// Login handling
if ($action === 'login') {
    include __DIR__ . '/views/login.php';
    exit;
}

if ($action === 'logout') {
    Auth::logout();
    header('Location: /ors/ap/?action=login');
    exit;
}

// Forbidden page for non-admin users
if ($action === 'forbidden') {
    include __DIR__ . '/views/forbidden.php';
    exit;
}

// Require authentication for all other actions
if (!Auth::isLoggedIn()) {
    header('Location: /ors/ap/?action=login');
    exit;
}

// Require admin role for control panel
if (!Auth::isAdmin()) {
    header('Location: /ors/ap/?action=forbidden');
    exit;
}

// Include layout header
include __DIR__ . '/views/layout_header.php';

// Route to appropriate view
switch ($action) {
    case 'dashboard':
        include __DIR__ . '/views/dashboard.php';
        break;

    case 'tasks':
        include __DIR__ . '/views/tasks.php';
        break;

    case 'kanban':
        include __DIR__ . '/views/kanban.php';
        break;

    case 'purchases':
        include __DIR__ . '/views/purchases.php';
        break;

    case 'items':
        include __DIR__ . '/views/items.php';
        break;

    case 'vendors':
        include __DIR__ . '/views/vendors.php';
        break;

    case 'templates':
        include __DIR__ . '/views/templates.php';
        break;

    case 'projects':
        include __DIR__ . '/views/projects.php';
        break;

    case 'lessons':
        include __DIR__ . '/views/lessons.php';
        break;

    case 'checklist':
        include __DIR__ . '/views/checklist.php';
        break;

    default:
        include __DIR__ . '/views/dashboard.php';
        break;
}

// Include layout footer
include __DIR__ . '/views/layout_footer.php';
