<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brandsite_sites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->unique();
            $table->json('draft')->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->unsignedInteger('published_revision')->nullable();
            $table->timestamps();
        });
        Schema::create('brandsite_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('brandsite_sites')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->json('configuration');
            $table->timestamp('created_at');
            $table->unique(['site_id', 'revision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brandsite_publications');
        Schema::dropIfExists('brandsite_sites');
    }
};
