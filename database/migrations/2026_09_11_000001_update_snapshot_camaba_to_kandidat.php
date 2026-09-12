<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('akd_mahasiswa_draft')) {
            return;
        }

        DB::table('akd_mahasiswa_draft')
            ->whereRaw("JSON_EXTRACT(snapshot_json, '$.camaba') IS NOT NULL")
            ->orderBy('draft_id')
            ->each(function ($row) {
                $snapshot = json_decode($row->snapshot_json, true);
                if (isset($snapshot['camaba'])) {
                    $snapshot['kandidat'] = $snapshot['camaba'];
                    unset($snapshot['camaba']);
                    DB::table('akd_mahasiswa_draft')
                        ->where('draft_id', $row->draft_id)
                        ->update(['snapshot_json' => json_encode($snapshot)]);
                }
            });
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('akd_mahasiswa_draft')) {
            return;
        }

        DB::table('akd_mahasiswa_draft')
            ->whereRaw("JSON_EXTRACT(snapshot_json, '$.kandidat') IS NOT NULL")
            ->orderBy('draft_id')
            ->each(function ($row) {
                $snapshot = json_decode($row->snapshot_json, true);
                if (isset($snapshot['kandidat'])) {
                    $snapshot['camaba'] = $snapshot['kandidat'];
                    unset($snapshot['kandidat']);
                    DB::table('akd_mahasiswa_draft')
                        ->where('draft_id', $row->draft_id)
                        ->update(['snapshot_json' => json_encode($snapshot)]);
                }
            });
    }
};
