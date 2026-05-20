<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sous_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_frs');
            $table->unsignedBigInteger('id_categorie');
            $table->string('nom');
            $table->timestamps();

            $table->foreign('id_frs')->references('id')->on('frs')->onDelete('cascade');
            $table->foreign('id_categorie')->references('id')->on('categories')->onDelete('cascade');
            $table->unique(['id_frs', 'id_categorie', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sous_categories');
    }
};
