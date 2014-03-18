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
 * @since         0.10.0
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
<h2>Missing Controller</h2>
<p class="error">
	<strong>Error: </strong>
	<?= sprintf('<em>%sController</em> could not be found.', h($pluginDot . $class)); ?>
</p>
<p class="error">
	<strong>Error: </strong>
	<?= sprintf('Create the class <em>%sController</em> below in file: %s', h($class), $path); ?>
</p>
<pre>
&lt;?php
namespace <?= h($namespace); ?>\Controller<?= h($prefixNs); ?>;

use <?= h($namespace); ?>\Controller\AppController;

class <?= h($class) . 'Controller extends ' . h($plugin); ?>AppController {

}
</pre>
<p class="notice">
	<strong>Notice: </strong>
	<?= sprintf('If you want to customize this error message, create %s', APP_DIR . DS . 'Template' . DS . 'Error' . DS . 'missing_controller.ctp'); ?>
</p>

<?= $this->element('exception_stack_trace'); ?>
