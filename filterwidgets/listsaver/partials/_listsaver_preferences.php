<?php $backendUserId = \BackendAuth::getUser()->id ?>
<ul class="list-unstyled m-0 mt-2">
    <?php if (count($listSaverPreferences)) : ?>
        <?php foreach ($listSaverPreferences as $listSaverPreference) : ?>
            <li>
                <div class="d-flex g-0 align-items-center">
                    <a data-request="<?= $this->getEventHandler('onApplyListSaverPreference') ?>" data-request-data="list_saver_preference:<?= $listSaverPreference->id ?>" class="text-secondary link-primary w-100" href="#" title="<?= __('Load List') ?>"><?= $listSaverPreference->name ?></a>

                    <?php if ($listSaverPreference->backend_user_id == $backendUserId) : ?>
                        <a data-request="<?= $this->getEventHandler('onDeleteListSaverPreference') ?>" data-request-data="list_saver_preference:<?= $listSaverPreference->id ?>" data-request-success="" class="link-danger px-1 w-auto text-secondary" href="#" title="<?= __('Delete List') ?>"><i class="bi-x-circle-fill"></i></a>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach ?>
    <?php else : ?>
        <li class="text-muted"><?= __('No saved lists') ?></li>
    <?php endif; ?>
</ul>