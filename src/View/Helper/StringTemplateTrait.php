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
 * @since         3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\View\Helper;

use Cake\View\StringTemplate;

/**
 * Adds string template functionality to any class by providing methods to
 * load and parse string templates.
 */
trait StringTemplateTrait {

/**
 * StringTemplate instance.
 *
 * @var \Cake\View\StringTemplate
 */
	protected $_templater;

/**
 * Initializes the StringTemplate class and loads templates
 *
 * @param array $templates
 * @param string $templateClass Class name of the template class to instantiate
 * @return void
 */
	public function initStringTemplates($templates = [], $templateClass = '\Cake\View\StringTemplate') {
		$this->_templater = new $templateClass($templates);
		if (empty($this->settings['templates'])) {
			return;
		}
		if (is_string($this->settings['templates'])) {
			$this->_templater->load($this->settings['templates']);
		}
		if (is_array($this->settings['templates'])) {
			$this->_templater->add($this->settings['templates']);
		}
	}

/**
 * Get/set templates to use.
 *
 * @param string|null|array $templates null or string allow reading templates. An array
 *   allows templates to be added.
 * @return void|string|array
 */
	public function templates($templates = null) {
		if ($templates === null || is_string($templates)) {
			return $this->_templater->get($templates);
		}
		return $this->_templater->add($templates);
	}

/**
 * Format a template string with $data
 *
 * @param string $name The template name.
 * @param array $data The data to insert.
 * @return string
 */
	public function formatTemplate($name, $data) {
		return $this->_templater->format($name, $data);
	}

/**
 * Returns the template engine object
 *
 * @return StringTemplate
 */
	public function getTemplater() {
		return $this->_templater;
	}

}
