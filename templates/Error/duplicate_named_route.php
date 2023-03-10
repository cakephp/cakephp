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
 * @since         3.3.1
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \Cake\Core\Exception\CakeException $error
 */

use Cake\Error\Debugger;
use function Cake\Core\h;

$this->layout = 'dev_error';

$this->assign('title', 'Duplicate Named Route');
$this->assign('templateName', 'duplicate_named_route.php');

$attributes = $error->getAttributes();

$this->start('subheading');
?>
    <strong>Error</strong>
    <?= h($error->getMessage()); ?>
<?php $this->end() ?>

<?php $this->start('file') ?>
<p>Route names must be unique across your entire application.
The same <code>_name</code> option cannot be used twice,
even if the names occur in different routing scopes.
Remove duplicate route names in your route configuration.</p>

<?php if (isset($attributes['duplicate'])): ?>
    <h3>Duplicate Route</h3>
    <table cellspacing="0" cellpadding="0" width="100%">
    <tr><th>Template</th><th>Defaults</th><th>Options</th></tr>
    <?php $other = $attributes['duplicate']; ?>
    <tr>
        <td><?= h($other->template) ?></td>
        <td><div class="cake-debug" data-open-all="true"><?= Debugger::exportVar($other->defaults) ?></div></td>
        <td><div class="cake-debug" data-open-all="true"><?= Debugger::exportVar($other->options) ?></div></td>
    </tr>
    </table>
<?php endif; ?>
<?php $this->end() ?>
