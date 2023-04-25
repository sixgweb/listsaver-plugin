<?php

namespace Sixgweb\ListSaver\Behaviors;

use Str;
use Backend\Classes\ControllerBehavior;
use Sixgweb\ListSaver\Models\Preference;

class ListSaverController extends ControllerBehavior
{
    protected $controller;

    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    public function onApplyFilterListPreference()
    {
        if (!$id = post('filter_list_preference')) {
            return;
        }

        if (!$preference = Preference::find($id)) {
            return;
        }

        $listWidget = $this->controller->listGetWidget();
        $listWidget->putUserPreference('visible', $preference->list['visible']);
        $listWidget->putUserPreference('order', $preference->list['order']);
        $listWidget->putUserPreference('per_page', $preference->list['per_page']);
        $result = $this->controller->listRefresh();

        if ($filterWidget = $this->controller->listGetFilterWidget()) {
            foreach ($filterWidget->getScopes() as $scope) {
                $filterWidget->putScopeValue($scope, $preference->filter[$scope->scopeName] ?? null);
                $result['#' . $scope->getId('group')] = $filterWidget->makePartial('scope', ['scope' => $scope]);
            }
        }

        return $result;
    }

    public function onSaveFilterListPreference()
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

        Preference::create([
            'name' => post('filter_list_name', 'New Preference'),
            'list' => $list,
            'filter' => $filter,
            'namespace' => $this->getPreferenceNamespace(),
            'group' => $this->getPreferenceGroup(),
        ]);

        return $this->filterListPreferencesRefresh();
    }

    public function onDeleteFilterListPreference()
    {
        if (!\BackendAuth::userHasAccess('sixgweb.listsaver.manage')) {
            return;
        }

        if (!$id = post('filter_list_preference')) {
            return;
        }

        if (!$preference = Preference::find($id)) {
            return;
        }

        $preference->delete();

        return $this->filterListPreferencesRefresh();
    }

    public function listSaverRender()
    {
        if (!\BackendAuth::userHasAccess('sixgweb.listsaver.access')) {
            return;
        }

        $this->vars['filterListPreferences'] = Preference::where('namespace', $this->getPreferenceNamespace())
            ->where('group', $this->getPreferenceGroup())
            ->lists('name', 'id');
        return $this->makeView('_filterlist_container');
    }

    public function filterListPreferencesRenderPreferences()
    {
        return $this->makeView('_filterlist_preferences');
    }

    protected function filterListPreferencesRefresh()
    {
        $this->vars['filterListPreferences'] = Preference::where('namespace', $this->getPreferenceNamespace())
            ->where('group', $this->getPreferenceGroup())
            ->lists('name', 'id');
        return ['#filterListPreferencesContainer' => $this->filterListPreferencesRenderPreferences()];
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
}
