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
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('template_type')->default('default');
            $table->string('theme_color')->default('#E83434');
            $table->string('banner_image')->nullable();
            $table->text('video_embed_code')->nullable();
            $table->json('features')->nullable();
            $table->json('testimonials')->nullable();
            $table->text('custom_css')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('views_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
