<?php

// API Gateway simple - Version démo
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Router simple
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($path === '/' || $path === '/api/health') {
        echo json_encode([
            'service' => 'API Gateway',
            'status' => 'UP',
            'timestamp' => date('Y-m-d H:i:s'),
            'microservices' => [
                'user-service' => 'http://localhost:8001',
                'account-service' => 'http://localhost:8002',
                'orchestrator-service' => 'http://localhost:8003'
            ],
            'endpoints' => [
                'GET /' => 'Service info',
                'GET /api/health' => 'Health check',
                'GET /api/demo' => 'Demo des microservices'
            ]
        ]);
    } elseif ($path === '/api/demo') {
        // Simulation d'appels aux microservices
        echo json_encode([
            'demo' => 'Architecture Microservices TP3',
            'services_running' => [
                'API Gateway' => 'Port 8000 - Point d\'entrée principal',
                'User Service' => 'Port 8001 - Gestion des utilisateurs',
                'Account Service' => 'Port 8002 - Gestion des comptes bancaires',
                'Orchestrator' => 'Port 8003 - Transactions atomiques'
            ],
            'architecture' => [
                'pattern' => 'Microservices avec Docker',
                'communication' => 'HTTP/REST entre services',
                'databases' => 'MySQL séparé par service',
                'orchestration' => 'Saga pattern pour cohérence'
            ],
            'next_steps' => [
                'Tester chaque service individuellement',
                'Implémenter la communication HTTP complète',
                'Ajouter les migrations de données',
                'Configurer les health checks'
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Route not found', 'path' => $path]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}