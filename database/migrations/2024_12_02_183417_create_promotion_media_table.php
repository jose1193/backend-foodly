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
        Schema::create('promotion_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('business_promo_media_url');
            $table->string('media_type');
            $table->foreignId('business_promo_item_id')->constrained('promotions')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_media');
    }
};
