<div class="users form">
    <?= $this->Form->create(false); ?>
        <fieldset>
            <legend><?= __('Add User'); ?></legend>
        </fieldset>
    <?= $this->Form->submit('Submit'); ?>
    <?= $this->Form->end(); ?>
</div>
