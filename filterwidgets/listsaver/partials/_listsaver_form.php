<div class="filter-box">
    <div class="filter-facet">
        <div class="facet-item is-grow">
            <div class="input-group input-group-sm">
                <input type="text" id="listSaverName" class="form-control top-0 me-0" name="list_saver_name" placeholder="List Name" autocomplete="off">
                <button type="submit" class="btn btn-outline-secondary me-0" id="listSaverSave" data-request="onSaveListSaverPreference" data-request-complete="$(document).trigger('mousedown');">Save</button>
            </div>
            <div id="listSaverPreferences">
                <?= $this->makePartial('listsaver_preferences') ?>
            </div>
        </div>

    </div>
</div>