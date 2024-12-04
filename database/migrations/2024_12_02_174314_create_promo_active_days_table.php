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
        Schema::create('promo_active_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('day_0')->default(false);
            $table->boolean('day_1')->default(false);
            $table->boolean('day_2')->default(false);
            $table->boolean('day_3')->default(false);
            $table->boolean('day_4')->default(false);
            $table->boolean('day_5')->default(false);
            $table->boolean('day_6')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_active_days');
    }
};
