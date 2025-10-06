<?php

namespace Persistence;

use Application\Ports\IUserApiRepository;
use Domain\UserApi;

class PDOUserApiRepository implements IUserApiRepository
{
    private $pdo;

    public function __construct() {
        $this->pdo = \DB::getConnection();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM user_apis ORDER BY id");
        $results = $stmt->fetchAll();

        return array_map(function($row) {
            return new UserApi(
                $row['first_name'],
                $row['last_name'],
                $row['email'],
                $row['phone'],
                $row['role'],
                $row['id']
            );
        }, $results);
    }

    public function save(UserApi $user): UserApi
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO user_apis (first_name, last_name, email, phone, role, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getPhone(),
            $user->getRole()
        ]);

        $id = $this->pdo->lastInsertId();

        return new UserApi(
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getPhone(),
            $user->getRole(),
            (int)$id
        );
    }

    public function findById(int $id): ?UserApi
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_apis WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) return null;

        return new UserApi(
            $row['first_name'],
            $row['last_name'],
            $row['email'],
            $row['phone'],
            $row['role'],
            $row['id']
        );
    }

    public function update(int $id, UserApi $user): ?UserApi
    {
        $stmt = $this->pdo->prepare("
            UPDATE user_apis
            SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $result = $stmt->execute([
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getPhone(),
            $user->getRole(),
            $id
        ]);

        if (!$result || $stmt->rowCount() === 0) return null;

        return new UserApi(
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getPhone(),
            $user->getRole(),
            $id
        );
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM user_apis WHERE id = ?");
        $result = $stmt->execute([$id]);
        return $result && $stmt->rowCount() > 0;
    }
}
