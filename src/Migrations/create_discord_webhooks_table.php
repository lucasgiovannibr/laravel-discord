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
        Schema::create('discord_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('guild_id');
            $table->string('channel_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('token', 64)->unique();
            $table->string('secret', 32)->nullable();
            $table->string('created_by');
            $table->timestamp('expires_at')->nullable();
            $table->integer('rate_limit')->default(60);
            $table->boolean('is_disabled')->default(false);
            $table->timestamps();
            
            $table->index(['guild_id', 'is_disabled']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discord_webhooks');
    }
}; 