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
namespace Cake\Core\Configure;

use Cake\Core\Exception\Exception;
use Cake\Core\Plugin;

/**
 * Trait providing utility methods for file based config engines.
 */
trait FileConfigTrait
{
    /**
     * The path this engine finds files on.
     *
     * @var string
     */
    protected $_path = '';

    /**
     * Get file path
     *
     * @param string $key The identifier to write to. If the key has a . it will be treated
     *  as a plugin prefix.
     * @param bool $checkExists Whether to check if file exists. Defaults to false.
     * @return string Full file path
     * @throws \Cake\Core\Exception\Exception When files don't exist or when
     *  files contain '..' as this could lead to abusive reads.
     */
    protected function _getFilePath($key, $checkExists = false)
    {
        if (strpos($key, '..') !== false) {
            throw new Exception('Cannot load/dump configuration files with ../ in them.');
        }

        list($plugin, $key) = pluginSplit($key);

        if ($plugin) {
            $file = Plugin::configPath($plugin) . $key;
        } else {
            $file = $this->_path . $key;
        }

        $file .= $this->_extension;

        if (!$checkExists || is_file($file)) {
            return $file;
        }

        $realPath = realpath($file);
        if ($realPath !== false && is_file($realPath)) {
            return $realPath;
        }

        throw new Exception(sprintf('Could not load configuration file: %s', $file));
    }
}
