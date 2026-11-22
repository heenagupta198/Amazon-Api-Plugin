<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('index_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('trade_date')->index();
            $table->decimal('nifty_value', 14, 4)->nullable();
            $table->decimal('nifty_previous_close', 14, 4)->nullable();
            $table->decimal('nifty_change', 12, 4)->nullable();
            $table->decimal('total_positive', 12, 4)->default(0);
            $table->decimal('total_negative', 12, 4)->default(0);
            $table->decimal('net_contribution', 12, 4)->default(0);
            $table->unsignedSmallInteger('advancers')->default(0);
            $table->unsignedSmallInteger('decliners')->default(0);
            $table->unsignedSmallInteger('unchanged')->default(0);
            $table->string('data_source', 32)->default('dhan');
            $table->boolean('is_live')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('index_snapshots');
    }
};