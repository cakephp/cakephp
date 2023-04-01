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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var string $name
 * @var string $file
 * @var array<string> $paths
 */
use function Cake\Core\h;

$this->layout = 'dev_error';

$this->assign('templateName', 'missing_cell_view.php');
$this->assign('title', 'Missing Cell View');

$this->start('subheading');
printf('The view for <em>%sCell</em> was not be found.', h($name));
$this->end();

$this->start('file');
?>
<p>
    Confirm you have created the file: "<?= h($file) ?>"
    in one of the following paths:
</p>
<ul>
<?php
    foreach ($paths as $path) :
        if (str_contains($path, CORE_PATH)) {
            continue;
        }
        echo sprintf('<li>%sCell/%s/%s</li>', h($path), h($name), h($file));
    endforeach;
?>
</ul>
<?php $this->end(); ?>
