<?php

namespace Sixgweb\ListSaver;

use Event;
use System\Classes\PluginBase;
use Sixgweb\ListSaver\Models\Preference;

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
        $this->extendListControllerConfig();
        $this->extendFilterScopesBefore();
        $this->extendFilterScopes();
    }

    /**
     * Register permissions
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'sixgweb.listsaver.access' => [
                'tab' => 'List Saver',
                'label' => 'Access List Saver'
            ],
            'sixgweb.listsaver.share' => [
                'tab' => 'List Saver',
                'label' => 'Share Lists (if enabled)'
            ],
            'sixgweb.listsaver.settings' => [
                'tab' => 'List Saver',
                'label' => 'Manage Settings'
            ],
        ];
    }

    /**
     * Register listsaver filter widget
     *
     * @return array
     */
    public function registerFilterWidgets()
    {
        return [
            \Sixgweb\ListSaver\FilterWidgets\ListSaver::class => 'listsaver',
        ];
    }

    /**
     * Register settings
     *
     * @return array
     */
    public function registerSettings()
    {
        return [
            'settings' => [
                'label' => 'ListSaver',
                'description' => 'Manage list saver settings.',
                'category' => 'ListSaver',
                'icon' => 'icon-list',
                'class' => \Sixgweb\ListSaver\Models\Settings::class,
                'permissions' => ['sixgweb.listsaver.settings'],
            ]
        ];
    }

    /**
     * Some controllers may not have a filter value defined but do allow
     * list setup.  This forces the filter to appear.
     *
     * @return void
     */
    protected function extendListControllerConfig()
    {
        Event::listen('system.extendConfigFile', function ($path, $config) {
            if (strpos($path, 'config_list.yaml')) {
                $showSetup = $config['showSetup'] ?? false;
                $hasFilter = isset($config['filter']);

                if (!$showSetup && !$hasFilter) {
                    return $config;
                }

                $config['filter'] = $config['filter'] ?? [];
                return $config;
            }
        });
    }

    /**
     * This is a workaround to set scopevalues in the session 
     * before other plugins reference them.  We remove the scope
     * to allow the filter widget to correctly handle the scope type
     * when added via the listcontroller or 3rd party extensions.
     *
     * @return void
     */
    protected function extendFilterScopesBefore()
    {
        Event::listen('backend.filter.extendScopesBefore', function ($filterWidget) {
            //Not applying a listsaver list
            if (!post('scopeName') == 'listsaver') {
                return;
            }

            //No listsaver preference id posted
            if (!$id = post('list_saver_preference')) {
                return;
            }

            //Preference not found
            if (!$preference = Preference::find($id)) {
                return;
            }

            //Loop preference filters, add, push value to session and remove
            foreach ($preference->filter as $key => $value) {
                $filterWidget->addScopes([
                    $key => [
                        'label' => $key,
                    ],
                ]);
                $scope = $filterWidget->getScope($key);
                $filterWidget->putScopeValue($scope, $value);
                $filterWidget->removeScope($key);
            }
        });
    }

    /**
     * Dynamically add the listsaver filterwiget to the listcontroller
     *
     * @return void
     */
    protected function extendFilterScopes()
    {
        Event::listen('backend.filter.extendScopes', function ($filterWidget) {

            //Check if is ListController
            if (!$filterWidget->getController()->methodExists('listExtendColumns')) {
                return;
            }

            //Check if is index action in ListController
            if ($filterWidget->getController()->getAction() != 'index') {
                return;
            }

            $dependsOn = [];

            //Widget depends on all scopes
            if ($allScopes = $filterWidget->getScopes()) {
                $dependsOn = array_keys($allScopes);
            }

            $filterWidget->addScopes([
                'listsaver' => [
                    'label' => 'List Saver',
                    'type' => 'listsaver',
                    'dependsOn' => $dependsOn,
                    'permissions' => ['sixgweb.listsaver.access'],
                ],
            ]);
        });
    }
}
