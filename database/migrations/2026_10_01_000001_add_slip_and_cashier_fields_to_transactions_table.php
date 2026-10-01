<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('id_number')->nullable()->after('employee_id');
            $table->unsignedBigInteger('cashier_id')->nullable()->after('employee_id');
            $table->timestamp('paid_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'id_number',
                'cashier_id',
                'paid_at',
            ]);
        });
    }
};
