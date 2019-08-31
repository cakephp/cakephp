<div>
	<h1><?= __('Add I18n Post') ?></h1>
    <?php
    echo $this->Form->create(false) . $this->Form->radio('Model.radio_field', [
        __('An Option'),
        __('Another Option'),
        __('Last Option')
    ]) . $this->Form->radio('no_model_prefix_radio_field', [
        __('Red'),
        __('Green'),
        __('Blue')
    ]) . $this->Form->input('OtherModel.field_name_to_inflect_and_add_to_pot') . $this->Form->input('field_name_to_exclude_from_pot', [
        'label' => __('Custom Label To Add To Pot')
    ]) . $this->Form->input('inflect_and_add_to_pot') . $this->Form->submit(__('Submit')) . $this->Form->end();
    ?>
</div>
<div>
	<h1><?= __('Paginate I18n') ?></h1>
    <?php
    echo $this->Paginator->sort('some_sort_field_name_to_add_to_pot') . $this->Paginator->sort('Model.sort_field_to_ignore_parse_custom', __('Custom Sort Name To Parse'));
    ?>
</div>