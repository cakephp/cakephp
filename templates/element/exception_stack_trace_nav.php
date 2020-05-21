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
 * @var array $trace
 */
use Cake\Error\Debugger;
?>
<a href="#" class="toggle-link toggle-vendor-frames">Toggle Vendor Stack Frames</a>

<ul class="stack-trace">
<?php foreach ($trace as $i => $stack): ?>
    <?php
    $class = isset($stack['file']) && strpos($stack['file'], APP) === false ? 'vendor-frame' : 'app-frame';
    $class .= $i == 0 ? ' active' : '';
    ?>
    <li class="stack-frame <?= $class ?>">
        <a href="#" data-target="stack-frame-<?= $i ?>">
            <?php if (isset($stack['class'])): ?>
                <span class="stack-function"><?= h($stack['class'] . $stack['type'] . $stack['function']) ?></span>
            <?php elseif (isset($stack['function'])): ?>
                <span class="stack-function"><?= h($stack['function']) ?></span>
            <?php endif; ?>
            <span class="stack-file">
            <?php if (isset($stack['file'], $stack['line'])): ?>
                <?= h(Debugger::trimPath($stack['file'])) ?>:<?= $stack['line'] ?>
            <?php else: ?>
                [internal function]
            <?php endif ?>
            </span>
        </a>
    </li>
<?php endforeach; ?>
</ul>
