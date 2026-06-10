<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ko_gagnant_participations', function (Blueprint $table) {
            $table->id();
            $table->string('game')->default('ko-gagnant');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('commune');
            $table->string('phone_number')->unique();
            $table->string('reseau');
            $table->unsignedSmallInteger('taps');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('won')->default(false);
            $table->string('prize_label')->nullable();
            $table->string('prize_type')->nullable();
            $table->string('prize_icon')->nullable();
            $table->string('wave_number')->nullable();
            $table->boolean('accepted_terms');
            $table->boolean('prize_claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->index('won');
            $table->index('taps');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ko_gagnant_participations');
    }
};
