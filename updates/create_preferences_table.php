<?php

namespace Sixgweb\ListSaver\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreatePreferencesTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('sixgweb_listsaver_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('namespace', 100);
            $table->string('group', 50);
            $table->text('list')->nullable();
            $table->text('filter')->nullable();
            $table->integer('site_id')->nullable()->unsigned();
            $table->integer('site_root_id')->nullable()->unsigned();
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('sixgweb_listsaver_preferences');
    }
};
