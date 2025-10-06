<?php

namespace Infrastructure\Repositories;

use Application\Ports\ICompteBancaireRepository;
use Domain\CompteBancaire;

class FileCompteBancaireRepository implements ICompteBancaireRepository
{
    private string $dataFile;

    public function __construct()
    {
        $this->dataFile = '/var/www/html/data/accounts.json';
        $dataDir = dirname($this->dataFile);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }
    }

    public function save(CompteBancaire $compte): CompteBancaire
    {
        $comptes = $this->loadComptes();
        $id = $this->getNextId($comptes);
        $compte->setId($id);
        $comptes[$id] = $this->toArray($compte);
        $this->saveComptes($comptes);
        return $compte;
    }

    public function find(int $id): ?CompteBancaire
    {
        $comptes = $this->loadComptes();
        if (!isset($comptes[$id])) {
            return null;
        }
        return $this->fromArray($comptes[$id]);
    }

    public function update(int $id, CompteBancaire $compte): ?CompteBancaire
    {
        $comptes = $this->loadComptes();
        if (!isset($comptes[$id])) {
            return null;
        }
        $compte->setId($id);
        $comptes[$id] = $this->toArray($compte);
        $this->saveComptes($comptes);
        return $compte;
    }

    public function delete(int $id): bool
    {
        $comptes = $this->loadComptes();
        if (!isset($comptes[$id])) {
            return false;
        }
        unset($comptes[$id]);
        return $this->saveComptes($comptes);
    }

    public function all(): array
    {
        $comptes = $this->loadComptes();
        return array_map([$this, 'fromArray'], $comptes);
    }

    public function findByUserId(int $userId): array
    {
        $comptes = $this->loadComptes();
        $userComptes = array_filter($comptes, function($compte) use ($userId) {
            return $compte['user_id'] == $userId;
        });
        return array_map([$this, 'fromArray'], $userComptes);
    }

    public function deleteByUserId(int $userId): bool
    {
        $comptes = $this->loadComptes();
        $deletedCount = 0;

        foreach ($comptes as $id => $compte) {
            if ($compte['user_id'] == $userId) {
                unset($comptes[$id]);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->saveComptes($comptes);
            return true;
        }

        return false;
    }

    private function loadComptes(): array
    {
        if (file_exists($this->dataFile)) {
            $content = file_get_contents($this->dataFile);
            return json_decode($content, true) ?: [];
        }
        return [
            1 => ['id' => 1, 'user_id' => 1, 'numero_compte' => 'CPT001', 'solde' => 1000.50, 'type_compte' => 'courant'],
            2 => ['id' => 2, 'user_id' => 2, 'numero_compte' => 'CPT002', 'solde' => 2500.00, 'type_compte' => 'epargne']
        ];
    }

    private function saveComptes(array $comptes): bool
    {
        return file_put_contents($this->dataFile, json_encode($comptes, JSON_PRETTY_PRINT)) !== false;
    }

    private function getNextId(array $comptes): int
    {
        return empty($comptes) ? 1 : max(array_keys($comptes)) + 1;
    }

    private function toArray(CompteBancaire $compte): array
    {
        return [
            'id' => $compte->getId(),
            'user_id' => $compte->getUserId(),
            'numero_compte' => $compte->getNumeroCompte(),
            'iban' => $compte->getIban(),
            'bic' => $compte->getBic(),
            'solde' => $compte->getSolde(),
            'type_compte' => $compte->getStatut()
        ];
    }

    private function fromArray(array $data): CompteBancaire
    {
        $compte = new CompteBancaire(
            $data['numero_compte'],
            $data['iban'] ?? '',
            $data['bic'] ?? '',
            $data['solde'] ?? 0.0,
            $data['user_id']
        );
        $compte->setId($data['id']);
        return $compte;
    }
}
