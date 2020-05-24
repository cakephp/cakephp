<?php
/**
 * String handling methods.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Utility
 * @since         CakePHP(tm) v 1.2.0.5551
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * String handling methods.
 *
 * @package       Cake.Utility
 */
class CakeText {

/**
 * Generate a random UUID
 *
 * @see http://www.ietf.org/rfc/rfc4122.txt
 * @return string RFC 4122 UUID
 */
	public static function uuid() {
		$random = function_exists('random_int') ? 'random_int' : 'mt_rand';
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			$random(0, 65535),
			$random(0, 65535),
			// 16 bits for "time_mid"
			$random(0, 65535),
			// 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
			$random(0, 4095) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			$random(0, 0x3fff) | 0x8000,
			// 48 bits for "node"
			$random(0, 65535),
			$random(0, 65535),
			$random(0, 65535)
		);
	}

/**
 * Tokenizes a string using $separator, ignoring any instance of $separator that appears between
 * $leftBound and $rightBound.
 *
 * @param string $data The data to tokenize.
 * @param string $separator The token to split the data on.
 * @param string $leftBound The left boundary to ignore separators in.
 * @param string $rightBound The right boundary to ignore separators in.
 * @return mixed Array of tokens in $data or original input if empty.
 */
	public static function tokenize($data, $separator = ',', $leftBound = '(', $rightBound = ')') {
		if (empty($data)) {
			return array();
		}

		$depth = 0;
		$offset = 0;
		$buffer = '';
		$results = array();
		$length = mb_strlen($data);
		$open = false;

		while ($offset <= $length) {
			$tmpOffset = -1;
			$offsets = array(
				mb_strpos($data, $separator, $offset),
				mb_strpos($data, $leftBound, $offset),
				mb_strpos($data, $rightBound, $offset)
			);
			for ($i = 0; $i < 3; $i++) {
				if ($offsets[$i] !== false && ($offsets[$i] < $tmpOffset || $tmpOffset == -1)) {
					$tmpOffset = $offsets[$i];
				}
			}
			if ($tmpOffset !== -1) {
				$buffer .= mb_substr($data, $offset, ($tmpOffset - $offset));
				$char = mb_substr($data, $tmpOffset, 1);
				if (!$depth && $char === $separator) {
					$results[] = $buffer;
					$buffer = '';
				} else {
					$buffer .= $char;
				}
				if ($leftBound !== $rightBound) {
					if ($char === $leftBound) {
						$depth++;
					}
					if ($char === $rightBound) {
						$depth--;
					}
				} else {
					if ($char === $leftBound) {
						if (!$open) {
							$depth++;
							$open = true;
						} else {
							$depth--;
						}
					}
				}
				$offset = ++$tmpOffset;
			} else {
				$results[] = $buffer . mb_substr($data, $offset);
				$offset = $length + 1;
			}
		}
		if (empty($results) && !empty($buffer)) {
			$results[] = $buffer;
		}

		if (!empty($results)) {
			return array_map('trim', $results);
		}

		return array();
	}

/**
 * Replaces variable placeholders inside a $str with any given $data. Each key in the $data array
 * corresponds to a variable placeholder name in $str.
 * Example: `CakeText::insert(':name is :age years old.', array('name' => 'Bob', '65'));`
 * Returns: Bob is 65 years old.
 *
 * Available $options are:
 *
 * - before: The character or string in front of the name of the variable placeholder (Defaults to `:`)
 * - after: The character or string after the name of the variable placeholder (Defaults to null)
 * - escape: The character or string used to escape the before character / string (Defaults to `\`)
 * - format: A regex to use for matching variable placeholders. Default is: `/(?<!\\)\:%s/`
 *   (Overwrites before, after, breaks escape / clean)
 * - clean: A boolean or array with instructions for CakeText::cleanInsert
 *
 * @param string $str A string containing variable placeholders
 * @param array $data A key => val array where each key stands for a placeholder variable name
 *     to be replaced with val
 * @param array $options An array of options, see description above
 * @return string
 */
	public static function insert($str, $data, $options = array()) {
		$defaults = array(
			'before' => ':', 'after' => null, 'escape' => '\\', 'format' => null, 'clean' => false
		);
		$options += $defaults;
		$format = $options['format'];
		$data = (array)$data;
		if (empty($data)) {
			return ($options['clean']) ? CakeText::cleanInsert($str, $options) : $str;
		}

		if (!isset($format)) {
			$format = sprintf(
				'/(?<!%s)%s%%s%s/',
				preg_quote($options['escape'], '/'),
				str_replace('%', '%%', preg_quote($options['before'], '/')),
				str_replace('%', '%%', preg_quote($options['after'], '/'))
			);
		}

		if (strpos($str, '?') !== false && is_numeric(key($data))) {
			$offset = 0;
			while (($pos = strpos($str, '?', $offset)) !== false) {
				$val = array_shift($data);
				$offset = $pos + strlen($val);
				$str = substr_replace($str, $val, $pos, 1);
			}
			return ($options['clean']) ? CakeText::cleanInsert($str, $options) : $str;
		}

		asort($data);

		$dataKeys = array_keys($data);
		$hashKeys = array_map('crc32', $dataKeys);
		$tempData = array_combine($dataKeys, $hashKeys);
		krsort($tempData);

		foreach ($tempData as $key => $hashVal) {
			$key = sprintf($format, preg_quote($key, '/'));
			$str = preg_replace($key, $hashVal, $str);
		}
		$dataReplacements = array_combine($hashKeys, array_values($data));
		foreach ($dataReplacements as $tmpHash => $tmpValue) {
			$tmpValue = (is_array($tmpValue)) ? '' : $tmpValue;
			$str = str_replace($tmpHash, $tmpValue, $str);
		}

		if (!isset($options['format']) && isset($options['before'])) {
			$str = str_replace($options['escape'] . $options['before'], $options['before'], $str);
		}
		return ($options['clean']) ? CakeText::cleanInsert($str, $options) : $str;
	}

