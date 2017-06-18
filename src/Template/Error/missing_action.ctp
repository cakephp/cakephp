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
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\Core\Plugin;

$namespace = Configure::read('App.namespace');
if (!empty($plugin)) {
    $namespace = str_replace('/', '\\', $plugin);
}
$prefixNs = '';
if (!empty($prefix)) {
    $prefix = Inflector::camelize($prefix);
    $prefixNs = '\\' . $prefix;
    $prefix .= DIRECTORY_SEPARATOR;
}

// Controller MissingAction support
if (isset($controller)) {
    $baseClass = $namespace . '\Controller\AppController';
    $extends = 'AppController';
    $type = 'Controller';
    $class = Inflector::camelize($controller);
}
// Mailer MissingActionException support
if (isset($mailer)) {
    $baseClass = 'Cake\Mailer\Mailer';
    $type = $extends = 'Mailer';
    $class = Inflector::camelize($mailer);
}

if (empty($plugin)) {
    $path = APP_DIR . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $prefix . h($class) . '.php' ;
} else {
    $path = Plugin::classPath($plugin) . $type . DIRECTORY_SEPARATOR . $prefix . h($class) . '.php';
}

$this->layout = 'dev_error';

$this->assign('title', sprintf('Missing Method in %s', h($class)));
$this->assign(
    'subheading',
    sprintf('The action <em>%s</em> is not defined in <em>%s</em>', h($action), h($class))
);
$this->assign('templateName', 'missing_action.ctp');

$this->start('file');
?>
<p class="error">
    <strong>Error: </strong>
    <?= sprintf('Create <em>%s::%s()</em> in file: %s.', h($class),  h($action), $path); ?>
</p>

<?php
$code = <<<PHP
<?php
namespace {$namespace}\\{$type}{$prefixNs};

use {$baseClass};

class {$class} extends {$extends}
{

    public function {$action}()
    {

    }
}
PHP;
?>

<div class="code-dump"><?php highlight_string($code) ?></div>
<?php $this->end() ?>
