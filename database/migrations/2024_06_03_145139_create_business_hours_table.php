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
        Schema::create('business_hours', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('business_id'); 
        $table->string('day')->nullable(); 
        $table->string('open_a')->nullable();
        $table->string('close_a')->nullable();
        $table->string('open_b')->nullable();
        $table->string('close_b')->nullable();
        $table->timestamps();

      
        $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_hours');
    }
};
