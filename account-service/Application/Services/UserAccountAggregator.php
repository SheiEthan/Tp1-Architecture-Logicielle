<?php

namespace Application\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service composite pour les requêtes transverses User + CompteBancaire
 */
class UserAccountAggregator
{
    private string $userServiceUrl;
    private string $accountServiceUrl;

    public function __construct()
    {
        $this->userServiceUrl = config('app.url'); // Même serveur pour le moment
        $this->accountServiceUrl = config('app.url');
    }

    /**
     * Récupérer un utilisateur avec tous ses comptes bancaires
     */
    public function getUserWithAccounts(int $userId): array
    {
        Log::info('Récupération agrégée User + Comptes', ['user_id' => $userId]);

        try {
            // Requête parallèle vers les deux microservices
            $responses = Http::pool(fn ($pool) => [
                $pool->get($this->userServiceUrl . '/api/users/' . $userId),
                $pool->get($this->accountServiceUrl . '/api/accounts/user/' . $userId),
            ]);

            $userResponse = $responses[0];
            $accountsResponse = $responses[1];

            // Vérifier la réponse utilisateur
            if (!$userResponse->successful()) {
                if ($userResponse->status() === 404) {
                    return [
                        'success' => false,
                        'error' => 'Utilisateur non trouvé',
                        'code' => 404
                    ];
                }

                throw new \Exception('Erreur service utilisateur: ' . $userResponse->body());
            }

            // Récupérer les comptes (peut être vide)
            $accounts = [];
            if ($accountsResponse->successful()) {
                $accounts = $accountsResponse->json();
            } else {
                Log::warning('Échec récupération comptes pour utilisateur', [
                    'user_id' => $userId,
                    'status' => $accountsResponse->status(),
                    'error' => $accountsResponse->body()
                ]);
            }

            $user = $userResponse->json();

            Log::info('Agrégation réussie', [
                'user_id' => $userId,
                'accounts_count' => count($accounts)
            ]);

            return [
                'success' => true,
                'data' => [
                    'user' => $user,
                    'accounts' => $accounts,
                    'stats' => [
                        'total_accounts' => count($accounts),
                        'total_balance' => $this->calculateTotalBalance($accounts)
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Échec agrégation User + Comptes', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Récupérer tous les utilisateurs avec leurs comptes (pour l'admin)
     */
    public function getAllUsersWithAccounts(): array
    {
        Log::info('Récupération de tous les utilisateurs avec comptes');

        try {
            // Requête parallèle vers les deux microservices
            $responses = Http::pool(fn ($pool) => [
                $pool->get($this->userServiceUrl . '/api/users'),
                $pool->get($this->accountServiceUrl . '/api/accounts'),
            ]);

            $usersResponse = $responses[0];
            $accountsResponse = $responses[1];

            if (!$usersResponse->successful()) {
                throw new \Exception('Erreur service utilisateur: ' . $usersResponse->body());
            }

            $users = $usersResponse->json();
            $accounts = $accountsResponse->successful() ? $accountsResponse->json() : [];

            // Grouper les comptes par utilisateur
            $accountsByUser = [];
            foreach ($accounts as $account) {
                $userId = $account['userId'] ?? $account['user_id'] ?? null;
                if ($userId) {
                    if (!isset($accountsByUser[$userId])) {
                        $accountsByUser[$userId] = [];
                    }
                    $accountsByUser[$userId][] = $account;
                }
            }

            // Associer chaque utilisateur à ses comptes
            $result = [];
            foreach ($users as $user) {
                $userId = $user['id'] ?? null;
                $userAccounts = $accountsByUser[$userId] ?? [];

                $result[] = [
                    'user' => $user,
                    'accounts' => $userAccounts,
                    'stats' => [
                        'total_accounts' => count($userAccounts),
                        'total_balance' => $this->calculateTotalBalance($userAccounts)
                    ]
                ];
            }

            Log::info('Agrégation globale réussie', [
                'users_count' => count($users),
                'accounts_count' => count($accounts)
            ]);

            return [
                'success' => true,
                'data' => $result,
                'summary' => [
                    'total_users' => count($users),
                    'total_accounts' => count($accounts),
                    'global_balance' => $this->calculateTotalBalance($accounts)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Échec agrégation globale', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * Récupérer les statistiques agrégées
     */
    public function getAggregatedStats(): array
    {
        try {
            $responses = Http::pool(fn ($pool) => [
                $pool->get($this->userServiceUrl . '/api/users'),
                $pool->get($this->accountServiceUrl . '/api/accounts'),
            ]);

            $usersResponse = $responses[0];
            $accountsResponse = $responses[1];

            $users = $usersResponse->successful() ? $usersResponse->json() : [];
            $accounts = $accountsResponse->successful() ? $accountsResponse->json() : [];

            return [
                'success' => true,
                'stats' => [
                    'total_users' => count($users),
                    'total_accounts' => count($accounts),
                    'average_accounts_per_user' => count($users) > 0 ? round(count($accounts) / count($users), 2) : 0,
                    'total_balance' => $this->calculateTotalBalance($accounts),
                    'average_balance_per_account' => count($accounts) > 0 ? round($this->calculateTotalBalance($accounts) / count($accounts), 2) : 0
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function calculateTotalBalance(array $accounts): float
    {
        return array_reduce($accounts, function ($total, $account) {
            return $total + (float)($account['solde'] ?? 0);
        }, 0.0);
    }
}
