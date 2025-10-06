<?php

namespace Application\Query;

use Application\DTO\CompteBancaireDTO;
use Application\Ports\ICompteBancaireRepository;
use Application\Mapping\CompteBancaireMapper;

class GetCompteBancaireQuery
{
    private ICompteBancaireRepository $repository;

    public function __construct(ICompteBancaireRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(int $id): ?CompteBancaireDTO
    {
        $compte = $this->repository->find($id);
        return $compte ? CompteBancaireMapper::toDTO($compte) : null;
    }
}
