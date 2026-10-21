<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationsCloturesTable extends Migration
{
    //
    public function up(): void
    {
        Schema::create('evaluations_clotures', function (Blueprint $table) {
            $table->id();

            $table->string('txt_num_dossier'); // Clé étrangère vers dossiers.txt_num_dossier
            $table->string('txt_nicad');       // Clé étrangère vers terrains.txt_nicad

            $table->string('slt_cours_amenager_clo')->nullable();
            $table->decimal('nbr_longueur_avant_clo', 10, 2)->nullable();
            $table->integer('slt_categorie_clo')->nullable();
            $table->decimal('nbr_prix_metre_carre_clo', 10, 2)->nullable();
            $table->decimal('nbr_coefficient_clo', 5, 2)->nullable();
            $table->decimal('nbr_valeur_clo', 15, 2)->nullable();
            $table->decimal('nbr_valeur_total_clotur', 15, 2)->nullable();

            $table->timestamps();

            // Clés étrangères (facultatives mais recommandées)
            $table->foreign('txt_num_dossier')->references('txt_num_dossier')->on('dossiers')->onDelete('cascade');
            $table->foreign('txt_nicad')->references('txt_nicad')->on('terrains')->onDelete('cascade');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('evaluations_clotures');
    }
}
