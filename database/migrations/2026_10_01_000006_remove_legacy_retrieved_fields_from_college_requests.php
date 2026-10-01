<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('college_requests', function (Blueprint $table) {
            $table->dropColumn(['retrieved_by', 'retrieved_at']);
        });
    }

    public function down(): void
    {
        Schema::table('college_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('retrieved_by')->nullable()->after('signed_at');
            $table->timestamp('retrieved_at')->nullable()->after('retrieved_by');
        });

        DB::table('college_requests')
            ->whereNotNull('released_at')
            ->update([
                'retrieved_by' => DB::raw('released_by'),
                'retrieved_at' => DB::raw('released_at'),
            ]);
    }
};
