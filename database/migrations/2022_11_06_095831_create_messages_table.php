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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->bigInteger('user_id')->unsigned();
            $table->string('message_id')->unique();
            $table->longText('message');
            $table->string('net_values')->nullable()->default(null);
            $table->string('conversation_id')->index();
            $table->string('channelId')->index(); 
            $table->string('date');
            $table->string('time');
            $table->string('conversationType'); 

            $table->foreign('user_id')
                ->on('teams_users')
                ->references('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
};
