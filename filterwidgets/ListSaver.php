<?php

namespace Sixgweb\ListSaver\FilterWidgets;

use Str;
use Event;
use BackendAuth;
use Backend\Classes\FilterWidgetBase;
use Sixgweb\ListSaver\Models\Settings;
use Sixgweb\ListSaver\Models\Preference;

/**
 * ListSaver Filter Widget
 *
 * @link https://docs.octobercms.com/3.x/extend/lists/filter-widgets.html
 */
class ListSaver extends FilterWidgetBase
{
    public $listFilterWidget;
    public $listWidget;

    public function init()
    {
        //For now, clear list saver if any refresh occurs on the list setup.
        //TODO: Check if list setup has changed and only clear if it has.
        Event::listen('backend.list.refresh', function ($listWidget) {
            $this->setProperties();

            //No listfilterwidget or posted a list preference, so don't clear.
            if (!$this->listFilterWidget || post('list_saver_preference')) {
                return;
            }

            $scope = $this->listFilterWidget->getScope('listsaver');
            $this->listFilterWidget->putScopeValue('listsaver', null);
            $result['#' . $scope->getId('group')] = $this->listFilterWidget->makePartial('scope', ['scope' => $scope]);
            return $result;
        });
    }

    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('listsaver');
    }

    public function renderForm()
    {
        $this->prepareVars();
        return $this->makePartial('listsaver_form');
    }

    public function prepareVars()
    {
        $this->setProperties();
        $this->vars['listSaverPreferences'] = $this->getListSaverPreferences();
        $this->vars['listSaverSharingEnabled'] = $this->getSharingEnabled();
        $this->vars['listFilterWidget'] = $this->listFilterWidget;
        $this->vars['scope'] = $this->filterScope;
        $this->vars['name'] = $this->getScopeName();
        $this->vars['value'] = $this->getLoadValue();
    }

    public function setProperties()
    {
        $this->listFilterWidget = $this->controller->listGetFilterWidget();
        $this->listWidget = $this->controller->listGetWidget();
    }

    public function loadAssets()
    {
        $this->addCss('css/listsaver.css');
        $this->addJs('js/listsaver.js');
    }

    public function getActiveValue()
    {
        if (post('clearScope')) {
            return null;
        }

        if ($id = post('list_saver_preference')) {
            $preference = Preference::find($id);
            return [$preference->id = $preference->name];
        }

        return null;
    }

    public function applyScopeToQuery($query)
    {
    }

    public function onSaveListSaverPreference()
    {
        if (!\BackendAuth::userHasAccess('sixgweb.listsaver.access')) {
            return;
        }

        if (!$name = trim(post('list_saver_name'))) {
            return;
        }

        $this->setProperties();

        $private = $this->getSharingEnabled()
            ? post('list_saver_private', 0)
            : 1;

        $list = [
            'visible' => $this->listWidget->getUserPreference('visible'),
            'order' => $this->listWidget->getUserPreference('order'),
            'per_page' => $this->listWidget->getUserPreference('per_page'),
        ];

        $filter = [];
        if ($this->listFilterWidget) {
            foreach ($this->listFilterWidget->getScopes() as $scope) {
                if ($scope->scopeName == 'listsaver') {
                    continue;
                }
                $filter[$scope->scopeName] = $scope->scopeValue;
            }
        }

        $preference = Preference::create([
            'name' => $name,
            'list' => $list,
            'filter' => $filter,
            'namespace' => $this->getPreferenceNamespace(),
            'group' => $this->getPreferenceGroup(),
            'backend_user_id' => BackendAuth::getUser()->id,
            'is_private' => (bool)$private,
            'blueprint_uuid' => $this->controller->vars['activeSource']->uuid ?? null,
        ]);

        $scope = $this->listFilterWidget->getScope('listsaver');
        $this->listFilterWidget->putScopeValue('listsaver', [$preference->id => $preference->name]);

        return [
            '#' . $scope->getId('group') => $this->listFilterWidget->makePartial('scope', ['scope' => $scope]),
            'closePopover' => true,
        ];
    }

    public function onDeleteListSaverPreference()
    {
        if (!\BackendAuth::userHasAccess('sixgweb.listsaver.access')) {
            return;
        }

        if (!$id = post('list_saver_preference')) {
            return;
        }

        if (!$preference = Preference::find($id)) {
            return;
        }

        if ($preference->backend_user_id != BackendAuth::getUser()->id) {
            return;
        }

        $this->setProperties();

        $preference->delete();

        $result = $this->listSaverRefresh();

        if ($value = $this->getLoadValue()) {
            if (key($value) == $id) {
                $scope = $this->listFilterWidget->getScope('listsaver');
                $this->listFilterWidget->putScopeValue($scope, null);
                $result['#' . $scope->getId('group')] = $this->listFilterWidget->makePartial('scope', ['scope' => $scope]);
                $result['closePopover'] = true;
            }
        }

        return $result;
    }

    public function onApplyListSaverPreference()
    {
        if (!$id = post('list_saver_preference')) {
            return;
        }

        if (!$preference = Preference::find($id)) {
            return;
        }

        $this->setProperties();

        $result = ['closePopover' => true];
        $this->listWidget->putUserPreference('visible', $preference->list['visible']);
        $this->listWidget->putUserPreference('order', $preference->list['order']);
        $this->listWidget->putUserPreference('per_page', $preference->list['per_page']);

        if ($this->listFilterWidget) {
            foreach ($this->listFilterWidget->getScopes() as $scope) {

                //Plugin.php is now setting scope values in extendFilterScopesBefore
                //Here we just update the partials and the listsaver value
                if ($scope->scopeName == 'listsaver') {
                    $this->listFilterWidget->putScopeValue($scope, [$preference->id => $preference->name]);
                }
                $result['#' . $scope->getId('group')] = $this->listFilterWidget->makePartial('scope', ['scope' => $scope]);
            }
        }

        return $result + $this->controller->listRefresh();
    }


    protected function getListSaverPreferences()
    {
        $query = Preference::where('namespace', $this->getPreferenceNamespace())
            ->where('group', $this->getPreferenceGroup())
            ->where(function ($query) {
                if (isset($this->controller->vars['activeSource']->uuid)) {
                    $query->where('blueprint_uuid', $this->controller->vars['activeSource']->uuid);
                } else {
                    $query->whereNull('blueprint_uuid');
                }
            })
            ->where(function ($query) {
                $query->where('backend_user_id', BackendAuth::getUser()->id);
                if (Settings::get('allow_shared_lists', false)) {
                    $query->orWhere('is_private', 0);
                }
            });

        /**
         * Opportunity for other extensions to modify the getListSaver() query;
         * 
         * Event::listen('sixgweb.listsaver.listSaverPreferencesQuery', function (&$query) {
         *  $query->whereNotIn('id', $this->getUserBlacklistedListSaverPreferences());
         * });
         */
        Event::fire('sixgweb.listsaver.listSaverPreferencesQuery', [&$query]);

        return $query->get();
    }

    protected function getPreferenceNamespace()
    {
        return Str::getClassId(
            Str::getClassNamespace(
                Str::getClassNamespace($this->controller)
            )
        );
    }

    protected function getPreferenceGroup()
    {
        return strtolower(class_basename($this->controller));
    }

    protected function listSaverRefresh()
    {
        $this->vars['listSaverPreferences'] = $this->getListSaverPreferences();
        return ['#listSaverPreferences' => $this->makePartial('listsaver_preferences')];
    }

    protected function getSharingEnabled()
    {
        if (!Settings::get('allow_shared_lists', false)) {
            return false;
        }

        if (!\BackendAuth::userHasAccess('sixgweb.listsaver.share')) {
            return false;
        }

        return true;
    }
}
