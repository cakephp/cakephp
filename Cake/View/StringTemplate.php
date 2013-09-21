<?php
namespace Cake\View;

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
