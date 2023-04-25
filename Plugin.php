<?php

namespace Sixgweb\ListSaver;

use Backend;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'ListSaver',
            'description' => 'No description provided yet...',
            'author' => 'Sixgweb',
            'icon' => 'icon-leaf'
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return [
            'sixgweb.listsaver.access' => [
                'tab' => 'List Saver',
                'label' => 'Access List Saver'
            ],
        ];
    }
}
