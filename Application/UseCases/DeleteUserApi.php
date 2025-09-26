<?php

namespace Application\UseCases;

use Application\Ports\IUserApiRepository;

class DeleteUserApi
{
    private IUserApiRepository $repository;

    public function __construct(IUserApiRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
