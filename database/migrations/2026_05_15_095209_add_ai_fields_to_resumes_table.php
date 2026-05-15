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
        Schema::table('resumes', function (Blueprint $table) {

            // AI Core outputs
            $table->integer('score')->nullable();

            // Structured AI data
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->text('summary')->nullable();

            // Full raw AI response
            $table->longText('raw_ai_response')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            //
        });
    }
};
