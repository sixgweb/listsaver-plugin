<?php

namespace Sixgweb\ListSaver\FilterWidgets;

use Str;
use Event;
use Backend\Classes\FilterWidgetBase;
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
        if (!\BackendAuth::userHasAccess('sixgweb.listsaver.manage')) {
            return;
        }

        if (!$name = trim(post('list_saver_name'))) {
            return;
        }

        $this->setProperties();

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
        if (!\BackendAuth::userHasAccess('sixgweb.listsaver.manage')) {
            return;
        }

        if (!$id = post('list_saver_preference')) {
            return;
        }

        if (!$preference = Preference::find($id)) {
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

        /**
         * Calling this method forces the list widget to fire the list.extendColumns event
         * in the defineListColumns method, allowing other extensions to modify the columns.
         * These columns then become available to load from our preferences.
         */
        $this->listWidget->getVisibleColumns();

        $result = ['closePopover' => true];
        $this->listWidget->putUserPreference('visible', $preference->list['visible']);
        $this->listWidget->putUserPreference('order', $preference->list['order']);
        $this->listWidget->putUserPreference('per_page', $preference->list['per_page']);

        if ($this->listFilterWidget) {
            foreach ($this->listFilterWidget->getScopes() as $scope) {
                if ($scope->scopeName == 'listsaver') {
                    $this->listFilterWidget->putScopeValue($scope, [$preference->id => $preference->name]);
                } else {
                    $this->listFilterWidget->putScopeValue($scope, $preference->filter[$scope->scopeName] ?? null);
                }
                $result['#' . $scope->getId('group')] = $this->listFilterWidget->makePartial('scope', ['scope' => $scope]);
            }
        }

        return $result + $this->controller->listRefresh();
    }


    protected function getListSaverPreferences()
    {
        return Preference::where('namespace', $this->getPreferenceNamespace())
            ->where('group', $this->getPreferenceGroup())
            ->where(function ($query) {
                if (isset($this->controller->vars['activeSource']->uuid)) {
                    $query->where('blueprint_uuid', $this->controller->vars['activeSource']->uuid);
                } else {
                    $query->whereNull('blueprint_uuid');
                }
            })
            ->lists('name', 'id');
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
        $this->vars['listSaverPreferences'] = Preference::where('namespace', $this->getPreferenceNamespace())
            ->where('group', $this->getPreferenceGroup())
            ->lists('name', 'id');
        return ['#listSaverPreferences' => $this->makePartial('listsaver_preferences')];
    }
}
