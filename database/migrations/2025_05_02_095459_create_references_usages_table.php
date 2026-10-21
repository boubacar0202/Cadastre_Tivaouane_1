
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
        Schema::create('references_usages', function (Blueprint $table) {
            $table->id();

            // Clés étrangères
            $table->string('txt_nicad');
            $table->string('txt_num_dossier');

            // Champs liés à l'usage
            $table->string('slt_usage');
            $table->string('slt_residence')->nullable();

            // Champs du TransitionGroup
            $table->string('txt_nomOccupantTG')->nullable();
            $table->string('txt_numAppartementTG')->nullable();
            $table->string('txt_activiteTG')->nullable();
            $table->string('txt_nineaTG')->nullable();
            $table->string('tel_telephoneTG')->nullable();
            $table->integer('nbr_montantLoyerTG')->nullable();
            $table->string('txt_dateLieuNaissanceTG')->nullable();
            $table->string('txt_cniPasseportTG')->nullable();
            $table->string('dt_dateDelivranceTG')->nullable();
            $table->integer('nbr_montantLoyerTotal')->nullable();
            $table->integer('nbr_TVATotal')->nullable();

            $table->timestamps();

            // Déclaration des clés étrangères
            $table->foreign('txt_nicad')->references('txt_nicad')->on('terrains')->onDelete('cascade');
            $table->foreign('txt_num_dossier')->references('txt_num_dossier')->on('dossiers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('references_usages');
    }
};





// 






