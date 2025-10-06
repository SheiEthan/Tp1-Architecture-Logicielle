<?php

namespace Application\Query;

use Application\Ports\ICompteBancaireRepository;
use Application\Mapping\CompteBancaireMapper;

class ListCompteBancaireQuery
{
    private ICompteBancaireRepository $repository;

    public function __construct(ICompteBancaireRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(): array
    {
        $comptes = $this->repository->all();
        return array_map([CompteBancaireMapper::class, 'toDTO'], $comptes);
    }
}
