<a href="javascript:;" class="filter-scope bg-light border-start" data-scope-name="<?= $name ?>" data-scope-id="<?= $value && is_array($value) ? key($value) : '' ?>">
    <span class="filter-label"><i class="bi-list"></i> <?= $value ? (is_array($value) ? $value[key($value)] : $value) : e(trans($scope->label)) ?></span>
</a>