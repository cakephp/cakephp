<?php
/**
 * Prints a stack trace for an exception
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Error\Debugger;
?>
<div class="exception-stack-trace columns large-2 large-pull-10">
	<h3>Stack Trace</h3>
	<ul>
	<?php foreach ($error->getTrace() as $i => $stack): ?>
		<li class="cake-stack-frame"><?php
		$excerpt = $arguments = '';
		$params = array();

		if (isset($stack['file']) && isset($stack['line'])):
			printf(
				'<a href="#" onclick="cakeExpand(event, \'stack-frame-%s\')">%s line %s</a>',
				$i,
				Debugger::trimPath($stack['file']),
				$stack['line']
			);
		else:
			echo '<a href="#">[internal function]</a>';
		endif;
		?></li>
	<?php endforeach; ?>
	</ul>
</div>

<div class="columns large-10 large-push-2">
<?php
foreach ($error->getTrace() as $i => $stack):
	$excerpt = $params = [];

	if (isset($stack['file']) && isset($stack['line'])):
		// TODO add line numbers
		$excerpt = Debugger::excerpt($stack['file'], $stack['line'] - 1, 2);
	endif;

	if (isset($stack['file'])):
		$file = $stack['file'];
	else:
		$file = '[internal function]';
	endif;

	if ($stack['function']):
		if (!empty($stack['args'])):
			foreach ((array)$stack['args'] as $arg):
				$params[] = Debugger::exportVar($arg, 4);
			endforeach;
		else:
			$params[] = 'No arguments';
		endif;
	endif;
?>
	<div id="stack-frame-<?= $i ?>" style="display:none;" class="cake-stack-details">
		<span class="stack-frame-file"><?= h($file) ?></span>
		<a href="#" onclick="cakeToggle(event, 'stack-args-<?= $i ?>')">show arguments</a>
		<div class="cake-code-dump">
			<pre><?= implode("\n", $excerpt) ?></pre>
		</div>
		<div class="cake-code-dump" id="stack-args-<?= $i ?>" style="display: none;">
			<pre><?= implode("\n", $params) ?></pre>
		</div>
	</div>
<?php endforeach; ?>
</div>

<script type="text/javascript">
function cakeExpand(event, id) {
	var el = document.getElementById(id);

	var others = document.getElementsByClassName('cake-stack-details');
	for (var i = 0, len = others.length; i < len; i++) {
		others[i].style.display = 'none';
	}
	return cakeToggle(event, id);
}
function cakeToggle(event, id) {
	var el = document.getElementById(id);
	el.style.display = (el.style.display === 'block') ? 'none' : 'block';
	event.preventDefault();
	return false;
}
</script>
