<?php

// Bootstrap pour service d'orchestration
require_once __DIR__ . '/../bootstrap.php';

use Presentation\UserAccountController;

// Router pour orchestrateur
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Headers JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($requestMethod === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $controller = new UserAccountController();
    
    // Routes d'orchestration atomique
    if (preg_match('#^/api/user-accounts/?$#', $path)) {
        if ($requestMethod === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $_POST = $input ?: [];
            echo $controller->store(new Request($_POST))->getContent();
        }
    } elseif (preg_match('#^/api/user-accounts/(\d+)/?$#', $path, $matches)) {
        $userId = $matches[1];
        if ($requestMethod === 'DELETE') {
            echo $controller->destroy($userId)->getContent();
        }
    } elseif ($path === '/api/health') {
        echo json_encode(['status' => 'UP', 'service' => 'orchestrator-service']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}