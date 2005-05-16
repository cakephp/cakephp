<?PHP

/** 
 *  Tag generator templates
 *
 *  Usually there's no need to change those.
*/

define('TAG_LINK', '<a href="%s"%s>%s</a>');

define('TAG_FORM', '<form %s>');

define('TAG_INPUT',			'<input name="data[%s]" %s/>');
define('TAG_HIDDEN',			'<input type="hidden" name="data[%s]" %s/>');
define('TAG_AREA',			'<textarea name="data[%s]"%s>%s</textarea>');
define('TAG_CHECKBOX',		'<label for="tag_%s"><input type="checkbox" name="data[%s]" id="tag_%s" %s/>%s</label>');
define('TAG_RADIOS', 		'<label for="tag_%s"><input type="radio" name="data[%s]" id="tag_%s" %s/>%s</label>');

define('TAG_SELECT_START', '<select name="data[%s]"%s>');
define('TAG_SELECT_EMPTY', '<option value=""%s></option>');
define('TAG_SELECT_OPTION','<option value="%s"%s>%s</option>');
define('TAG_SELECT_END',	'</select>');

define('TAG_PASSWORD',		'<input type="password" name="data[%s]" %s/>');
define('TAG_FILE',			'<input type="file" name="%s" %s/>');

define('TAG_SUBMIT',			'<input type="submit" %s/>');

define('TAG_IMAGE',			'<img src="%s" alt="%s" %s/>');

define('TAG_TABLE_HEADER',	'<th%s>%s</th>');
define('TAG_TABLE_HEADERS','<tr%s>%s</tr>');
define('TAG_TABLE_CELL',	'<td%s>%s</td>');
define('TAG_TABLE_ROW',		'<tr%s>%s</tr>');
