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
 * Copyright (c) 2005, CakePHP Authors/Developers
 *
 * Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com>
 *            Larry E. Masters aka PhpNut <nut@phpnut.com>
 *            Kamil Dzielinski aka Brego <brego.dk@gmail.com>
 *
 *  Licensed under The MIT License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Authors/Developers
 * @link         https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package      cake
 * @subpackage   cake.libs
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
  * Enter description here...
  */
uses('object');

/**
 * Short description for class
 *
 * Text-to-html parser, similar to Textile or RedCloth, only with a little different syntax. 
 *
 * @package    cake
 * @subpackage cake.libs
 * @since      CakePHP v 0.2.9
 */

class Flay extends Object
{
/**
  * Enter description here...
  *
  * @var string
  */
   var $text = null;

/**
  * Enter description here...
  *
  * @var boolean
  */
   var $allow_html = false;

/**
  * Constructor.
  *
  * @param unknown_type $text
  */
   function __construct ($text=null) 
   {
      $this->text = $text;
      parent::__construct();
   }

/**
  * Returns $text translated to HTML using the Flay syntax. 
  *
  * @param string $text Text to format
  * @param boolean $bare 
  * @param boolean $allowHtml Set this to trim whitespace and disable all HTML
  * @return string Formatted text
  */
   function toHtml ($text=null, $bare=false, $allowHtml=false) 
   {

      if (empty($text) && empty($this->text))
         return false;

      $text = $text? $text: $this->text;

      // trim whitespace and disable all HTML
      if ($allowHtml)
         $text = trim($text);
      else
         $text = str_replace('<', '&lt;', str_replace('>', '&gt;', trim($text)));

      if (!$bare) 
      {
         // multi-paragraph functions
         $text = preg_replace('#(?:[\n]{0,2})"""(.*)"""(?:[\n]{0,2})#s', "\n\n%BLOCKQUOTE%\n\n\\1\n\n%ENDBLOCKQUOTE%\n\n", $text);
         $text = preg_replace('#(?:[\n]{0,2})===(.*)===(?:[\n]{0,2})#s', "\n\n%CENTER%\n\n\\1\n\n%ENDCENTER%\n\n", $text);
      }

      // pre-parse newlines
      $text = preg_replace("#\r\n#", "\n", $text);
      $text = preg_replace("#[\n]{2,}#", "%PARAGRAPH%", $text);
      $text = preg_replace('#[\n]{1}#', "%LINEBREAK%", $text);

      // split into paragraphs and parse
      $out = '';
      foreach (split('%PARAGRAPH%', $text) as $line) 
      {
         
         if ($line) 
         {

            if (!$bare) 
            {
               // pre-parse links
               $links = array();
               $regs = null;
               if (preg_match_all('#\[([^\[]{4,})\]#', $line, $regs)) 
               {
                  foreach ($regs[1] as $reg) 
                  {
                     $links[] = $reg;
                     $line = str_replace("[{$reg}]",'%LINK'.(count($links)-1).'%', $line);
                  }
               }

               // MAIN TEXT FUNCTIONS
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
            if (preg_match_all("#([_A-Za-z0-9+-+]+(?:\.[_A-Za-z0-9+-]+)*@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*)#", $line, $emails)) 
            {
               foreach ($emails[1] as $email) 
               {
                  $line = str_replace($email, "<a href=\"mailto:{$email}\">{$email}</a>", $line);
               }
            }

            if (!$bare) 
            {
               // guess links
               $urls = null;
               if (preg_match_all("#((?:http|https|ftp|nntp)://[^ ]+)#", $line, $urls)) 
               {
                  foreach ($urls[1] as $url) 
                  {
                     $line = str_replace($url, "<a href=\"{$url}\">{$url}</a>", $line);
                  }
               }
               if (preg_match_all("#(www\.[^\n\%\ ]+[^\n\%\,\.\ ])#", $line, $urls))
               {
                  foreach ($urls[1] as $url) 
                  {
                     $line = str_replace($url, "<a href=\"http://{$url}\">{$url}</a>", $line);
                  }
               }
                     
               // re-parse links
               if (count($links)) 
               {   
                  for ($ii=0; $ii<count($links); $ii++) 
                  {
                     if (preg_match("#^(http|https|ftp|nntp)://#", $links[$ii]))
                     {
                        $prefix = null;
                     }
                     else 
                     {
                        $prefix = 'http://';
                     }
                     
                     if (preg_match('#^[^\ ]+\.(jpg|jpeg|gif|png)$#', $links[$ii]))
                     {
                        $with = "<img src=\"{$prefix}{$links[$ii]}\" alt=\"\" />";
                     }
                     elseif (preg_match('#^([^\]\ ]+)(?:\ ([^\]]+))?$#', $links[$ii], $regs))
                     {
                        if (isset($regs[2]))
                        {
                           if (preg_match('#\.(jpg|jpeg|gif|png)$#', $regs[2]))
                              $body = "<img src=\"{$prefix}{$regs[2]}\" alt=\"\" />";
                           else
                              $body = $regs[2];
   
                        }
                        else 
                        {
                           $body = $links[$ii];
                        }
                     	
                        $with = "<a href=\"{$prefix}{$regs[1]}\" target=\"_blank\">{$body}</a>";
                     }
                     else
                     {
                        $with = $prefix.$links[$ii];
                     }

                     $line = str_replace("%LINK{$ii}%", $with, $line);
                  }
               }
            }
         
            // re-parse newlines
            $out .= str_replace('%LINEBREAK%', "<br />\n", "<p>{$line}</p>\n");
         }
      }

      if (!$bare) 
      {
         // re-parse multilines
         $out = str_replace('<p>%BLOCKQUOTE%</p>', "<blockquote>", $out);
         $out = str_replace('<p>%ENDBLOCKQUOTE%</p>', "</blockquote>", $out);
         $out = str_replace('<p>%CENTER%</p>', "<center>", $out);
         $out = str_replace('<p>%ENDCENTER%</p>', "</center>", $out);
      }

      return $out;
   }

/**
 * Enter description here...
 *
 * @param unknown_type $string
 * @return unknown
 */
   function extractWords ($string) 
   {
      return preg_split('/[\s,\.:\/="!\(\)<>~\[\]]+/', $string);
   }

/**
 * Enter description here...
 *
 * @param unknown_type $words
 * @param unknown_type $string
 * @param unknown_type $max_snippets
 * @return unknown
 */
   function markedSnippets ($words, $string, $max_snippets=5) 
   {

      $string = strip_tags($string);

      $snips = array();
      $rest = $string;
      foreach ($words as $word) 
      {
         if (preg_match_all("/[\s,]+.{0,40}{$word}.{0,40}[\s,]+/i", $rest, $r)) 
         {
            foreach ($r as $result)
               $rest = str_replace($result, '', $rest);
            $snips = array_merge($snips, $r[0]);
         }
      }

      if (count($snips) > $max_snippets) 
      {
      	 $snips = array_slice($snips, 0, $max_snippets);
      }
      $joined = join(' <b>...</b> ', $snips);
      $snips = $joined? "<b>...</b> {$joined} <b>...</b>": substr($string, 0, 80).'<b>...</b>';

      return Flay::colorMark($words, $snips);
   }

/**
 * Enter description here...
 *
 * @param unknown_type $words
 * @param unknown_type $string
 * @return unknown
 */
   function colorMark($words, $string) 
   {
      $colors = array('yl','gr','rd','bl','fu','cy');

      $nextColorIndex = 0;
      foreach ($words as $word) 
      {
         $string = preg_replace("/({$word})/i", '<em class="'.$colors[$nextColorIndex%count($colors)]."\">\\1</em>", $string);
         $nextColorIndex++;
      }

      return $string;
   }

/**
 * Enter description here...
 *
 * @param unknown_type $text
 * @return unknown
 */
   function toClean ($text) 
   {
      return strip_tags(html_entity_decode($text, ENT_QUOTES));
   }
   
   function toParsedAndClean ($text)
   {
      return Flay::toClean(Flay::toHtml($text));
   }

/**
 * Enter description here...
 *
 * @param unknown_type $text
 * @param unknown_type $length
 * @param unknown_type $elipsis
 * @return unknown
 */
   function fragment ($text, $length, $elipsis='...') 
   {
      $soft=$length-5;
      $hard=$length+5;
      $rx = '/(.{'.$soft.','.$hard.'})[\s,\.:\/="!\(\)<>~\[\]]+.*/';
      if (preg_match($rx, $text, $r)) 
      {
         $out = $r[1];
      }
      else 
      {
         $out = substr($text,0,$length);
      }

      $out = $out.(strlen($out)<strlen($text)? $elipsis: null);
      return $out;
   }
}

?>