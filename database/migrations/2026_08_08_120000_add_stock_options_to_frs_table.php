<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frs', function (Blueprint $table) {
            $table->tinyInteger('show_stock')->default(1)->after('tiktok_pixel_id');
            $table->tinyInteger('allow_out_of_stock_orders')->default(0)->after('show_stock');
            $table->tinyInteger('show_null_stock')->default(1)->after('allow_out_of_stock_orders');
        });
    }

    public function down(): void
    {
        Schema::table('frs', function (Blueprint $table) {
            $table->dropColumn(['show_stock', 'allow_out_of_stock_orders', 'show_null_stock']);
        });
    }
};
