<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('booking_code', 8)->unique();
            $table->string('customer_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('plate_number');
            $table->string('vehicle_type');
            $table->string('brand');
            $table->string('model');
            $table->foreignId('service_id')->nullable()->constrained()->restrictOnDelete();
            $table->text('complaint')->nullable();
            $table->date('booking_date');
            $table->string('slot_time', 5);
            $table->string('status')->default('menunggu');
            $table->text('staff_note')->nullable();
            $table->foreignId('work_order_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->index(['booking_date', 'slot_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
