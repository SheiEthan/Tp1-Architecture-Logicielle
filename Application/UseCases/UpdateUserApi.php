<?php

namespace Application\UseCases;

use Application\DTO\UserApiDTO;
use Application\Ports\IUserApiRepository;
use Domain\UserApi;
use Application\Mapping\UserApiMapper;

class UpdateUserApi
{
    private IUserApiRepository $repository;

    public function __construct(IUserApiRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(int $id, UserApiDTO $dto): ?UserApiDTO
    {
        $user = new UserApi(
            $dto->firstName,
            $dto->lastName,
            $dto->email,
            $dto->phone,
            $dto->role
        );
        $updated = $this->repository->update($id, $user);
        return $updated ? UserApiMapper::toDTO($updated) : null;
    }
}
