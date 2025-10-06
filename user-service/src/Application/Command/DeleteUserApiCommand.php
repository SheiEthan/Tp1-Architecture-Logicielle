<?php

namespace Application\Command;

use Application\Ports\IUserApiRepository;

class DeleteUserApiCommand
{
    private IUserApiRepository $repository;

    public function __construct(IUserApiRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
