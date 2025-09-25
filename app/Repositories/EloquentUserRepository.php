<?php

namespace App\Repositories;

use App\Models\UserApi;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function all()
    {
        return UserApi::all();
    }

    public function find(int $id): ?UserApi
    {
        return UserApi::find($id);
    }

    public function create(array $data): UserApi
    {
        return UserApi::create($data);
    }

    public function update(int $id, array $data): ?UserApi
    {
        $user = $this->find($id);
        if (!$user) return null;
        $user->fill($data);
        $user->save();
        return $user;
    }

    public function delete(int $id): bool
    {
        $user = $this->find($id);
        if (!$user) return false;
        return (bool) $user->delete();
    }
}
