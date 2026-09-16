<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roulette_participations', function (Blueprint $table) {
            $table->id();
            $table->string('game')->default('roulette');
            $table->string('device_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->unsignedTinyInteger('age');
            $table->string('phone_number');
            $table->boolean('won')->default(false);
            $table->string('prize_label')->nullable();
            $table->timestamps();

            $table->index('phone_number');
            $table->index('won');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roulette_participations');
    }
};
