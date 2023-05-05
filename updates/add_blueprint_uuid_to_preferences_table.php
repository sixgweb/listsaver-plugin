<?php

namespace Sixgweb\ListSaver\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddBluePrintUuidToPreferencesTable extends Migration
{
    public function up()
    {
        Schema::table('sixgweb_listsaver_preferences', function ($table) {
            $table->string('blueprint_uuid')->nullable()->after('group');
        });
    }

    public function down()
    {
        Schema::table('sixgweb_listsaver_preferences', function ($table) {
            $table->dropColumn(['blueprint_uuid']);
        });
    }
}
