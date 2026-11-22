<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol', 32)->unique();
            $table->string('isin', 24)->nullable();
            $table->string('industry')->nullable();
            $table->unsignedBigInteger('security_id')->nullable()->index();
            $table->decimal('weightage', 8, 4)->nullable();
            $table->decimal('previous_close', 14, 4)->nullable();
            $table->decimal('ltp', 14, 4)->nullable();
            $table->decimal('change_amount', 14, 4)->nullable();
            $table->decimal('change_percent', 10, 4)->nullable();
            $table->decimal('positive_points', 12, 4)->default(0);
            $table->decimal('negative_points', 12, 4)->default(0);
            $table->decimal('net_contribution', 12, 4)->default(0);
            $table->timestamp('quotes_updated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};