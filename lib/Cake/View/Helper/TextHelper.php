<?php
/**
 * Text Helper
 *
 * Text manipulations: Highlight, excerpt, truncate, strip of links, convert email addresses to mailto: links...
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Helper
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppHelper', 'View/Helper');
App::uses('HtmlHelper', 'Helper');
App::uses('Multibyte', 'I18n');

/**
 * Text helper library.
 *
 * Text manipulations: Highlight, excerpt, truncate, strip of links, convert email addresses to mailto: links...
 *
 * @package       Cake.View.Helper
 * @property      HtmlHelper $Html
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html
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
 * Highlights a given phrase in a text. You can specify any expression in highlighter that
 * may include the \1 expression to include the $phrase found.
 *
 * ### Options:
 *
 * - `format` The piece of html with that the phrase will be highlighted
 * - `html` If true, will ignore any HTML tags, ensuring that only the correct text is highlighted
 *
 * @param string $text Text to search the phrase in
 * @param string $phrase The phrase that will be searched
 * @param array $options An array of html attributes and options.
 * @return string The highlighted text
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::highlight
 */
	public function highlight($text, $phrase, $options = array()) {
		if (empty($phrase)) {
			return $text;
		}

		$default = array(
			'format' => '<span class="highlight">\1</span>',
			'html' => false
		);
		$options = array_merge($default, $options);
		extract($options);

		if (is_array($phrase)) {
			$replace = array();
			$with = array();

			foreach ($phrase as $key => $segment) {
				$segment = '(' . preg_quote($segment, '|') . ')';
				if ($html) {
					$segment = "(?![^<]+>)$segment(?![^<]+>)";
				}

				$with[] = (is_array($format)) ? $format[$key] : $format;
				$replace[] = "|$segment|iu";
			}

			return preg_replace($replace, $with, $text);
		} else {
			$phrase = '(' . preg_quote($phrase, '|') . ')';
			if ($html) {
				$phrase = "(?![^<]+>)$phrase(?![^<]+>)";
			}

			return preg_replace("|$phrase|iu", $format, $text);
		}
	}

/**
 * Strips given text of all links (<a href=....)
 *
 * @param string $text Text
 * @return string The text without links
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::stripLinks
 */
	public function stripLinks($text) {
		return preg_replace('|<a\s+[^>]+>|im', '', preg_replace('|<\/a>|im', '', $text));
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
			'#(?<!href="|">)(?<!http://|https://|ftp://|nntp://)(www\.[^\n\%\ <]+[^<\n\%\,\.\ <])(?<!\))#i',
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
		foreach ($this->_placeholders as $md5 => $url) {
			$link = $url;
			if (!preg_match('#^[a-z]+\://#', $url)) {
				$url = 'http://' . $url;
			}
			$replace[$md5] = $this->Html->link($link, $url, $htmlOptions);
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
		foreach ($this->_placeholders as $md5 => $url) {
			$replace[$md5] = $this->Html->link($url, 'mailto:' . $url, $options);
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
 * Truncates text.
 *
 * Cuts a string to the length of $length and replaces the last characters
 * with the ending if the text is longer than length.
 *
 * ### Options:
 *
 * - `ending` Will be used as Ending and appended to the trimmed string
 * - `exact` If false, $text will not be cut mid-word
 * - `html` If true, HTML tags would be handled correctly
 *
 * @param string $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param array $options An array of html attributes and options.
 * @return string Trimmed string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::truncate
 */
	public function truncate($text, $length = 100, $options = array()) {
		$default = array(
			'ending' => '...', 'exact' => true, 'html' => false
		);
		$options = array_merge($default, $options);
		extract($options);

		if (!function_exists('mb_strlen')) {
			class_exists('Multibyte');
		}

		if ($html) {
			if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
				return $text;
			}
			$totalLength = mb_strlen(strip_tags($ending));
			$openTags = array();
			$truncate = '';

			preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
			foreach ($tags as $tag) {
				if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
					if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
						array_unshift($openTags, $tag[2]);
					} else if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
						$pos = array_search($closeTag[1], $openTags);
						if ($pos !== false) {
							array_splice($openTags, $pos, 1);
						}
					}
				}
				$truncate .= $tag[1];

				$contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
				if ($contentLength + $totalLength > $length) {
					$left = $length - $totalLength;
					$entitiesLength = 0;
					if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
						foreach ($entities[0] as $entity) {
							if ($entity[1] + 1 - $entitiesLength <= $left) {
								$left--;
								$entitiesLength += mb_strlen($entity[0]);
							} else {
								break;
							}
						}
					}

					$truncate .= mb_substr($tag[3], 0 , $left + $entitiesLength);
					break;
				} else {
					$truncate .= $tag[3];
					$totalLength += $contentLength;
				}
				if ($totalLength >= $length) {
					break;
				}
			}
		} else {
			if (mb_strlen($text) <= $length) {
				return $text;
			} else {
				$truncate = mb_substr($text, 0, $length - mb_strlen($ending));
			}
		}
		if (!$exact) {
			$spacepos = mb_strrpos($truncate, ' ');
			if (isset($spacepos)) {
				if ($html) {
					$bits = mb_substr($truncate, $spacepos);
					preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
					if (!empty($droppedTags)) {
						foreach ($droppedTags as $closingTag) {
							if (!in_array($closingTag[1], $openTags)) {
								array_unshift($openTags, $closingTag[1]);
							}
						}
					}
				}
				$truncate = mb_substr($truncate, 0, $spacepos);
			}
		}
		$truncate .= $ending;

		if ($html) {
			foreach ($openTags as $tag) {
				$truncate .= '</'.$tag.'>';
			}
		}

		return $truncate;
	}

/**
 * Extracts an excerpt from the text surrounding the phrase with a number of characters on each side
 * determined by radius.
 *
 * @param string $text String to search the phrase in
 * @param string $phrase Phrase that will be searched for
 * @param integer $radius The amount of characters that will be returned on each side of the founded phrase
 * @param string $ending Ending that will be appended
 * @return string Modified string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::excerpt
 */
	public function excerpt($text, $phrase, $radius = 100, $ending = '...') {
		if (empty($text) or empty($phrase)) {
			return $this->truncate($text, $radius * 2, array('ending' => $ending));
		}

		$append = $prepend = $ending;

		$phraseLen = mb_strlen($phrase);
		$textLen = mb_strlen($text);

		$pos = mb_strpos(mb_strtolower($text), mb_strtolower($phrase));
		if ($pos === false) {
			return mb_substr($text, 0, $radius) . $ending;
		}

		$startPos = $pos - $radius;
		if ($startPos <= 0) {
			$startPos = 0;
			$prepend = '';
		}

		$endPos = $pos + $phraseLen + $radius;
		if ($endPos >= $textLen) {
			$endPos = $textLen;
			$append = '';
		}

		$excerpt = mb_substr($text, $startPos, $endPos - $startPos);
		$excerpt = $prepend . $excerpt . $append;
		
		return $excerpt;
	}

/**
 * Creates a comma separated list where the last two items are joined with 'and', forming natural English
 *
 * @param array $list The list to be joined
 * @param string $and The word used to join the last and second last items together with. Defaults to 'and'
 * @param string $separator The separator used to join all the other items together. Defaults to ', '
 * @return string The glued together string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::toList
 */
	public function toList($list, $and = 'and', $separator = ', ') {
		if (count($list) > 1) {
			return implode($separator, array_slice($list, null, -1)) . ' ' . $and . ' ' . array_pop($list);
		} else {
			return array_pop($list);
		}
	}
}
