<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('blocks')->nullOnDelete();
            $table->string('type');
            $table->json('data')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->string('size')->default('md');
            $table->unsignedTinyInteger('grid_col_span')->default(1);
            $table->unsignedTinyInteger('grid_row_span')->default(1);
            $table->boolean('is_visible')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['page_id', 'position']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
