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
        Schema::create('property_models', function (Blueprint $table) {
            $table->id();

            // Photo - stored as string (file path or URL)
            $table->mediumText('photo')->nullable();

            // Listing type - e.g., 'Dijual' or 'Disewakan'
            $table->string('listing_type')->nullable();

            // Property type - e.g., 'Rumah', 'Apartemen'
            $table->string('property_type')->nullable();

            // Bedrooms and bathrooms - stored as string for flexibility
            $table->string('bedrooms')->nullable();
            $table->string('bathrooms')->nullable();

            // Address of the property
            $table->string('address')->nullable();

            // Square meters - stored as string
            $table->string('square_meters')->nullable();

            // Facilities - multiline text
            $table->text('facilities')->nullable();
            $table->text('title')->nullable();
            $table->text('harga')->nullable();

            $table->foreignId('user_id')
                ->nullable() // biar data lama yang belum punya user_id tetap valid
                ->constrained()
                ->onDelete('cascade');

            $table->string('property_status')->nullable();   // Baru/Bekas
            $table->float('safety_rank')->nullable();
            $table->float('rating')->nullable();
            $table->integer('review_count')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('map_url')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_models');
    }
};
