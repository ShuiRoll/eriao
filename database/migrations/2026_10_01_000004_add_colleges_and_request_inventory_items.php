<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('college_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_request_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        Schema::table('college_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('released_by')->nullable()->after('retrieved_at');
            $table->timestamp('released_at')->nullable()->after('released_by');
        });

        DB::table('college_requests')
            ->where('status', 'retrieved')
            ->update(['status' => 'released']);

        DB::table('college_requests')
            ->whereNotNull('retrieved_at')
            ->update([
                'released_by' => DB::raw('retrieved_by'),
                'released_at' => DB::raw('retrieved_at'),
            ]);
    }

    public function down(): void
    {
        DB::table('college_requests')
            ->where('status', 'released')
            ->update(['status' => 'retrieved']);

        Schema::table('college_requests', function (Blueprint $table) {
            $table->dropColumn(['released_by', 'released_at']);
        });

        Schema::dropIfExists('college_request_items');
        Schema::dropIfExists('colleges');
    }
};
