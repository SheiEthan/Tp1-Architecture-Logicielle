<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comptes_bancaires', function (Blueprint $table) {
            $table->id();
            $table->string('numero_compte')->unique();
            $table->string('iban')->unique();
            $table->string('bic');
            $table->decimal('solde', 15, 2)->default(0.00);
            $table->unsignedBigInteger('user_id'); // Référence vers le service User
            $table->enum('statut', ['actif', 'inactif', 'ferme'])->default('actif');
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index('user_id');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes_bancaires');
    }
};
