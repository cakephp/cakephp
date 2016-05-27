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
use Cake\Utility\Inflector;

$this->layout = 'dev_error';

$this->assign('title', 'Missing Template');
$this->assign('templateName', 'missing_template.ctp');

$isEmail = strpos($file, 'Email/') === 0;

$this->start('subheading');
?>
<?php if ($isEmail): ?>
    <strong>Error: </strong>
    <?= sprintf('The template %s</em> was not found.', h($file)); ?>
<?php else: ?>
    <strong>Error: </strong>
    <?= sprintf('The view for <em>%sController::%s()</em> was not found.', h(Inflector::camelize($this->request->controller)), h($this->request->action)); ?>
<?php endif ?>
<?php $this->end() ?>

<?php $this->start('file') ?>
<p>
    <?= sprintf('Confirm you have created the file: "%s"', h($file)) ?>
    in one of the following paths:
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
