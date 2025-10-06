<?php

// Account Service simple mais fonctionnel
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Capturer toutes les requêtes et les router manuellement
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Debugging
error_log("Account Service - Request URI: " . $requestUri);
error_log("Account Service - Path: " . $path);
error_log("Account Service - Method: " . $method);

// Simulation d'une base de données avec fichiers persistants
$dataFile = '/var/www/html/data/accounts.json';
$dataDir = dirname($dataFile);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

function loadAccounts() {
    global $dataFile;
    if (file_exists($dataFile)) {
        $content = file_get_contents($dataFile);
        return json_decode($content, true) ?: [];
    }
    return [
        1 => ['id' => 1, 'user_id' => 1, 'numero_compte' => 'CPT001', 'solde' => 1000.50, 'type_compte' => 'courant'],
        2 => ['id' => 2, 'user_id' => 2, 'numero_compte' => 'CPT002', 'solde' => 2500.00, 'type_compte' => 'epargne']
    ];
}

function saveAccounts($accounts) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($accounts, JSON_PRETTY_PRINT));
}

$accounts = loadAccounts();

try {
    if (($path === '/api/accounts' || $path === '/api/accounts/') && $method === 'GET') {
        // Liste de tous les comptes
        echo json_encode(array_values($accounts));

    } elseif (($path === '/api/accounts' || $path === '/api/accounts/') && $method === 'POST') {
        // Créer un compte
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['user_id']) || !isset($input['solde'])) {
            http_response_code(400);
            echo json_encode(['error' => 'user_id et solde sont requis']);
            exit;
        }

        $id = empty($accounts) ? 1 : max(array_keys($accounts)) + 1;
        $account = [
            'id' => $id,
            'user_id' => (int)$input['user_id'],
            'numero_compte' => 'CPT' . str_pad($id, 3, '0', STR_PAD_LEFT),
            'solde' => (float)$input['solde'],
            'type_compte' => $input['type_compte'] ?? 'courant'
        ];
        $accounts[$id] = $account;
        saveAccounts($accounts);

        http_response_code(201);
        echo json_encode($account);

    } elseif (preg_match('#^/api/accounts/(\d+)/?$#', $path, $matches) && $method === 'GET') {
        // Afficher un compte
        $id = (int)$matches[1];
        if (!isset($accounts[$id])) {
            http_response_code(404);
            echo json_encode(['error' => 'Account not found']);
            exit;
        }
        echo json_encode($accounts[$id]);

    } elseif (preg_match('#^/api/accounts/user/(\d+)/?$#', $path, $matches) && $method === 'GET') {
        // Comptes d'un utilisateur
        $userId = (int)$matches[1];
        $userAccounts = array_filter($accounts, function($account) use ($userId) {
            return $account['user_id'] === $userId;
        });
        echo json_encode(array_values($userAccounts));

    } elseif (preg_match('#^/api/accounts/(\d+)/?$#', $path, $matches) && $method === 'DELETE') {
        // Supprimer un compte
        $id = (int)$matches[1];
        if (!isset($accounts[$id])) {
            http_response_code(404);
            echo json_encode(['error' => 'Account not found']);
            exit;
        }
        unset($accounts[$id]);
        saveAccounts($accounts);
        http_response_code(204);

    } elseif ($path === '/api/health' || $path === '/api/health/') {
        echo json_encode(['status' => 'UP', 'service' => 'account-service', 'timestamp' => date('Y-m-d H:i:s')]);

    } elseif ($path === '/' || $path === '') {
        // Page d'accueil du service
        echo json_encode([
            'service' => 'Account Service',
            'status' => 'UP',
            'endpoints' => [
                'GET /api/accounts' => 'Liste des comptes',
                'POST /api/accounts' => 'Créer un compte',
                'GET /api/accounts/{id}' => 'Afficher un compte',
                'GET /api/accounts/user/{userId}' => 'Comptes d\'un utilisateur',
                'DELETE /api/accounts/{id}' => 'Supprimer un compte',
                'GET /api/health' => 'Health check'
            ],
            'debug' => [
                'request_uri' => $requestUri,
                'path' => $path,
                'method' => $method
            ]
        ]);

    } else {
        http_response_code(404);
        echo json_encode([
            'error' => 'Route not found',
            'debug' => [
                'request_uri' => $requestUri,
                'path' => $path,
                'method' => $method
            ],
            'available_routes' => [
                'GET /api/accounts' => 'Liste des comptes',
                'POST /api/accounts' => 'Créer un compte',
                'GET /api/accounts/{id}' => 'Afficher un compte',
                'GET /api/accounts/user/{userId}' => 'Comptes d\'un utilisateur',
                'DELETE /api/accounts/{id}' => 'Supprimer un compte'
            ]
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}