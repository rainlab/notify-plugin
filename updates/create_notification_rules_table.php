<?php namespace RainLab\Notify\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateNotificationRulesTable extends Migration
{
    public function up()
    {
        Schema::create('rainlab_notify_notification_rules', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('code')->index()->nullable();
            $table->string('class_name')->nullable();
            $table->text('description')->nullable();
            $table->mediumText('config_data')->nullable();
            $table->mediumText('condition_data')->nullable();
            $table->boolean('is_enabled')->default(0);
            $table->boolean('is_custom')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rainlab_notify_notification_rules');
    }
}