/**
 * Cleans up a CakeText::insert() formatted string with given $options depending on the 'clean' key in
 * $options. The default method used is text but html is also available. The goal of this function
 * is to replace all whitespace and unneeded markup around placeholders that did not get replaced
 * by CakeText::insert().
 *
 * @param string $str CakeText to clean.
 * @param array $options Options list.
 * @return string
 * @see CakeText::insert()
 */
	public static function cleanInsert($str, $options) {
		$clean = $options['clean'];
		if (!$clean) {
			return $str;
		}
		if ($clean === true) {
			$clean = array('method' => 'text');
		}
		if (!is_array($clean)) {
			$clean = array('method' => $options['clean']);
		}
		switch ($clean['method']) {
			case 'html':
				$clean = array_merge(array(
					'word' => '[\w,.]+',
					'andText' => true,
					'replacement' => '',
				), $clean);
				$kleenex = sprintf(
					'/[\s]*[a-z]+=(")(%s%s%s[\s]*)+\\1/i',
					preg_quote($options['before'], '/'),
					$clean['word'],
					preg_quote($options['after'], '/')
				);
				$str = preg_replace($kleenex, $clean['replacement'], $str);
				if ($clean['andText']) {
					$options['clean'] = array('method' => 'text');
					$str = CakeText::cleanInsert($str, $options);
				}
				break;
			case 'text':
				$clean = array_merge(array(
					'word' => '[\w,.]+',
					'gap' => '[\s]*(?:(?:and|or)[\s]*)?',
					'replacement' => '',
				), $clean);

				$kleenex = sprintf(
					'/(%s%s%s%s|%s%s%s%s)/',
					preg_quote($options['before'], '/'),
					$clean['word'],
					preg_quote($options['after'], '/'),
					$clean['gap'],
					$clean['gap'],
					preg_quote($options['before'], '/'),
					$clean['word'],
					preg_quote($options['after'], '/')
				);
				$str = preg_replace($kleenex, $clean['replacement'], $str);
				break;
		}
		return $str;
	}

/**
 * Wraps text to a specific width, can optionally wrap at word breaks.
 *
 * ### Options
 *
 * - `width` The width to wrap to. Defaults to 72.
 * - `wordWrap` Only wrap on words breaks (spaces) Defaults to true.
 * - `indent` CakeText to indent with. Defaults to null.
 * - `indentAt` 0 based index to start indenting at. Defaults to 0.
 *
 * @param string $text The text to format.
 * @param array|int $options Array of options to use, or an integer to wrap the text to.
 * @return string Formatted text.
 */
	public static function wrap($text, $options = array()) {
		if (is_numeric($options)) {
			$options = array('width' => $options);
		}
		$options += array('width' => 72, 'wordWrap' => true, 'indent' => null, 'indentAt' => 0);
		if ($options['wordWrap']) {
			$wrapped = static::wordWrap($text, $options['width'], "\n");
		} else {
			$wrapped = trim(chunk_split($text, $options['width'] - 1, "\n"));
		}
		if (!empty($options['indent'])) {
			$chunks = explode("\n", $wrapped);
			for ($i = $options['indentAt'], $len = count($chunks); $i < $len; $i++) {
				$chunks[$i] = $options['indent'] . $chunks[$i];
			}
			$wrapped = implode("\n", $chunks);
		}
		return $wrapped;
	}

