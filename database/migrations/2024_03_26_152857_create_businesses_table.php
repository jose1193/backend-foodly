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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->uuid('business_uuid')->unique();
            $table->string('business_logo')->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_email')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('business_address')->nullable();
            $table->string('business_zipcode')->nullable();
            $table->string('business_city')->nullable();
            $table->string('business_country')->nullable();
            $table->string('business_website')->nullable();
            $table->string('business_about_us')->nullable();
            $table->string('business_additional_info')->nullable();
           
            $table->double('business_latitude', 10, 6)->nullable(); 
            $table->double('business_longitude', 10, 6)->nullable(); 
            $table->foreignId('category_id')->constrained('categories')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
