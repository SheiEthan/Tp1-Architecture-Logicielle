<?php

namespace Application\DTO;

/**
 * Data Transfer Object pour CompteBancaire
 */
class CompteBancaireDTO
{
    public string $numeroCompte;
    public string $iban;
    public string $bic;
    public float $solde;
    public int $userId;
    public string $statut;

    public function __construct(
        string $numeroCompte,
        string $iban,
        string $bic,
        float $solde,
        int $userId,
        string $statut
    ) {
        $this->numeroCompte = $numeroCompte;
        $this->iban = $iban;
        $this->bic = $bic;
        $this->solde = $solde;
        $this->userId = $userId;
        $this->statut = $statut;
    }
}
