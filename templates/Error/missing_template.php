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
 * @var string $file
 * @var list<string> $paths
 */
use Cake\Utility\Inflector;
use function Cake\Core\h;

$this->layout = 'dev_error';

$this->assign('title', 'Missing Template');
$this->assign('templateName', 'missing_template.php');

$isEmail = str_starts_with($file, 'Email/');

$this->start('subheading');
?>
<?php if ($isEmail): ?>
    <strong>Error</strong>
    <?= sprintf('The template %s</em> was not found.', h($file)); ?>
<?php else: ?>
    <strong>Error</strong>
    <?= sprintf(
        'The view for <em>%sController::%s()</em> was not found.',
        h(Inflector::camelize($this->request->getParam('controller', ''))),
        h($this->request->getParam('action'))
    ); ?>
<?php endif ?>
<?php $this->end() ?>

<?php $this->start('file') ?>
<p>
    <?= sprintf('Confirm you have created the file: "%s"', h($file)) ?>
    in one of the following paths:
</p>
<ul>
<?php
    foreach ($paths as $path):
        if (str_contains($path, CORE_PATH)) {
            continue;
        }
        echo sprintf('<li>%s%s</li>', h($path), h($file));
    endforeach;
?>
</ul>
<?php $this->end() ?>
