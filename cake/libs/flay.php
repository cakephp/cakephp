<?php
/* SVN FILE: $Id$ */
/**
 * Text-to-HTML parser.
 *
 * Text-to-html parser, similar to {@link http://textism.com/tools/textile/ Textile} or {@link http://www.whytheluckystiff.net/ruby/redcloth/ RedCloth}.
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
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 0.2.9
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libraries.
 *
 */
if (!class_exists('Object')) {
	uses('object');
}
/**
 * Text-to-HTML parser.
 *
 * Text-to-html parser, similar to Textile or RedCloth, only with a little different syntax.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class Flay extends Object{
/**
 * Text to be parsed.
 *
 * @var string
 * @access public
 */
	var $text = null;
/**
 * Set this to allow HTML in the markup.
 *
 * @var boolean
 * @access public
 */
	var $allow_html = false;
/**
 * Constructor.
 *
 * @param string $text Text to transform
 */
	function __construct($text = null) {
		$this->text = $text;
		parent::__construct();
	}
/**
 * Returns given text translated to HTML using the Flay syntax.
 *
 * @param string $text String to format
 * @param boolean $bare	Set this to only do <p> transforms and > to &gt;, no typography additions.
 * @param boolean $allowHtml Set this to trim whitespace and disable all HTML
 * @return string Formatted text
 * @access public
 */
	function toHtml($text = null, $bare = false, $allowHtml = false) {
		if (empty($text) && empty($this->text)) {
			return false;
		}
		$text = $text ? $text : $this->text;
		// trim whitespace and disable all HTML
		if ($allowHtml) {
			$text = trim($text);
		} else {
			$text = str_replace('<', '&lt;', str_replace('>', '&gt;', trim($text)));
		}

		if (!$bare) {
			// multi-paragraph functions
			$text=preg_replace('#(?:[\n]{0,2})"""(.*)"""(?:[\n]{0,2})#s', "\n\n%BLOCKQUOTE%\n\n\\1\n\n%ENDBLOCKQUOTE%\n\n", $text);
			$text=preg_replace('#(?:[\n]{0,2})===(.*)===(?:[\n]{0,2})#s', "\n\n%CENTER%\n\n\\1\n\n%ENDCENTER%\n\n", $text);
		}

		// pre-parse newlines
		$text=preg_replace("#\r\n#", "\n", $text);
		$text=preg_replace("#[\n]{2,}#", "%PARAGRAPH%", $text);
		$text=preg_replace('#[\n]{1}#', "%LINEBREAK%", $text);
		$out ='';

		foreach (split('%PARAGRAPH%', $text)as $line) {
			if ($line) {
				if (!$bare) {
					$links = array();
					$regs = null;

					if (preg_match_all('#\[([^\[]{4,})\]#', $line, $regs)) {
						foreach ($regs[1] as $reg) {
							$links[] = $reg;
							$line = str_replace("[{$reg}]", '%LINK' . (count($links) - 1) . '%', $line);
						}
					}
					// bold
					$line = ereg_replace("\*([^\*]*)\*", "<strong>\\1</strong>", $line);
					// italic
					$line = ereg_replace("_([^_]*)_", "<em>\\1</em>", $line);
				}
				// entities
				$line = str_replace(' - ', ' &ndash; ', $line);
				$line = str_replace(' -- ', ' &mdash; ', $line);
				$line = str_replace('(C)', '&copy;', $line);
				$line = str_replace('(R)', '&reg;', $line);
				$line = str_replace('(TM)', '&trade;', $line);
				// guess e-mails
				$emails = null;
				if (preg_match_all("#([_A-Za-z0-9+-+]+(?:\.[_A-Za-z0-9+-]+)*@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*)#", $line, $emails)) {
					foreach ($emails[1] as $email) {
						$line = str_replace($email, "<a href=\"mailto:{$email}\">{$email}</a>", $line);
					}
				}

				if (!$bare) {
					$urls = null;
					if (preg_match_all("#((?:http|https|ftp|nntp)://[^ ]+)#", $line, $urls)) {
						foreach ($urls[1] as $url) {
							$line = str_replace($url, "<a href=\"{$url}\">{$url}</a>", $line);
						}
					}

					if (preg_match_all("#(www\.[^\n\%\ ]+[^\n\%\,\.\ ])#", $line, $urls)) {
						foreach ($urls[1] as $url) {
							$line = str_replace($url, "<a href=\"http://{$url}\">{$url}</a>", $line);
						}
					}

					if ($count = count($links)) {
						for ($ii = 0; $ii < $count; $ii++) {
							if (preg_match("#^(http|https|ftp|nntp)://#", $links[$ii])) {
								$prefix = null;
							} else {
								$prefix = 'http://';
							}
							if (preg_match('#^[^\ ]+\.(jpg|jpeg|gif|png)$#', $links[$ii])) {
								$with = "<img src=\"{$prefix}{$links[$ii]}\" alt=\"\" />";
							} elseif (preg_match('#^([^\]\ ]+)(?:\ ([^\]]+))?$#', $links[$ii], $regs)) {
								if (isset($regs[2])) {
									if (preg_match('#\.(jpg|jpeg|gif|png)$#', $regs[2])) {
										$body = "<img src=\"{$prefix}{$regs[2]}\" alt=\"\" />";
									} else {
										$body = $regs[2];
									}
								} else {
									$body = $links[$ii];
								}
								$with = "<a href=\"{$prefix}{$regs[1]}\" target=\"_blank\">{$body}</a>";
							} else {
								$with = $prefix . $links[$ii];
							}
							$line = str_replace("%LINK{$ii}%", $with, $line);
						}
					}
				}
				$out .= str_replace('%LINEBREAK%', "<br />\n", "<p>{$line}</p>\n");
			}
		}

		if (!$bare) {
			$out = str_replace('<p>%BLOCKQUOTE%</p>', "<blockquote>", $out);
			$out = str_replace('<p>%ENDBLOCKQUOTE%</p>', "</blockquote>", $out);
			$out = str_replace('<p>%CENTER%</p>', "<center>", $out);
			$out = str_replace('<p>%ENDCENTER%</p>', "</center>", $out);
		}
		return $out;
	}
