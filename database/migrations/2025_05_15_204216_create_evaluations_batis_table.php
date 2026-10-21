

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationsBatisTable extends Migration
{
    public function up()
    {
        Schema::create('evaluations_batis', function (Blueprint $table) {
            $table->id();
            $table->string('currentCat');
            $table->string('txt_num_dossier'); 
            $table->string('txt_nicad'); 
            $table->string('txt_dependant_domainePR')->nullable();
            $table->string('slt_categoriePR')->nullable();
            $table->decimal('nbr_prix_metre_carrePR',10,2)->nullable();
            $table->decimal('nbr_surface_bati_solPR',10,2)->nullable();
            $table->integer('nbr_niveauPR')->nullable();
            $table->decimal('nbr_surface_utilePR',10,2)->nullable();
            $table->decimal('slt_coeffPR',10,2)->nullable();
            $table->decimal('nbr_surface_corrigerPR',10,2)->nullable();
            $table->decimal('nbr_valeurPR',10,2)->nullable();  
            $table->string('slt_dependant_domaineTG')->nullable(); // Le domaine, si applicable
            $table->string('slt_categorieTG')->nullable();
            $table->decimal('nbr_prix_metre_carreTG', 10, 2)->nullable(); // Prix par mètre carré
            $table->decimal('nbr_surface_bati_solTG', 10, 2)->nullable(); // Surface bâtie au sol
            $table->integer('nbr_niveauTG')->nullable(); // Nombre de niveaux
            $table->decimal('nbr_surface_utileTG', 10, 2)->nullable(); // Surface utile
            $table->decimal('slt_coeffTG', 5, 2)->nullable(); // Coefficient
            $table->decimal('nbr_surface_corrigerTG', 10, 2)->nullable(); // Surface corrigée
            $table->decimal('nbr_valeurTG', 10, 2)->nullable(); // Valeur du terrain par occupant
            $table->decimal('txt_valeur_terrain_bati', 10, 2); // Valeur calculée 

            $table->timestamps(); 

            // Déclaration des clés étrangères
            $table->foreign('txt_nicad')->references('txt_nicad')->on('terrains')->onDelete('cascade');
            $table->foreign('txt_num_dossier')->references('txt_num_dossier')->on('dossiers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('evaluations_batis');
    }
}
