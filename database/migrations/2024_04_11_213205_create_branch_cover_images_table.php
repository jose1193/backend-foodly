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
        Schema::create('branch_cover_images', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_image_uuid')->unique();
            $table->string('branch_image_path');
            $table->foreignId('branch_id')->constrained('business_branches')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_cover_images');
    }
};
