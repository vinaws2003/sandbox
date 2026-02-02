<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('value', 16, 4);
            $table->json('metadata')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['node_id', 'type', 'recorded_at']);
            $table->index('recorded_at');
            $table->index(['type', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
