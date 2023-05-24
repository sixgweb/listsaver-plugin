<?php

namespace Sixgweb\ListSaver\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddSearchTermToPreferencesTable extends Migration
{
    public function up()
    {
        Schema::table('sixgweb_listsaver_preferences', function ($table) {
            $table->string('search_term')->nullable()->after('filter');
        });
    }

    public function down()
    {
        Schema::table('sixgweb_listsaver_preferences', function ($table) {
            $table->dropColumn(['search_term']);
        });
    }
}
