<?php
/**
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
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Core\Plugin;

$pluginDot = empty($plugin) ? null : $plugin . '.';
?>
<h2>Missing Component</h2>
<p class="error">
	<strong>Error: </strong>
	<?= sprintf('<em>%s</em> could not be found.', h($pluginDot . $class)); ?>
</p>
<p class="error">
	<strong>Error: </strong>
	<?= sprintf('Create the class <em>%s</em> below in file: %s', h($class), (empty($plugin) ? APP_DIR : Plugin::path($plugin)) . DS . 'Controller' . DS . 'Component' . DS . h($class) . '.php'); ?>
</p>
<pre>
&lt;?php
class <?= h($class); ?> extends Component {

}
</pre>
<p class="notice">
	<strong>Notice: </strong>
	<?= sprintf('If you want to customize this error message, create %s', APP_DIR . DS . 'Template' . DS . 'Error' . DS . 'missing_component.ctp'); ?>
</p>

<?= $this->element('exception_stack_trace'); ?>
