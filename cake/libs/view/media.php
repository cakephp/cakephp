<?php
/**
 * Methods to display or download any type of file
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
 * @subpackage    cake.cake.libs.view
 * @since         CakePHP(tm) v 1.2.0.5714
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('View', 'View', false);

class MediaView extends View {

/**
 * Holds known mime type mappings
 *
 * @var array
 * @access public
 */
	var $mimeType = array(
		'ai' => 'application/postscript', 'bcpio' => 'application/x-bcpio', 'bin' => 'application/octet-stream',
		'ccad' => 'application/clariscad', 'cdf' => 'application/x-netcdf', 'class' => 'application/octet-stream',
		'cpio' => 'application/x-cpio', 'cpt' => 'application/mac-compactpro', 'csh' => 'application/x-csh',
		'csv' => 'application/csv', 'dcr' => 'application/x-director', 'dir' => 'application/x-director',
		'dms' => 'application/octet-stream', 'doc' => 'application/msword', 'drw' => 'application/drafting',
		'dvi' => 'application/x-dvi', 'dwg' => 'application/acad', 'dxf' => 'application/dxf',
		'dxr' => 'application/x-director', 'eot' => 'application/vnd.ms-fontobject', 'eps' => 'application/postscript',
		'exe' => 'application/octet-stream', 'ez' => 'application/andrew-inset',
		'flv' => 'video/x-flv', 'gtar' => 'application/x-gtar', 'gz' => 'application/x-gzip',
		'bz2' => 'application/x-bzip', '7z' => 'application/x-7z-compressed', 'hdf' => 'application/x-hdf',
		'hqx' => 'application/mac-binhex40', 'ico' => 'image/vnd.microsoft.icon', 'ips' => 'application/x-ipscript',
		'ipx' => 'application/x-ipix', 'js' => 'application/x-javascript', 'latex' => 'application/x-latex',
		'lha' => 'application/octet-stream', 'lsp' => 'application/x-lisp', 'lzh' => 'application/octet-stream',
		'man' => 'application/x-troff-man', 'me' => 'application/x-troff-me', 'mif' => 'application/vnd.mif',
		'ms' => 'application/x-troff-ms', 'nc' => 'application/x-netcdf', 'oda' => 'application/oda',
		'otf' => 'font/otf', 'pdf' => 'application/pdf',
		'pgn' => 'application/x-chess-pgn', 'pot' => 'application/mspowerpoint', 'pps' => 'application/mspowerpoint',
		'ppt' => 'application/mspowerpoint', 'ppz' => 'application/mspowerpoint', 'pre' => 'application/x-freelance',
		'prt' => 'application/pro_eng', 'ps' => 'application/postscript', 'roff' => 'application/x-troff',
		'scm' => 'application/x-lotusscreencam', 'set' => 'application/set', 'sh' => 'application/x-sh',
		'shar' => 'application/x-shar', 'sit' => 'application/x-stuffit', 'skd' => 'application/x-koan',
		'skm' => 'application/x-koan', 'skp' => 'application/x-koan', 'skt' => 'application/x-koan',
		'smi' => 'application/smil', 'smil' => 'application/smil', 'sol' => 'application/solids',
		'spl' => 'application/x-futuresplash', 'src' => 'application/x-wais-source', 'step' => 'application/STEP',
		'stl' => 'application/SLA', 'stp' => 'application/STEP', 'sv4cpio' => 'application/x-sv4cpio',
		'sv4crc' => 'application/x-sv4crc', 'svg' => 'image/svg+xml', 'svgz' => 'image/svg+xml',
		'swf' => 'application/x-shockwave-flash', 't' => 'application/x-troff',
		'tar' => 'application/x-tar', 'tcl' => 'application/x-tcl', 'tex' => 'application/x-tex',
		'texi' => 'application/x-texinfo', 'texinfo' => 'application/x-texinfo', 'tr' => 'application/x-troff',
		'tsp' => 'application/dsptype', 'ttf' => 'font/ttf',
		'unv' => 'application/i-deas', 'ustar' => 'application/x-ustar',
		'vcd' => 'application/x-cdlink', 'vda' => 'application/vda', 'xlc' => 'application/vnd.ms-excel',
		'xll' => 'application/vnd.ms-excel', 'xlm' => 'application/vnd.ms-excel', 'xls' => 'application/vnd.ms-excel',
		'xlw' => 'application/vnd.ms-excel', 'zip' => 'application/zip', 'aif' => 'audio/x-aiff', 'aifc' => 'audio/x-aiff',
		'aiff' => 'audio/x-aiff', 'au' => 'audio/basic', 'kar' => 'audio/midi', 'mid' => 'audio/midi',
		'midi' => 'audio/midi', 'mp2' => 'audio/mpeg', 'mp3' => 'audio/mpeg', 'mpga' => 'audio/mpeg',
		'ra' => 'audio/x-realaudio', 'ram' => 'audio/x-pn-realaudio', 'rm' => 'audio/x-pn-realaudio',
		'rpm' => 'audio/x-pn-realaudio-plugin', 'snd' => 'audio/basic', 'tsi' => 'audio/TSP-audio', 'wav' => 'audio/x-wav',
		'asc' => 'text/plain', 'c' => 'text/plain', 'cc' => 'text/plain', 'css' => 'text/css', 'etx' => 'text/x-setext',
		'f' => 'text/plain', 'f90' => 'text/plain', 'h' => 'text/plain', 'hh' => 'text/plain', 'htm' => 'text/html',
		'html' => 'text/html', 'm' => 'text/plain', 'rtf' => 'text/rtf', 'rtx' => 'text/richtext', 'sgm' => 'text/sgml',
		'sgml' => 'text/sgml', 'tsv' => 'text/tab-separated-values', 'tpl' => 'text/template', 'txt' => 'text/plain',
		'xml' => 'text/xml', 'avi' => 'video/x-msvideo', 'fli' => 'video/x-fli', 'mov' => 'video/quicktime',
		'movie' => 'video/x-sgi-movie', 'mpe' => 'video/mpeg', 'mpeg' => 'video/mpeg', 'mpg' => 'video/mpeg',
		'qt' => 'video/quicktime', 'viv' => 'video/vnd.vivo', 'vivo' => 'video/vnd.vivo', 'gif' => 'image/gif',
		'ief' => 'image/ief', 'jpe' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg',
		'pbm' => 'image/x-portable-bitmap', 'pgm' => 'image/x-portable-graymap', 'png' => 'image/png',
		'pnm' => 'image/x-portable-anymap', 'ppm' => 'image/x-portable-pixmap', 'ras' => 'image/cmu-raster',
		'rgb' => 'image/x-rgb', 'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'xbm' => 'image/x-xbitmap',
		'xpm' => 'image/x-xpixmap', 'xwd' => 'image/x-xwindowdump', 'ice' => 'x-conference/x-cooltalk',
		'iges' => 'model/iges', 'igs' => 'model/iges', 'mesh' => 'model/mesh', 'msh' => 'model/mesh',
		'silo' => 'model/mesh', 'vrml' => 'model/vrml', 'wrl' => 'model/vrml',
		'mime' => 'www/mime', 'pdb' => 'chemical/x-pdb', 'xyz' => 'chemical/x-pdb');

