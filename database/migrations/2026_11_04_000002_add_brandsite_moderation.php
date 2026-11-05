<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('brandsite_sites', fn (Blueprint $table) => $table->timestamp('suspended_at')->nullable());
        Schema::create('brandsite_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('brandsite_sites')->cascadeOnDelete();
            $table->string('actor_type', 16);
            $table->unsignedBigInteger('actor_id');
            $table->string('action', 24);
            $table->unsignedInteger('revision')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brandsite_events');
        Schema::table('brandsite_sites', fn (Blueprint $table) => $table->dropColumn('suspended_at'));
    }
};
