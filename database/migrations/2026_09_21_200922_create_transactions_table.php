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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->biginteger('employee_id')->unsigned();

            $table->string('first_name');
            $table->string('last_name');

            $table->string('payment_method');
            $table->string('reference_num')->nullable();

            $table->string('discount_category')->nullable();
            $table->float('discount_price')->default(0);
            $table->float('tax')->default(0);
            $table->float('total_amount')->default(0);
            $table->float('cash')->default(0);
            $table->float('change')->default(0);

            $table->text('notes')->nullable();

            $table->enum('status', ['completed', 'paid', 'pending', 'refunded', 'void'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
