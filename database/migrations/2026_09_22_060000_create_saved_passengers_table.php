<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 10)->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 16)->nullable();
            $table->string('passport_number')->nullable();
            $table->string('nationality')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'first_name', 'last_name', 'date_of_birth'], 'saved_pax_identity_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_passengers');
    }
};
