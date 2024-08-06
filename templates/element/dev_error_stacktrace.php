<?php
/**
 * Prints a stack trace for an exception
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var array $trace
 */
use Cake\Error\Debugger;
use function Cake\Core\h;


foreach ($exceptions as $level => $exc):
    $parent = $exceptions[$level - 1] ?? null;
    $stackTrace = Debugger::getUniqueFrames($exc, $parent);
    $stackTrace = Debugger::formatTrace($stackTrace, [
        'format' => 'array',
        'args' => true,
    ]);

    if ($level != 0): ?>
        <div class="stack-exception-header">
            <span class="stack-exception-caused">Caused by</span>
            <span class="stack-exception-message"><?= Debugger::formatHtmlMessage($exc->getMessage()) ?></span>
            <span class="stack-exception-type"><?= h($exc::class); ?></span>
        </div>
    <?php endif; ?>

    <div class="stack-frame">
        <?php
        $line = $exc->getLine();
        $file = $exc->getFile();
        $excerpt = Debugger::excerpt($file, $line, 4);

        $lineno = $line ? $line - 4 : 0;
        ?>
        <span class="stack-frame-file">
            <?= h(Debugger::trimPath($file)); ?> at line <?= h($line) ?>
        </span>
        <?php if ($line !== null): ?>
            <?= $this->Html->link('(edit)', Debugger::editorUrl($file, $line), ['class' => 'stack-frame-edit']); ?>
        <?php endif; ?>

        <table class="code-excerpt" cellspacing="0" cellpadding="0">
        <?php foreach ($excerpt as $l => $line): ?>
            <tr>
                <td class="excerpt-number" data-number="<?= $lineno + $l ?>"></td>
                <td class="excerpt-line"><?= $line ?></td>
            </tr>
        <?php endforeach; ?>
        </table>
    </div>

    <ul class="stack-frames">
    <?php
    foreach ($stackTrace as $i => $stack):
        $excerpt = [];
        $params = [];
        $line = null;
        if (isset($stack['file'], $stack['line']) && is_numeric($stack['line'])):
            $line = $stack['line'];
            $excerpt = Debugger::excerpt($stack['file'], $line, 4);
        endif;

        if (isset($stack['file'])):
            $file = $stack['file'];
        else:
            $file = '[internal function]';
        endif;

        if (isset($stack['function'])):
            if (!empty($stack['args'])):
                foreach ((array)$stack['args'] as $arg):
                    $params[] = Debugger::exportVar($arg, 4);
                endforeach;
            else:
                $params[] = 'No arguments';
            endif;
        endif;

        $frameId = "{$level}-{$i}";
        $activeFrame = $i == 0;
        $vendorFrame = isset($stack['file']) && str_starts_with($stack['file'], ROOT . DS . 'vendor') ? 'vendor-frame' : '';
    ?>
        <li id="stack-frame-<?= $frameId ?>" class="stack-frame <?= $vendorFrame ?>">
            <div class="stack-frame-header">
                <button data-frame-id="<?= h($frameId) ?>" class="stack-frame-toggle">
                    &#x25BC;
                </button>

                <div class="stack-frame-header-content">
                    <span class="stack-frame-file">
                        <?= h(Debugger::trimPath($file)); ?>
                    </span>

                    <?php if ($line !== null): ?>
                        <span class="stack-frame-line">
                            <span class="stack-frame-label">at line</span><?= h($line) ?>
                        </span>
                    <?php endif ?>

                    <span class="stack-function">
                        <?php if (isset($stack['class']) || isset($stack['function'])): ?>
                            <span class="stack-frame-label">in</span>
                        <?php endif ?>
                        <?php if (isset($stack['class'])): ?>
                            <?= h($stack['class'] . $stack['type'] . $stack['function']) ?>
                        <?php elseif (isset($stack['function'])): ?>
                            <?= h($stack['function']) ?>
                        <?php endif; ?>
                    </span>

                    <?php if ($line !== null): ?>
                        <?= $this->Html->link('(edit)', Debugger::editorUrl($file, $line), ['class' => 'stack-frame-edit']); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div
                class="stack-frame-contents"
                id="stack-frame-details-<?= $frameId ?>"
                style="display: none"
            >
                <table class="code-excerpt" cellspacing="0" cellpadding="0">
                <?php $lineno = isset($stack['line']) && is_numeric($stack['line']) ? $stack['line'] - 4 : 0 ?>
                <?php foreach ($excerpt as $l => $line): ?>
                    <tr>
                        <td class="excerpt-number" data-number="<?= $lineno + $l ?>"></td>
                        <td class="excerpt-line"><?= $line ?></td>
                    </tr>
                <?php endforeach; ?>
                </table>

                <a href="#" class="stack-frame-args" data-target="stack-args-<?= $frameId ?>">Toggle Arguments</a>
                <div id="stack-args-<?= $frameId ?>" class="stack-args" style="display: none;">
                    <?php foreach ($params as $param): ?>
                        <div class="cake-debug"><?= $param ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endforeach; ?>
