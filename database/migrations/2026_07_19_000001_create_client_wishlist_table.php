<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_wishlist', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('id_client')->constrained('client')->cascadeOnDelete();
            $table->foreignId('id_produit')->constrained('produit')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['id_client', 'id_produit']);
            $table->index(['id_client', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_wishlist');
    }
};
