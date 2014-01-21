<?php
/**
 * Model/Table template file.
 *
 * Used by bake to create new Model files.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Utility\Inflector;

echo "<?php\n";
echo "namespace App\Model\Table;\n\n";
echo "use Cake\ORM\Table;\n\n";
?>
/**
 * <?= $name ?> Table
 *
<?php
foreach (['hasOne', 'belongsTo', 'hasMany', 'belongsToMany'] as $assocType) {
	if (!empty($associations[$assocType])) {
		foreach ($associations[$assocType] as $relation) {
			echo " * @property {$relation['className']} \${$relation['alias']}\n";
		}
	}
}
?>
 */
class <?= $className ?> extends <?= $plugin; ?>Table {
	public function initialize(array $config) {
<?php if ($useTable && $useTable !== Inflector::tableize($name)): ?>
		// Use table: False or table name
		$this->table('<?= $useTable; ?>');
<?php endif;

if ($primaryKey !== 'id'): ?>
		// Primary key field(s)
		$this->primaryKey('<?= $primaryKey; ?>');
<?php endif;

if ($displayField): ?>
		// Display field
		$this->displayField('<?= $displayField; ?>');
<?php endif;

if (!empty($actsAs)): ?>
		// Add Behaviors
		<?php foreach ($actsAs as $behavior): ?>
		$this->addBehavior('<?= var_export($behavior); ?>');
		<?php endforeach; ?>
<?php endif;

foreach ($associations as $assoc):
	if (!empty($assoc)):
?>

	//The Associations below have been created with all possible keys, those that are not needed can be removed
<?php
		break;
	endif;
endforeach;

foreach (['hasOne', 'belongsTo'] as $assocType):
	if (!empty($associations[$assocType])):
		echo "\n// $assocType associations";
		foreach ($associations[$assocType] as $i => $relation):
			$out = "\n\t\t\$this->$assocType('{$relation['alias']}', [\n";
			$out .= "\t\t\t'className' => '{$relation['className']}',\n";
			$out .= "\t\t\t'foreignKey' => '{$relation['foreignKey']}',\n";
			$out .= "\t\t\t'conditions' => '',\n";
			$out .= "\t\t\t'fields' => '',\n";
			$out .= "\t\t\t'order' => ''\n";
			$out .= "\t\t]);\n";
			echo $out;
		endforeach;
	endif;
endforeach;

if (!empty($associations['hasMany'])):
	echo "\n// hasMany associations";
	foreach ($associations['hasMany'] as $i => $relation):
		$out = "\n\t\t\$this->hasMany('{$relation['alias']}', [\n";
		$out .= "\t\t\t'className' => '{$relation['className']}',\n";
		$out .= "\t\t\t'foreignKey' => '{$relation['foreignKey']}',\n";
		$out .= "\t\t\t'dependent' => false,\n";
		$out .= "\t\t\t'conditions' => '',\n";
		$out .= "\t\t\t'fields' => '',\n";
		$out .= "\t\t\t'order' => '',\n";
		$out .= "\t\t\t'limit' => '',\n";
		$out .= "\t\t\t'offset' => '',\n";
		$out .= "\t\t\t'exclusive' => '',\n";
		$out .= "\t\t\t'finderQuery' => '',\n";
		$out .= "\t\t\t'counterQuery' => ''\n";
		$out .= "\t\t]);\n\n";
		echo $out;
	endforeach;
endif;

if (!empty($associations['belongsToMany'])):
	echo "\n// belongsToMany associations";
	foreach ($associations['belongsToMany'] as $i => $relation):
		$out = "\n\t\t\$this->belongsToMany('{$relation['alias']}', [\n";
		$out .= "\t\t\t'className' => '{$relation['className']}',\n";
		$out .= "\t\t\t'joinTable' => '{$relation['joinTable']}',\n";
		$out .= "\t\t\t'foreignKey' => '{$relation['foreignKey']}',\n";
		$out .= "\t\t\t'associationForeignKey' => '{$relation['associationForeignKey']}',\n";
		$out .= "\t\t\t'unique' => 'keepExisting',\n";
		$out .= "\t\t\t'conditions' => '',\n";
		$out .= "\t\t\t'fields' => '',\n";
		$out .= "\t\t\t'order' => '',\n";
		$out .= "\t\t\t'limit' => '',\n";
		$out .= "\t\t\t'offset' => '',\n";
		$out .= "\t\t\t'finderQuery' => '',\n";
		$out .= "\t\t]);\n\n";
		echo $out;
	endforeach;
endif;
?>
	}


<?php if (!empty($validate)): ?>
	// Validation rules
	public function validationDefault($validator) {
<?php foreach ($validate as $field => $validations): ?>
		$validator->add('<?= $field; ?>', [
<?php foreach ($validations as $key => $validator): ?>
				'<?= $key; ?>', [
					'rule' => ['<?= $validator; ?>'],
					//'message' => 'Your custom message here',
					//'allowEmpty' => false,
					//'required' => false,
					//'last' => false, // Stop validation after this rule
					//'on' => 'create', // Limit validation to 'create' or 'update' operations
				],
<?php endforeach; ?>
			]);
<?php endforeach; ?>
		return $validator;
	}
<?php endif; ?>


}
