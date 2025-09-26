<?php

namespace Application\Ports;

use Domain\UserApi;

interface IUserApiRepository
{
    public function save(UserApi $user): UserApi;
    public function find(int $id): ?UserApi;
    public function update(int $id, UserApi $user): ?UserApi;
    public function delete(int $id): bool;
}
