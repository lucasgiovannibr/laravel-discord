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
        Schema::create('discord_reaction_roles', function (Blueprint $table) {
            $table->id();
            $table->string('guild_id');
            $table->string('channel_id');
            $table->string('message_id');
            $table->string('emoji');
            $table->string('role_id');
            $table->string('group_id')->nullable();
            $table->string('created_by');
            $table->boolean('is_unique')->default(false);
            $table->timestamps();
            
            $table->index(['message_id', 'emoji']);
            $table->index(['guild_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discord_reaction_roles');
    }
}; 