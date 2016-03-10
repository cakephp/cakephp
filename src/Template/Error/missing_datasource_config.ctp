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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
$this->layout = 'dev_error';

$this->assign('title', 'Missing Datasource Configuration');
$this->assign('templateName', 'missing_datasource_config.ctp');

$this->start('subheading');
?>
    <strong>Error: </strong>
    <?php if (isset($name)): ?>
        The datasource configuration <em><?= h($name) ?></em> was not found in config<?= DIRECTORY_SEPARATOR . 'app.php' ?>.
    <?php else: ?>
        <?= h($message) ?>
    <?php endif; ?>
<?php $this->end() ?>
