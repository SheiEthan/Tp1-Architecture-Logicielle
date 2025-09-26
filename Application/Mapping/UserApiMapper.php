<?php

namespace Application\Mapping;

use Domain\UserApi;
use Application\DTO\UserApiDTO;

class UserApiMapper
{
    public static function toDTO(UserApi $user): UserApiDTO
    {
        return new UserApiDTO(
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getPhone(),
            $user->getRole()
        );
    }

    public static function fromDTO(UserApiDTO $dto): UserApi
    {
        return new UserApi(
            $dto->firstName,
            $dto->lastName,
            $dto->email,
            $dto->phone,
            $dto->role
        );
    }
}
