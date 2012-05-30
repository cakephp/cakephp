<?php
/**
 * Text Helper
 *
 * Text manipulations: Highlight, excerpt, truncate, strip of links, convert email addresses to mailto: links...
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Helper
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppHelper', 'View/Helper');

/**
 * Text helper library.
 *
 * Text manipulations: Highlight, excerpt, truncate, strip of links, convert email addresses to mailto: links...
 *
 * @package       Cake.View.Helper
 * @property      HtmlHelper $Html
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html
 * @see String
 */
class TextHelper extends AppHelper {

/**
 * helpers
 *
 * @var array
 */
	public $helpers = array('Html');

/**
 * An array of md5sums and their contents.
 * Used when inserting links into text.
 *
 * @var array
 */
	protected $_placeholders = array();

/**
 * String utility instance
 */
	protected $_engine;

/**
 * Constructor
 *
 * ### Settings:
 *
 * - `engine` Class name to use to replace String functionality.
 *            The class needs to be placed in the `Utility` directory.
 *
 * @param View $View the view object the helper is attached to.
 * @param array $settings Settings array Settings array
 * @throws CakeException when the engine class could not be found.
 */
	public function __construct(View $View, $settings = array()) {
		$settings = Hash::merge(array('engine' => 'String'), $settings);
		parent::__construct($View, $settings);
		list($plugin, $engineClass) = pluginSplit($settings['engine'], true);
		App::uses($engineClass, $plugin . 'Utility');
		if (class_exists($engineClass)) {
			$this->_engine = new $engineClass($settings);
		} else {
			throw new CakeException(__d('cake_dev', '%s could not be found', $engineClass));
		}
	}

/**
 * Call methods from String utility class
 */
	public function __call($method, $params) {
		return call_user_func_array(array($this->_engine, $method), $params);
	}

/**
 * Adds links (<a href=....) to a given text, by finding text that begins with
 * strings like http:// and ftp://.
 *
 * ### Options
 *
 * - `escape` Control HTML escaping of input. Defaults to true.
 *
 * @param string $text Text
 * @param array $options Array of HTML options, and options listed above.
 * @return string The text with links
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::autoLinkUrls
 */
	public function autoLinkUrls($text, $options = array()) {
		$this->_placeholders = array();
		$options += array('escape' => true);

		$text = preg_replace_callback(
			'#(?<!href="|src="|">)((?:https?|ftp|nntp)://[^\s<>()]+)#i',
			array(&$this, '_insertPlaceHolder'),
			$text
		);
		$text = preg_replace_callback(
			'#(?<!href="|">)(?<!\b[[:punct:]])(?<!http://|https://|ftp://|nntp://)www.[^\n\%\ <]+[^<\n\%\,\.\ <](?<!\))#i',
			array(&$this, '_insertPlaceHolder'),
			$text
		);
		if ($options['escape']) {
			$text = h($text);
		}
		return $this->_linkUrls($text, $options);
	}

/**
 * Saves the placeholder for a string, for later use.  This gets around double
 * escaping content in URL's.
 *
 * @param array $matches An array of regexp matches.
 * @return string Replaced values.
 */
	protected function _insertPlaceHolder($matches) {
		$key = md5($matches[0]);
		$this->_placeholders[$key] = $matches[0];
		return $key;
	}

/**
 * Replace placeholders with links.
 *
 * @param string $text The text to operate on.
 * @param array $htmlOptions The options for the generated links.
 * @return string The text with links inserted.
 */
	protected function _linkUrls($text, $htmlOptions) {
		$replace = array();
		foreach ($this->_placeholders as $hash => $url) {
			$link = $url;
			if (!preg_match('#^[a-z]+\://#', $url)) {
				$url = 'http://' . $url;
			}
			$replace[$hash] = $this->Html->link($link, $url, $htmlOptions);
		}
		return strtr($text, $replace);
	}

/**
 * Links email addresses
 *
 * @param string $text The text to operate on
 * @param array $options An array of options to use for the HTML.
 * @return string
 * @see TextHelper::autoLinkEmails()
 */
	protected function _linkEmails($text, $options) {
		$replace = array();
		foreach ($this->_placeholders as $hash => $url) {
			$replace[$hash] = $this->Html->link($url, 'mailto:' . $url, $options);
		}
		return strtr($text, $replace);
	}

/**
 * Adds email links (<a href="mailto:....) to a given text.
 *
 * ### Options
 *
 * - `escape` Control HTML escaping of input. Defaults to true.
 *
 * @param string $text Text
 * @param array $options Array of HTML options, and options listed above.
 * @return string The text with links
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::autoLinkEmails
 */
	public function autoLinkEmails($text, $options = array()) {
		$options += array('escape' => true);
		$this->_placeholders = array();

		$atom = '[a-z0-9!#$%&\'*+\/=?^_`{|}~-]';
		$text = preg_replace_callback(
			'/(' . $atom . '+(?:\.' . $atom . '+)*@[a-z0-9-]+(?:\.[a-z0-9-]+)+)/i',
			array(&$this, '_insertPlaceholder'),
			$text
		);
		if ($options['escape']) {
			$text = h($text);
		}
		return $this->_linkEmails($text, $options);
	}

/**
 * Convert all links and email addresses to HTML links.
 *
 * ### Options
 *
 * - `escape` Control HTML escaping of input. Defaults to true.
 *
 * @param string $text Text
 * @param array $options Array of HTML options, and options listed above.
 * @return string The text with links
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::autoLink
 */
	public function autoLink($text, $options = array()) {
		$text = $this->autoLinkUrls($text, $options);
		return $this->autoLinkEmails($text, array_merge($options, array('escape' => false)));
	}

/**
 * @see String::highlight()
 *
 * @param string $text Text to search the phrase in
 * @param string $phrase The phrase that will be searched
 * @param array $options An array of html attributes and options.
 * @return string The highlighted text
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::highlight
 */
	public function highlight($text, $phrase, $options = array()) {
		return $this->_engine->highlight($text, $phrase, $options);
	}

/**
 * @see String::stripLinks()
 *
 * @param string $text Text
 * @return string The text without links
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::stripLinks
 */
	public function stripLinks($text) {
		return $this->_engine->stripLinks($text);
	}

/**
 * @see String::truncate()
 *
 * @param string $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param array $options An array of html attributes and options.
 * @return string Trimmed string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::truncate
 */
	public function truncate($text, $length = 100, $options = array()) {
		return $this->_engine->truncate($text, $length, $options);
	}

/**
 * @see String::excerpt()
 *
 * @param string $text String to search the phrase in
 * @param string $phrase Phrase that will be searched for
 * @param integer $radius The amount of characters that will be returned on each side of the founded phrase
 * @param string $ending Ending that will be appended
 * @return string Modified string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::excerpt
 */
	public function excerpt($text, $phrase, $radius = 100, $ending = '...') {
		return $this->_engine->excerpt($text, $phrase, $radius, $ending);
	}

/**
 * @see String::toList()
 *
 * @param array $list The list to be joined
 * @param string $and The word used to join the last and second last items together with. Defaults to 'and'
 * @param string $separator The separator used to join all the other items together. Defaults to ', '
 * @return string The glued together string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::toList
 */
	public function toList($list, $and = 'and', $separator = ', ') {
		return $this->_engine->toList($list, $and, $separator);
	}

}
