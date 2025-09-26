<?php

namespace Domain;

use PHPUnit\Framework\TestCase;

class UserApiTest extends TestCase
{
    public function testAssignRoleAdmin()
    {
        $role = UserApi::assignRole('admin@example.com');
        $this->assertEquals('admin', $role);
    }

    public function testAssignRoleUser()
    {
        $role = UserApi::assignRole('user@example.com');
        $this->assertEquals('user', $role);
    }

    public function testUserApiEncapsulation()
    {
        $user = new UserApi('John', 'Doe', 'john@example.com', '0600000000', 'user');
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('john@example.com', $user->getEmail());
        $this->assertEquals('0600000000', $user->getPhone());
        $this->assertEquals('user', $user->getRole());
    }
}
