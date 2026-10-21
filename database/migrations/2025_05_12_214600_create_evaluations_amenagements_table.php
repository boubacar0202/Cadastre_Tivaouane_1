
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
        Schema::create('evaluations_amenagements', function (Blueprint $table) {
            $table->id();
            $table->string('txt_num_dossier');
            $table->string('txt_nicad');
            
            $table->string('txt_designation_am')->nullable();
            $table->decimal('nbr_valeur_unitaire_am', 10, 2)->nullable();
            $table->decimal('nbr_quantile_am', 10, 2)->nullable();
            $table->decimal('slt_coeficien_am', 4, 2)->nullable();
            $table->decimal('nbr_valeur_am', 15, 2)->nullable();
            $table->decimal('nbr_valeur_totale_ap', 15, 2)->nullable();

            $table->timestamps();

            $table->foreign('txt_num_dossier')->references('txt_num_dossier')->on('dossiers')->onDelete('cascade');
            $table->foreign('txt_nicad')->references('txt_nicad')->on('terrains')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations_amenagements');
    }
};
