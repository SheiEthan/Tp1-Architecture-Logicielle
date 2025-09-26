<?php

namespace Persistence;

use Application\Ports\IUserApiRepository;
use Domain\UserApi;
use App\Models\UserApi as EloquentUserApi;

class EloquentUserApiRepository implements IUserApiRepository
{
    public function all(): array
    {
        $models = EloquentUserApi::all();
        return array_map(function ($model) {
            return $this->toDomain($model);
        }, $models->all());
    }
    public function save(UserApi $user): UserApi
    {
        $model = new EloquentUserApi([
            'first_name' => $user->getFirstName(),
            'last_name'  => $user->getLastName(),
            'email'      => $user->getEmail(),
            'phone'      => $user->getPhone(),
            'role'       => $user->getRole(),
        ]);
        $model->save();
        return $this->toDomain($model);
    }

    public function find(int $id): ?UserApi
    {
        $model = EloquentUserApi::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function update(int $id, UserApi $user): ?UserApi
    {
        $model = EloquentUserApi::find($id);
        if (!$model) return null;
        $model->fill([
            'first_name' => $user->getFirstName(),
            'last_name'  => $user->getLastName(),
            'email'      => $user->getEmail(),
            'phone'      => $user->getPhone(),
            'role'       => $user->getRole(),
        ]);
        $model->save();
        return $this->toDomain($model);
    }

    public function delete(int $id): bool
    {
        $model = EloquentUserApi::find($id);
        if (!$model) return false;
        return $model->delete();
    }

    private function toDomain(EloquentUserApi $model): UserApi
    {
        return new UserApi(
            $model->first_name,
            $model->last_name,
            $model->email,
            $model->phone,
            $model->role
        );
    }
}
