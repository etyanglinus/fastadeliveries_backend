<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('brandsite_domains', fn (Blueprint $table) => $table->timestamp('last_connected_at')->nullable());
        DB::table('brandsite_domains')->whereNotNull('activated_at')->update(['last_connected_at' => DB::raw('activated_at')]);
    }

    public function down(): void
    {
        Schema::table('brandsite_domains', fn (Blueprint $table) => $table->dropColumn('last_connected_at'));
    }
};
