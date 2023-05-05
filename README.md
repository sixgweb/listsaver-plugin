Save the current list setup and filter values for all [ListControllers](https://docs.octobercms.com/3.x/extend/lists/list-controller.html).  Interface is provided via a custom filter widget and is automatically added to all ListControllers that allow list setup and/or filters

# Installation

### Requirements
- OctoberCMS 3.x

## Composer
```
composer require sixgweb/listsaver-plugin
```

## Marketplace

Add the plugin to your project via the [OctoberCMS Market Place](https://octobercms.com/plugin/sixgweb-listsaver).

### Command Line

```
php artisan project:sync
```

### Backend Installer

In the Backend, visit **Settings->System Updates->Install Packages** press the **Sync Project** button.

# Permissions

## Access Lists
User can access ListSaver and save/load their private lists or public lists (if sharing enabled and has permission).  Users can always delete their own lists.

## Share Lists
User can set lists as public/private when saving, if list sharing enabled.

## Access Settings
User can access ListSaver settings

# Settings

## Allow List Sharing
Allow users with permission to set list as public or private.  Other users will see all public lists.

# Usage

## Save Current List/Filters
Once your list setup and filters are in place, press the ListSave button, name your list and press save. If list sharing is enabled and you have permission, click the gear icon to set list to public/private before saving.

## Load List/Filters
Click the list saver button and select from your currently saved lists.  If list sharing is enabled, you will see all public lists.

# Events

## listSaverPreferencesQuery
Opportunity for 3rd party developers to modify the preferences query.
``` php
Event::listen('sixgweb.listsaver.listSaverPreferencesQuery', function (&$query) {
    $query->whereNotIn('id', $this->getUserIgnoreListSaverPreferences());
});
```