/**
 * Holds headers sent to browser before rendering media
 *
 * @var array
 * @access protected
 */
	var $_headers = array();

/**
 * Constructor
 *
 * @param object $controller
 */
	function __construct(&$controller) {
		parent::__construct($controller);
	}

/**
 * Display or download the given file
 *
 * @return unknown
 */
	function render() {
		$name = $download = $extension = $id = $modified = $path = $size = $cache = $mimeType = null;
		extract($this->viewVars, EXTR_OVERWRITE);

		if ($size) {
			$id = $id . '_' . $size;
		}

		if (is_dir($path)) {
			$path = $path . $id;
		} else {
			$path = APP . $path . $id;
		}

		if (!file_exists($path)) {
			header('Content-Type: text/html');
			$this->cakeError('error404');
		}

		if (is_null($name)) {
			$name = $id;
		}

		if (is_array($mimeType)) {
			$this->mimeType = array_merge($this->mimeType, $mimeType);
		}

		if (isset($extension) && isset($this->mimeType[strtolower($extension)]) && connection_status() == 0) {
			$chunkSize = 8192;
			$buffer = '';
			$fileSize = @filesize($path);
			$handle = fopen($path, 'rb');

			if ($handle === false) {
				return false;
			}
			if (!empty($modified)) {
				$modified = gmdate('D, d M Y H:i:s', strtotime($modified, time())) . ' GMT';
			} else {
				$modified = gmdate('D, d M Y H:i:s') . ' GMT';
			}

			if ($download) {
				$contentTypes = array('application/octet-stream');
				$agent = env('HTTP_USER_AGENT');

				if (preg_match('%Opera(/| )([0-9].[0-9]{1,2})%', $agent)) {
					$contentTypes[0] = 'application/octetstream';
				} else if (preg_match('/MSIE ([0-9].[0-9]{1,2})/', $agent)) {
					$contentTypes[0] = 'application/force-download';
					array_merge($contentTypes, array(
						'application/octet-stream',
						'application/download'
					));
				}
				foreach($contentTypes as $contentType) {
					$this->_header('Content-Type: ' . $contentType);
				}
				$this->_header(array(
					'Content-Disposition: attachment; filename="' . $name . '.' . $extension . '";',
					'Expires: 0',
					'Accept-Ranges: bytes',
					'Cache-Control: private' => false,
					'Pragma: private'));

				$httpRange = env('HTTP_RANGE');
				if (isset($httpRange)) {
					list($toss, $range) = explode('=', $httpRange);

					$size = $fileSize - 1;
					$length = $fileSize - $range;

					$this->_header(array(
						'HTTP/1.1 206 Partial Content',
						'Content-Length: ' . $length,
						'Content-Range: bytes ' . $range . $size . '/' . $fileSize));

					fseek($handle, $range);
				} else {
					$this->_header('Content-Length: ' . $fileSize);
				}
			} else {
				$this->_header('Date: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
				if ($cache) {
					if (!is_numeric($cache)) {
						$cache = strtotime($cache) - time();
					}
					$this->_header(array(
						'Cache-Control: max-age=' . $cache,
						'Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache) . ' GMT',
						'Pragma: cache'));
				} else {
					$this->_header(array(
						'Cache-Control: must-revalidate, post-check=0, pre-check=0',
						'Pragma: no-cache'));
				}
				$this->_header(array(
					'Last-Modified: ' . $modified,
					'Content-Type: ' . $this->mimeType[strtolower($extension)],
					'Content-Length: ' . $fileSize));
			}
			$this->_output();
			$this->_clearBuffer();

			while (!feof($handle)) {
				if (!$this->_isActive()) {
					fclose($handle);
					return false;
				}
				set_time_limit(0);
				$buffer = fread($handle, $chunkSize);
				echo $buffer;
				$this->_flushBuffer();
			}
			fclose($handle);
			return;
		}
		return false;
	}

/**
 * Method to set headers
 * @param mixed $header
 * @param boolean $boolean
 * @access protected
 */
	function _header($header, $boolean = true) {
		if (is_array($header)) {
			foreach ($header as $string => $boolean) {
				if (is_numeric($string)) {
					$this->_headers[] = array($boolean => true);
				} else {
					$this->_headers[] = array($string => $boolean);
				}
			}
			return;
		}
		$this->_headers[] = array($header => $boolean);
		return;
	}

/**
 * Method to output headers
 * @access protected
 */
	function _output() {
		foreach ($this->_headers as $key => $value) {
			$header = key($value);
			header($header, $value[$header]);
		}
	}

/**
 * Returns true if connection is still active
 * @return boolean
 * @access protected
 */
	function _isActive() {
		return connection_status() == 0 && !connection_aborted();
	}

/**
 * Clears the contents of the topmost output buffer and discards them
 * @return boolean
 * @access protected
 */
	function _clearBuffer() {
		return @ob_end_clean();
	}

/**
 * Flushes the contents of the output buffer
 * @access protected
 */
	function _flushBuffer() {
		@flush();
		@ob_flush();
	}
}
