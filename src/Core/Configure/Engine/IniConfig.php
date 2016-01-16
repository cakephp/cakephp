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
use Cake\Utility\Hash;

/**
 * Ini file configuration engine.
 *
 * Since IniConfig uses parse_ini_file underneath, you should be aware that this
 * class shares the same behavior, especially with regards to boolean and null values.
 *
 * In addition to the native `parse_ini_file` features, IniConfig also allows you
 * to create nested array structures through usage of `.` delimited names. This allows
 * you to create nested arrays structures in an ini config file. For example:
 *
 * `db.password = secret` would turn into `['db' => ['password' => 'secret']]`
 *
 * You can nest properties as deeply as needed using `.`'s. In addition to using `.` you
 * can use standard ini section notation to create nested structures:
 *
 * ```
 * [section]
 * key = value
 * ```
 *
 * Once loaded into Configure, the above would be accessed using:
 *
 * `Configure::read('section.key');
 *
 * You can combine `.` separated values with sections to create more deeply
 * nested structures.
 *
 * IniConfig also manipulates how the special ini values of
 * 'yes', 'no', 'on', 'off', 'null' are handled. These values will be
 * converted to their boolean equivalents.
 *
 * @see http://php.net/parse_ini_file
 */
class IniConfig implements ConfigEngineInterface
{

    use FileConfigTrait;

    /**
     * File extension.
     *
     * @var string
     */
    protected $_extension = '.ini';

    /**
     * The section to read, if null all sections will be read.
     *
     * @var string
     */
    protected $_section;

    /**
     * Build and construct a new ini file parser. The parser can be used to read
     * ini files that are on the filesystem.
     *
     * @param string|null $path Path to load ini config files from. Defaults to CONFIG.
     * @param string|null $section Only get one section, leave null to parse and fetch
     *     all sections in the ini file.
     */
    public function __construct($path = null, $section = null)
    {
        if ($path === null) {
            $path = CONFIG;
        }
        $this->_path = $path;
        $this->_section = $section;
    }

    /**
     * Read an ini file and return the results as an array.
     *
     * @param string $key The identifier to read from. If the key has a . it will be treated
     *  as a plugin prefix. The chosen file must be on the engine's path.
     * @return array Parsed configuration values.
     * @throws \Cake\Core\Exception\Exception when files don't exist.
     *  Or when files contain '..' as this could lead to abusive reads.
     */
    public function read($key)
    {
        $file = $this->_getFilePath($key, true);

        $contents = parse_ini_file($file, true);
        if (!empty($this->_section) && isset($contents[$this->_section])) {
            $values = $this->_parseNestedValues($contents[$this->_section]);
        } else {
            $values = [];
            foreach ($contents as $section => $attribs) {
                if (is_array($attribs)) {
                    $values[$section] = $this->_parseNestedValues($attribs);
                } else {
                    $parse = $this->_parseNestedValues([$attribs]);
                    $values[$section] = array_shift($parse);
                }
            }
        }
        return $values;
    }

    /**
     * parses nested values out of keys.
     *
     * @param array $values Values to be exploded.
     * @return array Array of values exploded
     */
    protected function _parseNestedValues($values)
    {
        foreach ($values as $key => $value) {
            if ($value === '1') {
                $value = true;
            }
            if ($value === '') {
                $value = false;
            }
            unset($values[$key]);
            if (strpos($key, '.') !== false) {
                $values = Hash::insert($values, $key, $value);
            } else {
                $values[$key] = $value;
            }
        }
        return $values;
    }

    /**
     * Dumps the state of Configure data into an ini formatted string.
     *
     * @param string $key The identifier to write to. If the key has a . it will be treated
     *  as a plugin prefix.
     * @param array $data The data to convert to ini file.
     * @return bool Success.
     */
    public function dump($key, array $data)
    {
        $result = [];
        foreach ($data as $k => $value) {
            $isSection = false;
            if ($k[0] !== '[') {
                $result[] = "[$k]";
                $isSection = true;
            }
            if (is_array($value)) {
                $kValues = Hash::flatten($value, '.');
                foreach ($kValues as $k2 => $v) {
                    $result[] = "$k2 = " . $this->_value($v);
                }
            }
            if ($isSection) {
                $result[] = '';
            }
        }
        $contents = trim(implode("\n", $result));

        $filename = $this->_getFilePath($key);
        return file_put_contents($filename, $contents) > 0;
    }

    /**
     * Converts a value into the ini equivalent
     *
     * @param mixed $value Value to export.
     * @return string String value for ini file.
     */
    protected function _value($value)
    {
        if ($value === null) {
            return 'null';
        }
        if ($value === true) {
            return 'true';
        }
        if ($value === false) {
            return 'false';
        }
        return (string)$value;
    }
}
