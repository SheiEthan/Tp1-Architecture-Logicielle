<?php

namespace Domain;

/**
 * Entité CompteBancaire (Clean Architecture)
 * Ne dépend d'aucun ORM/framework
 */
class CompteBancaire
{
    private string $numeroCompte;
    private string $iban;
    private string $bic;
    private float $solde;
    private int $userId; // Référence vers le service User
    private string $statut;

    public function __construct(
        string $numeroCompte,
        string $iban,
        string $bic,
        float $solde,
        int $userId,
        string $statut = 'actif'
    ) {
        $this->numeroCompte = $numeroCompte;
        $this->iban = $iban;
        $this->bic = $bic;
        $this->solde = $solde;
        $this->userId = $userId;
        $this->statut = $statut;
    }

    // Getters
    public function getNumeroCompte(): string { return $this->numeroCompte; }
    public function getIban(): string { return $this->iban; }
    public function getBic(): string { return $this->bic; }
    public function getSolde(): float { return $this->solde; }
    public function getUserId(): int { return $this->userId; }
    public function getStatut(): string { return $this->statut; }

    // Règles métier
    public function crediter(float $montant): void
    {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif');
        }
        $this->solde += $montant;
    }

    public function debiter(float $montant): void
    {
        if ($montant <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif');
        }
        if ($this->solde < $montant) {
            throw new \DomainException('Solde insuffisant');
        }
        $this->solde -= $montant;
    }

    public function fermer(): void
    {
        if ($this->solde > 0) {
            throw new \DomainException('Impossible de fermer un compte avec un solde positif');
        }
        $this->statut = 'ferme';
    }

    // Génération automatique IBAN (simplifié)
    public static function genererIban(string $numeroCompte): string
    {
        return 'FR' . str_pad($numeroCompte, 21, '0', STR_PAD_LEFT);
    }

    // Génération BIC par défaut
    public static function genererBic(): string
    {
        return 'STREAMFR21';
    }
}
