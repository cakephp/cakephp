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
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Utility\Inflector;

echo "<?php\n";
?>
namespace <?php echo $baseNamespace; ?>\Test\TestCase\<?php echo $subNamespace ?>;

<?php foreach ($uses as $dependency): ?>
use <?php echo $dependency; ?>;
<?php endforeach; ?>
<?php if ($type === 'Controller'): ?>
use Cake\TestSuite\ControllerTestCase;
<?php else: ?>
use Cake\TestSuite\TestCase;
<?php endif; ?>

/**
 * <?php echo $fullClassName; ?> Test Case
 */
<?php if ($type === 'Controller'): ?>
class <?php echo $className; ?>Test extends ControllerTestCase {
<?php else: ?>
class <?php echo $className; ?>Test extends TestCase {
<?php endif; ?>

<?php if (!empty($fixtures)): ?>
/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = [
		'<?php echo join("',\n\t\t'", $fixtures); ?>'
	];

<?php endif; ?>
<?php if (!empty($construction)): ?>
/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
<?php echo $preConstruct ? "\t\t" . $preConstruct : ''; ?>
		$this-><?php echo $subject . ' = ' . $construction; ?>
<?php echo $postConstruct ? "\t\t" . $postConstruct : ''; ?>
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this-><?php echo $subject; ?>);

		parent::tearDown();
	}

<?php endif; ?>
<?php foreach ($methods as $method): ?>
/**
 * test<?php echo Inflector::camelize($method); ?> method
 *
 * @return void
 */
	public function test<?php echo Inflector::camelize($method); ?>() {
	}

<?php endforeach; ?>
}
