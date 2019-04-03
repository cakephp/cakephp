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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Core\Configure;

$this->layout = 'dev_error';

$pluginPath = Configure::read('App.paths.plugins.0');

$this->assign('title', 'Missing Plugin');
$this->assign('templateName', 'missing_plugin.ctp');

$this->start('subheading');
?>
    <strong>Error: </strong>
    The application is trying to load a file from the <em><?= h($plugin) ?></em> plugin.
    <br>
    <br>
    Make sure your plugin <em><?= h($plugin) ?></em> is in the <?= h($pluginPath) ?> directory and was loaded.
<?php $this->end() ?>

<?php $this->start('file') ?>
<?php
$code = <<<PHP
<?php
Plugin::load('{$plugin}');
PHP;

?>
<div class="code-dump"><?php highlight_string($code) ?></div>

<p class="notice">
    <strong>Loading all plugins: </strong>
    <?= sprintf('If you wish to load all plugins at once, use the following line in your %s file', 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php'); ?>
</p>

<?php
$code = <<<PHP
<?php
Plugin::loadAll();
PHP;
?>
<div class="code-dump"><?php highlight_string($code) ?></div>
<?php $this->end() ?>
