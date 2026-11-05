<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('brandsite_domains', function (Blueprint $table) {
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('routing_verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('brandsite_domains', fn (Blueprint $table) => $table->dropColumn(['activated_at', 'routing_verified_at']));
    }
};
