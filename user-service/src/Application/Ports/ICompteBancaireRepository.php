<?php

namespace Application\Ports;

use Domain\CompteBancaire;

interface ICompteBancaireRepository
{
    public function save(CompteBancaire $compte): CompteBancaire;
    public function find(int $id): ?CompteBancaire;
    public function findByUserId(int $userId): array;
    public function update(int $id, CompteBancaire $compte): ?CompteBancaire;
    public function delete(int $id): bool;
    public function deleteByUserId(int $userId): bool;
    public function all(): array;
}
