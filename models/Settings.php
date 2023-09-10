<?php

namespace Sixgweb\ListSaver\Models;

use Config;
use Backend\Classes\NavigationManager;
use BackendMenu;

class Settings extends \System\Models\SettingModel
{
    public $settingsCode = 'sixgweb_listsaver_settings';

    public $settingsFields = 'fields.yaml';

    public function getPathOptions()
    {
        $options = [];
        $custom = [];

        if ($this->enabled_paths) {
            $custom = array_pluck($this->enabled_paths, 'path', 'path');
        }

        $basepath = Config::get('app.url') . '/';
        foreach (BackendMenu::listMainMenuItemsWithSubitems() as $itemIndex => $itemInfo) {
            $item = $itemInfo->mainMenuItem;
            $url = str_replace($basepath, '', $item->url);
            if ($this->shouldAddUrl($url)) {
                $options[$url] = e(__($item->label));
            }
            foreach ($itemInfo->subMenuItems as $subItem) {
                $url = str_replace($basepath, '', $subItem->url);
                if ($this->shouldAddUrl($url)) {
                    $options[$url] = e(__($item->label)) . ' - ' . e(__($subItem->label));
                }
            }
        }

        return array_merge($custom, $options);
    }

    protected function shouldAddUrl($url)
    {
        $count = substr_count($url, '/');
        return $count > 2;
    }
}
