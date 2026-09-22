<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('confirmation_sent_at')->nullable()->after('booked_at');
        });

        Schema::table('passengers', function (Blueprint $table) {
            $table->unsignedTinyInteger('extra_bags')->default(0)->after('seat_number');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('confirmation_sent_at');
        });

        Schema::table('passengers', function (Blueprint $table) {
            $table->dropColumn('extra_bags');
        });
    }
};
