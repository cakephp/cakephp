<?php
/**
 * CSS helping functions
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.webroot
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (!defined('CAKE_CORE_INCLUDE_PATH')) {
	header('HTTP/1.1 404 Not Found');
	exit('File Not Found');
}

/**
 * Ensure required classes are available.
 */
App::import('Core', 'File');

/**
 * Make clean CSS
 *
 * @param unknown_type $path
 * @param unknown_type $name
 * @return unknown
 */
	function make_clean_css($path, $name) {
		App::import('Vendor', 'csspp' . DS . 'csspp');
		$data = file_get_contents($path);
		$csspp = new csspp();
		$output = $csspp->compress($data);
		$ratio = 100 - (round(strlen($output) / strlen($data), 3) * 100);
		$output = " /* file: $name, ratio: $ratio% */ " . $output;
		return $output;
	}
/**
 * Write CSS cache
 *
 * @param unknown_type $path
 * @param unknown_type $content
 * @return unknown
 */
	function write_css_cache($path, $content) {
		if (!is_dir(dirname($path))) {
			mkdir(dirname($path));
		}
		$cache = new File($path);
		return $cache->write($content);
	}

	if (preg_match('|\.\.|', $url) || !preg_match('|^ccss/(.+)$|i', $url, $regs)) {
		die('Wrong file name.');
	}

	$filename = 'css/' . $regs[1];
	$filepath = CSS . $regs[1];
	$cachepath = CACHE . 'css' . DS . str_replace(array('/','\\'), '-', $regs[1]);

	if (!file_exists($filepath)) {
		die('Wrong file name.');
	}

	if (file_exists($cachepath)) {
		$templateModified = filemtime($filepath);
		$cacheModified = filemtime($cachepath);

		if ($templateModified > $cacheModified) {
			$output = make_clean_css($filepath, $filename);
			write_css_cache($cachepath, $output);
		} else {
			$output = file_get_contents($cachepath);
		}
	} else {
		$output = make_clean_css($filepath, $filename);
		write_css_cache($cachepath, $output);
		$templateModified = time();
	}

	header("Date: " . date("D, j M Y G:i:s ", $templateModified) . 'GMT');
	header("Content-Type: text/css");
	header("Expires: " . gmdate("D, d M Y H:i:s", time() + DAY) . " GMT");
	header("Cache-Control: max-age=86400, must-revalidate"); // HTTP/1.1
	header("Pragma: cache");        // HTTP/1.0
	print $output;
