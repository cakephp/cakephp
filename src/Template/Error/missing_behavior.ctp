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
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Core\Plugin;
use Cake\Core\Configure;

$namespace = Configure::read('App.namespace');
if (!empty($plugin)) {
    $namespace = str_replace('/', '\\', $plugin);
}

$pluginPath = Configure::read('App.paths.plugins.0');

$pluginDot = empty($plugin) ? null : $plugin . '.';
if (empty($plugin)) {
    $filePath = APP_DIR . DIRECTORY_SEPARATOR;
}
if (!empty($plugin) && Plugin::loaded($plugin)) {
    $filePath = Plugin::classPath($plugin);
}
if (!empty($plugin) && !Plugin::loaded($plugin)) {
    $filePath = $pluginPath . h($plugin) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
}

$this->layout = 'dev_error';

$this->assign('templateName', 'missing_behavior.ctp');

$this->assign('title', 'Missing Behavior');

$this->start('subheading');
printf('<em>%s</em> could not be found.', h($pluginDot . $class));
echo $this->element('plugin_class_error', ['pluginPath' => $pluginPath]);
$this->end();

$this->start('file');
?>
<p class="error">
    <strong>Error: </strong>
    <?= sprintf('Create the class <em>%s</em> below in file: %s', h($class), $filePath . 'Model' . DIRECTORY_SEPARATOR . 'Behavior' . DIRECTORY_SEPARATOR . h($class) . '.php'); ?>
</p>

<?php
$code = <<<PHP
<?php
namespace {$namespace}\Model\Behavior;

use Cake\ORM\Behavior;

class {$class} extends Behavior
{

}
PHP;
?>
<div class="code-dump"><?php highlight_string($code) ?></div>
<?php $this->end() ?>
