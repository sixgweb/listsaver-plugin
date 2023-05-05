<div class="filter-box">
    <div class="filter-facet">
        <div class="facet-item is-grow">
            <div class="input-group input-group-sm">
                <input type="text" id="listSaverName" class="form-control top-0 me-0 w-auto" name="list_saver_name" placeholder="<?= __('List Name') ?>" autocomplete="off">
                <button type="submit" class="btn btn-outline-secondary" id="listSaverSave" data-request="<?= $this->getEventHandler('onSaveListSaverPreference') ?>"><?= __('Save') ?></button>
                <?php if ($listSaverSharingEnabled) : ?>
                    <button class="btn btn-link text-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi-gear"></i></button>
                    <div class="dropdown-menu p-2">
                        <div class="form-check-sm form-check form-switch">
                            <input name="list_saver_private" value="1" class="form-check-input" type="checkbox" role="switch" id="listSaverPrivate" checked>
                            <label class="form-check-label" for="flexSwitchCheckDefault"><?= __('Private List') ?></label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div id="listSaverPreferences">
                <?= $this->makePartial('listsaver_preferences') ?>
            </div>
        </div>

    </div>
</div>