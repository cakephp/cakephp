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
 * @var \Cake\Core\Exception\CakeException $error
 */

use Cake\Error\Debugger;
use Cake\Routing\Router;
use function Cake\Core\h;

$this->layout = 'dev_error';

$this->assign('title', 'Missing Route');
$this->assign('templateName', 'missing_route.php');

$attributes = $error->getAttributes();

$this->start('subheading');
?>
    <strong>Error</strong>
    <?= h($error->getMessage()); ?>
<?php $this->end() ?>

<?php $this->start('file') ?>
<p>None of the currently connected routes match the provided parameters.
Add a matching route to <?= 'config' . DIRECTORY_SEPARATOR . 'routes.php' ?></p>

<?php if (!empty($attributes['context'])): ?>
<p>The passed context was:</p>
<div class="cake-debug">
    <?= Debugger::exportVar($attributes['context']); ?>
</div>
<?php endif; ?>

<h3>Connected Routes</h3>
<table cellspacing="0" cellpadding="0" width="100%">
<tr><th>Template</th><th>Defaults</th><th>Options</th></tr>
<?php foreach (Router::routes() as $route): ?>
    <tr>
        <td><?= h($route->template) ?></td>
        <td><div class="cake-debug" data-open-all="true"><?= Debugger::exportVar($route->defaults) ?></div></td>
        <td><div class="cake-debug" data-open-all="true"><?= Debugger::exportVar($route->options) ?></div></td>
    </tr>
<?php endforeach; ?>
</table>
<?php $this->end() ?>
