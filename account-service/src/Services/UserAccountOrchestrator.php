<?php

namespace Application\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service orchestrateur pour les transactions atomiques entre UserApi et CompteBancaire
 */
class UserAccountOrchestrator
{
    private string $accountServiceUrl;

    public function __construct()
    {
        // URLs des microservices avec les ports exposés
        $this->userServiceUrl = 'http://localhost:8001';
        $this->accountServiceUrl = 'http://localhost:8002';
    }

    /**
     * Orchestration de la création : User + CompteBancaire atomique
     */
    public function createUserWithAccount(array $userData): array
    {
        Log::info('Démarrage orchestration création User + Compte', $userData);

        try {
            // Étape 1 : Créer l'utilisateur (via commande directe)
            $userResponse = $this->createUser($userData);

            if (!$userResponse['success']) {
                throw new \Exception('Échec création utilisateur: ' . $userResponse['error']);
            }

            $userId = $userResponse['data']['id'] ?? null;
            if (!$userId) {
                throw new \Exception('ID utilisateur non retourné');
            }

            // Étape 2 : Créer le compte bancaire
            $accountResponse = $this->createAccount($userId);

            if (!$accountResponse['success']) {
                // Rollback : supprimer l'utilisateur
                $this->deleteUser($userId);
                throw new \Exception('Échec création compte bancaire: ' . $accountResponse['error']);
            }

            Log::info('Orchestration création réussie', [
                'user_id' => $userId,
                'account_id' => $accountResponse['data']['id'] ?? null
            ]);

            return [
                'success' => true,
                'user' => $userResponse['data'],
                'account' => $accountResponse['data']
            ];

        } catch (\Exception $e) {
            Log::error('Échec orchestration création', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Orchestration de la suppression : User + CompteBancaire atomique
     */
    public function deleteUserWithAccounts(int $userId): array
    {
        Log::info('Démarrage orchestration suppression User + Comptes', ['user_id' => $userId]);

        try {
            // Étape 1 : Supprimer les comptes bancaires
            $accountResponse = $this->deleteAccountsByUser($userId);

            if (!$accountResponse['success']) {
                throw new \Exception('Échec suppression comptes bancaires: ' . $accountResponse['error']);
            }

            // Étape 2 : Supprimer l'utilisateur
            $userResponse = $this->deleteUser($userId);

            if (!$userResponse['success']) {
                // Rollback impossible car les comptes sont déjà supprimés
                // Log l'erreur mais continue
                Log::error('Échec suppression utilisateur après suppression comptes', [
                    'user_id' => $userId,
                    'error' => $userResponse['error']
                ]);

                throw new \Exception('Échec suppression utilisateur: ' . $userResponse['error']);
            }

            Log::info('Orchestration suppression réussie', ['user_id' => $userId]);

            return [
                'success' => true,
                'message' => 'Utilisateur et comptes supprimés avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Échec orchestration suppression', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function createUser(array $userData): array
    {
        try {
            $response = Http::post($this->accountServiceUrl . '/api/users', $userData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'HTTP ' . $response->status() . ': ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function createAccount(int $userId): array
    {
        try {
            $response = Http::post($this->accountServiceUrl . '/api/accounts', [
                'user_id' => $userId
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'HTTP ' . $response->status() . ': ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function deleteUser(int $userId): array
    {
        try {
            $response = Http::delete($this->accountServiceUrl . '/api/users/' . $userId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'HTTP ' . $response->status() . ': ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function deleteAccountsByUser(int $userId): array
    {
        try {
            $response = Http::delete($this->accountServiceUrl . '/api/accounts/user/' . $userId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'HTTP ' . $response->status() . ': ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
