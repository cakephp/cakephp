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
$this->layout = 'dev_error';

$this->assign('templateName', 'missing_connection.ctp');
$this->assign('title', 'Missing Database Connection');


$this->start('subheading'); ?>
A Database connection using was missing or unable to connect.
<br/>
<?php
if (isset($reason)):
    echo sprintf('The database server returned this error: %s', h($reason));
endif;
$this->end();

$this->start('file');
echo $this->element('auto_table_warning');
$this->end();
