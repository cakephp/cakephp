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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var string $action
 */
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;
use function Cake\Core\h;

$namespace = Configure::read('App.namespace');
if (!empty($plugin)) {
    $namespace = str_replace('/', '\\', $plugin);
}
$prefixNs = '';
$prefix = $prefix ?? '';
if ($prefix) {
    $prefix = array_map('Cake\Utility\Inflector::camelize', explode('/', $prefix));
    $prefixNs = '\\' . implode('\\', $prefix);
    $prefix = implode(DIRECTORY_SEPARATOR, $prefix) . DIRECTORY_SEPARATOR;
}

$type = 'Controller';
$class = Inflector::camelize($controller);

if (empty($plugin)) {
    $path = APP_DIR . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $prefix . h($class) . '.php';
} else {
    $path = Plugin::classPath($plugin) . $type . DIRECTORY_SEPARATOR . $prefix . h($class) . '.php';
}

$this->layout = 'dev_error';

$this->assign('title', sprintf('Invalid Parameter', h($class)));
$this->assign(
    'subheading',
    sprintf('<strong>Error</strong> The passed parameter or parameter type is invalid in <em>%s::%s()</em>', h($class), h($action))
);
$this->assign('templateName', 'invalid_parameter.php');

$this->start('file');
?>
<p class="error">
    <strong>Error</strong>
    <?= h($message); ?>
</p>

<div class="code-dump"><?php highlight_string($code) ?></div>
<?php $this->end() ?>
