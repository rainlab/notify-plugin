<?php namespace RainLab\Notify\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateConditionsRulesTable extends Migration
{
    public function up()
    {
        Schema::create('rainlab_notify_rule_conditions', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('class_name')->nullable();
            $table->mediumText('config_data')->nullable();
            $table->string('condition_control_type', 100)->nullable();
            $table->string('rule_host_type', 100)->nullable();
            $table->integer('rule_host_id')->unsigned()->nullable()->index();
            $table->integer('rule_parent_id')->unsigned()->nullable()->index();
            $table->index(['rule_host_id', 'rule_host_type'], 'host_rule_id_type');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rainlab_notify_rule_conditions');
    }
}
