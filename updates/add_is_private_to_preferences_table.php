<?php

namespace Sixgweb\ListSaver\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddIsPrivateToPreferencesTable extends Migration
{
    public function up()
    {
        Schema::table('sixgweb_listsaver_preferences', function ($table) {
            $table->boolean('is_private')->default(1)->after('backend_user_id');
        });
    }

    public function down()
    {
        Schema::table('sixgweb_listsaver_preferences', function ($table) {
            $table->dropColumn(['is_private']);
        });
    }
}
