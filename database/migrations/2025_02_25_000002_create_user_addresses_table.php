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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('address');
            $table->string('city');
            $table->string('country');
            $table->string('zip_code');
            $table->double('latitude', 10, 6)->nullable();
            $table->double('longitude', 10, 6)->nullable();
            $table->foreignId('address_label_id')->constrained('address_labels')->onDelete('cascade');
            $table->boolean('principal')->default(false); // Only one can be true per user
            $table->timestamps();
            
            // Simple index for performance
            $table->index(['user_id', 'principal']);
            // Ensure user can't have duplicate labels (e.g., two "Home" addresses)
            $table->unique(['user_id', 'address_label_id'], 'unique_user_address_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
}; 