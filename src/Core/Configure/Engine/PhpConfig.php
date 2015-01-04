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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core\Configure\Engine;

use Cake\Core\Configure\ConfigEngineInterface;
use Cake\Core\Configure\FileConfigTrait;
use Cake\Core\Exception\Exception;
use Cake\Core\Plugin;

/**
 * PHP engine allows Configure to load configuration values from
 * files containing simple PHP arrays.
 *
 * Files compatible with PhpConfig should define a `$config` variable, that
 * contains all of the configuration data contained in the file.
 */
class PhpConfig implements ConfigEngineInterface
{

    use FileConfigTrait;

    /**
     * File extension.
     *
     * @var string
     */
    protected $_extension = '.php';

    /**
     * Constructor for PHP Config file reading.
     *
     * @param string|null $path The path to read config files from. Defaults to CONFIG.
     */
    public function __construct($path = null)
    {
        if ($path === null) {
            $path = CONFIG;
        }
        $this->_path = $path;
    }

    /**
     * Read a config file and return its contents.
     *
     * Files with `.` in the name will be treated as values in plugins. Instead of
     * reading from the initialized path, plugin keys will be located using Plugin::path().
     *
     * @param string $key The identifier to read from. If the key has a . it will be treated
     *  as a plugin prefix.
     * @return array Parsed configuration values.
     * @throws \Cake\Core\Exception\Exception when files don't exist or they don't contain `$config`.
     *  Or when files contain '..' as this could lead to abusive reads.
     */
    public function read($key)
    {
        if (strpos($key, '..') !== false) {
            throw new Exception('Cannot load configuration files with ../ in them.');
        }

        $file = $this->_getFilePath($key);
        if (!is_file($file)) {
            throw new Exception(sprintf('Could not load configuration file: %s', $file));
        }

        include $file;
        if (!isset($config)) {
            throw new Exception(sprintf('No variable $config found in %s', $file));
        }
        return $config;
    }

    /**
     * Converts the provided $data into a string of PHP code that can
     * be used saved into a file and loaded later.
     *
     * @param string $key The identifier to write to. If the key has a . it will be treated
     *  as a plugin prefix.
     * @param array $data Data to dump.
     * @return int Bytes saved.
     */
    public function dump($key, array $data)
    {
        $contents = '<?php' . "\n" . '$config = ' . var_export($data, true) . ';';

        $filename = $this->_getFilePath($key);
        return file_put_contents($filename, $contents);
    }
}
