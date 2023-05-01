<?php

namespace Sixgweb\ListSaver;

use Event;
use System\Classes\PluginBase;

use function Ramsey\Uuid\v1;

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
            'description' => 'Adds ability to save the current ListController filter and list setup values',
            'author' => 'Sixgweb',
            'icon' => 'icon-list'
        ];
    }

    public function boot()
    {
        Event::listen('system.extendConfigFile', function ($path, $config) {
            if (strpos($path, 'config_list.yaml')) {
                $config['filter'] = $config['filter'] ?? [];
                return $config;
            }
        });

        Event::listen('backend.filter.extendScopes', function ($filterWidget) {

            //Check if is ListController
            if (!$filterWidget->getController()->methodExists('listExtendColumns')) {
                return;
            }

            $dependsOn = [];

            if ($allScopes = $filterWidget->getScopes()) {
                $dependsOn = array_keys($allScopes);
            }

            $filterWidget->addScopes([
                'listsaver' => [
                    'label' => 'List Saver',
                    'type' => 'listsaver',
                    'dependsOn' => $dependsOn,
                ],
            ]);
        }, -1);
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return [
            'sixgweb.listsaver.access' => [
                'tab' => 'List Saver',
                'label' => 'Access Saved Lists'
            ],
            'sixgweb.listsaver.manage' => [
                'tab' => 'List Saver',
                'label' => 'Manage Lists'
            ],
        ];
    }

    public function registerFilterWidgets()
    {
        return [
            \Sixgweb\ListSaver\FilterWidgets\ListSaver::class => 'listsaver',
        ];
    }
}
