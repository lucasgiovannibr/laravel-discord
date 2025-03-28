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
        Schema::create('discord_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('guild_id');
            $table->string('channel_id');
            $table->string('user_id');
            $table->string('status')->default('open');
            $table->string('subject')->nullable();
            $table->timestamp('last_activity')->nullable();
            $table->string('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('transcript_url')->nullable();
            $table->timestamps();
            
            $table->index(['guild_id', 'status']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discord_tickets');
    }
}; 