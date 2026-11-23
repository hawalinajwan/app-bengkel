<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->unsignedInteger('dp_amount')->default(0)->after('slot_time');
            $table->dateTime('dp_paid_at')->nullable()->after('dp_amount');
            $table->string('dp_method')->nullable()->after('dp_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['dp_amount', 'dp_paid_at', 'dp_method']);
        });
    }
};
