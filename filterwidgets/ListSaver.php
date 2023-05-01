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

    public function init()
    {
        //For now, clear list saver if any refresh occurs on the list setup.
        //TODO: Check if list setup has changed and only clear if it has.
        Event::listen('backend.list.refresh', function ($listWidget) {
            if (!$filterWidget = $listWidget->getController()->listGetFilterWidget()) {
                return;
            }
            $scope = $filterWidget->getScope('listsaver');
            $filterWidget->putScopeValue('listsaver', null);
            $result['#' . $scope->getId('group')] = $filterWidget->makePartial('scope', ['scope' => $scope]);
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
        $this->listFilterWidget = $this->controller->listGetFilterWidget();
        $this->vars['listSaverPreferences'] = $this->getListSaverPreferences();
        $this->vars['listFilterWidget'] = $this->listFilterWidget;
        $this->vars['scope'] = $this->filterScope;
        $this->vars['name'] = $this->getScopeName();
        $this->vars['value'] = $this->getLoadValue();
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

        $listWidget = $this->controller->listGetWidget();
        $list = [
            'visible' => $listWidget->getUserPreference('visible'),
            'order' => $listWidget->getUserPreference('order'),
            'per_page' => $listWidget->getUserPreference('per_page'),
        ];

        $filter = [];
        if ($filterWidget = $this->controller->listGetFilterWidget()) {
            foreach ($filterWidget->getScopes() as $scope) {
                $filter[$scope->scopeName] = $scope->scopeValue;
            }
        }

        $preference = Preference::create([
            'name' => post('list_saver_name', 'New Preference'),
            'list' => $list,
            'filter' => $filter,
            'namespace' => $this->getPreferenceNamespace(),
            'group' => $this->getPreferenceGroup(),
            'blueprint_uuid' => $this->controller->vars['activeSource']->uuid ?? null,
        ]);

        $scope = $filterWidget->getScope('listsaver');
        $filterWidget->putScopeValue('listsaver', [$preference->id => $preference->name]);

        return ['#' . $scope->getId('group') => $filterWidget->makePartial('scope', ['scope' => $scope])];
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

        $preference->delete();

        if ($value = $this->getLoadValue()) {
            if (key($value) == $id) {
                $this->controller->listGetFilterWidget()->putScopeValue('listsaver', null);
            }
        }

        return $this->listSaverRefresh();
    }

    public function onApplyListSaverPreference()
    {
        if (!$id = post('list_saver_preference')) {
            return;
        }

        if (!$preference = Preference::find($id)) {
            return;
        }

        $result = [];
        $listWidget = $this->controller->listGetWidget();
        $listWidget->putUserPreference('visible', $preference->list['visible']);
        $listWidget->putUserPreference('order', $preference->list['order']);
        $listWidget->putUserPreference('per_page', $preference->list['per_page']);

        if ($filterWidget = $this->controller->listGetFilterWidget()) {
            foreach ($filterWidget->getScopes() as $scope) {
                if ($scope->scopeName == 'listsaver') {
                    $filterWidget->putScopeValue($scope, [$preference->id => $preference->name]);
                } else {
                    $filterWidget->putScopeValue($scope, $preference->filter[$scope->scopeName] ?? null);
                }
                $result['#' . $scope->getId('group')] = $filterWidget->makePartial('scope', ['scope' => $scope]);
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
