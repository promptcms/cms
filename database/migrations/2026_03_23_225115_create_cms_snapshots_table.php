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
        Schema::create('cms_snapshots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('label');
            $table->text('description')->nullable();
            $table->longText('snapshot'); // JSON: all nodes, meta, menu_items, settings
            $table->string('created_by')->nullable(); // user email or 'ai'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_snapshots');
    }
};
