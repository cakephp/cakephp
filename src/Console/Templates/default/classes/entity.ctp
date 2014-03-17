<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
echo "<?php\n";
?>
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
