<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('mechanic_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('queue_date');
            $table->unsignedSmallInteger('queue_number');
            $table->string('tracking_code', 8)->unique();
            $table->text('complaint')->nullable();
            $table->string('status')->default('menunggu');
            $table->text('public_note')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->timestamps();
            $table->unique(['queue_date', 'queue_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
