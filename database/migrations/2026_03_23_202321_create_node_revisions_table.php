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
        Schema::create('node_revisions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('node_id');
            $table->longText('snapshot');
            $table->text('prompt')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('node_id')->references('id')->on('nodes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('node_revisions');
    }
};
