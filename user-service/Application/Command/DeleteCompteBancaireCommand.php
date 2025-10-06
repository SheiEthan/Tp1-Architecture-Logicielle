<?php

namespace Application\Command;

use Application\Ports\ICompteBancaireRepository;

class DeleteCompteBancaireCommand
{
    private ICompteBancaireRepository $repository;

    public function __construct(ICompteBancaireRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
