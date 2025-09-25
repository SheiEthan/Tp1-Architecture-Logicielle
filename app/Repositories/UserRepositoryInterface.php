<?php

namespace App\Repositories;

use App\Models\UserApi;

interface UserRepositoryInterface
{
    /** @return \Illuminate\Database\Eloquent\Collection|UserApi[] */
    public function all();

    public function find(int $id): ?UserApi;

    public function create(array $data): UserApi;

    public function update(int $id, array $data): ?UserApi;

    public function delete(int $id): bool;
}
