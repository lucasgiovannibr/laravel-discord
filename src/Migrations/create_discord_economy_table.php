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
        Schema::create('discord_economy', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('guild_id');
            $table->integer('balance')->default(0);
            $table->integer('total_earned')->default(0);
            $table->integer('total_spent')->default(0);
            $table->timestamp('last_daily')->nullable();
            $table->integer('streak')->default(0);
            $table->timestamps();
            
            $table->unique(['user_id', 'guild_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discord_economy');
    }
}; 