<?php
/**
 * Model template file.
 *
 * Used by bake to create new Model files.
 *
 * PHP 5
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
 * @package       Cake.Console.Templates.default.classes
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

echo "<?php\n";
echo "App::uses('{$plugin}AppModel', '{$pluginPath}Model');\n";
?>
/**
 * <?php echo $name ?> Model
 *
<?php
foreach (array('hasOne', 'belongsTo', 'hasMany', 'hasAndBelongsToMany') as $assocType) {
	if (!empty($associations[$assocType])) {
		foreach ($associations[$assocType] as $relation) {
			echo " * @property {$relation['className']} \${$relation['alias']}\n";
		}
	}
}
?>
 */
class <?php echo $name ?> extends <?php echo $plugin; ?>AppModel {

<?php if ($useDbConfig !== 'default'): ?>
/**
 * Use database config
 *
 * @var string
 */
	public $useDbConfig = '<?php echo $useDbConfig; ?>';

<?php endif;

if ($useTable && $useTable !== Inflector::tableize($name)):
	$table = "'$useTable'";
	echo "/**\n * Use table\n *\n * @var mixed False or table name\n */\n";
	echo "\tpublic \$useTable = $table;\n\n";
endif;

if ($primaryKey !== 'id'): ?>
/**
 * Primary key field
 *
 * @var string
 */
	public $primaryKey = '<?php echo $primaryKey; ?>';

<?php endif;

if ($displayField): ?>
/**
 * Display field
 *
 * @var string
 */
	public $displayField = '<?php echo $displayField; ?>';

<?php endif;

if (!empty($actsAs)): ?>
/**
 * Behaviors
 *
 * @var array
 */
	public $actsAs = array(<?php echo "\n\t"; foreach ($actsAs as $behavior): echo "\t"; var_export($behavior); echo ",\n\t"; endforeach; ?>);

<?php endif;

if (!empty($validate)):
	echo "/**\n * Validation rules\n *\n * @var array\n */\n";
	echo "\tpublic \$validate = array(\n";
	foreach ($validate as $field => $validations):
		echo "\t\t'$field' => array(\n";
		foreach ($validations as $key => $validator):
			echo "\t\t\t'$key' => array(\n";
			echo "\t\t\t\t'rule' => array('$validator'),\n";
			echo "\t\t\t\t//'message' => 'Your custom message here',\n";
			echo "\t\t\t\t//'allowEmpty' => false,\n";
			echo "\t\t\t\t//'required' => false,\n";
			echo "\t\t\t\t//'last' => false, // Stop validation after this rule\n";
			echo "\t\t\t\t//'on' => 'create', // Limit validation to 'create' or 'update' operations\n";
			echo "\t\t\t),\n";
		endforeach;
		echo "\t\t),\n";
	endforeach;
	echo "\t);\n";
endif;

foreach ($associations as $assoc):
	if (!empty($assoc)):
?>

	//The Associations below have been created with all possible keys, those that are not needed can be removed
<?php
		break;
	endif;
endforeach;

foreach (array('hasOne', 'belongsTo') as $assocType):
	if (!empty($associations[$assocType])):
		$typeCount = count($associations[$assocType]);
		echo "\n/**\n * $assocType associations\n *\n * @var array\n */";
		echo "\n\tpublic \$$assocType = array(";
		foreach ($associations[$assocType] as $i => $relation):
			$out = "\n\t\t'{$relation['alias']}' => array(\n";
			$out .= "\t\t\t'className' => '{$relation['className']}',\n";
			$out .= "\t\t\t'foreignKey' => '{$relation['foreignKey']}',\n";
			$out .= "\t\t\t'conditions' => '',\n";
			$out .= "\t\t\t'fields' => '',\n";
			$out .= "\t\t\t'order' => ''\n";
			$out .= "\t\t)";
			if ($i + 1 < $typeCount) {
				$out .= ",";
			}
			echo $out;
		endforeach;
		echo "\n\t);\n";
	endif;
endforeach;

if (!empty($associations['hasMany'])):
	$belongsToCount = count($associations['hasMany']);
	echo "\n/**\n * hasMany associations\n *\n * @var array\n */";
	echo "\n\tpublic \$hasMany = array(";
	foreach ($associations['hasMany'] as $i => $relation):
		$out = "\n\t\t'{$relation['alias']}' => array(\n";
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
		$out .= "\t\t)";
		if ($i + 1 < $belongsToCount) {
			$out .= ",";
		}
		echo $out;
	endforeach;
	echo "\n\t);\n\n";
endif;

if (!empty($associations['hasAndBelongsToMany'])):
	$habtmCount = count($associations['hasAndBelongsToMany']);
	echo "\n/**\n * hasAndBelongsToMany associations\n *\n * @var array\n */";
	echo "\n\tpublic \$hasAndBelongsToMany = array(";
	foreach ($associations['hasAndBelongsToMany'] as $i => $relation):
		$out = "\n\t\t'{$relation['alias']}' => array(\n";
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
		$out .= "\t\t)";
		if ($i + 1 < $habtmCount) {
			$out .= ",";
		}
		echo $out;
	endforeach;
	echo "\n\t);\n\n";
endif;
?>
}
