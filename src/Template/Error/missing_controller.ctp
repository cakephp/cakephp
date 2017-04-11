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

$pluginDot = empty($plugin) ? null : $plugin . '.';
$namespace = Configure::read('App.namespace');
$prefixNs = '';
$prefixPath = '';

if (!empty($prefix)) {
    $prefix = array_map('\Cake\Utility\Inflector::camelize', explode('/', $prefix));
    $prefixNs = '\\' . implode('\\', $prefix);
    $prefixPath = implode(DIRECTORY_SEPARATOR, $prefix) . DIRECTORY_SEPARATOR;
}

if (!empty($plugin)) {
    $namespace = str_replace('/', '\\', $plugin);
}
if (empty($plugin)) {
    $path = APP_DIR . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . $prefixPath . h($class) . 'Controller.php' ;
} else {
    $path = Plugin::classPath($plugin) . 'Controller' . DIRECTORY_SEPARATOR . $prefixPath . h($class) . 'Controller.php';
}

$this->layout = 'dev_error';

$this->assign('title', 'Missing Controller');
$this->assign('templateName', 'missing_controller.ctp');

$this->start('subheading');
?>
<strong>Error: </strong>
<em><?= h($pluginDot . $class) ?>Controller</em> could not be found.
<p>
    In the case you tried to access a plugin controller make sure you added it to your composer file or you use the autoload option for the plugin.
</p>
<?php $this->end() ?>

<?php $this->start('file') ?>
<p class="error">
    <strong>Error: </strong>
    Create the class <em><?= h($class) ?>Controller</em> below in file: <?= h($path) ?>
</p>

<?php
$code = <<<PHP
<?php
namespace {$namespace}\Controller{$prefixNs};

use {$namespace}\Controller\AppController;

class {$class}Controller extends AppController
{

}
PHP;
?>
<div class="code-dump"><?php highlight_string($code) ?></div>
<?php $this->end() ?>
