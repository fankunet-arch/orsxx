<?php
/**
 * Phases API
 * Handles project phases
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use ORS\Auth;
use ORS\Response;
use ORS\Phase;

// Require authentication
Auth::requireAuth();

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        handleList();
        break;

    default:
        Response::notFound('Action not found');
}

function handleList(): void
{
    $phases = Phase::getAllOrdered();
    Response::success(['phases' => $phases]);
}
