<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\EloquentUserRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(
            \Application\Ports\IUserApiRepository::class,
            \Persistence\EloquentUserApiRepository::class
        );
        // Binding pour le microservice CompteBancaire
        $this->app->bind(
            \Application\Ports\ICompteBancaireRepository::class,
            \Persistence\EloquentCompteBancaireRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