/**
 * Unicode aware version of wordwrap.
 *
 * @param string $text The text to format.
 * @param int $width The width to wrap to. Defaults to 72.
 * @param string $break The line is broken using the optional break parameter. Defaults to '\n'.
 * @param bool $cut If the cut is set to true, the string is always wrapped at the specified width.
 * @return string Formatted text.
 */
	public static function wordWrap($text, $width = 72, $break = "\n", $cut = false) {
		$paragraphs = explode($break, $text);
		foreach ($paragraphs as &$paragraph) {
			$paragraph = static::_wordWrap($paragraph, $width, $break, $cut);
		}
		return implode($break, $paragraphs);
	}

/**
 * Helper method for wordWrap().
 *
 * @param string $text The text to format.
 * @param int $width The width to wrap to. Defaults to 72.
 * @param string $break The line is broken using the optional break parameter. Defaults to '\n'.
 * @param bool $cut If the cut is set to true, the string is always wrapped at the specified width.
 * @return string Formatted text.
 */
	protected static function _wordWrap($text, $width = 72, $break = "\n", $cut = false) {
		if ($cut) {
			$parts = array();
			while (mb_strlen($text) > 0) {
				$part = mb_substr($text, 0, $width);
				$parts[] = trim($part);
				$text = trim(mb_substr($text, mb_strlen($part)));
			}
			return implode($break, $parts);
		}

		$parts = array();
		while (mb_strlen($text) > 0) {
			if ($width >= mb_strlen($text)) {
				$parts[] = trim($text);
				break;
			}

			$part = mb_substr($text, 0, $width);
			$nextChar = mb_substr($text, $width, 1);
			if ($nextChar !== ' ') {
				$breakAt = mb_strrpos($part, ' ');
				if ($breakAt === false) {
					$breakAt = mb_strpos($text, ' ', $width);
				}
				if ($breakAt === false) {
					$parts[] = trim($text);
					break;
				}
				$part = mb_substr($text, 0, $breakAt);
			}

			$part = trim($part);
			$parts[] = $part;
			$text = trim(mb_substr($text, mb_strlen($part)));
		}

		return implode($break, $parts);
	}

/**
 * Highlights a given phrase in a text. You can specify any expression in highlighter that
 * may include the \1 expression to include the $phrase found.
 *
 * ### Options:
 *
 * - `format` The piece of html with that the phrase will be highlighted
 * - `html` If true, will ignore any HTML tags, ensuring that only the correct text is highlighted
 * - `regex` a custom regex rule that is used to match words, default is '|$tag|iu'
 *
 * @param string $text Text to search the phrase in.
 * @param string|array $phrase The phrase or phrases that will be searched.
 * @param array $options An array of html attributes and options.
 * @return string The highlighted text
 * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::highlight
 */
	public static function highlight($text, $phrase, $options = array()) {
		if (empty($phrase)) {
			return $text;
		}

		$defaults = array(
			'format' => '<span class="highlight">\1</span>',
			'html' => false,
			'regex' => "|%s|iu"
		);
		$options += $defaults;
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
				$replace[] = sprintf($options['regex'], $segment);
			}

			return preg_replace($replace, $with, $text);
		}

		$phrase = '(' . preg_quote($phrase, '|') . ')';
		if ($html) {
			$phrase = "(?![^<]+>)$phrase(?![^<]+>)";
		}

		return preg_replace(sprintf($options['regex'], $phrase), $format, $text);
	}

/**
 * Strips given text of all links (<a href=....).
 *
 * @param string $text Text
 * @return string The text without links
 * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::stripLinks
 */
	public static function stripLinks($text) {
		return preg_replace('|<a\s+[^>]+>|im', '', preg_replace('|<\/a>|im', '', $text));
	}

/**
 * Truncates text starting from the end.
 *
 * Cuts a string to the length of $length and replaces the first characters
 * with the ellipsis if the text is longer than length.
 *
 * ### Options:
 *
 * - `ellipsis` Will be used as Beginning and prepended to the trimmed string
 * - `exact` If false, $text will not be cut mid-word
 *
 * @param string $text CakeText to truncate.
 * @param int $length Length of returned string, including ellipsis.
 * @param array $options An array of options.
 * @return string Trimmed string.
 */
	public static function tail($text, $length = 100, $options = array()) {
		$defaults = array(
			'ellipsis' => '...', 'exact' => true
		);
		$options += $defaults;
		extract($options);

		if (!function_exists('mb_strlen')) {
			class_exists('Multibyte');
		}

		if (mb_strlen($text) <= $length) {
			return $text;
		}

		$truncate = mb_substr($text, mb_strlen($text) - $length + mb_strlen($ellipsis));
		if (!$exact) {
			$spacepos = mb_strpos($truncate, ' ');
			$truncate = $spacepos === false ? '' : trim(mb_substr($truncate, $spacepos));
		}

		return $ellipsis . $truncate;
	}

