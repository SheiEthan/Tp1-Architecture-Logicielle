<?php

namespace Domain;

/**
 * Entité UserApi (Clean Architecture)
 * Ne dépend d'aucun ORM/framework
 */
class UserApi
{
    private ?int $id;
    private string $firstName;
    private string $lastName;
    private string $email;
    private ?string $phone;
    private string $role;

    public function __construct(string $firstName, string $lastName, string $email, ?string $phone, string $role, ?int $id = null)
    {
        // Règles métier et invariants ici
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phone = $phone;
        $this->role = $role;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getRole(): string { return $this->role; }

    // Exemple de règle métier : assignation auto du profil
    public static function assignRole(string $email): string
    {
        // Exemple : si email contient 'admin', rôle = 'admin', sinon 'user'
        return str_contains($email, 'admin') ? 'admin' : 'user';
    }
}
