<?php

namespace Application\Command;

use Application\DTO\CompteBancaireDTO;
use Application\Ports\ICompteBancaireRepository;
use Domain\CompteBancaire;
use Application\Mapping\CompteBancaireMapper;

class CreateCompteBancaireCommand
{
    private ICompteBancaireRepository $repository;

    public function __construct(ICompteBancaireRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(int $userId): CompteBancaireDTO
    {
        // Génération automatique des données du compte
        $numeroCompte = $this->genererNumeroCompte();
        $iban = CompteBancaire::genererIban($numeroCompte);
        $bic = CompteBancaire::genererBic();

        $compte = new CompteBancaire(
            $numeroCompte,
            $iban,
            $bic,
            0.00, // Solde initial
            $userId,
            'actif'
        );

        $created = $this->repository->save($compte);
        return CompteBancaireMapper::toDTO($created);
    }

    private function genererNumeroCompte(): string
    {
        return 'ACC' . time() . rand(1000, 9999);
    }
}
