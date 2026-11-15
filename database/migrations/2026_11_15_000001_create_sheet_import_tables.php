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
            $table->string('spreadsheet_id')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('sheet_tabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_source_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('header_row')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['sheet_source_id', 'name']);
        });

        Schema::create('table_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_tab_id')->constrained()->cascadeOnDelete();
            $table->string('destination_table', 64);
            $table->json('unique_columns');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['sheet_tab_id', 'destination_table']);
        });

        Schema::create('column_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_mapping_id')->constrained()->cascadeOnDelete();
            $table->string('source_column', 64);
            $table->string('destination_column', 64);
            $table->string('data_type', 32);
            $table->timestamps();

            $table->unique(['table_mapping_id', 'source_column']);
            $table->unique(['table_mapping_id', 'destination_column']);
        });

        Schema::create('import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 32)->index();
            $table->string('trigger', 32);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('import_run_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_mapping_id')->nullable()->constrained()->nullOnDelete();
            $table->string('destination_table', 64)->nullable();
            $table->unsignedInteger('rows_read')->default(0);
            $table->unsignedInteger('rows_written')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->json('columns_added')->nullable();
            $table->string('status', 32)->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_run_entries');
        Schema::dropIfExists('import_runs');
        Schema::dropIfExists('column_mappings');
        Schema::dropIfExists('table_mappings');
        Schema::dropIfExists('sheet_tabs');
        Schema::dropIfExists('sheet_sources');
    }
};
