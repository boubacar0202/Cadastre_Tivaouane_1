<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationsCoursAmenageesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('evaluations_cours_amenagees', function (Blueprint $table) {
            $table->id();
            $table->string('txt_num_dossier'); // clé étrangère vers dossiers.txt_num_dossier
            $table->string('txt_nicad');       // clé étrangère vers terrains.txt_nicad

            $table->decimal('nbr_valeur_total_ca', 15, 2)->nullable();
            $table->string('slt_cours_amenager_totale')->nullable();
            $table->decimal('nbr_surface_ca_total', 10, 2)->nullable();
            $table->integer('slt_categorie_ca_total')->nullable();
            $table->decimal('nbr_prix_metre_carre_ca_total', 10, 2)->nullable();
            $table->decimal('nbr_coefficient_ca_total', 5, 2)->nullable();
            $table->decimal('nbr_valeur_ca_total', 15, 2)->nullable();

            $table->timestamps();

            // Clés étrangères (facultatives, mais recommandées)
            $table->foreign('txt_nicad')->references('txt_nicad')->on('terrains')->onDelete('cascade');
            $table->foreign('txt_num_dossier')->references('txt_num_dossier')->on('dossiers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations_cours_amenagees');
    }
}
