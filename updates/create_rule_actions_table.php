<?php namespace RainLab\Notify\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateRuleActionsTable extends Migration
{
    public function up()
    {
        Schema::create('rainlab_notify_rule_actions', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('class_name')->nullable();
            $table->mediumText('config_data')->nullable();
            $table->integer('rule_host_id')->unsigned()->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rainlab_notify_rule_actions');
    }
}
