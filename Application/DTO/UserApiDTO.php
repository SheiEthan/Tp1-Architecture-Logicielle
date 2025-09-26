<?php

namespace Application\DTO;

/**
 * Data Transfer Object pour UserApi
 */
class UserApiDTO
{
    public string $firstName;
    public string $lastName;
    public string $email;
    public ?string $phone;
    public string $role;

    public function __construct(string $firstName, string $lastName, string $email, ?string $phone, string $role)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phone = $phone;
        $this->role = $role;
    }
}
