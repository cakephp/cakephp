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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Core\Plugin;
use Cake\Core\Configure;

$pluginPath = Configure::read('App.paths.plugins.0');

$pluginDot = empty($plugin) ? null : $plugin . '.';
if (empty($plugin)) {
	$filePath = APP_DIR . DS;
}
if (!empty($plugin) && Plugin::loaded($plugin)) {
	$filePath = Plugin::classPath($plugin);
}
if (!empty($plugin) && !Plugin::loaded($plugin)) {
	$filePath = $pluginPath . h($plugin) . DS . 'src' . DS;
}
?>
<h2>Missing View</h2>
<p class="error">
	<strong>Error: </strong>
	<?= sprintf('<em>%s</em> could not be found.', h($pluginDot . $class)); ?>
	<?php
		if (!empty($plugin) && !Plugin::loaded($plugin)):
			echo sprintf('Make sure your plugin <em>%s</em> is in the %s directory and was loaded.', h($plugin), $pluginPath);
		endif;
	?>
</p>
<p class="error">
	<strong>Error: </strong>
	<?= sprintf('Create the class <em>%s</em> below in file: %s', h($class), $filePath . 'View' . DS . h($class) . '.php'); ?>
</p>
<pre>
&lt;?php
class <?= h($class); ?> extends View {

}
</pre>
<p class="notice">
	<strong>Notice: </strong>
	<?= sprintf('If you want to customize this error message, create %s', APP_DIR . DS . 'Template' . DS . 'Error' . DS . 'missing_view.ctp'); ?>
</p>

<?= $this->element('exception_stack_trace'); ?>
