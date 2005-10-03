<?php
/* SVN FILE: $Id$ */

/**
 * Automatic generation of HTML FORMs from given data.
 * 
 * Used for scaffolding.
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
 * @subpackage   cake.libs.helpers
 * @since        CakePHP v 0.9.2
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */


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
 * Automatic generation of HTML FORMs from given data.
 *
 * @package    cake
 * @subpackage cake.libs.helpers
 * @since      CakePHP v 0.9.2
 *
 */
class FormHelper extends Helper
{

	var $helpers = array('html');
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
	 * @param string $field  	If field is to be used for CRUD, this should be modelName/fieldName
	 * @return bool 			If there are errors this method returns true, else false. 
	 */
	function isFieldError($field )
	{
		$error = 1;
		$this->html->setFormTag( $field );
		if( $error == $this->html->tagIsInvalid( $this->html->model, $this->html->field) )
		{
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns a formatted LABEL element for HTML FORMs.
	 *
	 * @param string $tagName 	If field is to be used for CRUD, this should be modelName/fieldName
	 * @param string $text 		Text that will appear in the label field.
	 * @return string 			The formatted LABEL element
	 */
	function labelTag( $tagName, $text )
	{
		return sprintf( TAG_LABEL, $tagName, $text );
	}

	/**
	 * Returns a formatted DIV tag for HTML FORMs.
	 *
	 * @param string $class 	If field is to be used for CRUD, this should be modelName/fieldName
	 * @param string $text 		Text that will appear in the label field.
	 * @return string 			The formatted DIV element
	 */
	function divTag( $class, $text )
	{
		return sprintf( TAG_DIV, $class, $text );
	}

	/**
	 * Returns a formatted P tag with class for HTML FORMs.
	 *
	 * @param string $class 	If field is to be used for CRUD, this should be modelName/fieldName
	 * @param string $text 		Text that will appear in the label field.
	 * @return string 			The formatted DIV element
	 */
	function pTag( $class, $text )
	{
		return sprintf( TAG_P_CLASS, $class, $text );
	}

	/**
	 * Returns a formatted INPUT tag for HTML FORMs.
	 *
	 * @param string $tagName 	If field is to be used for CRUD, this should be modelName/fieldName
	 * @param string $prompt 	Text that will appear in the label field.
	 * @param bool $required 	True if this field is required.
	 * @param string $errorMsg 	Text that will appear if an error has occurred.
	 * @param int $size 		Size attribute for INPUT element
	 * @param array $htmlOptions 
	 * @return string 			The formatted INPUT element
	 */
	function generateInputDiv($tagName, $prompt, $required=false, $errorMsg=null, $size=20, $htmlOptions=null )
	{
		$str = $this->html->inputTag( $tagName, $size, $htmlOptions );
		$strLabel = $this->labelTag( $tagName, $prompt );

		$divClass = "optional";

		if( $required )
		$divClass = "required";

		$strError = ""; // initialize the error to empty.

		if( $this->isFieldError( $tagName ) )
		{
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );

		return $this->divTag( $divClass, $divTagInside );

	}

	/**
	 * Returns a formatted CHECKBOX tag inside a DIV for HTML FORMs.
	 *
	 * @param HtmlHelper $html 	The HtmlHelper object which is creating this form.
	 * @param string $tagName 	If field is to be used for CRUD, this should be modelName/fieldName
	 * @param string $prompt 	Text that will appear in the label field.
	 * @param bool $required 	True if this field is required.
	 * @param string $errorMsg 	Text that will appear if an error has occurred.
	 * @param int $size 		Size attribute for INPUT element
	 * @param array $htmlOptions 
	 * @return string 			The formatted INPUT element
	 */
	function generateCheckboxDiv($tagName, $prompt, $required=false, $errorMsg=null, $htmlOptions=null )
	{
	   $htmlOptions['class'] = "inputCheckbox";
		$str = $this->html->checkbox( $tagName, null, $htmlOptions );
		$strLabel = $this->labelTag( $tagName, $prompt );

		$divClass = "optional";

		if( $required )
		$divClass = "required";

		$strError = ""; // initialize the error to empty.

		if( $this->isFieldError( $tagName ) )
		{
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );

		return $this->divTag( $divClass, $divTagInside );

	}

	/**
	 * Returns a formatted date option element for HTML FORMs.
	 *
	 * @param string $tagName 	If field is to be used for CRUD, this should be modelName/fieldName
	 * @param string $prompt 	Text that will appear in the label field.
	 * @param bool $required 	True if this field is required.
	 * @param string $errorMsg 	Text that will appear if an error has occurred.
	 * @param int $size 		Size attribute for INPUT element
	 * @param array $htmlOptions 
	 * @return string 			The formatted INPUT element
	 */	
	function generateDate($tagName, $prompt, $required=false, $errorMsg=null, $size=20, $htmlOptions=null )
	{
		$str = $this->html->dateTimeOptionTag( $tagName, 'MDY' , 'NONE' );
		$strLabel = $this->labelTag( $tagName, $prompt );

		$divClass = "optional";

		if( $required )
		$divClass = "required";

		$strError = ""; // initialize the error to empty.

		if( $this->isFieldError( $tagName ) )
		{
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );

		$requiredDiv = $this->divTag( $divClass, $divTagInside );

		return $this->divTag("date", $requiredDiv);
	}
	
	/**
	 * Returns a formatted datetime option element for HTML FORMs.
	 *
	 * @param string $tagName 	If field is to be used for CRUD, this should be modelName/fieldName
	 * @param string $prompt 	Text that will appear in the label field.
	 * @param bool $required 	True if this field is required.
	 * @param string $errorMsg 	Text that will appear if an error has occurred.
	 * @param int $size 		Size attribute for INPUT element
	 * @param array $htmlOptions 
	 * @return string 			The formatted datetime option element
	 */		
	function generateDateTime($tagName, $prompt, $required=false, $errorMsg=null, $size=20, $htmlOptions=null )
	{
		$str = $this->html->dateTimeOptionTag( $tagName, 'MDY' , '12' );
		$strLabel = $this->labelTag( $tagName, $prompt );

		$divClass = "optional";

		if( $required )
		$divClass = "required";

		$strError = ""; // initialize the error to empty.

		if( $this->isFieldError( $tagName ) )
		{
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );

		$requiredDiv = $this->divTag( $divClass, $divTagInside );

		return $this->divTag("date", $requiredDiv);
	}
	
	/**
	 * Returns a formatted TEXTAREA inside a DIV for use with HTML forms.
	 *
	 * @param string $tagName	If field is to be used for CRUD, this should be modelName/fieldName
	 * @param string $prompt	Text that will appear in the label field.
	 * @param boolean $required	True if this field is required.
	 * @param string $errorMsg	ext that will appear if an error has occurred.
	 * @param integer $cols		Number of columns.
	 * @param integer $rows		Number of rows.
	 * @param array $htmlOptions
	 * @return string 			The formatted TEXTAREA element
	 */
	function generateAreaDiv($tagName, $prompt, $required=false, $errorMsg=null, $cols=60, $rows=10,  $htmlOptions=null )
	{
		$str = $this->html->areaTag( $tagName, $cols, $rows, $htmlOptions );
		$strLabel = $this->labelTag( $tagName, $prompt );

		$divClass = "optional";

		if( $required )
		$divClass = "required";

		$strError = ""; // initialize the error to empty.

		if( $this->isFieldError( $tagName ) )
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
	 * @param string $tagName 	If field is to be used for CRUD, this should be modelName/fieldName
	 * @param string $prompt 	Text that will appear in the label field
	 * @param array $options 	Options to be contained in SELECT element
	 * @param string $selected 	Text of the currently selected item
	 * @param array $selectAttr	Array of HTML attributes for the SELECT element
	 * @param array $optionAttr Array of HTML attributes for the OPTION elements
	 * @param bool $required 	True if this field is required
	 * @param string $errorMsg 	Text that will appear if an error has occurred	 
	 * @return string 			The formatted INPUT element
	 */
	function generateSelectDiv($tagName, $prompt, $options, $selected=null, $selectAttr=null, $optionAttr=null, $required=false,  $errorMsg=null)
	{
		$str = $this->html->selectTag( $tagName, $options, $selected, $selectAttr, $optionAttr );
		$strLabel = $this->labelTag( $tagName, $prompt );

		$divClass = "optional";

		if( $required )
		$divClass = "required";

		$strError = ""; // initialize the error to empty.

		if( $this->isFieldError( $tagName ) )
		{
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );

		return $this->divTag( $divClass, $divTagInside );

	}	
	
	/**
	 * Returns a formatted submit widget for HTML FORMs.
	 *
	 * @param string $prompt 	Text that will appear on the widget
	 * @param array $htmlOptions
	 * @return string 			The formatted submit widget
	 */
	function generateSubmitDiv($displayText, $htmlOptions = null)
	{
		return $this->divTag( 'submit', $this->html->submitTag( $displayText, $htmlOptions) );

	}

	/**
	 * Generates an form to go onto a HtmlHelper object.
	 *
	 * @param array $fields 	An array of form field definitions
	 * @param boolean $readOnly	True if the form should be rendered as READONLY
	 * @return string 			The completed form specified by the $fields parameter
	 */
	function generateFields( $fields, $readOnly = false )
	{
		$strFormFields = '';

		foreach( $fields as $field )
		{
         if( isset( $field['type'] ) )
         {
            //  initialize some optional parameters to avoid the notices
            if( !isset($field['required'] ) )
               $field['required'] = false;
            if( !isset( $field['errorMsg'] ) )
               $field['errorMsg'] = null;
            if( !isset( $field['htmlOptions'] ) )
               $field['htmlOptions'] = array();

            if( $readOnly )
            {
               $field['htmlOptions']['READONLY'] = "readonly";
            }
            switch( $field['type'] )
   			{
   				case "input" :
   				//  If the size has not been set, initialize it to 40.
   				if( !isset( $field['size'] ) )
   				{
   				  $field['size'] = 40;
   				}
      				$strFormFields = $strFormFields.$this->generateInputDiv( $field['tagName'], $field['prompt'], $field['required'], $field['errorMsg'], $field['size'], $field['htmlOptions'] );
   				break;
   				case "checkbox" :
      				$strFormFields = $strFormFields.$this->generateCheckboxDiv( $field['tagName'], $field['prompt'], $field['required'], $field['errorMsg'], $field['htmlOptions'] );
   				break;
   				case "select";
   				case "selectMultiple";
   				{
   				   if( "selectMultiple" == $field['type'] )
   				   {
   				      $field['selectAttr']['multiple'] = 'multiple';
   				      $field['selectAttr']['class'] = 'selectMultiple';
   				   }
   				   //  If the selected attribute has not been set, initialize it to null.
   				   if( !isset( $field['selected'] ) )
   				     $field['selected'] = null;
   				   if( !isset( $field['selectAttr'] ) )
   				     $field['selectAttr'] = null;
   				   if( !isset( $field['optionsAttr'] ) )
   				     $field['optionsAttr'] = null;
   				   
   				   if( $readOnly )
   				     $field['selectAttr']['DISABLED'] = true;

   				   $strFormFields = $strFormFields.$this->generateSelectDiv( $field['tagName'], $field['prompt'], $field['options'], $field['selected'], $field['selectAttr'], $field['optionsAttr'], $field['required'], $field['errorMsg'] );
   				}
   				break;
   				case "area";
   				{
   				   if( !isset( $field['rows'] ) )
   				     $field['rows'] = 10;
   				   if( !isset( $field['cols'] ) )
   				     $field['cols'] = 60;
   				   $strFormFields = $strFormFields.$this->generateAreaDiv( $field['tagName'], $field['prompt'], $field['required'], $field['errorMsg'], $field['cols'], $field['rows'], $field['htmlOptions'] );
   				}
   				break;
   				case "fieldset";
   				$strFieldsetFields = $this->generateFields( $field['fields'] );
   
   				$strFieldSet = sprintf( '
   					<fieldset>
   						<legend>%s</legend>
   						<div class="notes">
   							<h4>%s</h4>
   							<p class="last">%s</p>
   						</div>
   						%s
   					</fieldset>', $field['legend'], $field['noteHeading'], $field['note'], $strFieldsetFields );
      				$strFormFields = $strFormFields.$strFieldSet;
   				break;
   				case "hidden";
   				  $strFormFields = $strFormFields . $this->html->hiddenTag( $field['tagName']);
   				  break;
   			   case "date":
   			     $strFormFields = $strFormFields.$this->generateDate( $field['tagName'], $field['prompt'] );
   			   break;
   			   case "datetime":
   			     $strFormFields = $strFormFields.$this->generateDateTime( $field['tagName'], $field['prompt'] );
   			   break;
   				default:
   				//bugbug:  i don't know how to put out a notice that an unknown type was entered.
   				break;
   			} // end switch $field['type']
         } // end if isset $field['type']
		}
		return $strFormFields;
	}
}

?>