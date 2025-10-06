<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserApi extends Model
{
    // Connexion pour le microservice Utilisateur
    protected $connection = 'mysql_users';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'role',
    ];
}
