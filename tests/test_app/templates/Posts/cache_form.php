<?php
use function Cake\I18n\__;
?>
<div class="users form">
    <?= $this->Form->create(); ?>
        <fieldset>
            <legend><?= __('Add User'); ?></legend>
        </fieldset>
    <?= $this->Form->submit('Submit'); ?>
    <?= $this->Form->end(); ?>
</div>
