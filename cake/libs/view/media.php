<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.view
 * @since			CakePHP(tm) v 1.2.0.5714
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class MediaView extends View {
/**
 * Holds known mime type mappings
 *
 * @var array
 */
	var $mimeType = array('ai' => 'application/postscript', 'bcpio' => 'application/x-bcpio', 'bin' => 'application/octet-stream',
								'ccad' => 'application/clariscad', 'cdf' => 'application/x-netcdf', 'class' => 'application/octet-stream',
								'cpio' => 'application/x-cpio', 'cpt' => 'application/mac-compactpro', 'csh' => 'application/x-csh',
								'csv' => 'application/csv', 'dcr' => 'application/x-director', 'dir' => 'application/x-director',
								'dms' => 'application/octet-stream', 'doc' => 'application/msword', 'drw' => 'application/drafting',
								'dvi' => 'application/x-dvi', 'dwg' => 'application/acad', 'dxf' => 'application/dxf', 'dxr' => 'application/x-director',
								'eps' => 'application/postscript', 'exe' => 'application/octet-stream', 'ez' => 'application/andrew-inset',
								'flv' => 'video/x-flv', 'gtar' => 'application/x-gtar', 'gz' => 'application/x-gzip', 'hdf' => 'application/x-hdf',
								'hqx' => 'application/mac-binhex40', 'ips' => 'application/x-ipscript', 'ipx' => 'application/x-ipix',
								'js' => 'application/x-javascript', 'latex' => 'application/x-latex', 'lha' => 'application/octet-stream',
								'lsp' => 'application/x-lisp', 'lzh' => 'application/octet-stream', 'man' => 'application/x-troff-man',
								'me' => 'application/x-troff-me', 'mif' => 'application/vnd.mif', 'ms' => 'application/x-troff-ms',
								'nc' => 'application/x-netcdf', 'oda' => 'application/oda', 'pdf' => 'application/pdf',
								'pgn' => 'application/x-chess-pgn', 'pot' => 'application/mspowerpoint', 'pps' => 'application/mspowerpoint',
								'ppt' => 'application/mspowerpoint', 'ppz' => 'application/mspowerpoint', 'pre' => 'application/x-freelance',
								'prt' => 'application/pro_eng', 'ps' => 'application/postscript', 'roff' => 'application/x-troff',
								'scm' => 'application/x-lotusscreencam', 'set' => 'application/set', 'sh' => 'application/x-sh',
								'shar' => 'application/x-shar', 'sit' => 'application/x-stuffit', 'skd' => 'application/x-koan',
								'skm' => 'application/x-koan', 'skp' => 'application/x-koan', 'skt' => 'application/x-koan',
								'smi' => 'application/smil', 'smil' => 'application/smil', 'sol' => 'application/solids',
								'spl' => 'application/x-futuresplash', 'src' => 'application/x-wais-source', 'step' => 'application/STEP',
								'stl' => 'application/SLA', 'stp' => 'application/STEP', 'sv4cpio' => 'application/x-sv4cpio',
								'sv4crc' => 'application/x-sv4crc', 'swf' => 'application/x-shockwave-flash', 't' => 'application/x-troff',
								'tar' => 'application/x-tar', 'tcl' => 'application/x-tcl', 'tex' => 'application/x-tex',
								'texi' => 'application/x-texinfo', 'texinfo' => 'application/x-texinfo', 'tr' => 'application/x-troff',
								'tsp' => 'application/dsptype', 'unv' => 'application/i-deas', 'ustar' => 'application/x-ustar',
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
 * Constructor
 *
 * @param object $controller
 */
	function __construct(&$controller) {
		parent::__construct($controller);
	}
/**
 * Enter description here...
 *
 * @return unknown
 */
	function render() {
		$name = null;
		$download = null;
		$extension = null;
		$id = null;
		$modified = null;
		$path = null;
		$size = null;
		$cache = null;
		extract($this->viewVars, EXTR_OVERWRITE);

		if ($size) {
			$id = $id . "_$size";
		}
		$path = APP . $path . $id;

		if (is_null($name)) {
			$name = $id;
		}

		if (file_exists($path) && isset($extension) && array_key_exists($extension, $this->mimeType) && connection_status() == 0) {
			$chunkSize = 1 * (1024 * 8);
			$buffer = '';
			$fileSize = @filesize($path);
			$handle = fopen($path, 'rb');

			if ($handle === false) {
				return false;
			}
			if (!empty($modified)) {
				$modified = gmdate('D, d M Y H:i:s', strtotime($modified, time())) . ' GMT';
			} else {
				$modified = gmdate('D, d M Y H:i:s').' GMT';
			}

			if ($download) {
				$contentType = 'application/octet-stream';
				$agent = env('HTTP_USER_AGENT');

				if (preg_match('%Opera(/| )([0-9].[0-9]{1,2})%', $agent) || preg_match('/MSIE ([0-9].[0-9]{1,2})/', $agent)) {
					$contentType = 'application/octetstream';
				}
				header('Content-Type: ' . $contentType);
				header("Content-Disposition: attachment; filename=\"" . $name . '.' . $extension . "\";");
				header("Expires: 0");
				header('Accept-Ranges: bytes');
				header("Cache-Control: private", false);
				header("Pragma: private");

				$httpRange = env('HTTP_RANGE');

				if (isset($httpRange)) {
					list ($toss, $range) = explode("=", $httpRange);
					str_replace($range, "-", $range);

					$size = $fileSize - 1;
					$length = $fileSize - $range;

					header("HTTP/1.1 206 Partial Content");
					header("Content-Length: $length");
					header("Content-Range: bytes $range$size/$fileSize");
					fseek($handle, $range);
				} else {
					header("Content-Length: " . $fileSize);
				}
			} else {
				header("Date: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
				if ($cache) {
					if (!is_numeric($cache)) {
						$cache = strtotime($cache) - time();
					}
					header("Cache-Control: max-age=$cache");
					header("Expires: " . gmdate("D, d M Y H:i:s", time() + $cache) . " GMT");
					header("Pragma: cache");
				} else {
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					header("Pragma: no-cache");
				}
				header("Last-Modified: $modified");
				header("Content-Type: " . $this->mimeType[$extension]);
				header("Content-Length: " . $fileSize);
			}
			@ob_end_clean();

			while (!feof($handle) && connection_status() == 0 && !connection_aborted()) {
				set_time_limit(0);
				$buffer = fread($handle, $chunkSize);
				echo $buffer;
				@flush();
				@ob_flush();
			}
			fclose($handle);
			exit(0);
		}
		return false;
	}
}
?>