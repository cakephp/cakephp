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
 * @package       Cake.View.Errors
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Core\Plugin;
use Cake\Core\Configure;
use Cake\Utility\Inflector;

$pluginDot = empty($plugin) ? null : $plugin . '.';
$namespace = Configure::read('App.namespace');
$prefixNs = '';
$prefixPath = '';

if (!empty($prefix)) {
	$prefix = Inflector::camelize($prefix);
	$prefixNs = '\\' . $prefix;
	$prefixPath = $prefix . DS;
}

if (!empty($plugin)) {
	$namespace = $plugin;
}
if (empty($plugin)) {
	$path = APP_DIR . DS . 'Controller' . DS . $prefixPath . h($class) . 'Controller.php' ;
} else {
	$path = Plugin::path($plugin) . 'Controller' . DS . $prefixPath . h($class) . 'Controller.php';
}

?>
<h2><?php echo __d('cake_dev', 'Missing Controller'); ?></h2>
<p class="error">
	<strong><?php echo __d('cake_dev', 'Error'); ?>: </strong>
	<?php echo __d('cake_dev', '%s could not be found.', '<em>' . h($pluginDot . $class) . 'Controller</em>'); ?>
</p>
<p class="error">
	<strong><?php echo __d('cake_dev', 'Error'); ?>: </strong>
	<?php echo __d('cake_dev', 'Create the class %s below in file: %s', '<em>' . h($class) . 'Controller</em>', $path); ?>
</p>
<pre>
&lt;?php
namespace <?= h($namespace); ?>\Controller<?= h($prefixNs); ?>;

use <?= h($namespace); ?>\Controller\AppController;

class <?php echo h($class) . 'Controller extends ' . h($plugin); ?>AppController {

}
</pre>
<p class="notice">
	<strong><?php echo __d('cake_dev', 'Notice'); ?>: </strong>
	<?php echo __d('cake_dev', 'If you want to customize this error message, create %s', APP_DIR . DS . 'View' . DS . 'Errors' . DS . 'missing_controller.ctp'); ?>
</p>

<?php echo $this->element('exception_stack_trace'); ?>
