<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP v 1.2.0.4116
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
  * Included libraries.
  */
uses('l10n');
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class I18n extends Object {
/**
 * Enter description here...
 *
 * @var unknown_type
 * @access private
 */
	var $__l10n = null;
/**
 * Enter description here...
 *
 * @var unknown_type
 * @access public
 */
	var $locale = null;
/**
 * Enter description here...
 *
 * @var unknown_type
 * @access private
 */
	var $__domains = array();
/**
 * Enter description here...
 *
 * @var unknown_type
 * @access private
 */
	var $__noLocal = null;
/**
 * Enter description here...
 *
 * @return unknown
 */
	function &getInstance() {
		static $instance = array();
		if (!$instance) {
			$instance[0] =& new I18n();
			$instance[0]->__l10n =& new L10n();
		}
		return $instance[0];
	}
/**
 * Enter description here...
 *
 * @param unknown_type $message
 * @param unknown_type $message2
 * @param unknown_type $domain
 * @param unknown_type $category
 * @param unknown_type $count
 * @param unknown_type $directory
 * @return unknown
 */
	function translate($message, $message2 = null, $domain = null, $category = null, $count = null, $directory) {
		$_this =& I18n::getInstance();
		$language = Configure::read('Config.language');

		if(!empty($_SESSION['Config']['locale'])) {
			$_this->locale = $_SESSION['Config']['locale'];
		} else{
			$_this->__l10n->get($language);
			$_this->locale = $_this->__l10n->locale;
		}

		if(is_null($domain)) {
			if (preg_match('/views{0,1}\\'.DS.'([^\/]*)/', $directory, $regs)) {
				$domain = $regs[1];
			} elseif (preg_match('/controllers{0,1}\\'.DS.'([^\/]*)/', $directory, $regs)) {
				$domain = ($regs[1]);
			}

			if(isset($domain) && $domain == 'templates') {
				if (preg_match('/templates{0,1}\\'.DS.'([^\/]*)/', $directory, $regs)) {
					$domain = ($regs[1]);
				}
			}
		}

		if(!isset($_this->__domains[$domain])) {
			$_this->__bindTextDomain($domain);
		}

		if (!isset($count)) {
			$pli = 0;
		} elseif (!empty($_this->__domains[$domain]["%plural-c"]) && is_null($_this->__noLocal)) {
			$ph = $_this->__domains[$domain]["%plural-c"];
			$pli = $_this->__pluralGuess($ph, $count);
		} else {
			if ($count != 1) {
				$pli = 1;
			} else {
				$pli = 0;
			}
		}

		if(!empty($_this->__domains[$domain][$message])) {
			if (($trans = $_this->__domains[$domain][$message]) || ($pli) && ($trans = $_this->__domains[$domain][$message2])) {
				if (is_array($trans)) {
					if (!isset($trans[$pli])) {
						$pli = 0;
					}
					$trans = $trans[$pli];
				}
				if (strlen($trans)) {
					$message = $trans;
					return $message;
				}
			}
		}

		if(!empty($pli)) {
			return($message2);
		}
		return($message);
    }
/**
 * Enter description here...
 *
 * @param unknown_type $type
 * @param unknown_type $n
 * @return unknown
 * @access private
 */
	function __pluralGuess(&$type, $n) {
		if (is_string($type)) {
			if (($type == "nplurals=1;plural=0;") || !strlen($type)) {
				$type = -1;
			} elseif ($type == "nplurals=2;plural=n!=1;") {
				$type = 1;
			} elseif ($type == "nplurals=2;plural=n>1;") {
				$type = 2;
			} elseif (strpos($type, "n%100!=11")) {

				if (strpos($type, "n!=0")) {
					$type = 21;
				}

				if (strpos($type, "n%10<=4")) {
					$type = 22;
				}

				if (strpos($type, "n%10>=2")) {
					$type = 23;
				}
			} elseif (strpos($type, "n<=4")) {
				$type = 25;
			} elseif (strpos($type, "n==2")) {
				$type = 31;
			} elseif (strpos($type, "n%10>=2")) {
				$type = 26;
			} elseif (strpos($type, "n%100==3")) {
				$type = 28;
			} elseif (strpos($type, ";plural=n;")) {
				$type = 7;
			} else {
				$type = 0;
			}
		}

		switch ($type) {
			case -1:
				return   (0);
			case 1:
				if ($n != 1) {
					return (1);
				}
				return (0);
			case 2:
				if ($n > 1) {
					return (1);
				}
				return (0);
			case 7:
				return   ($n);
			case 21:
				if (($n % 10 == 1) && ($n % 100 != 11)) {
					return (0);
				}

				if ($n != 0 ) {
					return (1);
				}
				return (2);
			case 22:
				if (($n % 10 == 1) && ($n % 100 != 11)) {
					return (0);
				}

				if (($n % 10 >= 2) && ($n % 10 <= 4) && ($n % 100 < 10 || $n % 100 >= 20)) {
					return (1);
				}
				return (2);
			case 23:
				if (($n % 10 == 1) && ($n % 100 != 11)) {
					return (0);
				}

				if (($n %10 >= 2) && ($n % 100 < 10 || $n % 100 >= 20)) {
					return (1);
				}
				return (2);
			case 25:
				if ($n==1) {
					return (0);
				}

				if ($n >= 2 && $n <= 4) {
					return (1);
				}
				return (2);
			case 26:
				if ($n==1) {
					return (0);
				}

				if ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) {
					return (1);
				}
				return (2);
			case 28:
				if ($n % 100 == 1) {
					return (0);
				}

				if ($n % 100 == 2 || $n % 100 == 3 || $n % 100 == 4) {
					return (2);
				}
				return (3);
			case 31:
				if ($n == 1) {
					return (0);
				}

				if ($n == 2) {
					return (1);
				}
				return (2);
			default:
				$type = -1;
		}
		return(0);
	}
