<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produit', function (Blueprint $table) {
            $table->boolean('promo_enabled')->default(false)->after('pv_3');
            $table->dateTime('promo_start_at')->nullable()->after('promo_enabled');
            $table->dateTime('promo_end_at')->nullable()->after('promo_start_at');
            $table->unsignedInteger('promo_quantity')->nullable()->after('promo_end_at');
            $table->decimal('promo_price', 10, 2)->nullable()->after('promo_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('produit', function (Blueprint $table) {
            $table->dropColumn([
                'promo_enabled',
                'promo_start_at',
                'promo_end_at',
                'promo_quantity',
                'promo_price',
            ]);
        });
    }
};
