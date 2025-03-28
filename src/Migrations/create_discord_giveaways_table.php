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
        Schema::create('discord_giveaways', function (Blueprint $table) {
            $table->id();
            $table->string('guild_id');
            $table->string('channel_id');
            $table->string('message_id');
            $table->string('creator_id');
            $table->string('prize');
            $table->text('description')->nullable();
            $table->integer('winners_count')->default(1);
            $table->timestamp('ends_at');
            $table->boolean('ended')->default(false);
            $table->json('winners')->nullable();
            $table->timestamps();
            
            $table->index(['guild_id', 'ended']);
            $table->index(['message_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discord_giveaways');
    }
}; 