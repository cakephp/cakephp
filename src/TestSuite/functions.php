<?php
declare(strict_types=1);

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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Core\Configure;
use RuntimeException;

/**
 * Helper function to load plugin configuration for tests.
 *
 * This function reads plugin configuration from a plugins.php file and stores it
 * in the application configuration for later use by test cases.
 *
 * Usage:
 * ```
 * // In your tests/bootstrap.php file:
 * \Cake\TestSuite\enablePluginLoadingForTests();
 * ```
 *
 * @param string|null $configPath The path to the config directory. Defaults to CONFIG.
 * @return void
 * @throws \RuntimeException When the plugins.php file returns invalid data
 * @since 5.3.0
 */
function enablePluginLoadingForTests(?string $configPath = null): void
{
    $configPath ??= CONFIG;
    $pluginsFile = rtrim($configPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plugins.php';

    if (!file_exists($pluginsFile)) {
        throw new RuntimeException('Unable to load plugins list');
    }

    // phpcs:ignore
    $plugins = require $pluginsFile;
    if (!is_array($plugins)) {
        throw new RuntimeException(
            sprintf(
                'The plugins configuration file `%s` must return an array. Got `%s` instead.',
                $pluginsFile,
                gettype($plugins),
            ),
        );
    }

    // Store the plugins configuration globally
    Configure::write('Test.plugins', $plugins);
}
