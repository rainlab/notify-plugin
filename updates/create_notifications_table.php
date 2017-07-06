<?php namespace RainLab\Notify\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('rainlab_notify_notifications', function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_type');
            $table->morphs('notifiable');
            $table->string('icon')->nullable();
            $table->string('type')->nullable();
            $table->text('body')->nullable();
            $table->mediumText('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rainlab_notify_notifications');
    }
}
