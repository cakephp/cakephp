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
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

$this->layout = 'dev_error';

$this->assign('title', 'Missing Layout');
$this->assign('templateName', 'missing_layout.ctp');

$this->start('subheading');
?>
    <strong>Error: </strong>
    The layout file <em><?= h($file) ?></em> can not be found or does not exist.
<?php $this->end() ?>

<?php $this->start('file') ?>
<p>
    Confirm you have created the file: <?= h($file) ?> in one of the following paths:
</p>
<ul>
<?php
    $paths = $this->_paths($this->plugin);
    foreach ($paths as $path):
        if (strpos($path, CORE_PATH) !== false) {
            continue;
        }
        echo sprintf('<li>%s%s</li>', h($path), h($file));
    endforeach;
?>
</ul>
<?php $this->end() ?>
