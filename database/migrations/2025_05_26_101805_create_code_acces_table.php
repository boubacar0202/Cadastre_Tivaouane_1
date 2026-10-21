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
       Schema::create('code_acces', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ex: "00002/2025"
            $table->boolean('utilise')->default(false); // Pour savoir si le code a été utilisé
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('code_acces');
    }
};
