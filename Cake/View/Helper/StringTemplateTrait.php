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
 * @since         CakePHP(tm) v 1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\View\StringTemplate;

trait StringTemplateTrait {

/**
 * StringTemplate instance.
 *
 * @var Cake\View\StringTemplate
 */
	protected $_templater;

/**
 * Initializes the StringTemplate class and loads templates
 *
 * @param array $templates
 * @return void
 */
	protected function _initStringTemplates($templates = []) {
		$this->_templater = new StringTemplate();
		$this->_templater->add($templates);
		if (isset($this->settings['templates'])) {
			$this->_templater->load($this->settings['templates']);
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

}