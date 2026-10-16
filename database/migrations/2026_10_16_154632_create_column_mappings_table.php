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
        Schema::create('column_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_mapping_id')->constrained()->cascadeOnDelete();
            $table->string('source_column');
            $table->string('destination_column');
            $table->string('data_type');
            $table->boolean('nullable')->default(true);
            $table->string('default_value')->nullable();
            $table->timestamps();

            $table->unique(['import_mapping_id', 'source_column']);
            $table->unique(['import_mapping_id', 'destination_column']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('column_mappings');
    }
};
