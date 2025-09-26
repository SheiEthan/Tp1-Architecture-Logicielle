<?php

namespace Application\UseCases;

use Application\DTO\UserApiDTO;
use Application\Ports\IUserApiRepository;
use Application\Mapping\UserApiMapper;

class GetUserApi
{
    private IUserApiRepository $repository;

    public function __construct(IUserApiRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(int $id): ?UserApiDTO
    {
        $user = $this->repository->find($id);
        return $user ? UserApiMapper::toDTO($user) : null;
    }
}