/**
 * Enter description here...
 *
 * @param unknown_type $domain
 * @return unknown
 * @access private
 */
	function __bindTextDomain($domain) {
		$_this =& I18n::getInstance();
		$_this->__noLocal = true;

		$searchPath[] = VIEWS . $domain . DS . 'locale';
		$searchPath[] = CAKE . 'locale';

		foreach (explode(",",$_this->locale) as $d) {
			$d = trim($d);
			$d = strtok($d, "@.-+=%:; ");

			if (strlen($d)) {
				$dir[] = $d;
			}

			if (strpos($d, "_")) {
				$dir[] = strtok($d, "_");
			}
		}

		foreach ($searchPath as $directory) {
			foreach ($dir as $lang) {
				$file = $directory . DS . $lang . DS . 'LC_MESSAGES' . DS . $domain;

				if (file_exists($fn = "$file.mo") && ($f = fopen($fn, "rb"))) {
					$_this->__loadMo($f, $domain);
					$_this->__noLocal = null;
					break 2;
				} elseif (file_exists($fn = "$file.po") && ($f = fopen($fn, "r"))) {
					$_this->__loadPo($f, $domain);
					$_this->__noLocal = null;
					break 2;
				}
			}
		}

		if(empty($_this->__domains[$domain])) {
			return($domain);
		}

		if ($head = $_this->__domains[$domain][""]) {
			foreach (explode("\n", $head) as $line) {
				$header = strtok($line,":");
				$line = trim(strtok("\n"));
				$_this->__domains[$domain]["%po-header"][strtolower($header)] = $line;
			}

			if(isset($_this->__domains[$domain]["%po-header"]["plural-forms"])) {
				$switch = preg_replace("/[(){}\\[\\]^\\s*\\]]+/", "", $_this->__domains[$domain]["%po-header"]["plural-forms"]);
				$_this->__domains[$domain]["%plural-c"] = $switch;
			}
		}
		return($domain);
	}
