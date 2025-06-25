<?php
/**
 * Endpoint per fornire il token CSRF
 */
require_once 'database.php';

// Avvia la sessione
SessionManager::start();

// Imposta header per JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

echo json_encode(['csrf_token' => Utils::generateCSRFToken()]);

