<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Core\Plugin;
use Cake\Core\Configure;
$namespace = Configure::read('App.namespace');

$pluginPath = Configure::read('App.paths.plugins.0');
$pluginDot = empty($plugin) ? null : $plugin . '.';

if (empty($plugin)) {
    $filePath = APP_DIR . DIRECTORY_SEPARATOR;
    $namespace = str_replace('/', '\\', $plugin);
}
if (!empty($plugin) && Plugin::isLoaded($plugin)) {
    $filePath = Plugin::classPath($plugin);
}
if (!empty($plugin) && !Plugin::isLoaded($plugin)) {
    $filePath = $pluginPath . h($plugin) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
}

$this->layout = 'dev_error';
$this->assign('title', 'Missing View');
$this->assign('templateName', 'missing_view.ctp');

$this->start('subheading');
?>
    <strong>Error: </strong>
    <em><?= h($pluginDot . $class) ?></em> could not be found.
    <?php if (!empty($plugin) && !Plugin::isLoaded($plugin)): ?>
    Make sure your plugin <em><?= h($plugin) ?></em> is in the <?= h($pluginPath) ?> directory and was loaded.
    <?php endif ?>
    <?= $this->element('plugin_class_error', ['pluginPath' => $pluginPath]) ?>

<?php $this->end() ?>

<?php $this->start('file') ?>
<p class="error">
    <strong>Error: </strong>
    <?= sprintf('Create the class <em>%s</em> below in file: %s', h($class), $filePath . 'View' . DIRECTORY_SEPARATOR . h($class) . '.php'); ?>
</p>
<?php
$code = <<<PHP
<?php
namespace {$namespace}\View;

use Cake\View\View;

class {$class}View extends View
{

}
PHP;
?>
<div class="code-dump"><?php highlight_string($code) ?></div>
<?php $this->end() ?>
