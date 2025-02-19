<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up()
    {
        Schema::create('business_menu_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_menu_id')->constrained('business_menus')->onDelete('cascade');
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate favorites
            $table->unique(['user_id', 'business_menu_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_menu_favorites');
    }
};
