<?= "<?php\n"; ?>
namespace <?= $namespace ?>\Model\Entity;

use Cake\ORM\Entity;

/**
 * <?= $name ?> Entity.
 */
class <?= $name ?> extends Entity {

<?php if (!empty($fields)): ?>
<?php
$fields = array_map(function($el) { return "'$el'"; }, $fields);
?>
/**
 * Fields that can be mass assigned using newEntity() or patchEntity().
 *
 * @var array
 */
	protected $_accessible = [<?= implode(', ', $fields) ?>];

<?php endif ?>
}
