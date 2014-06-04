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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Network\Session;
use Cake\View\Helper;
use Cake\View\View;

/**
 * FlashHelper
 */
class FlashHelper extends Helper {

	use StringTemplateTrait;

	protected $_defaultConfig = [
		'templates' => [
			'flash' => '<div id="{{key}}-message" class="message-{{class}}">{{message}}</div>'
		]
	];

	public function out($key = 'flash', $attrs = []) {
		$flash = $this->request->session()->read("Message.$key");
		$this->request->session()->delete("Message.$key");

		if (!$flash) {
			return '';
		}

		if (!empty($attrs)) {
			$flash = array_merge($flash, $attrs);
		}

		$message = $flash['message'];
		$class = $flash['type'];
		$params = $flash['params'];

		if (isset($flash['element'])) {
			$params['element'] = $flash['element'];
		}

		if (empty($params['element'])) {
			if (!empty($flash['class'])) {
				$class = $flash['class'];
			}
			return $this->formatTemplate('flash', [
				'class' => $class,
				'key' => $key,
				'message' => $message
			]);
		}

		$params['message'] = $message;
		$params['type'] = $class;

		return $this->_View->element($params['element'], $params);
	}

/**
 * Event listeners.
 *
 * @return array
 */
	public function implementedEvents() {
		return [];
	}
}
