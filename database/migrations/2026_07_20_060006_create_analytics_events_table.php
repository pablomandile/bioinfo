<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('block_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->char('ip_hash', 64)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('referrer_host')->nullable();
            $table->json('utm')->nullable();
            $table->text('target_url')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['page_id', 'type', 'created_at']);
            $table->index('block_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