/**
 * Enter description here...
 *
 * @param unknown_type $file
 * @param unknown_type $domain
 * @access private
 */
	function __loadMo($file, $domain) {
		$_this =& I18n::getInstance();
		$data = fread($file, 1<<20);
		fclose($file);

		if ($data) {
			$header = substr($data, 0, 20);
			$header = unpack("L1magic/L1version/L1count/L1o_msg/L1o_trn", $header);
			extract($header);

			if ((dechex($magic) == "950412de") && ($version == 0)) {
				for ($n = 0; $n < $count; $n++) {
					$r = unpack("L1len/L1offs", substr($data, $o_msg + $n * 8, 8));
					$msgid = substr($data, $r["offs"], $r["len"]);
					unset($msgid_plural);

					if (strpos($msgid, "\000")) {
						list($msgid, $msgid_plural) = explode("\000", $msgid);
					}
					$r = unpack("L1len/L1offs", substr($data, $o_trn + $n * 8, 8));
					$msgstr = substr($data, $r["offs"], $r["len"]);

					if (strpos($msgstr, "\000")) {
						$msgstr = explode("\000", $msgstr);
					}
					$_this->__domains[$domain][$msgid] = $msgstr;

					if (isset($msgid_plural)) {
						$_this->__domains[$domain][$msgid_plural] = &$_this->__domains[$domain][$msgid];
					}
				}
			}
		}
	}
/**
 * Enter description here...
 *
 * @param unknown_type $file
 * @param unknown_type $domain
 * @return unknown
 * @access private
 */
	function __loadPo($file, $domain) {
		$_this =& I18n::getInstance();
		$type = 0;
		$translations = array();
		$translationKey = "";
		$plural = 0;
		$header = "";

		do {
			$line = trim(fgets($file, 1024));

			if ($line == "" || $line[0] == "#") {
				continue;
			}

			if (preg_match("/msgid[[:space:]]+\"(.+)\"$/i", $line, $regs)) {
				$type = 1;
				$translationKey = stripcslashes($regs[1]);
			} elseif (preg_match("/msgid[[:space:]]+\"\"$/i", $line, $regs)) {
				$type = 2;
				$translationKey = "";
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && ($type == 1 || $type == 2 || $type == 3)) {
				$type = 3;
				$translationKey .= stripcslashes($regs[1]);
			} elseif (preg_match("/msgstr[[:space:]]+\"(.+)\"$/i", $line, $regs) && ($type == 1 || $type == 3) && $translationKey) {
				$translations[$translationKey] = stripcslashes($regs[1]);
				$type = 4;
			} elseif (preg_match("/msgstr[[:space:]]+\"\"$/i", $line, $regs) && ($type == 1 || $type == 3) && $translationKey) {
				$type = 4;
				$translations[$translationKey] = "";
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 4 && $translationKey) {
				$translations[$translationKey] .= stripcslashes($regs[1]);
			} elseif (preg_match("/msgid_plural[[:space:]]+\".*\"$/i", $line, $regs)) {
				$type = 6;
			} elseif (preg_match("/msgstr\[(\d+)\][[:space:]]+\"(.+)\"$/i", $line, $regs) && ($type == 6) && $translationKey) {
				$plural = $regs[1];
				$translations[$translationKey][$plural] = stripcslashes($regs[2]);
				$type = 6;
			} elseif (preg_match("/msgstr\[(\d+)\][[:space:]]+\"\"$/i", $line, $regs) && ($type == 6) && $translationKey) {
				$plural = $regs[1];
				$translations[$translationKey][$plural] = "";
				$type = 6;
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 6 && $translationKey) {
				$translations[$translationKey][$plural] .= stripcslashes($regs[1]);
			} elseif (preg_match("/msgstr[[:space:]]+\"(.+)\"$/i", $line, $regs) && $type == 2 && !$translationKey) {
				$header = stripcslashes($regs[1]);
				$type = 5;
			} elseif (preg_match("/msgstr[[:space:]]+\"\"$/i", $line, $regs) && !$translationKey) {
				$header = "";
				$type = 5;
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 5) {
				$header .= stripcslashes($regs[1]);
			} else {
				unset($translations[$translationKey]);
				$type = 0;
				$translationKey = "";
				$plural = 0;
			}
		} while (!feof($file));

		fclose($file);
		$merge[""] = $header;
		return $_this->__domains[$domain] = array_merge($merge ,$translations);
	}
/**
 * Enter description here...
 *
 * @param unknown_type $domain
 * @param unknown_type $codeset
 * @return unknown
 * @access private
 * @todo Not implemented
 */
	function __bindTextDomainCodeset($domain, $codeset) {
		return($domain);
	}
}
?>