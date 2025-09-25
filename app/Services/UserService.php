<?php

namespace App\Services;

use App\Repositories\UserRepositoryInterface;
use App\Models\UserApi;

class UserService
{
    private UserRepositoryInterface $users;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    public function listUsers()
    {
        return $this->users->all();
    }

    public function getUser(int $id): ?UserApi
    {
        return $this->users->find($id);
    }

    public function createUser(array $data): UserApi
    {
        // règle d'assignation automatique du profil
        $data['role'] = $this->assignRoleFromEmail($data['email'] ?? null);
        return $this->users->create($data);
    }

    public function updateUser(int $id, array $data): ?UserApi
    {
        if (isset($data['email'])) {
            $data['role'] = $this->assignRoleFromEmail($data['email']);
        }
        return $this->users->update($id, $data);
    }

    public function deleteUser(int $id): bool
    {
        return $this->users->delete($id);
    }

    private function assignRoleFromEmail(?string $email): string
    {
        if (!$email) return 'user';
        $domain = strtolower(substr(strrchr($email, "@"), 1) ?: '');
        return $domain === 'company.com' ? 'admin' : 'user';
    }
}
