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
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Database\Exception\QueryException;
use function Cake\Core\h;

$this->setLayout('dev_error');

$this->assign('title', 'Database Error');
$this->assign('templateName', 'pdo_error.php');

$this->start('subheading');
?>
    <strong>Error</strong>
    <?= h($message); ?>
<?php $this->end() ?>

<?php $this->start('file') ?>
<p class="notice">
    If you are using SQL keywords as table column names, you can enable identifier
    quoting for your database connection in config/app.php.
</p>
<?php if ($error instanceof QueryException) : ?>
    <p class="notice">
        <strong>SQL Query: </strong>
    </p>
    <pre><?= h($error->getQueryString()); ?></pre>
<?php endif; ?>
<?= $this->element('auto_table_warning'); ?>
<?php $this->end() ?>
