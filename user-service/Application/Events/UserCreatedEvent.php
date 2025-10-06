<?php

namespace Application\Events;

/**
 * Événement déclenché lors de la création d'un utilisateur
 */
class UserCreatedEvent
{
    public int $userId;
    public array $userData;
    public string $timestamp;

    public function __construct(int $userId, array $userData)
    {
        $this->userId = $userId;
        $this->userData = $userData;
        $this->timestamp = now()->toISOString();
    }

    public function toArray(): array
    {
        return [
            'event' => 'user.created',
            'user_id' => $this->userId,
            'user_data' => $this->userData,
            'timestamp' => $this->timestamp
        ];
    }
}
