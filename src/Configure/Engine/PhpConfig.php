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
namespace Cake\Configure\Engine;

use Cake\Configure\ConfigEngineInterface;
use Cake\Core\App;
use Cake\Error;

/**
 * PHP engine allows Configure to load configuration values from
 * files containing simple PHP arrays.
 *
 * Files compatible with PhpConfig should define a `$config` variable, that
 * contains all of the configuration data contained in the file.
 */
class PhpConfig implements ConfigEngineInterface {

/**
 * The path this engine finds files on.
 *
 * @var string
 */
	protected $_path = null;

/**
 * Constructor for PHP Config file reading.
 *
 * @param string $path The path to read config files from. Defaults to APP . 'Config/'
 */
	public function __construct($path = null) {
		if (!$path) {
			$path = APP . 'Config/';
		}
		$this->_path = $path;
	}

/**
 * Read a config file and return its contents.
 *
 * Files with `.` in the name will be treated as values in plugins. Instead of
 * reading from the initialized path, plugin keys will be located using App::path().
 *
 * @param string $key The identifier to read from. If the key has a . it will be treated
 *  as a plugin prefix.
 * @return array Parsed configuration values.
 * @throws \Cake\Error\Exception when files don't exist or they don't contain `$config`.
 *  Or when files contain '..' as this could lead to abusive reads.
 */
	public function read($key) {
		if (strpos($key, '..') !== false) {
			throw new Error\Exception('Cannot load configuration files with ../ in them.');
		}

		$file = $this->_getFilePath($key);
		if (!is_file($file)) {
			throw new Error\Exception(sprintf('Could not load configuration file: %s', $file));
		}

		include $file;
		if (!isset($config)) {
			throw new Error\Exception(sprintf('No variable $config found in %s', $file));
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
	public function dump($key, $data) {
		$contents = '<?php' . "\n" . '$config = ' . $this->var_export($data) . ';';

		$filename = $this->_getFilePath($key);
		return file_put_contents($filename, $contents);
	}

/**
 * Get file path
 *
 * @param string $key The identifier to write to. If the key has a . it will be treated
 *  as a plugin prefix.
 * @return string Full file path
 */
	protected function _getFilePath($key) {
		if (substr($key, -4) === '.php') {
			$key = substr($key, 0, -4);
		}
		list($plugin, $key) = pluginSplit($key);
		$key .= '.php';

		if ($plugin) {
			$file = App::path('Config', $plugin)[0] . $key;
		} else {
			$file = $this->_path . $key;
		}

		return $file;
	}

    protected function var_export($var, $indent="") {
        switch (gettype($var)) {
            case "string":
                //return "'" . addcslashes($var, "\\\$\"\r\n\t\v\f") . "'";
                return "'" . str_replace("'", '\'',$var) . "'";
            case "array":
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = "$indent    "
                        . ($indexed ? "" : $this->var_export($key) . " => ")
                        . $this->var_export($value, "$indent    ");
                }
                return "[\n" . implode(",\n", $r) . "\n" . $indent . "]";
            case "boolean":
                return $var ? "true" : "false";
            default:
                return var_export($var, TRUE);
        }
    }

}
