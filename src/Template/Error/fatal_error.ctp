<?php
/**
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
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

?>
<h2>Fatal Error</h2>
<p class="error">
	<strong>Error: </strong>
	<?= h($error->getMessage()); ?>
	<br>

	<strong>File</strong>
	<?= h($error->getFile()); ?>
	<br>

	<strong>Line: </strong>
	<?= h($error->getLine()); ?>
</p>
<p class="notice">
	<strong>Notice: </strong>
	<?= sprintf('If you want to customize this error message, create %s', APP_DIR . DS . 'Template' . DS . 'Error' . DS . 'fatal_error.ctp'); ?>
</p>
