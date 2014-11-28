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
<ul class="stack-trace">
<?php foreach ($error->getTrace() as $i => $stack): ?>
	<li class="stack-frame">
	<?php if (isset($stack['file']) && isset($stack['line'])): ?>
		<a href="#" data-target="stack-frame-<?= $i ?>">
			<span class="stack-file"><?= Debugger::trimPath($stack['file']) ?></span>
			<?php if (!isset($stack['class'])): ?>
				<span class="stack-function"><?= h($stack['function']) ?></span>
			<?php else: ?>
				<span class="stack-function"><?= h($stack['class']) ?>::<?= h($stack['function']) ?></span>
			<?php endif; ?>
			<span class="stack-line">line <?= $stack['line'] ?></span>
		</a>
	<?php else: ?>
		<a href="#">[internal function]</a>
	<?php endif; ?>
	</li>
<?php endforeach; ?>
</ul>
