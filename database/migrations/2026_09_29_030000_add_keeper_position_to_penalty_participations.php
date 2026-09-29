<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penalty_participations', function (Blueprint $table) {
            $table->decimal('keeper_x', 5, 2)->default(50)->after('goals');
            $table->decimal('keeper_y', 5, 2)->default(30)->after('keeper_x');
        });
    }

    public function down(): void
    {
        Schema::table('penalty_participations', function (Blueprint $table) {
            $table->dropColumn(['keeper_x', 'keeper_y']);
        });
    }
};
