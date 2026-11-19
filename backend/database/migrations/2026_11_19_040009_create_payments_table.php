<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('payments', function (Blueprint $table): void { $table->id(); $table->foreignId('work_order_id')->constrained()->restrictOnDelete(); $table->string('method'); $table->unsignedInteger('amount'); $table->dateTime('paid_at'); $table->timestamps(); }); } public function down(): void { Schema::dropIfExists('payments'); } };
