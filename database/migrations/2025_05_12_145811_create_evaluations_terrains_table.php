
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
        Schema::create('evaluations_terrains', function (Blueprint $table) {
            $table->id();
            
            // Clés étrangères
            $table->string('txt_nicad');
            $table->string('txt_num_dossier');
            $table->string('nbr_surface');

            $table->decimal('txt_superficie_totale',15,2);
            $table->decimal('txt_superficie_bati_sol',15,2);
            $table->integer('slt_secteur');
            $table->integer('nbr_prix_metre_carre');
            $table->decimal('nbr_valeur_terrain',15,2);
            $table->decimal('nbr_valeurVenaleLimeuble', 15, 2)->nullable();
            $table->decimal('nbr_valeurLocative', 15, 2)->nullable();
            $table->date('dt_dateEvaluation')->nullable();
 
            $table->timestamps();

            // Déclaration des clés étrangères
            $table->foreign('txt_nicad')->references('txt_nicad')->on('terrains')->onDelete('cascade');
            $table->foreign(columns: 'txt_num_dossier')->references('txt_num_dossier')->on('dossiers')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations_terrains');
    }
};



