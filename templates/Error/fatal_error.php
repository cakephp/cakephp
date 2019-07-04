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
 * @since         2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
$this->layout = 'dev_error';

$this->assign('title', 'Fatal Error');
$this->assign('templateName', 'fatal_error.php');

$this->start('subheading');
?>
    <strong>Error</strong>
    <?= h($error->getMessage()) ?>
    <br>
    <br>

    <strong>File</strong>
    <?= h($error->getFile()) ?>
    <br><br>
    <strong>Line</strong>
    <?= h($error->getLine()) ?>
<?php $this->end() ?>

<?php
$this->start('file');
if (extension_loaded('xdebug')):
    xdebug_print_function_stack();
endif;
$this->end();
