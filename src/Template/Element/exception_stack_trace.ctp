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
	<div id="stack-frame-<?= $i ?>" style="display:none;" class="stack-details">
		<span class="stack-frame-file"><?= h($file) ?></span>
		<a href="#" class="stack-frame-args" data-target="stack-args-<?= $i ?>">show arguments</a>
		<div class="code-dump">
			<pre><?= implode("\n", $excerpt) ?></pre>
		</div>
		<div class="code-dump" id="stack-args-<?= $i ?>" style="display: none;">
			<pre><?= implode("\n", $params) ?></pre>
		</div>
	</div>
<?php endforeach; ?>
