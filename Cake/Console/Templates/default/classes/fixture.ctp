<?php
/**
 * Fixture Template file
 *
 * Fixture Template used when baking fixtures with bake
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
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

echo "<?php\n";
?>
<?php echo '<?php' . "\n"; ?>
namespace <?php echo $namespace; ?>\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * <?php echo $model; ?>Fixture
 *
 */
class <?php echo $model; ?>Fixture extends TestFixture {

<?php if ($table): ?>
/**
 * Table name
 *
 * @var string
 */
	public $table = '<?php echo $table; ?>';

<?php endif; ?>
<?php if ($import): ?>
/**
 * Import
 *
 * @var array
 */
	public $import = <?php echo $import; ?>;

<?php endif; ?>
<?php if ($schema): ?>
/**
 * Fields
 *
 * @var array
 */
	public $fields = <?php echo $schema; ?>;

<?php endif; ?>
<?php if ($records): ?>
/**
 * Records
 *
 * @var array
 */
	public $records = <?php echo $records; ?>;

<?php endif; ?>
}
