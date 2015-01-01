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
use Cake\Core\Plugin;

if (empty($plugin)) {
    return;
}

echo '<br><br>';

if (!Plugin::loaded($plugin)):
    echo sprintf('Make sure your plugin <em>%s</em> is in the %s directory and was loaded.', h($plugin), $pluginPath);
else:
    echo sprintf('Make sure your plugin was loaded from %s and Composer is able to autoload its classes, see %s and %s',
        '<em>config' . DS . 'bootstrap.php</em>',
        '<a href="http://book.cakephp.org/3.0/en/plugins.html#loading-a-plugin">Loading a plugin</a>',
        '<a href="http://book.cakephp.org/3.0/en/plugins.html#autoloading-plugin-classes">Plugins - autoloading plugin classes</a>'
    );
endif;

?>
