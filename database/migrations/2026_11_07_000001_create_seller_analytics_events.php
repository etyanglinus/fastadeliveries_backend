<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('seller_analytics_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->unsignedBigInteger('store_id')->index();
            $table->string('channel', 24);
            $table->string('event', 24);
            $table->char('visitor', 64);
            $table->char('session', 64);
            $table->string('device', 12);
            $table->string('referrer', 253)->nullable();
            $table->timestamp('created_at')->index();
            $table->index(['store_id', 'created_at', 'channel']);
        });
    }
    public function down(): void { Schema::dropIfExists('seller_analytics_events'); }
};
