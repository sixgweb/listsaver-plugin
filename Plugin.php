<?php

namespace Sixgweb\ListSaver;

use App;
use Event;
use System\Classes\PluginBase;
use Sixgweb\ListSaver\Models\Settings;
use Sixgweb\ListSaver\Models\Preference;
use Backend\Classes\Controller as BackendController;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{

    /**
     * Memoized flag to determine if the plugin is disabled for the current controller
     */
    protected $isEnabledForPath;

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
        if (App::runningInBackend() && $this->isEnabledForPath()) {
            $this->extendFilterScopes();
            $this->extendFilterScopesBefore();
            $this->extendListControllerConfig();

            if (Settings::get('uselist_filename')) {
                $this->extendImportExportControllerConfig();
            }
        }
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
     * If exporting and useList is true, then set the export fileName to the
     * listsaver name, if it has one.
     * 
     * Notes: If your controller overrides the behaviors export method and you're dynamically
     * setting export[useList] in the export override, this will not work.  Move the
     * useList override to the constructor, after parent::__construct() is called.
     *
     * @return void
     */
    protected function extendImportExportControllerConfig()
    {
        Event::listen('backend.page.beforeDisplay', function ($controller) {

            //Only run on the export action
            if ($controller->getAction() == 'export') {

                //If importExportController config useList is false, then return
                $exportController = $controller->asExtension('ImportExportController');
                if (!$exportController->getConfig('export[useList]')) {
                    return;
                }

                if (!$listController = $controller->asExtension('ListController')) {
                    return;
                }

                //Make the listWidget and set the filterWidget
                $listController->makeList();

                //Get the filterWidget or return
                if (!$filterWidget = $listController->listGetFilterWidget()) {
                    return;
                }

                //Get the listSaver scope or return
                if (!$listSaver = $filterWidget->getScope('listsaver')) {
                    return;
                }

                //Get the scopeValue or return
                if (!$val = $filterWidget->getScopeValue($listSaver)) {
                    return;
                }

                //Slugify the listsaver value and set the export fileName
                $val = is_array($val) ? $val[key($val)] : $val;
                $val = str_slug($val);
                $config = $exportController->getConfig('export');
                $config['fileName'] = $val;
                $exportController->setConfig(['export' => $config]);
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

            $actions = ['index', 'export'];
            //Check if is index action in ListController
            if (in_array($filterWidget->getController()->getAction(), $actions) === false) {
                return;
            }

            $dependsOn = [];

            //Widget depends on all scopes
            if ($allScopes = $filterWidget->getScopes()) {
                $dependsOn = array_keys($allScopes);
            }

            $filterWidget->addScopes([
                'listsaver' => [
                    'label' => __('List Saver'),
                    'type' => 'listsaver',
                    'dependsOn' => $dependsOn,
                    'permissions' => ['sixgweb.listsaver.access'],
                ],
            ]);
        });
    }

    protected function isEnabledForPath()
    {
        if (isset($this->isEnabledForPath)) {
            return $this->isEnabledForPath;
        }

        $this->isEnabledForPath = true;

        if ($enabled = Settings::get('enabled_paths')) {
            $this->isEnabledForPath = false;
            foreach ($enabled as $enable) {
                $path = $enable['path'] ?? '';
                if (isset($path[0]) && $path[0] == '/') {
                    $path = substr($path, 1);
                }
                if ($path == \Request::path()) {
                    $this->isEnabledForPath = true;
                    break;
                }
            }
        }

        return $this->isEnabledForPath;
    }
}
