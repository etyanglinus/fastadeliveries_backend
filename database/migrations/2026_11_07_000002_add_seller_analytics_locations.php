<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('seller_analytics_events', function (Blueprint $table) {
            $table->char('country', 2)->nullable();
            $table->string('city', 120)->nullable();
        });
    }
    public function down(): void {
        Schema::table('seller_analytics_events', fn (Blueprint $table) => $table->dropColumn(['country', 'city']));
    }
};
