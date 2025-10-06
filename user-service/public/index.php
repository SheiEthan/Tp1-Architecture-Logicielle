<?php

// User Service avec architecture CQRS
require_once __DIR__ . '/../src/Infrastructure/Container.php';
require_once __DIR__ . '/../src/Domain/UserApi.php';
require_once __DIR__ . '/../src/Application/Ports/IUserApiRepository.php';
require_once __DIR__ . '/../src/Infrastructure/Repositories/FileUserApiRepository.php';
require_once __DIR__ . '/../src/Application/DTO/UserApiDTO.php';
require_once __DIR__ . '/../src/Application/Mapping/UserApiMapper.php';
require_once __DIR__ . '/../src/Application/Command/CreateUserApiCommand.php';
require_once __DIR__ . '/../src/Application/Command/DeleteUserApiCommand.php';
require_once __DIR__ . '/../src/Application/Query/GetUserApiQuery.php';
require_once __DIR__ . '/../src/Application/Query/ListUserApiQuery.php';

use Infrastructure\Repositories\FileUserApiRepository;
use Application\Command\CreateUserApiCommand;
use Application\Command\DeleteUserApiCommand;
use Application\Query\GetUserApiQuery;
use Application\Query\ListUserApiQuery;
use Application\DTO\UserApiDTO;
use Application\Mapping\UserApiMapper;

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

try {
    if ($path === '/api/users' || $path === '/api/users/') {
        if ($method === 'GET') {
            // Query: List Users
            $query = Container::get(ListUserApiQuery::class);
            $users = $query->handle();
            echo json_encode(array_map([UserApiMapper::class, 'toArray'], $users));
            
        } elseif ($method === 'POST') {
            // Command: Create User + Account (Saga pattern)
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['first_name']) || !isset($input['last_name']) || !isset($input['email'])) {
                http_response_code(400);
                echo json_encode(['error' => 'first_name, last_name et email sont requis']);
                exit;
            }

            $dto = new UserApiDTO(
                null,
                $input['first_name'],
                $input['last_name'],
                $input['email'],
                $input['phone'] ?? null,
                null
            );

            $command = Container::get(CreateUserApiCommand::class);
            $userDto = $command->handle($dto);

            // Saga: Create associated account
            $accountData = [
                'user_id' => $userDto->id,
                'type_compte' => 'courant'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://account-service:80/api/accounts");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($accountData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $accountResponse = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Check for errors in account creation
            if (!empty($curlError) || $accountResponse === false || $httpCode === 0 || $httpCode !== 201) {
                // Rollback: delete created user
                $deleteCommand = Container::get(DeleteUserApiCommand::class);
                $deleteCommand->handle($userDto->id);

                http_response_code(500);
                echo json_encode([
                    'error' => 'Transaction échouée : impossible de créer le compte bancaire',
                    'details' => 'L\'utilisateur n\'a pas été créé pour maintenir la cohérence des données',
                    'user_creation' => 'ROLLED_BACK',
                    'account_creation' => 'FAILED'
                ]);
                exit;
            }

            $response = ['user' => UserApiMapper::toArray($userDto)];
            $account = json_decode($accountResponse, true);
            $response['account_created'] = $account;
            $response['transaction_status'] = 'SUCCESS';

            http_response_code(201);
            echo json_encode($response);
        }

    } elseif (preg_match('#^/api/users/(\d+)/?$#', $path, $matches)) {
        $id = (int)$matches[1];
        
        if ($method === 'GET') {
            // Query: Get User
            $query = Container::get(GetUserApiQuery::class);
            $user = $query->handle($id);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                exit;
            }
            
            echo json_encode(UserApiMapper::toArray($user));
            
        } elseif ($method === 'DELETE') {
            // Command: Delete User + Account (Saga pattern)
            $query = Container::get(GetUserApiQuery::class);
            $user = $query->handle($id);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                exit;
            }

            // Saga: Get and delete user's accounts first
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://account-service:80/api/accounts/user/$id");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $accountsResponse = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $deletedAccounts = [];
            
            if (!empty($curlError) || $accountsResponse === false || $httpCode === 0) {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Impossible de vérifier les comptes associés',
                    'transaction_status' => 'FAILED'
                ]);
                exit;
            }

            if ($httpCode === 200) {
                $accounts = json_decode($accountsResponse, true);
                if (is_array($accounts)) {
                    foreach ($accounts as $account) {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, "http://account-service:80/api/accounts/" . $account['id']);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                        $deleteResponse = curl_exec($ch);
                        $deleteHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        if ($deleteHttpCode === 200) {
                            $deletedAccounts[] = $account['id'];
                        }
                    }
                }
            }

            // Delete user
            $deleteCommand = Container::get(DeleteUserApiCommand::class);
            $deleted = $deleteCommand->handle($id);

            if ($deleted) {
                echo json_encode([
                    'message' => 'User and all associated accounts deleted successfully',
                    'user_id' => $id,
                    'accounts_deleted' => $deletedAccounts,
                    'transaction_status' => 'SUCCESS'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete user']);
            }
        }

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
    error_log("Error in user-service: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}