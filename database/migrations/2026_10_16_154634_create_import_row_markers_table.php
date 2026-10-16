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
        Schema::create('import_row_markers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_tab_id')->constrained()->cascadeOnDelete();
            $table->string('table_name');
            $table->unsignedInteger('row_index');
            $table->string('row_hash', 64);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->timestamps();

            $table->unique(['sheet_tab_id', 'table_name', 'row_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_row_markers');
    }
};
