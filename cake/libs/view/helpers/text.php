<?php
/* SVN FILE: $Id$ */
/**
 * Text Helper
 *
 * Text manipulations: Highlight, excerpt, truncate, strip of links, convert email addresses to mailto: links...
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 * @since         CakePHP(tm) v 0.10.0.1076
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libraries.
 *
 */
if (!class_exists('HtmlHelper')) {
	App::import('Helper', 'Html');
}
if (!class_exists('Multibyte')) {
	App::import('Core', 'Multibyte');
}
/**
 * Text helper library.
 *
 * Text manipulations: Highlight, excerpt, truncate, strip of links, convert email addresses to mailto: links...
 *
 * @package       cake
 * @subpackage    cake.cake.libs.view.helpers
 */
class TextHelper extends AppHelper {
/**
 * Highlights a given phrase in a text. You can specify any expression in highlighter that
 * may include the \1 expression to include the $phrase found.
 *
 * @param string $text Text to search the phrase in
 * @param string $phrase The phrase that will be searched
 * @param string $highlighter The piece of html with that the phrase will be highlighted
 * @param boolean $considerHtml If true, will ignore any HTML tags, ensuring that only the correct text is highlighted
 * @return string The highlighted text
 * @access public
 */
	function highlight($text, $phrase, $highlighter = '<span class="highlight">\1</span>', $considerHtml = false) {
		if (empty($phrase)) {
			return $text;
		}

		if (is_array($phrase)) {
			$replace = array();
			$with = array();

			foreach ($phrase as $key => $value) {
				$key = $value;
				$value = $highlighter;
				$key = '(' . $key . ')';
				if ($considerHtml) {
					$key = '(?![^<]+>)' . $key . '(?![^<]+>)';
				}
				$replace[] = '|' . $key . '|iu';
				$with[] = empty($value) ? $highlighter : $value;
			}

			return preg_replace($replace, $with, $text);
		} else {
			$phrase = '(' . $phrase . ')';
			if ($considerHtml) {
				$phrase = '(?![^<]+>)' . $phrase . '(?![^<]+>)';
			}

			return preg_replace('|'.$phrase.'|iu', $highlighter, $text);
		}
	}
/**
 * Strips given text of all links (<a href=....)
 *
 * @param string $text Text
 * @return string The text without links
 * @access public
 */
	function stripLinks($text) {
		return preg_replace('|<a\s+[^>]+>|im', '', preg_replace('|<\/a>|im', '', $text));
	}
/**
 * Adds links (<a href=....) to a given text, by finding text that begins with
 * strings like http:// and ftp://.
 *
 * @param string $text Text to add links to
 * @param array $htmlOptions Array of HTML options.
 * @return string The text with links
 * @access public
 */
	function autoLinkUrls($text, $htmlOptions = array()) {
		$options = var_export($htmlOptions, true);
		$text = preg_replace_callback('#(?<!href="|">)((?:https?|ftp|nntp)://[^\s<>()]+)#i', create_function('$matches',
			'$Html = new HtmlHelper(); $Html->tags = $Html->loadConfig(); return $Html->link($matches[0], $matches[0],' . $options . ');'), $text);

		return preg_replace_callback('#(?<!href="|">)(?<!http://|https://|ftp://|nntp://)(www\.[^\n\%\ <]+[^<\n\%\,\.\ <])(?<!\))#i',
			create_function('$matches', '$Html = new HtmlHelper(); $Html->tags = $Html->loadConfig(); return $Html->link($matches[0], "http://" . $matches[0],' . $options . ');'), $text);
	}
/**
 * Adds email links (<a href="mailto:....) to a given text.
 *
 * @param string $text Text
 * @param array $htmlOptions Array of HTML options.
 * @return string The text with links
 * @access public
 */
	function autoLinkEmails($text, $htmlOptions = array()) {
		$options = 'array(';

		foreach ($htmlOptions as $option => $value) {
			$options .= "'$option' => '$value', ";
		}
		$options .= ')';

		return preg_replace_callback('#([_A-Za-z0-9+-]+(?:\.[_A-Za-z0-9+-]+)*@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*)#',
						create_function('$matches', '$Html = new HtmlHelper(); $Html->tags = $Html->loadConfig(); return $Html->link($matches[0], "mailto:" . $matches[0],' . $options . ');'), $text);
	}
/**
 * Convert all links and email adresses to HTML links.
 *
 * @param string $text Text
 * @param array $htmlOptions Array of HTML options.
 * @return string The text with links
 * @access public
 */
	function autoLink($text, $htmlOptions = array()) {
		return $this->autoLinkEmails($this->autoLinkUrls($text, $htmlOptions), $htmlOptions);
	}
/**
 * Truncates text.
 *
 * Cuts a string to the length of $length and replaces the last characters
 * with the ending if the text is longer than length.
 *
 * @param string  $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param mixed $ending If string, will be used as Ending and appended to the trimmed string. Can also be an associative array that can contain the last three params of this method.
 * @param boolean $exact If false, $text will not be cut mid-word
 * @param boolean $considerHtml If true, HTML tags would be handled correctly
 * @return string Trimmed string.
 */
	function truncate($text, $length = 100, $ending = '...', $exact = true, $considerHtml = false) {
		if (is_array($ending)) {
			extract($ending);
		}
		if ($considerHtml) {
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
				$truncate = mb_substr($text, 0, $length - strlen($ending));
			}
		}
		if (!$exact) {
			$spacepos = mb_strrpos($truncate, ' ');
			if (isset($spacepos)) {
				if ($considerHtml) {
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

		if ($considerHtml) {
			foreach ($openTags as $tag) {
				$truncate .= '</'.$tag.'>';
			}
		}

		return $truncate;
	}
/**
 * Alias for truncate().
 *
 * @see TextHelper::truncate()
 * @access public
 */
	function trim() {
		$args = func_get_args();
		return call_user_func_array(array(&$this, 'truncate'), $args);
	}
/**
 * Extracts an excerpt from the text surrounding the phrase with a number of characters on each side determined by radius.
 *
 * @param string $text String to search the phrase in
 * @param string $phrase Phrase that will be searched for
 * @param integer $radius The amount of characters that will be returned on each side of the founded phrase
 * @param string $ending Ending that will be appended
 * @return string Modified string
 * @access public
 */
	function excerpt($text, $phrase, $radius = 100, $ending = "...") {
		if (empty($text) or empty($phrase)) {
			return $this->truncate($text, $radius * 2, $ending);
		}

		$phraseLen = strlen($phrase);
		if ($radius < $phraseLen) {
			$radius = $phraseLen;
		}

		$pos = strpos(strtolower($text), strtolower($phrase));

		$startPos = 0;
		if ($pos > $radius) {
			$startPos = $pos - $radius;
		}

		$textLen = strlen($text);

		$endPos = $pos + $phraseLen + $radius;
		if ($endPos >= $textLen) {
			$endPos = $textLen;
		}

		$excerpt = substr($text, $startPos, $endPos - $startPos);
		if ($startPos != 0) {
			$excerpt = substr_replace($excerpt, $ending, 0, $phraseLen);
		}

		if ($endPos != $textLen) {
			$excerpt = substr_replace($excerpt, $ending, -$phraseLen);
		}

		return $excerpt;
	}
/**
 * Creates a comma separated list where the last two items are joined with 'and', forming natural English
 *
 * @param array $list The list to be joined
 * @return string
 * @access public
 */
	function toList($list, $and = 'and') {
		$return = '';
		$count = count($list) - 1;
	    $counter = 0;
		foreach ($list as $i => $item) {
			$return .= $item;
			if ($count > 0 && $counter < $count) {
				$return .= ($counter < $count - 1 ? ', ' : " {$and} ");
			}
		    $counter++;
		}
		return $return;
	}
/**
 * Text-to-html parser, similar to Textile or RedCloth, only with a little different syntax.
 *
 * @param string $text String to "flay"
 * @param boolean $allowHtml Set to true if if html is allowed
 * @return string "Flayed" text
 * @access public
 * @todo Change this. We need a real Textile parser.
 * @codeCoverageIgnoreStart
 */
	function flay($text, $allowHtml = false) {
		trigger_error(__('(TextHelper::flay) Deprecated: the Flay library is no longer supported and will be removed in a future version.', true), E_USER_WARNING);
		if (!class_exists('Flay')) {
			uses('flay');
		}
		return Flay::toHtml($text, false, $allowHtml);
	}
/**
 * @codeCoverageIgnoreEnd
 */
}
?>
