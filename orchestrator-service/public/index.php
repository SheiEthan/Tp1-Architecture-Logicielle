<?php

// Orchestrator Service - Requêtes transverses et agrégations
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

error_log("Orchestrator - Request URI: " . $requestUri);
error_log("Orchestrator - Path: " . $path);
error_log("Orchestrator - Method: " . $method);

function callService($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['response' => $response, 'status' => $httpCode];
}

try {
    if (preg_match('#^/api/users/(\d+)/profile/?$#', $path, $matches) && $method === 'GET') {
        // REQUÊTE TRANSVERSE : Profil utilisateur avec ses comptes bancaires
        $userId = (int)$matches[1];
        
        // Récupérer les données utilisateur
        $userResult = callService("http://user-service:80/api/users/{$userId}");
        if ($userResult['status'] !== 200) {
            http_response_code(404);
            echo json_encode(['error' => 'Utilisateur non trouvé']);
            exit;
        }
        
        // Récupérer les comptes bancaires de l'utilisateur
        $accountsResult = callService("http://account-service:80/api/accounts/user/{$userId}");
        
        $user = json_decode($userResult['response'], true);
        $accounts = ($accountsResult['status'] === 200) ? 
                   json_decode($accountsResult['response'], true) : [];
        
        // Agrégation des données
        $profile = [
            'user_id' => $user['id'],
            'nom_complet' => $user['first_name'] . ' ' . $user['last_name'],
            'email' => $user['email'],
            'telephone' => $user['phone'],
            'comptes_bancaires' => $accounts,
            'nombre_comptes' => count($accounts),
            'solde_total' => array_sum(array_column($accounts, 'solde'))
        ];
        
        echo json_encode($profile);
        
    } elseif ($path === '/api/users/profiles' && $method === 'GET') {
        // REQUÊTE TRANSVERSE : Tous les profils utilisateurs avec comptes
        $usersResult = callService("http://user-service:80/api/users");
        $accountsResult = callService("http://account-service:80/api/accounts");
        
        if ($usersResult['status'] !== 200) {
            http_response_code(500);
            echo json_encode(['error' => 'Impossible de récupérer les utilisateurs']);
            exit;
        }
        
        $users = json_decode($usersResult['response'], true);
        $allAccounts = ($accountsResult['status'] === 200) ? 
                      json_decode($accountsResult['response'], true) : [];
        
        // Grouper les comptes par utilisateur
        $accountsByUser = [];
        foreach ($allAccounts as $account) {
            $accountsByUser[$account['user_id']][] = $account;
        }
        
        $profiles = [];
        foreach ($users as $user) {
            $userAccounts = $accountsByUser[$user['id']] ?? [];
            $profiles[] = [
                'user_id' => $user['id'],
                'nom_complet' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'comptes_bancaires' => $userAccounts,
                'nombre_comptes' => count($userAccounts),
                'solde_total' => array_sum(array_column($userAccounts, 'solde'))
            ];
        }
        
        echo json_encode($profiles);
        
    } elseif ($path === '/api/health' || $path === '/api/health/') {
        // Health check avec vérification des services
        $userHealth = callService("http://user-service:80/api/health");
        $accountHealth = callService("http://account-service:80/api/health");
        
        echo json_encode([
            'service' => 'orchestrator-service',
            'status' => 'UP',
            'services_status' => [
                'user-service' => ($userHealth['status'] === 200) ? 'UP' : 'DOWN',
                'account-service' => ($accountHealth['status'] === 200) ? 'UP' : 'DOWN'
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } elseif ($path === '/' || $path === '') {
        // Documentation de l'orchestrateur
        echo json_encode([
            'service' => 'Orchestrator Service - Requêtes Transverses',
            'status' => 'UP',
            'endpoints' => [
                'GET /api/users/{id}/profile' => 'Profil utilisateur avec comptes bancaires',
                'GET /api/users/profiles' => 'Tous les profils utilisateurs',
                'GET /api/health' => 'Health check orchestrateur'
            ],
            'description' => 'Service composite pour requêtes agrégées entre User et Account services',
            'debug' => [
                'request_uri' => $requestUri,
                'path' => $path,
                'method' => $method
            ]
        ]);
        
    } else {
        http_response_code(404);
        echo json_encode([
            'error' => 'Route not found in orchestrator',
            'available_routes' => [
                'GET /api/users/{id}/profile' => 'Profil utilisateur complet',
                'GET /api/users/profiles' => 'Tous les profils',
                'GET /api/health' => 'Health check'
            ]
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}