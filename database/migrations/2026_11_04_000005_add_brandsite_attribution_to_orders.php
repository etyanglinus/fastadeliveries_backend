<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->unsignedBigInteger('brandsite_site_id')->nullable()->index());
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['brandsite_site_id']);
            $table->dropColumn('brandsite_site_id');
        });
    }
};
