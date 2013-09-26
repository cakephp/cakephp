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
 * @since         CakePHP(tm) v3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Configure\Engine\PhpConfig;
use Cake\Core\Plugin;
use Cake\Error;

/**
 * Provides a interface for registering and inserting
 * content into simple logic-less string templates.
 *
 * Used by several helpers to provide simple flexible templates
 * for generating HTML and other content.
 */
class StringTemplate {

/**
 * The templates this instance holds.
 *
 * @var array
 */
	protected $_templates = [];

/**
 * Load a config file containing templates.
 *
 * Template files should define a `$config` variable containing
 * all the templates to load. Loaded templates will be merged with existing
 * templates.
 *
 * @param string $file The file to load
 * @return void
 */
	public function load($file) {
		list($plugin, $file) = pluginSplit($file);
		$path = APP . 'Config/';
		if ($plugin !== null) {
			$path = Plugin::path($plugin) . 'Config/';
		}
		$loader = new PhpConfig($path);
		$templates = $loader->read($file);
		$this->add($templates);
	}

/**
 * Add one or more template strings.
 *
 * @param array $templates The templates to add.
 * @return void
 */
	public function add(array $templates) {
		$this->_templates = array_merge($this->_templates, $templates);
	}

/**
 * Get one or all templates.
 *
 * @param string $name Leave null to get all templates, provide a name to get a single template.
 * @return string|array|null Either the template(s) or null
 */
	public function get($name = null) {
		if ($name === null) {
			return $this->_templates;
		}
		if (!isset($this->_templates[$name])) {
			return null;
		}
		return $this->_templates[$name];
	}

/**
 * Remove the named template.
 *
 * @param string $name The template to remove.
 * @return void
 */
	public function remove($name) {
		unset($this->_templates[$name]);
	}

/**
 * Format a template string with $data
 *
 * @param string $name The template name.
 * @param array $data The data to insert.
 * @return string
 */
	public function format($name, array $data) {
		$template = $this->get($name);
		if ($template === null) {
			return '';
		}
		$replace = [];
		$keys = array_keys($data);
		foreach ($keys as $key) {
			$replace['{{' . $key . '}}'] = $data[$key];
		}
		return strtr($template, $replace);
	}

}
