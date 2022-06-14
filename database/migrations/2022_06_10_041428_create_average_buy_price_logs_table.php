<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('average_buy_price_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id');
            $table->float('prev_avg_buy_price');
            $table->float('new_avg_buy_price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('average_buy_price_logs');
    }
};
