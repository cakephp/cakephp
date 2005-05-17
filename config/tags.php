<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id: index.php 109 2005-05-16 00:52:42Z phpnut $
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
  * Purpose: paths.php
  * Enter description here...
  *
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.config
  * @since Cake v 0.2.9
  * @version $Revision: 115 $
  * @modifiedby $LastChangedBy: phpnut $
  * @lastmodified $Date: 2005-05-16 18:47:54 -0500 (Mon, 16 May 2005) $
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  *
  */

/**
  * Enter description here...
  *
  */
define('TAG_LINK', '<a href="%s"%s>%s</a>');
/**
  * Enter description here...
  *
  */
define('TAG_FORM', '<form %s>');
/**
  * Enter description here...
  *
  */
define('TAG_INPUT',			'<input name="data[%s]" %s/>');
/**
  * Enter description here...
  *
  */
define('TAG_HIDDEN',			'<input type="hidden" name="data[%s]" %s/>');
/**
  * Enter description here...
  *
  */
define('TAG_AREA',			'<textarea name="data[%s]"%s>%s</textarea>');
/**
  * Enter description here...
  *
  */
define('TAG_CHECKBOX',		'<label for="tag_%s"><input type="checkbox" name="data[%s]" id="tag_%s" %s/>%s</label>');
/**
  * Enter description here...
  *
  */
define('TAG_RADIOS', 		'<label for="tag_%s"><input type="radio" name="data[%s]" id="tag_%s" %s/>%s</label>');
/**
  * Enter description here...
  *
  */
define('TAG_SELECT_START', '<select name="data[%s]"%s>');
/**
  * Enter description here...
  *
  */
define('TAG_SELECT_EMPTY', '<option value=""%s></option>');
/**
  * Enter description here...
  *
  */
define('TAG_SELECT_OPTION','<option value="%s"%s>%s</option>');
/**
  * Enter description here...
  *
  */
define('TAG_SELECT_END',	'</select>');
/**
  * Enter description here...
  *
  */
define('TAG_PASSWORD',		'<input type="password" name="data[%s]" %s/>');
/**
  * Enter description here...
  *
  */
define('TAG_FILE',			'<input type="file" name="%s" %s/>');
/**
  * Enter description here...
  *
  */
define('TAG_SUBMIT',			'<input type="submit" %s/>');
/**
  * Enter description here...
  *
  */
define('TAG_IMAGE',			'<img src="%s" alt="%s" %s/>');
/**
  * Enter description here...
  *
  */
define('TAG_TABLE_HEADER',	'<th%s>%s</th>');
/**
  * Enter description here...
  *
  */
define('TAG_TABLE_HEADERS','<tr%s>%s</tr>');
/**
  * Enter description here...
  *
  */
define('TAG_TABLE_CELL',	'<td%s>%s</td>');
/**
  * Enter description here...
  *
  */
define('TAG_TABLE_ROW',		'<tr%s>%s</tr>');
?>