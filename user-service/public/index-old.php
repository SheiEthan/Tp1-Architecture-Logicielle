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
    return file_put_contents($dataFile, json_encode($users, JSON_PRETTY_PRINT)) !== false;
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
        if (!$input ||
            !isset($input['first_name']) || empty($input['first_name']) ||
            !isset($input['last_name']) || empty($input['last_name']) ||
            !isset($input['email']) || empty($input['email'])) {
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

    } elseif ($path === '/api/test-connection' || $path === '/api/test-connection/') {
        // Test endpoint pour diagnostiquer la connexion au service de comptes
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://account-service:80/api/accounts");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo json_encode([
            'test' => 'Connection to account-service',
            'response' => $response,
            'curl_error' => $curlError,
            'http_code' => $httpCode,
            'response_type' => gettype($response),
            'response_is_false' => $response === false,
            'response_empty' => empty($response),
            'curl_error_empty' => empty($curlError)
        ]);

    } elseif (preg_match('#^/api/users/(\d+)/?$#', $path, $matches) && $method === 'DELETE') {
        // Supprimer un utilisateur et ses comptes associés avec pattern Saga
        $id = (int)$matches[1];
        if (!isset($users[$id])) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        // SAGA PATTERN - PHASE 1: Récupérer les comptes associés
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://account-service:80/api/accounts/user/{$id}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);

        $accountsResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // DEBUG: Log pour comprendre ce qui se passe
        error_log("DEBUG DELETE - Response: " . var_export($accountsResponse, true));
        error_log("DEBUG DELETE - Curl Error: " . var_export($curlError, true));
        error_log("DEBUG DELETE - HTTP Code: " . $httpCode);

        // Vérifier si le service de comptes est accessible
        if (!empty($curlError) || $accountsResponse === false || $httpCode === 0) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Transaction échouée : impossible de vérifier les comptes associés',
                'details' => 'Le service de comptes est indisponible, suppression annulée pour maintenir la cohérence',
                'user_deletion' => 'CANCELLED',
                'accounts_deletion' => 'FAILED',
                'curl_error' => $curlError ?: 'Service unavailable',
                'http_code' => $httpCode,
                'response' => $accountsResponse,
                'debug_test_result' => 'Service connection failed - Saga rollback triggered'
            ]);
            exit;
        }

        $accounts = json_decode($accountsResponse, true);
        if (!is_array($accounts)) {
            $accounts = [];
        }

        // SAGA PATTERN - PHASE 2: Supprimer tous les comptes associés
        $deletedAccounts = [];
        $failedDeletions = [];

        foreach ($accounts as $account) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://account-service:80/api/accounts/{$account['id']}");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $deleteResponse = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (!$curlError && $httpCode === 204) {
                $deletedAccounts[] = $account['id'];
            } else {
                $failedDeletions[] = [
                    'account_id' => $account['id'],
                    'error' => $curlError,
                    'http_code' => $httpCode
                ];
            }
        }

        // SAGA PATTERN - VÉRIFICATION: Si tous les comptes n'ont pas pu être supprimés, annuler
        if (!empty($failedDeletions)) {
            // COMPENSATION: Recréer les comptes qui ont été supprimés (rollback)
            foreach ($deletedAccounts as $deletedAccountId) {
                // Trouver les données du compte supprimé pour le recréer
                foreach ($accounts as $account) {
                    if ($account['id'] == $deletedAccountId) {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, "http://account-service:80/api/accounts");
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                            'user_id' => $account['user_id'],
                            'solde' => $account['solde'],
                            'type_compte' => $account['type_compte'],
                            'numero_compte' => $account['numero_compte']
                        ]));
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                        curl_exec($ch);
                        curl_close($ch);
                        break;
                    }
                }
            }

            http_response_code(500);
            echo json_encode([
                'error' => 'Transaction échouée : impossible de supprimer tous les comptes',
                'details' => 'Certains comptes n\'ont pas pu être supprimés, utilisateur conservé pour maintenir la cohérence',
                'user_deletion' => 'CANCELLED',
                'accounts_deletion' => 'PARTIAL_FAILURE',
                'deleted_accounts' => $deletedAccounts,
                'failed_deletions' => $failedDeletions,
                'rollback_status' => 'COMPENSATED'
            ]);
            exit;
        }

        // SAGA PATTERN - PHASE 3: Supprimer l'utilisateur (seulement si tous les comptes ont été supprimés)
        $userBackup = $users[$id]; // Sauvegarde pour rollback potentiel
        unset($users[$id]);

        if (!saveUsers($users)) {
            // COMPENSATION: Restaurer l'utilisateur si la sauvegarde échoue
            $users[$id] = $userBackup;

            http_response_code(500);
            echo json_encode([
                'error' => 'Transaction échouée : impossible de sauvegarder la suppression utilisateur',
                'details' => 'Les comptes ont été supprimés mais l\'utilisateur n\'a pas pu être supprimé',
                'user_deletion' => 'FAILED',
                'accounts_deletion' => 'SUCCESS',
                'rollback_status' => 'USER_RESTORED'
            ]);
            exit;
        }

        // SUCCÈS COMPLET
        http_response_code(200);
        echo json_encode([
            'message' => 'User and all associated accounts deleted successfully',
            'user_id' => $id,
            'accounts_deleted' => $deletedAccounts,
            'transaction_status' => 'SUCCESS'
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
