<?php $canManageListSaver = \BackendAuth::userHasAccess('sixgweb.listsaver.manage'); ?>
<ul class="list-unstyled m-0 <?= $canManageListSaver ? 'mt-2' : '' ?>">
    <?php if (count($listSaverPreferences)) : ?>
        <?php foreach ($listSaverPreferences as $id => $name) : ?>
            <li>
                <div class="d-flex g-0 align-items-center">
                    <a data-request="onApplyListSaverPreference" data-request-data="list_saver_preference:<?= $id ?>" data-request-complete="$(document).trigger('mousedown');" class="dropdown-item w-100" href="#"><?= $name ?></a>

                    <?php if ($canManageListSaver) : ?>
                        <a data-request="onDeleteListSaverPreference" data-request-data="list_saver_preference:<?= $id ?>" data-request-success="" class="dropdown-item px-1 w-auto text-secondary" href="#"><i class="bi-x-circle-fill"></i></a>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach ?>
    <?php else : ?>
        <li class="text-muted">No saved lists</li>
    <?php endif; ?>
</ul>