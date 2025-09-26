<?php

namespace Application\UseCases;

use Application\Ports\IUserApiRepository;
use Application\Mapping\UserApiMapper;
use Application\DTO\UserApiDTO;

class ListUserApi
{
    private IUserApiRepository $repository;

    public function __construct(IUserApiRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(): array
    {
        $users = $this->repository->all();
        return array_map([UserApiMapper::class, 'toDTO'], $users);
    }
}
