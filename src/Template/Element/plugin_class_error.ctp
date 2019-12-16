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
 */
use Cake\Core\Plugin;

if (empty($plugin)) {
    return;
}

echo '<br><br>';

if (!Plugin::isLoaded($plugin)):
    echo sprintf('Make sure your plugin <em>%s</em> is in the %s directory and was loaded.', h($plugin), $pluginPath);
else:
    echo sprintf('Make sure your plugin was loaded from %s and Composer is able to autoload its classes, see %s and %s',
        '<em>config' . DIRECTORY_SEPARATOR . 'bootstrap.php</em>',
        '<a href="https://book.cakephp.org/3/en/plugins.html#loading-a-plugin">Loading a plugin</a>',
        '<a href="https://book.cakephp.org/3/en/plugins.html#autoloading-plugin-classes">Plugins - autoloading plugin classes</a>'
    );
endif;

?>
