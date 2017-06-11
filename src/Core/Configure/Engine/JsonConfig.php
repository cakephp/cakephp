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
namespace Cake\Core\Configure\Engine;

use Cake\Core\Configure\ConfigEngineInterface;
use Cake\Core\Configure\FileConfigTrait;
use Cake\Core\Exception\Exception;

/**
 * JSON engine allows Configure to load configuration values from
 * files containing JSON strings.
 */
class JsonConfig implements ConfigEngineInterface
{

    use FileConfigTrait;

    /**
     * File extension.
     *
     * @var string
     */
    protected $_extension = '.json';

    /**
     * Constructor for JSON Config file reading.
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
     *   as a plugin prefix.
     * @return array Parsed configuration values.
     * @throws \Cake\Core\Exception\Exception When files don't exist or when
     *   files contain '..' (as this could lead to abusive reads) or when there
     *   is an error parsing the JSON string.
     */
    public function read($key)
    {
        $file = $this->_getFilePath($key, true);

        $values = json_decode(file_get_contents($file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(sprintf(
                'Error parsing JSON string fetched from config file "%s.json": %s',
                $key,
                json_last_error_msg()
            ));
        }
        if (!is_array($values)) {
            throw new Exception(sprintf(
                'Decoding JSON config file "%s.json" did not return an array',
                $key
            ));
        }

        return $values;
    }

    /**
     * Converts the provided $data into a JSON string that can be used saved
     * into a file and loaded later.
     *
     * @param string $key The identifier to write to. If the key has a . it will
     *  be treated as a plugin prefix.
     * @param array $data Data to dump.
     * @return bool Success
     */
    public function dump($key, array $data)
    {
        $filename = $this->_getFilePath($key);

        return file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT)) > 0;
    }
}
