<?php

namespace Application\Command;

use Application\Ports\ICompteBancaireRepository;

class DeleteCompteBancaireByUserCommand
{
    private ICompteBancaireRepository $repository;

    public function __construct(ICompteBancaireRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(int $userId): bool
    {
        return $this->repository->deleteByUserId($userId);
    }
}
