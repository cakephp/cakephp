<?PHP 
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005 Cake Authors/Developers                      + //
// +                                                                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + You may not use this file except in compliance with the License. + //
// +                                                                  + //
// + You may obtain a copy of the License at:                         + //
// + License page: http://www.opensource.org/licenses/mit-license.php + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Flay
  * Text-to-html parser, similar to Textile or RedCloth, only with somehow different syntax. 
  * See Flay::test() for examples.
  * Test with $flay = new Flay(); $flay->test();
  *
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  *
  */

/**
  * Enter description here...
  *
  */
uses('object');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Flay extends Object {
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $text = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $allow_html = false;

/**
  * Enter description here...
  *
  * @param unknown_type $text
  */
	function __construct ($text=null) {
		$this->text = $text;
		parent::__construct();
	}

/**
  * Enter description here...
  *
  * @param unknown_type $text
  * @return unknown
  */
	function toHtml ($text=null) {
		$text = $text? $text: $this->text;

		// trim whitespace and disable all HTML
		$text = str_replace('<', '&lt;', str_replace('>', '&gt;', trim($text)));

		// multi-paragraph functions
		$text = preg_replace('#(?:[\n]{0,2})"""(.*)"""(?:[\n]{0,2})#s', "\n\n%BLOCKQUOTE%\n\n\\1\n\n%ENDBLOCKQUOTE%\n\n", $text);
		$text = preg_replace('#(?:[\n]{0,2})===(.*)===(?:[\n]{0,2})#s', "\n\n%CENTER%\n\n\\1\n\n%ENDCENTER%\n\n", $text);

		// pre-parse newlines
		$text = preg_replace("#\r\n#", "\n", $text);
		$text = preg_replace("#[\n]{2,}#", "%PARAGRAPH%", $text);
		$text = preg_replace('#[\n]{1}#', "%LINEBREAK%", $text);

		// split into paragraphs and parse
		$out = '';
		foreach (split('%PARAGRAPH%', $text) as $line) {
			
			if ($line) {

				// pre-parse links
				$links = array();
				$regs = null;
				if (preg_match_all('#\[([^\[]{4,})\]#', $line, $regs)) {
					foreach ($regs[1] as $reg) {
						$links[] = $reg;
						$line = str_replace("[{$reg}]",'%LINK'.(count($links)-1).'%', $line);
					}
				}

				// MAIN TEXT FUNCTIONS
				// bold
				$line = ereg_replace("\*([^\*]*)\*", "<strong>\\1</strong>", $line);
				// italic
				$line = ereg_replace("_([^_]*)_", "<em>\\1</em>", $line);
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
				// guess links
				$urls = null;
				if (preg_match_all("#((?:http|https|ftp|nntp)://[^ ]+)#", $line, $urls)) {
					foreach ($urls[1] as $url) {
						$line = str_replace($url, "<a href=\"{$url}\">{$url}</a>", $line);
					}
				}
				if (preg_match_all("#(www\.[^ ]+)#", $line, $urls)) {
					foreach ($urls[1] as $url) {
						$line = str_replace($url, "<a href=\"{$url}\">{$url}</a>", $line);
					}
				}
				
							
				// re-parse links
				if (count($links)) {
					for ($ii=0; $ii<count($links); $ii++) {

						if (preg_match('#\.(jpg|jpeg|gif|png)$#', $links[$ii]))
							$with = "<img src=\"{$links[$ii]}\" alt=\"\" />";
						elseif (preg_match('#^([^\]\ ]+)(?: ([^\]]+))?$#', $links[$ii], $regs))
							$with = "<a href=\"{$regs[1]}\" target=\"_blank\">".(isset($regs[2])? $regs[2]: $regs[1])."</a>";
						else
							$with = $links[$ii];

						$line = str_replace("%LINK{$ii}%", $with, $line);
					}
				}
			
				// re-parse newlines
				$out .= str_replace('%LINEBREAK%', "<br />\n", "<p>{$line}</p>\n");
			}
		}

		// re-parse multilines
		$out = str_replace('<p>%BLOCKQUOTE%</p>', "<blockquote>", $out);
		$out = str_replace('<p>%ENDBLOCKQUOTE%</p>', "</blockquote>", $out);
		$out = str_replace('<p>%CENTER%</p>', "<center>", $out);
		$out = str_replace('<p>%ENDCENTER%</p>', "</center>", $out);

		return $out;
	}
}

?>