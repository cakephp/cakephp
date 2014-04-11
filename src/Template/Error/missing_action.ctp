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
use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\Core\Plugin;

$namespace = Configure::read('App.namespace');
if (!empty($plugin)) {
	$namespace = $plugin;
}
$prefixNs = '';
if (!empty($prefix)) {
	$prefix = Inflector::camelize($prefix);
	$prefixNs = '\\' . $prefix;
}
if (empty($plugin)) {
	$path = APP_DIR . DS . 'Controller' . DS . $prefix . DS . h($controller) . '.php' ;
} else {
	$path = Plugin::path($plugin) . 'Controller' . DS . $prefix . DS . h($class) . '.php';
}
?>
<h2><?= sprintf('Missing Method in %s', h($controller)); ?></h2> <p class="error">
	<strong>Error: </strong>
	<?= sprintf('The action <em>%s</em> is not defined in controller <em>%s</em>', h($action), h($controller)); ?>
</p>
<p class="error">
	<strong>Error: </strong>
	<?= sprintf('Create <em>%s::%s()</em> in file: %s.', h($controller),  h($action), $path); ?>
</p>
<pre>
&lt;?php
namespace <?= h($namespace); ?>\Controller<?= h($prefixNs); ?>;

use <?= h($namespace); ?>\Controller\AppController;

class <?= h($controller); ?> extends AppController {

<strong>
	public function <?= h($action); ?>() {

	}
</strong>
}
</pre>
<p class="notice">
	<strong>Notice: </strong>
	<?= sprintf('If you want to customize this error message, create %s', APP_DIR . DS . 'Template' . DS . 'Error' . DS . 'missing_action.ctp'); ?>
</p>
<?= $this->element('exception_stack_trace'); ?>
