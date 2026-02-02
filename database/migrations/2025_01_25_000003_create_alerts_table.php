<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('metric_type');
            $table->enum('condition', ['gt', 'gte', 'lt', 'lte', 'eq', 'neq']);
            $table->decimal('threshold', 16, 4);
            $table->enum('notification_channel', ['mail', 'slack', 'database']);
            $table->string('notification_target')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('cooldown_minutes')->default(15);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'metric_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
