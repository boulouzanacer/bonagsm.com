<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produit', function (Blueprint $table) {
            $table->decimal('tva', 5, 2)->nullable()->after('pv_3');
        });
    }

    public function down(): void
    {
        Schema::table('produit', function (Blueprint $table) {
            $table->dropColumn('tva');
        });
    }
};
