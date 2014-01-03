<?php
/**
 *
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
 * @since         CakePHP(tm) v 2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

?>
<h2><?= __d('cake_dev', 'Fatal Error'); ?></h2>
<p class="error">
	<strong><?= __d('cake_dev', 'Error'); ?>: </strong>
	<?= h($error->getMessage()); ?>
	<br>

	<strong><?= __d('cake_dev', 'File'); ?>: </strong>
	<?= h($error->getFile()); ?>
	<br>

	<strong><?= __d('cake_dev', 'Line'); ?>: </strong>
	<?= h($error->getLine()); ?>
</p>
<p class="notice">
	<strong><?= __d('cake_dev', 'Notice'); ?>: </strong>
	<?= __d('cake_dev', 'If you want to customize this error message, create %s', APP_DIR . DS . 'View' . DS . 'Error' . DS . 'fatal_error.ctp'); ?>
</p>
