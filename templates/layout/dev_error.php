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
 */
use Cake\Error\Debugger;
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
    body {
        font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
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
        padding: 10px;
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
        margin-bottom: 16px;
    }
    .header-type {
        display: block;
        font-size: 16px;
    }
    .header-help a {
        color: #fff;
    }

    .error-content {
        display: flex;
    }
    .col-left,
    .col-right {
        overflow-y: auto;
        padding: 10px;
    }
    .col-left {
        background: #ececec;
        flex: 0 0 30%;
    }
    .col-right {
        flex: 1;
    }

    .toggle-vendor-frames {
        color: #404041;
        display: block;
        padding: 5px;
        margin-bottom: 10px;
        text-align: center;
        text-decoration: none;
    }
    .toggle-vendor-frames:hover,
    .toggle-vendor-frames:active {
        background: #e5e5e5;
    }

    .code-dump,
    pre {
        background: #fff;
        border-radius: 4px;
        padding: 5px;
        white-space: pre-wrap;
        margin: 0;
    }

    .error,
    .error-subheading {
        font-size: 18px;
        margin-top: 0;
        padding: 20px 16px;
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
    .stack-frame {
        background: #e5e5e5;
        padding: 10px;
        margin-bottom: 10px;
    }
    .stack-frame:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    .stack-frame a {
        display: block;
        color: #212121;
        text-decoration: none;
    }
    .stack-frame.active {
        background: #F5F7FA;
    }
    .stack-frame a:hover {
        text-decoration: underline;
    }
    .stack-frame-header {
        display: flex;
        align-items: center;
    }
    .stack-frame-file a {
        color: #212121;
    }

    .stack-frame-args {
        flex: 0 0 150px;
        display: block;
        padding: 8px 14px;
        text-decoration: none;
        background-color: #606c76;
        border-radius: 4px;
        cursor: pointer;
        color: #fff;
        text-align: center;
        margin-bottom: 10px;
    }
    .stack-frame-args:hover {
        background-color: #D33C47;
    }

    .stack-frame-file {
        flex: 1;
        word-break:break-all;
        margin-right: 10px;
        font-size: 16px;
    }
    .stack-file,
    .stack-function {
        display: block;
    }

    .stack-frame-file,
    .stack-file {
        font-family: consolas, monospace;
    }
    .stack-function {
        font-weight: bold;
    }
    .stack-file {
        font-size: 0.9em;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
        direction: rtl;
    }

    .stack-details {
        background: #ececec;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 18px;
    }

    .code-excerpt {
        width: 100%;
        margin: 10px 0 0 0;
        background: #fefefe;
    }
    .code-highlight {
        display: block;
        background: #fff59d;
    }
    .excerpt-line {
        padding: 0;
    }
    .excerpt-number {
        background: #f6f6f6;
        width: 50px;
        text-align: right;
        color: #666;
        border-right: 1px solid #ddd;
        padding: 2px;
    }
    .excerpt-number:after {
        content: attr(data-number);
    }
    .cake-debug {
        margin-top: 10px;
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
    </style>
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
        <div class="col-left">
            <?= $this->element('exception_stack_trace_nav') ?>
        </div>
        <div class="col-right">
            <?php if ($this->fetch('subheading')): ?>
            <p class="error-subheading">
                <?= $this->fetch('subheading') ?>
            </p>
            <?php endif; ?>

            <?= $this->element('exception_stack_trace'); ?>

            <div class="error-suggestion">
                <?= $this->fetch('file') ?>
            </div>

            <?php if ($this->fetch('templateName')): ?>
            <p class="customize">
                If you want to customize this error message, create
                <em><?= 'templates' . DIRECTORY_SEPARATOR . 'Error' . DIRECTORY_SEPARATOR . $this->fetch('templateName') ?></em>
            </p>
            <?php endif; ?>
        </div>
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
            bindEvent('.stack-frame a', 'click', function(event) {
                each(frames, function(el) {
                    el.classList.remove('active');
                });
                this.parentNode.classList.add('active');

                each(details, function(el) {
                    el.style.display = 'none';
                });

                var target = document.getElementById(this.dataset['target']);
                toggleElement(target);
                event.preventDefault();
            });

            bindEvent('.toggle-vendor-frames', 'click', function(event) {
                each(frames, function(el) {
                    if (el.classList.contains('vendor-frame')) {
                        toggleElement(el);
                    }
                });
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
