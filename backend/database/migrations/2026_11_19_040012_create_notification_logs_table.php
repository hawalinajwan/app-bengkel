<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('notification_logs', function (Blueprint $table): void { $table->id(); $table->string('type'); $table->string('channel'); $table->string('recipient'); $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete(); $table->string('status'); $table->text('error')->nullable(); $table->timestamp('sent_at')->nullable(); $table->timestamps(); }); } public function down(): void { Schema::dropIfExists('notification_logs'); } };