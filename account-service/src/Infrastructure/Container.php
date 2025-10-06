<?php

use Infrastructure\Repositories\FileCompteBancaireRepository;
use Application\Command\CreateCompteBancaireCommand;
use Application\Command\DeleteCompteBancaireCommand;
use Application\Command\DeleteCompteBancaireByUserCommand;
use Application\Command\UpdateCompteBancaireCommand;
use Application\Query\GetCompteBancaireQuery;
use Application\Query\GetCompteBancaireByUserQuery;
use Application\Query\ListCompteBancaireQuery;

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
            case FileCompteBancaireRepository::class:
                return new FileCompteBancaireRepository();
            case CreateCompteBancaireCommand::class:
                return new CreateCompteBancaireCommand(self::get(FileCompteBancaireRepository::class));
            case DeleteCompteBancaireCommand::class:
                return new DeleteCompteBancaireCommand(self::get(FileCompteBancaireRepository::class));
            case DeleteCompteBancaireByUserCommand::class:
                return new DeleteCompteBancaireByUserCommand(self::get(FileCompteBancaireRepository::class));
            case UpdateCompteBancaireCommand::class:
                return new UpdateCompteBancaireCommand(self::get(FileCompteBancaireRepository::class));
            case GetCompteBancaireQuery::class:
                return new GetCompteBancaireQuery(self::get(FileCompteBancaireRepository::class));
            case GetCompteBancaireByUserQuery::class:
                return new GetCompteBancaireByUserQuery(self::get(FileCompteBancaireRepository::class));
            case ListCompteBancaireQuery::class:
                return new ListCompteBancaireQuery(self::get(FileCompteBancaireRepository::class));
            default:
                throw new Exception("Unknown class: $class");
        }
    }
}
