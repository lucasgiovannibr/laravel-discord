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
        Schema::create('discord_events', function (Blueprint $table) {
            $table->id();
            $table->string('guild_id');
            $table->string('channel_id');
            $table->string('message_id')->nullable();
            $table->string('creator_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->string('location')->nullable();
            $table->integer('max_participants')->default(0);
            $table->boolean('is_private')->default(false);
            $table->timestamps();
            
            $table->index(['guild_id']);
            $table->index(['start_time']);
        });

        Schema::create('discord_event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('discord_events')->onDelete('cascade');
            $table->string('user_id');
            $table->timestamp('joined_at')->nullable();
            $table->text('note')->nullable();
            $table->boolean('will_attend')->nullable();
            $table->timestamps();
            
            $table->unique(['event_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discord_event_participants');
        Schema::dropIfExists('discord_events');
    }
}; 