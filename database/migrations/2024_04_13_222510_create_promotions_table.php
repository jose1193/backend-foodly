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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('sub_title')->nullable();
            $table->string('description')->nullable();
            //Time of promotion
            $table->string('start_date')->nullable();
            $table->string('expire_date')->nullable();
            
            $table->json('versions')->nullable();
            $table->json('prices')->nullable();
            $table->integer('favorites_count')->default(0);
            $table->boolean('available')->default(true);
           
            
            //relation with business
            $table->foreignId('business_id')->constrained('businesses')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};