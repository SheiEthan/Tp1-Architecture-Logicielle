<?php

namespace Application\Query;

use Application\DTO\UserApiDTO;
use Application\Ports\IUserApiRepository;
use Application\Mapping\UserApiMapper;

class GetUserApiQuery
{
    private IUserApiRepository $repository;

    public function __construct(IUserApiRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(int $id): ?UserApiDTO
    {
        $user = $this->repository->find($id);
        return $user ? UserApiMapper::toDTO($user) : null;
    }
}
