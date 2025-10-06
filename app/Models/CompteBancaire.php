<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompteBancaire extends Model
{
    // Connexion pour le microservice CompteBancaire
    protected $connection = 'mysql_accounts';

    protected $table = 'comptes_bancaires';

    protected $fillable = [
        'numero_compte',
        'iban',
        'bic',
        'solde',
        'user_id',
        'statut',
    ];

    protected $casts = [
        'solde' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Pas de relation Eloquent car UserApi est dans un autre microservice
    // user_id stocke seulement la référence vers le service User

    // Scopes pour les requêtes courantes
    public function scopeActive($query)
    {
        return $query->where('statut', 'actif');
    }
}
