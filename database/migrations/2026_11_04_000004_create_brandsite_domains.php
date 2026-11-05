<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brandsite_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('brandsite_sites')->cascadeOnDelete();
            $table->string('hostname', 253)->unique();
            $table->char('verification_token', 64);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brandsite_domains');
    }
};
