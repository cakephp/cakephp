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
 * @var \Cake\Core\Exception\CakeException $error
 */
use Cake\Error\Debugger;
use function Cake\Core\h;
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        Error: <?= h($this->fetch('title')) ?>
    </title>
    <?= $this->Html->meta('icon') ?>
    <style>
    * {
        box-sizing: border-box;
    }
    :root {
        --typeface: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
        --typeface-mono: consolas, monospace;

        --border-radius: 5px;
        --layout-padding: 30px;
        --layout-vertical-gap: 20px;

        --color-vendor-frame: #7c7c7c;

        --breakpoint-tablet: 810px;
    }

    /* Smaller viewport variations */
    @media (max-width: 810px) {
        :root {
            --layout-padding: 20px;
        }
    }

    body {
        font-family: var(--typeface);
        color: #404041;
        background: #F5F7FA;
        font-size: 14px;
        letter-spacing: .01em;
        line-height: 1.6;
        padding: 0 0 40px;
        margin: 0;
        height: 100%;
    }
    header {
        flex: 1;
        background-color: #D33C47;
        color: #ffffff;
        padding: var(--layout-padding);
    }
    .header-title {
        display: flex;
        align-items: center;
        font-size: 30px;
        margin: 0;
    }
    .header-title a {
        font-size: 18px;
        cursor: pointer;
        margin-left: 10px;
        user-select: none;
    }
    .header-title code {
        margin: 0 10px;
    }
    .header-description {
        display: block;
        font-size: 18px;
        line-height: 1.2;
        margin-bottom: var(--layout-vertical-gap);
    }
    .header-type {
        display: block;
        font-size: 16px;
    }
    .header-help a {
        color: #fff;
    }
    .error-content {
        padding: var(--layout-padding);
    }

    .code-dump,
    pre {
        background: #fff;
        border-radius: var(--border-radius);
        padding: 5px;
        white-space: pre-wrap;
        margin: 0;
    }

    .error,
    .error-subheading {
        border-radius: var(--border-radius);
        font-size: 18px;
        margin-top: 0;
        padding: var(--layout-vertical-gap) 16px;
    }
    .error-subheading {
        color: #fff;
        background-color: #319795;
    }
    .error-subheading strong {
        color: #fff;
        background-color: #4fd1c5;
        border-radius: 9999px;
        padding: 4px 12px;
        margin-right: 8px;
    }
    .error {
        color: #fff;
        background: #2779BD;
    }
    .error strong {
        color: #fff;
        background-color: #6CB2EB;
        border-radius: 9999px;
        padding: 4px 12px;
        margin-right: 8px;
    }

    .stack-trace {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    /* Previous exception blocks */
    .stack-exception-header {
        margin: 36px 0 12px 8px;
    }
    .stack-exception-caused {
        font-size: 1.6em;
        display: block;
        margin-bottom: var(--layout-vertical-gap);
    }
    .stack-exception-type {
        display: block;
        font-family: var(--typeface-mono);
    }
    .stack-exception-message {
        margin-bottom: 10px;
        font-size: 1.2em;
        font-weight: bold;
    }

    .stack-frames {
        list-style: none;
        padding: 0;
        margin: 0;
        border-radius: var(--border-radius);
    }
    .stack-frame {
        padding: 10px;
        background: #eaeaea;
        padding: 10px;
        border-bottom: 2px solid #f5f7fa;
        overflow: hidden;
    }
    .vendor-frame {
        background: #f1f1f1;
    }
    .stack-frame:first-child {
        border-radius: var(--border-radius) var(--border-radius) 0 0;
    }
    .stack-frame:last-child {
        border-radius: 0 0 var(--border-radius) var(--border-radius);
        border-bottom: none;
        margin-bottom: 0;
    }
    .stack-frame a {
        color: #212121;
        text-decoration: none;
    }
    .stack-frame.active {
        background: #F5F7FA;
    }
    .stack-frame a:hover {
        text-decoration: underline;
    }

    /* Stack frame headers */
    .stack-frame-header {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .stack-frame-header-content {
        display: flex;
        gap: 8px;
    }
    .vendor-frame .stack-frame-header-content,
    .vendor-frame .stack-frame-header-content  a {
        color: var(--color-vendor-frame);
    }
    @media (max-width: 810px) {
        .stack-frame-header-content {
            flex-direction: column;
        }
    }
    .stack-function,
    .stack-frame-file,
    .stack-frame-line,
    .stack-file {
        font-family: var(--typeface-mono);
    }
    .stack-file {
        font-size: 0.9em;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
        direction: rtl;
    }
    .stack-frame-file {
        word-break: break-all;
    }
    .stack-frame-label {
        font-family: var(--typeface);
        font-weight: normal;
        margin: 0 5px 0 0;
        font-size: 0.9em;
    }
    .stack-function .stack-frame-label {
        margin: 0;
    }

    .stack-frame-edit {
        margin: 0 5px 0 0;
    }
    .stack-frame-toggle {
        cursor: pointer;
        color: #525252;
        border: 1px solid #d2d2d2;
        border-radius: var(--border-radius);
        height: 28px;
        width: 28px;
        background: #F5F7FA;
        line-height: 1.5;
    }
    .stack-frame-toggle.active {
        transform: rotate(180deg);
    }
    .stack-frame-header .stack-frame-toggle {
        opacity: 0.7;
    }

    .stack-frame-args {
        display: block;
        margin: 10px 0 0 0;
    }
    .stack-frame-args:hover {
        color: #D33C47;
    }
    .stack-args h4 {
        margin-top: 0;
    }

    /* Suggestion and help context */
    .error-suggestion {
        margin-bottom: var(--layout-vertical-gap);
    }

    /* Code excerpts */
    .code-excerpt {
        width: 100%;
        margin: 10px 0 0 0;
        background: #fefefe;
    }
    .code-highlight {
        display: block;
        background: #fff59d;
        padding-left: 4px;
    }
    .excerpt-line {
        padding: 0;
    }
    /* php 8.3 adds pre around highlighted code */
    .code-highlight > pre,
    .excerpt-line > pre {
        padding: 0;
        background: none;
    }
    .excerpt-line > code {
        padding-left: 4px;
    }
    .excerpt-number {
        background: #f6f6f6;
        width: 50px;
        text-align: right;
        color: #666;
        border-right: 1px solid #ddd;
        padding: 2px 4px;
    }
    .excerpt-number:after {
        content: attr(data-number);
    }
    table {
        text-align: left;
    }
    th, td {
        padding: 4px;
    }
    th {
        border-bottom: 1px solid #ccc;
    }

    .cake-debug {
        margin-top: 10px;
    }
    </style>
    <?php require CAKE . 'Error/Debug/dumpHeader.html'; ?>
</head>
<body>
    <header>
        <?php
        $title = explode("\n", trim($this->fetch('title')));
        $errorTitle = array_shift($title);
        $errorDescription = implode("\n", $title);
        ?>
        <h1 class="header-title">
            <span><?= Debugger::formatHtmlMessage($errorTitle) ?></span>
            <a>&#128203</a>
        </h1>
        <?php if (strlen($errorDescription)) : ?>
            <span class="header-description"><?= Debugger::formatHtmlMessage($errorDescription) ?></span>
        <?php endif ?>
        <span class="header-type"><?= get_class($error) ?></span>
    </header>
    <div class="error-content">
        <?php if ($this->fetch('subheading')): ?>
        <p class="error-subheading">
            <?= $this->fetch('subheading') ?>
        </p>
        <?php endif; ?>

        <?php if ($this->fetch('file')): ?>
        <div class="error-suggestion">
            <?= $this->fetch('file') ?>
        </div>
        <?php endif; ?>

        <?= $this->element('dev_error_stacktrace'); ?>

        <?php if ($this->fetch('templateName')): ?>
        <p class="customize">
            If you want to customize this error message, create
            <em><?= 'templates' . DIRECTORY_SEPARATOR . 'Error' . DIRECTORY_SEPARATOR . $this->fetch('templateName') ?></em>
        </p>
        <?php endif; ?>
    </div>

    <script type="text/javascript">
        function bindEvent(selector, eventName, listener) {
            var els = document.querySelectorAll(selector);
            for (var i = 0, len = els.length; i < len; i++) {
                els[i].addEventListener(eventName, listener, false);
            }
        }

        function toggleElement(el) {
            if (el.style.display === 'none') {
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
            }
        }

        function each(els, cb) {
            var i, len;
            for (i = 0, len = els.length; i < len; i++) {
                cb(els[i], i);
            }
        }

        window.addEventListener('load', function() {
            bindEvent('.stack-frame-args', 'click', function(event) {
                var target = this.dataset['target'];
                var el = document.getElementById(target);
                toggleElement(el);
                event.preventDefault();
            });

            var details = document.querySelectorAll('.stack-details');
            var frames = document.querySelectorAll('.stack-frame');
            bindEvent('.stack-frame-toggle', 'click', function(event) {
                this.classList.toggle('active');

                var frameId = this.dataset.frameId;
                var frame = document.getElementById('stack-frame-details-' + frameId);
                toggleElement(frame);
                event.preventDefault();
            });

            bindEvent('.header-title a', 'click', function(event) {
                event.preventDefault();
                var text = '';
                each(this.parentNode.childNodes, function(el) {
                    if (el.nodeName !== 'A') {
                        text += el.textContent.trim();
                    }
                });

                // Use execCommand(copy) as it has the widest support.
                var textArea = document.createElement("textarea");
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                var el = this;
                try {
                    document.execCommand('copy');

                    // Show a success icon and then revert
                    var original = el.innerText;
                    el.innerText = '\ud83c\udf70';
                    setTimeout(function () {
                        el.innerText =  original;
                    }, 1000);
                } catch (err) {
                    alert('Unable to update clipboard ' + err);
                }
                document.body.removeChild(textArea);
                this.parentNode.parentNode.scrollIntoView(true);
            });
        });
    </script>
</body>
</html>
