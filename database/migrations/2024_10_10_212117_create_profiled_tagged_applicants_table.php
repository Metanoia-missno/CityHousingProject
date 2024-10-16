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
        Schema::create('profiled_tagged_applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelter_applicant_id')->constrained('shelter_applicants')->onDelete('cascade');
            $table->foreignId('civil_status_id')->constrained('civil_statuses')->onDelete('cascade');
            $table->foreignId('religion_id')->constrained('religions')->onDelete('cascade');
            $table->foreignId('address_id')->constrained('addresses')->onDelete('cascade');
            $table->foreignId('government_program_id')->constrained('government_programs')->onDelete('cascade');
            $table->foreignId('tribe_id')->constrained('tribes')->onDelete('cascade');
            $table->foreignId('living_situation_id')->constrained('living_situations')->onDelete('cascade');
            $table->foreignId('case_specification_id')->nullable()->constrained('case_specifications')->onDelete('cascade');
            $table->text('living_situation_case_specification')->nullable();
            $table->date('date_of_birth');
            $table->char('sex', 6);
            $table->string('occupation', 255);
            $table->integer('year_of_residency');
            $table->string('contact_number');
            $table->text('house_no_or_street_name')->nullable();
            $table->dateTime('date_tagged');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiled_tagged_applicants');
    }
};
