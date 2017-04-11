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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Error\Debugger;
?>
<a href="#" class="toggle-link toggle-vendor-frames">toggle vendor stack frames</a>

<ul class="stack-trace">
<?php foreach ($error->getTrace() as $i => $stack): ?>
    <?php $class = (isset($stack['file']) && strpos($stack['file'], APP) === false) ? 'vendor-frame' : 'app-frame'; ?>
    <li class="stack-frame <?= $class ?>">
    <?php if (isset($stack['function'])): ?>
        <a href="#" data-target="stack-frame-<?= $i ?>">
            <?php if (isset($stack['class'])): ?>
                <span class="stack-function">&rang; <?= h($stack['class'] . $stack['type'] . $stack['function']) ?></span>
            <?php else: ?>
                <span class="stack-function">&rang; <?= h($stack['function']) ?></span>
            <?php endif; ?>
            <span class="stack-file">
            <?php if (isset($stack['file'], $stack['line'])): ?>
                <?= h(Debugger::trimPath($stack['file'])) ?>, line <?= $stack['line'] ?>
            <?php else: ?>
                [internal function]
            <?php endif ?>
            </span>
        </a>
    <?php else: ?>
        <a href="#">&rang; [internal function]</a>
    <?php endif; ?>
    </li>
<?php endforeach; ?>
</ul>
