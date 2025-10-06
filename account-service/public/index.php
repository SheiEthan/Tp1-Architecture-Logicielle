<?php

// Account Service avec architecture CQRS
require_once __DIR__ . '/../src/Infrastructure/Container.php';
require_once __DIR__ . '/../src/Domain/CompteBancaire.php';
require_once __DIR__ . '/../src/Ports/ICompteBancaireRepository.php';
require_once __DIR__ . '/../src/Infrastructure/Repositories/FileCompteBancaireRepository.php';
require_once __DIR__ . '/../src/DTO/CompteBancaireDTO.php';
require_once __DIR__ . '/../src/Mapping/CompteBancaireMapper.php';
require_once __DIR__ . '/../src/Command/CreateCompteBancaireCommand.php';
require_once __DIR__ . '/../src/Command/DeleteCompteBancaireCommand.php';
require_once __DIR__ . '/../src/Command/DeleteCompteBancaireByUserCommand.php';
require_once __DIR__ . '/../src/Command/UpdateCompteBancaireCommand.php';
require_once __DIR__ . '/../src/Query/GetCompteBancaireQuery.php';
require_once __DIR__ . '/../src/Query/GetCompteBancaireByUserQuery.php';
require_once __DIR__ . '/../src/Query/ListCompteBancaireQuery.php';

use Infrastructure\Repositories\FileCompteBancaireRepository;
use Application\Command\CreateCompteBancaireCommand;
use Application\Command\DeleteCompteBancaireCommand;
use Application\Command\DeleteCompteBancaireByUserCommand;
use Application\Command\UpdateCompteBancaireCommand;
use Application\Query\GetCompteBancaireQuery;
use Application\Query\GetCompteBancaireByUserQuery;
use Application\Query\ListCompteBancaireQuery;
use Application\DTO\CompteBancaireDTO;
use Application\Mapping\CompteBancaireMapper;

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
    if ($path === '/api/accounts' || $path === '/api/accounts/') {
        if ($method === 'GET') {
            // Query: List Accounts
            $query = Container::get(ListCompteBancaireQuery::class);
            $comptes = $query->handle();
            echo json_encode(array_map([CompteBancaireMapper::class, 'toArray'], $comptes));
            
        } elseif ($method === 'POST') {
            // Command: Create Account
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['user_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'user_id est requis']);
                exit;
            }

            // Generate account number
            $query = Container::get(ListCompteBancaireQuery::class);
            $existingAccounts = $query->handle();
            $accountNumber = 'CPT' . str_pad(count($existingAccounts) + 1, 3, '0', STR_PAD_LEFT);

            $dto = new CompteBancaireDTO(
                null,
                $accountNumber,
                '', // iban
                '', // bic
                0.0, // solde initial
                $input['user_id'],
                $input['type_compte'] ?? 'courant'
            );

            $command = Container::get(CreateCompteBancaireCommand::class);
            $compteDto = $command->handle($dto);

            http_response_code(201);
            echo json_encode(CompteBancaireMapper::toArray($compteDto));
        }

    } elseif (preg_match('#^/api/accounts/(\d+)/?$#', $path, $matches)) {
        $id = (int)$matches[1];
        
        if ($method === 'GET') {
            // Query: Get Account
            $query = Container::get(GetCompteBancaireQuery::class);
            $compte = $query->handle($id);
            
            if (!$compte) {
                http_response_code(404);
                echo json_encode(['error' => 'Account not found']);
                exit;
            }
            
            echo json_encode(CompteBancaireMapper::toArray($compte));
            
        } elseif ($method === 'DELETE') {
            // Command: Delete Account
            $command = Container::get(DeleteCompteBancaireCommand::class);
            $deleted = $command->handle($id);
            
            if ($deleted) {
                echo json_encode(['message' => 'Account deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Account not found']);
            }
            
        } elseif ($method === 'PUT') {
            // Command: Update Account
            $input = json_decode(file_get_contents('php://input'), true);
            
            $query = Container::get(GetCompteBancaireQuery::class);
            $existingCompte = $query->handle($id);
            
            if (!$existingCompte) {
                http_response_code(404);
                echo json_encode(['error' => 'Account not found']);
                exit;
            }

            $dto = new CompteBancaireDTO(
                $id,
                $input['numero_compte'] ?? $existingCompte->getNumeroCompte(),
                $input['iban'] ?? $existingCompte->getIban(),
                $input['bic'] ?? $existingCompte->getBic(),
                $input['solde'] ?? $existingCompte->getSolde(),
                $input['user_id'] ?? $existingCompte->getUserId(),
                $input['statut'] ?? $existingCompte->getStatut()
            );

            $command = Container::get(UpdateCompteBancaireCommand::class);
            $updatedCompte = $command->handle($id, $dto);
            
            if ($updatedCompte) {
                echo json_encode(CompteBancaireMapper::toArray($updatedCompte));
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update account']);
            }
        }

    } elseif (preg_match('#^/api/accounts/user/(\d+)/?$#', $path, $matches)) {
        $userId = (int)$matches[1];
        
        if ($method === 'GET') {
            // Query: Get Accounts by User
            $query = Container::get(GetCompteBancaireByUserQuery::class);
            $comptes = $query->handle($userId);
            echo json_encode(array_map([CompteBancaireMapper::class, 'toArray'], $comptes));
            
        } elseif ($method === 'DELETE') {
            // Command: Delete All User Accounts
            $command = Container::get(DeleteCompteBancaireByUserCommand::class);
            $deleted = $command->handle($userId);
            
            if ($deleted) {
                echo json_encode(['message' => 'All user accounts deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'No accounts found for this user']);
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
                'GET /api/accounts' => 'Liste des comptes',
                'POST /api/accounts' => 'Créer un compte',
                'GET /api/accounts/{id}' => 'Afficher un compte',
                'PUT /api/accounts/{id}' => 'Modifier un compte',
                'DELETE /api/accounts/{id}' => 'Supprimer un compte',
                'GET /api/accounts/user/{userId}' => 'Comptes d\'un utilisateur',
                'DELETE /api/accounts/user/{userId}' => 'Supprimer tous les comptes d\'un utilisateur'
            ]
        ]);
    }

} catch (Exception $e) {
    error_log("Error in account-service: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}