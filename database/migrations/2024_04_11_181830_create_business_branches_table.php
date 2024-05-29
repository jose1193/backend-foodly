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
        Schema::create('business_branches', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_uuid')->unique();
            $table->string('branch_logo')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('branch_email')->nullable();
            $table->string('branch_phone')->nullable();
            $table->string('branch_about_us')->nullable();
            $table->string('branch_additional_info')->nullable();
            $table->string('branch_address')->nullable();
            $table->string('branch_zipcode')->nullable();
            $table->string('branch_city')->nullable();
            $table->string('branch_country')->nullable();
            $table->string('branch_website')->nullable();
            $table->double('branch_latitude', 10, 6)->nullable(); 
            $table->double('branch_longitude', 10, 6)->nullable(); 
            $table->foreignId('business_id')->constrained('businesses')->onUpdate('cascade')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_branches');
    }
};
