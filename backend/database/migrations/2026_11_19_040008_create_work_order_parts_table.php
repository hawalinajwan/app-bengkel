<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_parts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sparepart_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('qty');
            $table->unsignedInteger('price');
            $table->timestamps();
            $table->unique(['work_order_id', 'sparepart_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_parts');
    }
};
