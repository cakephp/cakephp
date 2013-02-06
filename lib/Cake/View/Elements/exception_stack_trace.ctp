<?php
/**
 * Prints a stack trace for an exception
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Elements
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Debugger', 'Utility');
?>
<h3>Stack Trace</h3>
<ul class="cake-stack-trace">
<?php foreach ($error->getTrace() as $i => $stack): ?>
	<li><?php
	$excerpt = $arguments = '';
	$params = array();

	if (isset($stack['file']) && isset($stack['line'])):
		printf(
			'<a href="#" onclick="traceToggle(event, \'file-excerpt-%s\')">%s line %s</a>',
			$i,
			Debugger::trimPath($stack['file']),
			$stack['line']
		);
		$excerpt = sprintf('<div id="file-excerpt-%s" class="cake-code-dump" style="display:none;"><pre>', $i);
		$excerpt .= implode("\n", Debugger::excerpt($stack['file'], $stack['line'] - 1, 2));
		$excerpt .= '</pre></div> ';
	else:
		echo '<a href="#">[internal function]</a>';
	endif;
	echo ' &rarr; ';
	if ($stack['function']):
		$args = array();
		if (!empty($stack['args'])):
			foreach ((array)$stack['args'] as $arg):
				$args[] = Debugger::getType($arg);
				$params[] = Debugger::exportVar($arg, 2);
			endforeach;
		endif;

		$called = isset($stack['class']) ? $stack['class'] . $stack['type'] . $stack['function'] : $stack['function'];
	
		printf(
			'<a href="#" onclick="traceToggle(event, \'trace-args-%s\')">%s(%s)</a> ',
			$i,
			$called,
			implode(', ', $args)
		);
		$arguments = sprintf('<div id="trace-args-%s" class="cake-code-dump" style="display: none;"><pre>', $i);
		$arguments .= implode("\n", $params);
		$arguments .= '</pre></div>';
	endif;
	echo $excerpt;
	echo $arguments;
	?></li>
<?php endforeach; ?>
</ul>
<script type="text/javascript">
function traceToggle(event, id) {
	var el = document.getElementById(id);
	el.style.display = (el.style.display == 'block') ? 'none' : 'block';
	event.preventDefault();
	return false;
}
</script>
