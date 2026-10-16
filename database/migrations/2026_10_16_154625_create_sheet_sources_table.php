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
        Schema::create('sheet_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('spreadsheet_id');
            $table->string('credentials_path')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique('spreadsheet_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheet_sources');
    }
};
