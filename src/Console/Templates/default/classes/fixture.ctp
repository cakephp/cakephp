<?php
/**
 * Fixture Template file
 *
 * Fixture Template used when baking fixtures with bake
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
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<?= '<?php' . "\n"; ?>
namespace <?= $namespace; ?>\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * <?= $name; ?>Fixture
 *
 */
class <?= $name; ?>Fixture extends TestFixture {

<?php if ($table): ?>
/**
 * Table name
 *
 * @var string
 */
	public $table = '<?= $table; ?>';

<?php endif; ?>
<?php if ($import): ?>
/**
 * Import
 *
 * @var array
 */
	public $import = <?= $import; ?>;

<?php endif; ?>
<?php if ($schema): ?>
/**
 * Fields
 *
 * @var array
 */
	public $fields = <?= $schema; ?>;

<?php endif; ?>
<?php if ($records): ?>
/**
 * Records
 *
 * @var array
 */
	public $records = <?= $records; ?>;

<?php endif; ?>
}
