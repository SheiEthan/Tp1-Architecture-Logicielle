<?php

use Infrastructure\Repositories\FileUserApiRepository;
use Application\Command\CreateUserApiCommand;
use Application\Command\DeleteUserApiCommand;
use Application\Query\GetUserApiQuery;
use Application\Query\ListUserApiQuery;

class Container
{
    private static array $instances = [];

    public static function get(string $class)
    {
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = self::make($class);
        }
        return self::$instances[$class];
    }

    private static function make(string $class)
    {
        switch ($class) {
            case FileUserApiRepository::class:
                return new FileUserApiRepository();
            case CreateUserApiCommand::class:
                return new CreateUserApiCommand(self::get(FileUserApiRepository::class));
            case DeleteUserApiCommand::class:
                return new DeleteUserApiCommand(self::get(FileUserApiRepository::class));
            case GetUserApiQuery::class:
                return new GetUserApiQuery(self::get(FileUserApiRepository::class));
            case ListUserApiQuery::class:
                return new ListUserApiQuery(self::get(FileUserApiRepository::class));
            default:
                throw new Exception("Unknown class: $class");
        }
    }
}
