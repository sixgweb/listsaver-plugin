<?php

namespace Sixgweb\ListSaver\Models;

use Model;

/**
 * Preference Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Preference extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'sixgweb_listsaver_preferences';

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public $fillable = [
        'name',
        'namespace',
        'group',
        'list',
        'filter',
    ];

    public $jsonable = [
        'list',
        'filter',
    ];
}
