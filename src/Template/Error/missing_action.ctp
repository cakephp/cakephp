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
	$prefix .= DS;
}
if (empty($plugin)) {
	$path = APP_DIR . DS . 'Controller' . DS . $prefix . h($controller) . '.php' ;
} else {
	$path = Plugin::classPath($plugin) . 'Controller' . DS . $prefix . h($controller) . '.php';
}

$this->extend('dev_error');

$heading = sprintf('Missing Method in %s', h($controller));

$this->assign('title', $heading);
$this->assign('heading', $heading);

$this->assign(
	'subheading',
	sprintf('The action <em>%s</em> is not defined in controller <em>%s</em>', h($action), h($controller))
);
$this->assign('templateName', 'missing_action.ctp');

$this->start('file');
?>
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
<?php $this->end() ?>
