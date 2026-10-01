<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->unsignedInteger('reorder_level')->default(0)->after('max');
            $table->unsignedInteger('front_quantity')->default(0)->after('reorder_level');
            $table->unsignedInteger('warehouse_quantity')->default(0)->after('front_quantity');
        });

        DB::table('product_items')->update([
            'front_quantity' => DB::raw('quantity'),
            'warehouse_quantity' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->dropColumn([
                'reorder_level',
                'front_quantity',
                'warehouse_quantity',
            ]);
        });
    }
};
