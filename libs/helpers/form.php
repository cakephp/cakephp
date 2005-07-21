<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 * Purpose: Dispatcher
 * Dispatches the request, creating aproppriate models and controllers.
 * 
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.libs.helpers
 * @since CakePHP v 0.9.2
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

uses( 'helpers/html' );

/**
 * Tag template for a div. 
 */
define('TAG_DIV', '<div class="%s">%s</div>');

/**
 * Tag template for a div. 
 */
define('TAG_P_CLASS', '<p class="%s">%s</p>');

/**
 * Tag template for a label. 
 */
define('TAG_LABEL', '<label for="%s">%s</label>');

/**
 * Tag template for a fieldset. 
 */
define('TAG_FIELDSET', '<fieldset><legend>%s</legend>%s</label>');

/**
 * Form helper library.
 *
 * @package cake
 * @subpackage cake.libs.helpers
 * @since CakePHP v 0.9.1
 *
 */
class FormHelper
{

	/**
 * Constructor which takes an instance of the HtmlHelper class.
 *
 * @param object $htmlHelper  the HtmlHelper object to use as our helper.
 * @return void 
 */
	function FormHelper()
	{
	}

	/**
 * Returns a formatted error message for given FORM field, NULL if no errors.
 *
 * @param string $field  If field is to be used for CRUD, this should be modelName/fieldName
 * @return bool If there are errors this method returns true, else false. 
 */
	function isFieldError(HtmlHelper $html, $field )
	{
		$error = 1;
		$html->setFormTag( $field );
		if( $error == $html->tagIsInvalid( $html->model, $html->field) )
		{
			return true;
		} else {
			return false;
		}
	}

	/**
 * Returns a formatted LABEL tag for HTML FORMs.
 *
 * @param string $tagName If field is to be used for CRUD, this should be modelName/fieldName
 * @param string $text Text that will appear in the label field.
 * @return string The formatted LABEL element
 */
	function labelTag( $tagName, $text )
	{
		return sprintf( TAG_LABEL, $tagName, $text );
	}

	/**
 * Returns a formatted DIV tag for HTML FORMs.
 *
 * @param string $class If field is to be used for CRUD, this should be modelName/fieldName
 * @param string $text Text that will appear in the label field.
 * @return string The formatted DIV element
 */
	function divTag( $class, $text )
	{
		return sprintf( TAG_DIV, $class, $text );
	}

	/**
 * Returns a formatted P tag with class for HTML FORMs.
 *
 * @param string $class If field is to be used for CRUD, this should be modelName/fieldName
 * @param string $text Text that will appear in the label field.
 * @return string The formatted DIV element
 */
	function pTag( $class, $text )
	{
		return sprintf( TAG_P_CLASS, $class, $text );
	}

	/**
 * Returns a formatted INPUT tag for HTML FORMs.
 *
 * @param HtmlHelper $html The HtmlHelper object which is creating this form.
 * @param string $tagName If field is to be used for CRUD, this should be modelName/fieldName
 * @param string $prompt Text that will appear in the label field.
 * @param bool $required True if this field is required.
 * @param string $errorMsg Text that will appear if an error has occurred.
 * @param int $size Size attribute for INPUT element
 * @param array $htmlOptions 
 * @return string The formatted INPUT element
 */
	function generateInputDiv(HtmlHelper $html, $tagName, $prompt, $required=false, $errorMsg=null, $size=20, $htmlOptions=null )
	{
		$str = $html->inputTag( $tagName, $size, $htmlOptions );
		$strLabel = $this->labelTag( $tagName, $prompt );

		$divClass = "optional";

		if( $required )
		$divClass = "required";

		$strError = ""; // initialize the error to empty.

		if( $this->isFieldError( $html, $tagName ) )
		{
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );

		return $this->divTag( $divClass, $divTagInside );

	}

	function generateAreaDiv(HtmlHelper $html, $tagName, $prompt, $required=false, $errorMsg=null, $cols=60, $rows=10,  $htmlOptions=null )
	{
		$str = $html->areaTag( $tagName, $cols, $rows, $htmlOptions );
		$strLabel = $this->labelTag( $tagName, $prompt );

		$divClass = "optional";

		if( $required )
		$divClass = "required";

		$strError = ""; // initialize the error to empty.

		if( $this->isFieldError( $html, $tagName ) )
		{
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );

		return $this->divTag( $divClass, $divTagInside );

	}
	/**
 * Returns a formatted SELECT tag for HTML FORMs.
 *
 * @param HtmlHelper $html The HtmlHelper object which is creating this form.
 * @param string $tagName If field is to be used for CRUD, this should be modelName/fieldName
 * @param string $prompt Text that will appear in the label field.
 * @param array $options Options to be contained in SELECT element
 * @param bool $required True if this field is required.
 * @param string $selected Text of the currently selected item
 * @param bool $required True if this field is required.
 * @param bool $required True if this field is required.
 * @param string $errorMsg Text that will appear if an error has occurred.
 * @param array $selectAttr
 * @param array $optionAttr
 * @return string The formatted INPUT element
 */
	function generateSelectDiv(HtmlHelper $html, $tagName, $prompt, $options, $selected=null, $selectAttr=null, $optionAttr=null, $required=false,  $errorMsg=null)
	{
		$str = $html->selectTag( $tagName, $options, $selected, $selectAttr, $optionAttr );
		$strLabel = $this->labelTag( $tagName, $prompt );

		$divClass = "optional";

		if( $required )
		$divClass = "required";

		$strError = ""; // initialize the error to empty.

		if( $this->isFieldError( $html, $tagName ) )
		{
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );

		return $this->divTag( $divClass, $divTagInside );

	}

/**
 * Generates an form to go onto a HtmlHelper object.
 *
 * @param HtmlHelper $html The HtmlHelper object which is creating this form.
 * @param array $fields An array of form field definitions.
 * @return string The completed form specified by the $fields praameter.
 */
	function generateFields( $html, $fields )
	{
		$strFormFields = '';

		foreach( $fields as $field )
		{
			switch( $field['type'] )
			{
				case "input" :
				$strFormFields = $strFormFields.$this->generateInputDiv( $html, $field['tagName'], $field['prompt'], $field['required'], $field['errorMsg'], $field['size'], $field['htmlOptions'] );
				break;
				case "select";
				$strFormFields = $strFormFields.$this->generateSelectDiv( $html, $field['tagName'], $field['prompt'], $field['options'], $field['selected'], $field['selectAttr'], $field['optionsAttr'], $field['required'], $field['errorMsg'] );
				break;
				case "area";
				$strFormFields = $strFormFields.$this->generateAreaDiv( $html, $field['tagName'], $field['prompt'], $field['required'], $field['errorMsg'], $field['cols'], $field['rows'], $field['htmlOptions'] );
				break;
				case "fieldset";
				$strFieldsetFields = $this->generateFields( $html, $field['fields'] );

				$strFieldSet = sprintf( '
					<fieldset>
						<legend>%s</legend>
						<div class="notes">
							<h4>%s</h4>
							<p class="last">%s</p>
						</div>
						%s
					</fieldset>', $field['legend'], $field['noteHeading'], $field['note'], $strFieldsetFields );
				return $strFieldSet;
				break;
				default:
				//bugbug:  i don't know how to put out a notice that an unknown type was entered.
				break;
			}
		}
		return $strFormFields;
	}
}

?>