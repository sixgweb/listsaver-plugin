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
                $filterWidget->putScopeValue($scope, $preference->filter[$scope->scopeName] ?? null);
                $result['#' . $scope->getId('group')] = $filterWidget->makePartial('scope', ['scope' => $scope]);
            }
        }

        return $result + $this->controller->listRefresh();
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

        Preference::create([
            'name' => post('list_saver_name', 'New Preference'),
            'list' => $list,
            'filter' => $filter,
            'namespace' => $this->getPreferenceNamespace(),
            'group' => $this->getPreferenceGroup(),
        ]);

        return $this->listSaverRefresh();
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

        return $this->listSaverRefresh();
    }

    public function listSaverRender()
    {
        if (!\BackendAuth::userHasAccess('sixgweb.listsaver.access')) {
            return;
        }

        $this->vars['listSaverPreferences'] = Preference::where('namespace', $this->getPreferenceNamespace())
            ->where('group', $this->getPreferenceGroup())
            ->lists('name', 'id');
        return $this->makeView('_listsaver_container');
    }

    public function listSaverRenderPreferences()
    {
        return $this->makeView('_listsaver_preferences');
    }

    protected function listSaverRefresh()
    {
        $this->vars['listSaverPreferences'] = Preference::where('namespace', $this->getPreferenceNamespace())
            ->where('group', $this->getPreferenceGroup())
            ->lists('name', 'id');
        return ['#listSaverContainer' => $this->listSaverRenderPreferences()];
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
