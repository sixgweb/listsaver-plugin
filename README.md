Behavior and ListController toolbar button to save current list setup and filter values.

# Usage

Add behavior to backend controller

``` php
public $implement = [
    \Sixgweb\ListSaver\Behaviors\ListSaverController::class
];

```

Add listSaverRender() to your controller _list_toolbar.php file

``` html
<div data-control="toolbar">
    
    ...

    <?= $this->listSaverRender() ?>
</div>
```