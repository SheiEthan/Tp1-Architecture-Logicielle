<?php

namespace Application\Command;

use Application\DTO\UserApiDTO;
use Application\Ports\IUserApiRepository;
use Domain\UserApi;
use Application\Mapping\UserApiMapper;

class UpdateUserApiCommand
{
    private IUserApiRepository $repository;

    public function __construct(IUserApiRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(int $id, UserApiDTO $dto): ?UserApiDTO
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
