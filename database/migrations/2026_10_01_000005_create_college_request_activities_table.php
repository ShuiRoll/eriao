<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_request_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_request_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('action');
            $table->text('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        DB::table('college_requests')
            ->whereNotNull('recorded_by')
            ->orderBy('id')
            ->chunkById(100, function ($requests) {
                foreach ($requests as $request) {
                    DB::table('college_request_activities')->insert([
                        'college_request_id' => $request->id,
                        'user_id' => $request->recorded_by,
                        'action' => 'Letter recorded',
                        'details' => 'Existing request imported into the activity log.',
                        'created_at' => $request->created_at,
                    ]);

                    if ($request->signed_by && $request->signed_at) {
                        DB::table('college_request_activities')->insert([
                            'college_request_id' => $request->id,
                            'user_id' => $request->signed_by,
                            'action' => 'Request signed',
                            'details' => 'Existing signature activity imported.',
                            'created_at' => $request->signed_at,
                        ]);
                    }

                    if ($request->released_by && $request->released_at) {
                        DB::table('college_request_activities')->insert([
                            'college_request_id' => $request->id,
                            'user_id' => $request->released_by,
                            'action' => 'Items released to college',
                            'details' => 'Existing release activity imported.',
                            'created_at' => $request->released_at,
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_request_activities');
    }
};
