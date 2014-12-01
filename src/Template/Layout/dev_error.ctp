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
<!DOCTYPE html>
<html>
<head>
	<?= $this->Html->charset() ?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>
		Error: <?= $this->fetch('title') ?>
	</title>
	<?= $this->Html->meta('icon') ?>
</head>
<body>
	<header>
		<h1 class="header-title">
			<?= $this->fetch('title') ?>
			<span class="header-type"><?= get_class($error) ?></span>
		</h1>
		<div class="header-help">
			<a target="_blank" href="http://book.cakephp.org/3.0/">Documentation</a>
			<a target="_blank" href="http://api.cakephp.org/3.0/">API</a>
		</div>
	</header>

	<div class="error-contents">
		<p class="error-subheading">
			<?= $this->fetch('subheading') ?>
		</p>

		<?= $this->element('exception_stack_trace'); ?>

		<div class="error-suggestion">
			<?= $this->fetch('file') ?>
		</div>

		<?php if ($this->fetch('templateName')): ?>
		<p class="notice">
			If you want to customize this error message, create
			<em><?= APP_DIR . DS . 'Template' . DS . 'Error' . DS . $this->fetch('templateName') ?></em>
		</p>
		<?php endif; ?>
	</div>

	<div class="error-nav">
		<?= $this->element('exception_stack_trace_nav') ?>
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
	bindEvent('.stack-frame-args', 'click', function() {
		var target = this.dataset['target'];
		var el = document.getElementById(target);
		toggleElement(el);
		return false;
	});

	var details = document.querySelectorAll('.stack-details');
	var frames = document.querySelectorAll('.stack-frame');
	bindEvent('.stack-frame a', 'click', function() {
		each(frames, function(el) {
			el.classList.remove('active');
		});
		this.parentNode.classList.add('active');

		each(details, function(el) {
			el.style.display = 'none';
		});

		var target = document.getElementById(this.dataset['target']);
		toggleElement(target);
		return false;
	});
});
</script>

<style>
body {
	font: 14px helvetica, arial, sans-serif;
	color: #222;
	background-color: #D4D4D4;
	padding:0;
	margin: 0;
	max-height: 100%;
}

.code-dump,
pre {
	background: #fefefe;
	border: 1px solid #ddd;
	padding: 5px;
}

header {
	background-color: #C3232D;
	color: #ffffff;
	padding: 16px 10px;
	border-bottom: 3px solid #626262;
}
.header-title {
	margin: 0;
	font-weight: normal;
	font-size: 30px;
	line-height: 64px;
}
.header-type {
	opacity: 0.75;
	display: block;
	font-size: 16px;
	line-height: 1;
}
.header-help {
	font-size: 12px;
	line-height: 1;
	position: absolute;
	top: 30px;
	right: 16px;
}
.header-help a {
	color: #fff;
}

.error-nav {
	float: left;
	width: 30%;
}
.error-contents {
	padding: 10px 1%;
	float: right;
	width: 68%;
}

.error,
.error-subheading {
	font-size: 18px;
	margin-top: 0;
	padding: 10px;
}
.error-subheading {
	background: #1798A5;
	color: #fff;
}
.error {
	background: #ffd54f;
}

.stack-trace {
	list-style: none;
	margin: 0;
	padding: 0;
}
.stack-frame {
	padding: 10px;
}
.stack-frame a {
	display: block;
	color: #212121;
}
.stack-frame.active {
	background: #f5f5f5;
}
.stack-file,
.stack-function {
	display: block;
	margin-bottom: 5px;
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
}

.stack-details {
	background: #ececec;
	box-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
	padding: 10px;
	margin-bottom: 18px;
}
.stack-frame-args {
	float: right;
}

.code-excerpt {
	width: 100%;
	margin: 5px 0;
	background: #fefefe;
}
.code-highlight {
	display: block;
	background: #fff59d;
}
.excerpt-line {
	padding-left: 2px;
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
</style>

</body>
</html>
