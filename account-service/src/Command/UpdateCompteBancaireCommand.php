<?php

namespace Application\Command;

use Application\DTO\CompteBancaireDTO;
use Application\Ports\ICompteBancaireRepository;
use Domain\CompteBancaire;
use Application\Mapping\CompteBancaireMapper;

class UpdateCompteBancaireCommand
{
    private ICompteBancaireRepository $repository;

    public function __construct(ICompteBancaireRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(int $id, CompteBancaireDTO $dto): ?CompteBancaireDTO
    {
        $compte = CompteBancaireMapper::fromDTO($dto);
        $updated = $this->repository->update($id, $compte);
        return $updated ? CompteBancaireMapper::toDTO($updated) : null;
    }
}
