<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('menu_id');
            $table->string('label');
            $table->string('url')->nullable();
            $table->ulid('node_id')->nullable();
            $table->ulid('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('target')->default('_self');
            $table->timestamps();

            $table->foreign('menu_id')->references('id')->on('nodes')->cascadeOnDelete();
            $table->foreign('node_id')->references('id')->on('nodes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
