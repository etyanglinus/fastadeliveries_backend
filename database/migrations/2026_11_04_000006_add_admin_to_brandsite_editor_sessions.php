<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('brandsite_editor_sessions', fn (Blueprint $table) => $table->unsignedBigInteger('admin_id')->nullable()->index()); }
    public function down(): void { Schema::table('brandsite_editor_sessions', function (Blueprint $table) { $table->dropIndex(['admin_id']); $table->dropColumn('admin_id'); }); }
};
