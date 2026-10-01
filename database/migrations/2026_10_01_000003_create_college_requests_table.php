<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->nullable();
            $table->string('college_name');
            $table->string('subject');
            $table->string('recipient_name');
            $table->date('letter_received_at');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index();
            $table->unsignedBigInteger('recorded_by');
            $table->unsignedBigInteger('signed_by')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->unsignedBigInteger('retrieved_by')->nullable();
            $table->timestamp('retrieved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_requests');
    }
};