/**
 * Truncates text.
 *
 * Cuts a string to the length of $length and replaces the last characters
 * with the ellipsis if the text is longer than length.
 *
 * ### Options:
 *
 * - `ellipsis` Will be used as Ending and appended to the trimmed string (`ending` is deprecated)
 * - `exact` If false, $text will not be cut mid-word
 * - `html` If true, HTML tags would be handled correctly
 *
 * @param string $text CakeText to truncate.
 * @param int $length Length of returned string, including ellipsis.
 * @param array $options An array of html attributes and options.
 * @return string Trimmed string.
 * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::truncate
 */
	public static function truncate($text, $length = 100, $options = array()) {
		$defaults = array(
			'ellipsis' => '...', 'exact' => true, 'html' => false
		);
		if (isset($options['ending'])) {
			$defaults['ellipsis'] = $options['ending'];
		} elseif (!empty($options['html']) && Configure::read('App.encoding') === 'UTF-8') {
			$defaults['ellipsis'] = "\xe2\x80\xa6";
		}
		$options += $defaults;
		extract($options);

		if (!function_exists('mb_strlen')) {
			class_exists('Multibyte');
		}

		if ($html) {
			if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
				return $text;
			}
			$totalLength = mb_strlen(strip_tags($ellipsis));
			$openTags = array();
			$truncate = '';

			preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
			foreach ($tags as $tag) {
				if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
					if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
						array_unshift($openTags, $tag[2]);
					} elseif (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
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

					$truncate .= mb_substr($tag[3], 0, $left + $entitiesLength);
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
			}
			$truncate = mb_substr($text, 0, $length - mb_strlen($ellipsis));
		}
		if (!$exact) {
			$spacepos = mb_strrpos($truncate, ' ');
			if ($html) {
				$truncateCheck = mb_substr($truncate, 0, $spacepos);
				$lastOpenTag = mb_strrpos($truncateCheck, '<');
				$lastCloseTag = mb_strrpos($truncateCheck, '>');
				if ($lastOpenTag > $lastCloseTag) {
					preg_match_all('/<[\w]+[^>]*>/s', $truncate, $lastTagMatches);
					$lastTag = array_pop($lastTagMatches[0]);
					$spacepos = mb_strrpos($truncate, $lastTag) + mb_strlen($lastTag);
				}
				$bits = mb_substr($truncate, $spacepos);
				preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
				if (!empty($droppedTags)) {
					if (!empty($openTags)) {
						foreach ($droppedTags as $closingTag) {
							if (!in_array($closingTag[1], $openTags)) {
								array_unshift($openTags, $closingTag[1]);
							}
						}
					} else {
						foreach ($droppedTags as $closingTag) {
							$openTags[] = $closingTag[1];
						}
					}
				}
			}
			$truncate = mb_substr($truncate, 0, $spacepos);
		}
		$truncate .= $ellipsis;

		if ($html) {
			foreach ($openTags as $tag) {
				$truncate .= '</' . $tag . '>';
			}
		}

		return $truncate;
	}

/**
 * Extracts an excerpt from the text surrounding the phrase with a number of characters on each side
 * determined by radius.
 *
 * @param string $text CakeText to search the phrase in
 * @param string $phrase Phrase that will be searched for
 * @param int $radius The amount of characters that will be returned on each side of the founded phrase
 * @param string $ellipsis Ending that will be appended
 * @return string Modified string
 * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::excerpt
 */
	public static function excerpt($text, $phrase, $radius = 100, $ellipsis = '...') {
		if (empty($text) || empty($phrase)) {
			return static::truncate($text, $radius * 2, array('ellipsis' => $ellipsis));
		}

		$append = $prepend = $ellipsis;

		$phraseLen = mb_strlen($phrase);
		$textLen = mb_strlen($text);

		$pos = mb_strpos(mb_strtolower($text), mb_strtolower($phrase));
		if ($pos === false) {
			return mb_substr($text, 0, $radius) . $ellipsis;
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
 * Creates a comma separated list where the last two items are joined with 'and', forming natural language.
 *
 * @param array $list The list to be joined.
 * @param string $and The word used to join the last and second last items together with. Defaults to 'and'.
 * @param string $separator The separator used to join all the other items together. Defaults to ', '.
 * @return string The glued together string.
 * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::toList
 */
	public static function toList($list, $and = null, $separator = ', ') {
		if ($and === null) {
			$and = __d('cake', 'and');
		}
		if (count($list) > 1) {
			return implode($separator, array_slice($list, null, -1)) . ' ' . $and . ' ' . array_pop($list);
		}

		return array_pop($list);
	}
}
