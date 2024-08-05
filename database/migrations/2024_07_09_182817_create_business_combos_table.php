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
        Schema::create('business_combos', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid')->unique();
        $table->foreignId('business_menu_id')->constrained('business_menus')->onUpdate('cascade')->onDelete('cascade');
        $table->string('name');
        $table->string('description')->nullable();
        $table->text('versions')->nullable();
        $table->text('prices')->nullable();
        $table->integer('favorites_count')->default(0);
        $table->boolean('available')->default(true);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_combos');
    }
};
