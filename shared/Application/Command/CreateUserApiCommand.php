<?php

namespace Application\Command;

use Application\DTO\UserApiDTO;
use Application\Ports\IUserApiRepository;
use Domain\UserApi;
use Application\Mapping\UserApiMapper;

class CreateUserApiCommand
{
    private IUserApiRepository $repository;

    public function __construct(IUserApiRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(UserApiDTO $dto): UserApiDTO
    {
        $role = UserApi::assignRole($dto->email);
        $user = new UserApi(
            $dto->firstName,
            $dto->lastName,
            $dto->email,
            $dto->phone,
            $role
        );
        $created = $this->repository->save($user);
        return UserApiMapper::toDTO($created);
    }
}
