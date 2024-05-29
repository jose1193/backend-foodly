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
            $table->uuid('promotion_uuid')->unique();
            $table->string('promotion_title');
            $table->string('promotion_description')->nullable();
            //Time of promotion
            $table->string('promotion_start_date')->nullable();
            $table->string('promotion_end_date')->nullable();
            
            //type of promotion
            $table->string('promotion_type')->nullable();
            //promotion status
            $table->string('promotion_status')->nullable();
           
            
            //relation with business
            $table->foreignId('business_id')->constrained('businesses')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
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