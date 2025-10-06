<?php

namespace Application\Events;

/**
 * Événement déclenché lors de la suppression d'un utilisateur
 */
class UserDeletedEvent
{
    public int $userId;
    public string $timestamp;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->timestamp = now()->toISOString();
    }

    public function toArray(): array
    {
        return [
            'event' => 'user.deleted',
            'user_id' => $this->userId,
            'timestamp' => $this->timestamp
        ];
    }
}
