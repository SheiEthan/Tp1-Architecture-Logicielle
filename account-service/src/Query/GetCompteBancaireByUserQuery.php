<?php

namespace Application\Query;

use Application\Ports\ICompteBancaireRepository;
use Application\Mapping\CompteBancaireMapper;

class GetCompteBancaireByUserQuery
{
    private ICompteBancaireRepository $repository;

    public function __construct(ICompteBancaireRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(int $userId): array
    {
        $comptes = $this->repository->findByUserId($userId);
        return array_map([CompteBancaireMapper::class, 'toDTO'], $comptes);
    }
}
