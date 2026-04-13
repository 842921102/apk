<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 将原写入为 today_eat + request_payload.source=mp-custom-wizard 的历史，规范为 source_type=custom_wizard。
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::table('recipe_histories')
                ->where('source_type', 'today_eat')
                ->whereRaw("json_extract(request_payload, '$.source') = ?", ['mp-custom-wizard'])
                ->update(['source_type' => 'custom_wizard']);

            return;
        }

        DB::table('recipe_histories')
            ->where('source_type', 'today_eat')
            ->where('request_payload->source', 'mp-custom-wizard')
            ->update(['source_type' => 'custom_wizard']);
    }

    public function down(): void
    {
        DB::table('recipe_histories')
            ->where('source_type', 'custom_wizard')
            ->update(['source_type' => 'today_eat']);
    }
};
