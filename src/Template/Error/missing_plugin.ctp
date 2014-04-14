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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
?>
<h2>Missing Plugin</h2>
<p class="error">
	<strong>Error: </strong>
	<?= sprintf('The application is trying to load a file from the <em>%s</em> plugin', h($plugin)); ?>
</p>
<p class="error">
	<strong>Error: </strong>
	<?= sprintf('Make sure your plugin %s is in the %s directory and was loaded', APP_DIR . DS . 'Plugin', h($plugin)); ?>
</p>
<pre>
&lt;?php
Plugin::load('<?= h($plugin)?>');

</pre>
<p class="notice">
	<strong>Loading all plugins: </strong>
	<?= sprintf('If you wish to load all plugins at once, use the following line in your %s file', APP_DIR . DS . 'Config' . DS . 'bootstrap.php'); ?>
</p>
<pre>
Plugin::loadAll();
</pre>
<p class="notice">
	<strong>Notice: </strong>
	<?= sprintf('If you want to customize this error message, create %s', APP_DIR . DS . 'Template' . DS . 'Error' . DS . 'missing_plugin.ctp'); ?>
</p>

<?= $this->element('exception_stack_trace'); ?>
