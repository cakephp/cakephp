<?php
/**
 * Test Case bake template
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org
 * @package       cake
 * @subpackage    cake.console.libs.templates.objects
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
echo "<?php\n";
?>
App::import('<?php echo $type; ?>', '<?php echo $className;?>');

class <?php echo $className; ?>TestCase extends CakeTestCase {
<?php if (!empty($fixtures)): ?>
	var $fixtures = array('<?php echo join("', '", $fixtures); ?>');

<?php endif; ?>
<?php foreach ($methods as $method): ?>
	function test<?php echo Inflector::classify($method); ?>() {
		
	}

<?php endforeach;?>
}
<?php echo '?>'; ?>