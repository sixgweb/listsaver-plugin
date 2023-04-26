<a href="javascript:;" class="filter-scope btn btn-secondary rounded-0 border-start shadow-0" data-scope-name="<?= $name ?>">
    <span class="filter-label"><i class="bi-list"></i> <?= $value ? (is_array($value) ? $value[key($value)] : $value) : e(trans($scope->label)) ?></span>
</a>