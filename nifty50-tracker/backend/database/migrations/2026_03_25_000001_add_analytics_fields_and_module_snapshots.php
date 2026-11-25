<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('avg_volume_20d')->nullable()->after('volume');
            $table->decimal('unusual_volume_ratio', 10, 4)->nullable()->after('avg_volume_20d');
        });

        Schema::create('module_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('module_key', 64)->unique();
            $table->json('payload');
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['avg_volume_20d', 'unusual_volume_ratio']);
        });
        Schema::dropIfExists('module_snapshots');
    }
};