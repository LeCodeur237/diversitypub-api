<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalty_participations', function (Blueprint $table) {
            $table->id();
            $table->uuid('play_token')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number')->unique();
            $table->string('team', 20);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('goals')->default(0);
            $table->boolean('completed')->default(false);
            $table->string('prize_label')->nullable();
            $table->boolean('accepted_terms');
            $table->timestamps();

            $table->index(['completed', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalty_participations');
    }
};