/**
 * Return the words of the string as an array.
 *
 * @param string $string
 * @return array Array of words
 * @access public
 */
	function extractWords($string) {
		$split = preg_split('/[\s,\.:\/="!\(\)<>~\[\]]+/', $string);
		return $split;
	 }
/**
 * Return given string with words in array colorMarked, up to a number of times (defaults to 5).
 *
 * @param array $words			Words to look for and markup
 * @param string $string		String to look in
 * @param integer $max_snippets	Max number of snippets to extract
 * @return string String with words marked
 * @see colorMark
 * @access public
 */
	function markedSnippets($words, $string, $max_snippets = 5) {
		$string = strip_tags($string);
		$snips = array();
		$rest = $string;
		foreach ($words as $word) {
			if (preg_match_all("/[\s,]+.{0,40}{$word}.{0,40}[\s,]+/i", $rest, $r)) {
				foreach ($r as $result) {
					$rest = str_replace($result, '', $rest);
				}
				$snips = array_merge($snips, $r[0]);
			}
		}

		if (count($snips) > $max_snippets) {
			$snips = array_slice($snips, 0, $max_snippets);
		}
		$joined = implode(' <b>...</b> ', $snips);
		$snips = $joined ? "<b>...</b> {$joined} <b>...</b>" : substr($string, 0, 80) . '<b>...</b>';
		return $this->colorMark($words, $snips);
	}
/**
 * Returns string with EM elements with color classes added.
 *
 * @param array $words Array of words to be colorized
 * @param string $string Text in which the words might be found
 * @return string String with words colorized
 * @access public
 */
	function colorMark($words, $string) {
		$colors=array('yl', 'gr', 'rd', 'bl', 'fu', 'cy');
		$nextColorIndex = 0;
		foreach ($words as $word) {
			$string = preg_replace("/({$word})/i", '<em class="' . $colors[$nextColorIndex % count($colors)] . "\">\\1</em>", $string);
			$nextColorIndex++;
		}
		return $string;
	}
/**
 * Returns given text with tags stripped out.
 *
 * @param string $text Text to clean
 * @return string Cleaned text
 * @access public
 */
	function toClean($text) {
		$strip = strip_tags(html_entity_decode($text, ENT_QUOTES));
		return $strip;
	}
/**
 * Return parsed text with tags stripped out.
 *
 * @param string $text Text to parse and clean
 * @return string Cleaned text
 * @access public
 */
	function toParsedAndClean($text) {
		return $this->toClean(Flay::toHtml($text));
	}
/**
 * Return a fragment of a text, up to $length characters long, with an ellipsis after it.
 *
 * @param string $text		Text to be truncated.
 * @param integer $length	Max length of text.
 * @param string $ellipsis	Sign to print after truncated text.
 * @return string Fragment
 * @access public
 */
	function fragment($text, $length, $ellipsis = '...') {
		$soft = $length - 5;
		$hard = $length + 5;
		$rx = '/(.{' . $soft . ',' . $hard . '})[\s,\.:\/="!\(\)<>~\[\]]+.*/';

		if (preg_match($rx, $text, $r)) {
			$out = $r[1];
		} else {
			$out = substr($text, 0, $length);
		}
		$out = $out . (strlen($out) < strlen($text) ? $ellipsis : null);
		return $out;
	}
}
?>