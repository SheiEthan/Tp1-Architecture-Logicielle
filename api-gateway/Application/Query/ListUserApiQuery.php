<?php

namespace Application\Query;

use Application\Ports\IUserApiRepository;
use Application\Mapping\UserApiMapper;
use Application\DTO\UserApiDTO;

class ListUserApiQuery
{
    private IUserApiRepository $repository;

    public function __construct(IUserApiRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(): array
    {
        $users = $this->repository->all();
        return array_map([UserApiMapper::class, 'toDTO'], $users);
    }
}
