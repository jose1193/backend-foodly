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
        Schema::create('promotion_branch_images', function (Blueprint $table) {
            $table->id();
            $table->uuid('promotion_branch_image_uuid')->unique();
            $table->string('promotion_branch_image_path');
            $table->foreignId('promotion_branch_id')->constrained('promotion_branches')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_branch_images');
    }
};
