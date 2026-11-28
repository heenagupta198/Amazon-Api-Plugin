<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('volume')->nullable()->after('change_percent');
        });

        Schema::table('index_snapshots', function (Blueprint $table) {
            $table->unsignedBigInteger('total_volume')->nullable()->after('nifty_change');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn('volume');
        });

        Schema::table('index_snapshots', function (Blueprint $table) {
            $table->dropColumn('total_volume');
        });
    }
};