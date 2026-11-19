<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('work_order_status_logs', function (Blueprint $table): void { $table->id(); $table->foreignId('work_order_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('from_status')->nullable(); $table->string('to_status'); $table->timestamp('created_at')->useCurrent(); }); } public function down(): void { Schema::dropIfExists('work_order_status_logs'); } };
