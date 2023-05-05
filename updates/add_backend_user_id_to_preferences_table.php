<?php

namespace Sixgweb\ListSaver\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddBackendUserIdToPreferencesTable extends Migration
{
    public function up()
    {
        Schema::table('sixgweb_listsaver_preferences', function ($table) {
            $table->integer('backend_user_id')->unsigned()->nullable()->after('group');
        });
    }

    public function down()
    {
        Schema::table('sixgweb_listsaver_preferences', function ($table) {
            $table->dropColumn(['backend_user_id']);
        });
    }
}
