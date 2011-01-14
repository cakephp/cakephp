<?php
/**
 * Fixture Template file
 *
 * Fixture Template used when baking fixtures with bake
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<?php echo '<?php' . "\n"; ?>
/* <?php echo $model; ?> Fixture generated on: <?php echo  date('Y-m-d H:i:s') . " : ". time(); ?> */
class <?php echo $model; ?>Fixture extends CakeTestFixture {
	var $name = '<?php echo $model; ?>';
<?php if ($table): ?>
	var $table = '<?php echo $table; ?>';
<?php endif; ?>
<?php if ($import): ?>
	var $import = <?php echo $import; ?>;
<?php endif; ?>

<?php if ($schema): ?>
	var $fields = <?php echo $schema; ?>;
<?php endif;?>

<?php if ($records): ?>
	var $records = <?php echo $records; ?>;
<?php endif;?>
}
<?php echo '?>'; ?>