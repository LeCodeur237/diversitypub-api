<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->hasIndex('ko_gagnant_participations', 'ko_gagnant_participations_taps_index')) {
            Schema::table('ko_gagnant_participations', function (Blueprint $table) {
                $table->index('taps');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('ko_gagnant_participations', 'ko_gagnant_participations_taps_index')) {
            Schema::table('ko_gagnant_participations', function (Blueprint $table) {
                $table->dropIndex('ko_gagnant_participations_taps_index');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('$table')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            $rows = DB::select(
                'SELECT COUNT(1) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [DB::getDatabaseName(), $table, $indexName]
            );

            return (int) ($rows[0]->aggregate ?? 0) > 0;
        }

        return false;
    }
};
