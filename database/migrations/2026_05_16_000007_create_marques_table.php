<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_frs');
            $table->string('nom');
            $table->timestamps();

            $table->foreign('id_frs')->references('id')->on('frs')->onDelete('cascade');
            $table->unique(['id_frs', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marques');
    }
};
