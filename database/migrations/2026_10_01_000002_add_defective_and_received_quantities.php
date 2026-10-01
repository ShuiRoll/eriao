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
            $table->unsignedInteger('defective_quantity')->default(0)->after('warehouse_quantity');
        });

        Schema::table('product_order_items', function (Blueprint $table) {
            $table->unsignedInteger('received_quantity')->default(0)->after('quantity');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE product_items MODIFY status ENUM('available', 'unavailable', 'out_of_stock', 'defective') NOT NULL DEFAULT 'available'");
            DB::statement("ALTER TABLE purchase_order MODIFY status ENUM('completed', 'received', 'incomplete', 'approved', 'pending', 'rejected', 'cancelled', 'archived') NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->dropColumn('defective_quantity');
        });

        Schema::table('product_order_items', function (Blueprint $table) {
            $table->dropColumn('received_quantity');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE product_items MODIFY status ENUM('available', 'unavailable', 'out_of_stock') NOT NULL DEFAULT 'available'");
            DB::statement("ALTER TABLE purchase_order MODIFY status ENUM('completed', 'received', 'approved', 'pending', 'rejected') NOT NULL");
        }
    }
};
