<?php

// User Service simple mais fonctionnel
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

// Debugging pour voir ce qui arrive
error_log("Request URI: " . $requestUri);
error_log("Path: " . $path);
error_log("Method: " . $method);

// Simulation d'une base de données avec fichiers persistants
$dataFile = '/var/www/html/data/users.json';
$dataDir = dirname($dataFile);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

function loadUsers() {
    global $dataFile;
    if (file_exists($dataFile)) {
        $content = file_get_contents($dataFile);
        return json_decode($content, true) ?: [];
    }
    return [
        1 => ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com', 'phone' => '0123456789'],
        2 => ['id' => 2, 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com', 'phone' => '0987654321']
    ];
}

function saveUsers($users) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($users, JSON_PRETTY_PRINT));
}

$users = loadUsers();

try {
    // Router toutes les requêtes
    if (($path === '/api/users' || $path === '/api/users/') && $method === 'GET') {
        // Liste des utilisateurs
        echo json_encode(array_values($users));

    } elseif (($path === '/api/users' || $path === '/api/users/') && $method === 'POST') {
        // Créer un utilisateur avec compte par défaut
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !$input['first_name'] || !$input['last_name'] || !$input['email']) {
            http_response_code(400);
            echo json_encode(['error' => 'first_name, last_name et email sont requis']);
            exit;
        }

        $id = empty($users) ? 1 : max(array_keys($users)) + 1;
        $user = [
            'id' => $id,
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'phone' => $input['phone'] ?? null
        ];
        $users[$id] = $user;
        saveUsers($users);

        // Créer automatiquement un compte par défaut via le service des comptes
        $accountData = [
            'user_id' => $id,
            'solde' => 0.00,
            'type_compte' => 'courant'
        ];

        // Appel au service des comptes avec gestion transactionnelle
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://account-service:80/api/accounts');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($accountData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $accountResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // TRANSACTION ATOMIQUE : Si création compte échoue, annuler création utilisateur
        if ($httpCode !== 201 || !$accountResponse) {
            // Rollback : supprimer l'utilisateur créé
            unset($users[$id]);
            saveUsers($users);

            http_response_code(500);
            echo json_encode([
                'error' => 'Transaction échouée : impossible de créer le compte bancaire',
                'details' => 'L\'utilisateur n\'a pas été créé pour maintenir la cohérence des données',
                'user_creation' => 'ROLLED_BACK',
                'account_creation' => 'FAILED'
            ]);
            exit;
        }

        $response = ['user' => $user];
        $account = json_decode($accountResponse, true);
        $response['account_created'] = $account;
        $response['transaction_status'] = 'SUCCESS';

        http_response_code(201);
        echo json_encode($response);

    } elseif (preg_match('#^/api/users/(\d+)/?$#', $path, $matches) && $method === 'GET') {
        // Afficher un utilisateur
        $id = (int)$matches[1];
        if (!isset($users[$id])) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        echo json_encode($users[$id]);

    } elseif (preg_match('#^/api/users/(\d+)/?$#', $path, $matches) && $method === 'DELETE') {
        // Supprimer un utilisateur et ses comptes associés
        $id = (int)$matches[1];
        if (!isset($users[$id])) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        // Supprimer d'abord les comptes associés via le service des comptes
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://account-service:80/api/accounts/user/{$id}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $accountsResponse = curl_exec($ch);
        curl_close($ch);

        $deletedAccounts = [];
        if ($accountsResponse) {
            $accounts = json_decode($accountsResponse, true);
            if (is_array($accounts)) {
                foreach ($accounts as $account) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "http://account-service:80/api/accounts/{$account['id']}");
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

                    $deleteResponse = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($httpCode === 204) {
                        $deletedAccounts[] = $account['id'];
                    }
                }
            }
        }

        // Supprimer l'utilisateur
        unset($users[$id]);
        saveUsers($users);

        http_response_code(200);
        echo json_encode([
            'message' => 'User deleted successfully',
            'user_id' => $id,
            'accounts_deleted' => $deletedAccounts
        ]);

    } elseif ($path === '/api/health' || $path === '/api/health/') {
        echo json_encode(['status' => 'UP', 'service' => 'user-service', 'timestamp' => date('Y-m-d H:i:s')]);

    } elseif ($path === '/' || $path === '') {
        // Page d'accueil du service
        echo json_encode([
            'service' => 'User Service',
            'status' => 'UP',
            'endpoints' => [
                'GET /api/users' => 'Liste des utilisateurs',
                'POST /api/users' => 'Créer un utilisateur',
                'GET /api/users/{id}' => 'Afficher un utilisateur',
                'DELETE /api/users/{id}' => 'Supprimer un utilisateur',
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
                'GET /api/users' => 'Liste des utilisateurs',
                'POST /api/users' => 'Créer un utilisateur',
                'GET /api/users/{id}' => 'Afficher un utilisateur',
                'DELETE /api/users/{id}' => 'Supprimer un utilisateur'
            ]
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
