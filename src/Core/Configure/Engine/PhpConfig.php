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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core\Configure\Engine;

use Cake\Core\Configure\ConfigEngineInterface;
use Cake\Core\Configure\FileConfigTrait;
use Cake\Core\Exception\Exception;

/**
 * PHP engine allows Configure to load configuration values from
 * files containing simple PHP arrays.
 *
 * Files compatible with PhpConfig should return an array that
 * contains all of the configuration data contained in the file.
 *
 * An example configuration file would look like::
 *
 * ```
 * <?php
 * return [
 *     'debug' => false,
 *     'Security' => [
 *         'salt' => 'its-secret'
 *     ],
 *     'App' => [
 *         'namespace' => 'App'
 *     ]
 * ];
 * ```
 *
 * @see Cake\Core\Configure::load() for how to load custom configuration files.
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
     * Setting a `$config` variable is deprecated. Use `return` instead.
     *
     * @param string $key The identifier to read from. If the key has a . it will be treated
     *  as a plugin prefix.
     * @return array Parsed configuration values.
     * @throws \Cake\Core\Exception\Exception when files don't exist or they don't contain `$config`.
     *  Or when files contain '..' as this could lead to abusive reads.
     */
    public function read($key)
    {
        $file = $this->_getFilePath($key, true);

        $config = null;

        $return = include $file;
        if (is_array($return)) {
            return $return;
        }

        if ($config === null) {
            throw new Exception(sprintf('Config file "%s" did not return an array', $key . '.php'));
        }
        deprecationWarning(sprintf(
            'PHP configuration files like "%s" should not set `$config`. Instead return an array.',
            $key . '.php'
        ));

        return $config;
    }

    /**
     * Converts the provided $data into a string of PHP code that can
     * be used saved into a file and loaded later.
     *
     * @param string $key The identifier to write to. If the key has a . it will be treated
     *  as a plugin prefix.
     * @param array $data Data to dump.
     * @return bool Success
     */
    public function dump($key, array $data)
    {
        $contents = '<?php' . "\n" . 'return ' . var_export($data, true) . ';';

        $filename = $this->_getFilePath($key);

        return file_put_contents($filename, $contents) > 0;
    }
}
