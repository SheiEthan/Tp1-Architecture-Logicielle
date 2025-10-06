<?php

namespace Infrastructure\Repositories;

use Application\Ports\IUserApiRepository;
use Domain\UserApi;

class FileUserApiRepository implements IUserApiRepository
{
    private string $dataFile;

    public function __construct()
    {
        $this->dataFile = '/var/www/html/data/users.json';
        $dataDir = dirname($this->dataFile);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }
    }

    public function save(UserApi $user): UserApi
    {
        $users = $this->loadUsers();
        $id = $this->getNextId($users);
        $user->setId($id);
        $users[$id] = $this->toArray($user);
        $this->saveUsers($users);
        return $user;
    }

    public function find(int $id): ?UserApi
    {
        $users = $this->loadUsers();
        if (!isset($users[$id])) {
            return null;
        }
        return $this->fromArray($users[$id]);
    }

    public function update(int $id, UserApi $user): ?UserApi
    {
        $users = $this->loadUsers();
        if (!isset($users[$id])) {
            return null;
        }
        $user->setId($id);
        $users[$id] = $this->toArray($user);
        $this->saveUsers($users);
        return $user;
    }

    public function delete(int $id): bool
    {
        $users = $this->loadUsers();
        if (!isset($users[$id])) {
            return false;
        }
        unset($users[$id]);
        return $this->saveUsers($users);
    }

    public function all(): array
    {
        $users = $this->loadUsers();
        return array_map([$this, 'fromArray'], $users);
    }

    private function loadUsers(): array
    {
        if (file_exists($this->dataFile)) {
            $content = file_get_contents($this->dataFile);
            return json_decode($content, true) ?: [];
        }
        return [
            1 => ['id' => 1, 'firstName' => 'John', 'lastName' => 'Doe', 'email' => 'john@example.com', 'phone' => '0123456789', 'role' => 'user'],
            2 => ['id' => 2, 'firstName' => 'Jane', 'lastName' => 'Smith', 'email' => 'jane@example.com', 'phone' => '0987654321', 'role' => 'user']
        ];
    }

    private function saveUsers(array $users): bool
    {
        return file_put_contents($this->dataFile, json_encode($users, JSON_PRETTY_PRINT)) !== false;
    }

    private function getNextId(array $users): int
    {
        return empty($users) ? 1 : max(array_keys($users)) + 1;
    }

    private function toArray(UserApi $user): array
    {
        return [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'role' => $user->getRole()
        ];
    }

    private function fromArray(array $data): UserApi
    {
        $user = new UserApi(
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            $data['phone'] ?? null,
            $data['role'] ?? 'user'
        );
        $user->setId($data['id']);
        return $user;
    }
}
