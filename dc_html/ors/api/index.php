<?php
/**
 * API Router Bridge
 * Routes API requests to the actual API files in app/ors/api/
 */

$apiName = $_GET['api'] ?? null;

if (!$apiName) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'API endpoint not specified']);
    exit;
}

// Sanitize API name
$apiName = preg_replace('/[^a-zA-Z0-9_]/', '', $apiName);

$apiPath = dirname(dirname(dirname(__DIR__))) . '/app/ors/api/' . $apiName . '.php';

if (!file_exists($apiPath)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'API endpoint not found']);
    exit;
}

require_once $apiPath;
