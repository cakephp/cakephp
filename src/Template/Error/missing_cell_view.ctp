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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Utility\Inflector;

$this->layout = 'dev_error';

$this->assign('templateName', 'missing_cell_view.ctp');
$this->assign('title', 'Missing Cell View');

$this->start('subheading');
printf('The view for <em>%sCell</em> was not be found.', h(Inflector::camelize($name)));
$this->end();

$this->start('file');
?>
<p>
    Confirm you have created the file: "<?= h($file . $this->_ext) ?>"
    in one of the following paths:
</p>
<ul>
<?php
    $paths = $this->_paths($this->plugin);
    foreach ($paths as $path):
        if (strpos($path, CORE_PATH) !== false) {
            continue;
        }
        echo sprintf('<li>%sCell/%s/%s</li>', h($path), h($name), h($file . $this->_ext));
    endforeach;
?>
</ul>
<?php $this->end(); ?>
