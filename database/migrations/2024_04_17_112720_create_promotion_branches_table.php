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
        Schema::create('promotion_branches', function (Blueprint $table) {
            $table->id();
            $table->uuid('promotion_branch_uuid')->unique();
            $table->string('promotion_branch_title');
            $table->string('promotion_branch_description')->nullable();
            $table->string('promotion_branch_start_date')->nullable();
            $table->string('promotion_branch_end_date')->nullable();
            $table->string('promotion_branch_type')->nullable();
            $table->string('promotion_branch_status')->nullable();
            $table->foreignId('branch_id')->constrained('business_branches')->onUpdate('cascade')->onDelete('cascade');
            //softdelete
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_branches');
    }
};
