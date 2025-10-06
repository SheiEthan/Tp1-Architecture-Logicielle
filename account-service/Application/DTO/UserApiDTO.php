<?php

namespace Application\DTO;

/**
 * Data Transfer Object pour UserApi
 */
class UserApiDTO
{
    public ?int $id;
    public string $firstName;
    public string $lastName;
    public string $email;
    public ?string $phone;
    public string $role;

    public function __construct(string $firstName, string $lastName, string $email, ?string $phone, string $role, ?int $id = null)
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phone = $phone;
        $this->role = $role;
    }
}
