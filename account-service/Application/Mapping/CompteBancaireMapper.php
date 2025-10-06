<?php

namespace Application\Mapping;

use Domain\CompteBancaire;
use Application\DTO\CompteBancaireDTO;

class CompteBancaireMapper
{
    public static function toDTO(CompteBancaire $compte): CompteBancaireDTO
    {
        return new CompteBancaireDTO(
            $compte->getNumeroCompte(),
            $compte->getIban(),
            $compte->getBic(),
            $compte->getSolde(),
            $compte->getUserId(),
            $compte->getStatut()
        );
    }

    public static function fromDTO(CompteBancaireDTO $dto): CompteBancaire
    {
        return new CompteBancaire(
            $dto->numeroCompte,
            $dto->iban,
            $dto->bic,
            $dto->solde,
            $dto->userId,
            $dto->statut
        );
    }
}
