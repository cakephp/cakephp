<?php
/* SVN FILE: $Id$ */

/**
 * Html Helper class file.
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
 * @since        CakePHP v 0.9.1
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Html Helper class for easy use of html widgets.
 *
 * HtmlHelper encloses all methods needed while working with html pages.
 *
 * @package    cake
 * @subpackage cake.libs.helpers
 * @since      CakePHP v 0.9.1
 */
class HtmlHelper extends Helper
{
    /*************************************************************************
    * Public variables
    *************************************************************************/

    /**#@+
    * @access public
    */

    /**
     * Enter description here...
     *
     * @var unknown_type
     */
    var $base   = null;

    /**
     * Enter description here...
     *
     * @var unknown_type
     */
    var $here   = null;
    /**
     * Enter description here...
     *
     * @var unknown_type
     */
    var $params = array();
    /**
     * Enter description here...
     *
     * @var unknown_type
     */
    var $action = null;
    /**
     * Enter description here...
     *
     * @var unknown_type
     */
    var $data   = null;
    /**
     * Enter description here...
     *
     * @var unknown_type
     */
    var $model  = null;
    /**
     * Enter description here...
     *
     * @var unknown_type
     */
    var $field  = null;

    /**#@-*/

    /*************************************************************************
    * Private variables
    *************************************************************************/

    /**#@+
    * @access private
    */

    /**
     * Breadcrumbs.
     *
     * @var    array
     * @access private
     */
    var $_crumbs = array();



    /**
     * Adds $name and $link to the breadcrumbs array.
     *
     * @param string $name Text for link
     * @param string $link URL for link
     */
    function addCrumb($name, $link)
    {
        $this->_crumbs[] = array($name, $link);
    }

    /**
     * Returns a charset meta-tag
     *
     * @param  string  $charset
     * @param  boolean $return Wheter this method should return a value or
     *                         output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function charset($charset, $return)
    {
        return $this->output(sprintf($this->tags['charset'], $charset), $return);
    }

    /**
     * Finds URL for specified action.
     *
     * Returns an URL pointing to a combination of controller and action. Param
     * $url can be:
     *   + Empty - the method will find adress to actuall controller/action.
     *   + '/' - the method will find base URL of application.
     *   + A combination of controller/action - the method will find url for it.
     *
     * @param  string  $url
     * @param  boolean $return Wheter this method should return a value or
     *                         output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function url($url = null, $return = false)
    {
        if (empty($url))
        {
            return $this->here;
        }
        elseif ($url{0} == '/')
        {
            $output = $this->base . $url;
        }
        else
        {
            $output = $this->base.'/'.strtolower($this->params['controller']).'/'.$url;
        }

        return $this->output(ereg_replace('&([^a])', '&amp;\1', $output), $return);
    }

    /**
     * Creates an HTML link.
     *
     * If $url starts with "http://" this is treated as an external link. Else,
     * it is treated as a path to controller/action and parsed with the
     * HtmlHelper::url() method.
     *
     * If the $url is empty, $title is used instead.
     *
     * @param  string  $title          The content of the "a" tag.
     * @param  string  $url
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  string  $confirmMessage Confirmation message.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function link($title, $url = null, $htmlAttributes = null, $confirmMessage = false,
    $return = false)
    {
        $url = $url? $url: $title;

        if ($confirmMessage)
        {
            $htmlAttributes['onclick'] = "return confirm('{$confirmMessage}');";
        }

        if (strpos($url, '://'))
        {
            $output = sprintf($this->tags['link'], $url,
            $this->_parseAttributes($htmlAttributes), $title);
        }
        else
        {
            $output = sprintf($this->tags['link'], $this->url($url, true),
            $this->_parseAttributes($htmlAttributes), $title);
        }

        return $this->output($output, $return);
    }

    /**
     * Creates a submit widget.
     *
     * @param  string  $caption        Text on submit button
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function submit($caption = 'Submit', $htmlAttributes = null, $return = false)
    {
        $htmlAttributes['value'] = $caption;
        return $this->output(sprintf($this->tags['submit'],
        $this->_parseAttributes($htmlAttributes, null, '', ' ')), $return);
    }



    /**
     * Creates a password input widget.
     *
     * @param  string  $fieldName      If field is to be used for CRUD, this
     *                                 should be modelName/fieldName.
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function password($fieldName, $htmlAttributes = null, $return = false)
    {
        $this->setFormTag($fieldName);
        $htmlAttributes['size'] = $size;

        if (empty($htmlAttributes['value']))
        {
            $htmlAttributes['value'] = $this->tagValue($fieldName);
        }

        return $this->output(sprintf($this->tags['password'], $this->model, $this->field,
        $this->_parseAttributes($htmlAttributes, null, '', ' ')), $return);
    }





    /**
     * Creates a textarea widget.
     *
     * @param  string  $fieldName      If field is to be used for CRUD, this
     *                                 should be modelName/fieldName.
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function textarea($fieldName, $htmlAttributes = null, $return = false)
    {
        $this->setFormTag($fieldName);

        if (empty($htmlAttributes['value']))
        {
            $value = $this->tagValue($fieldName);
        }
        else
        {
            $value = empty($htmlAttributes['value']);
        }

        if ($this->tagIsInvalid($this->model, $this->field))
        {
            $htmlAttributes['class'] = 'form_error';
        }

        return $this->output(sprintf($this->tags['textarea'], $this->model,$this->field,
        $this->_parseAttributes($htmlAttributes, null, ' '), $value), $return);
    }

    /**
     * Creates a checkbox widget.
     *
     * @param  string  $fieldName      If field is to be used for CRUD, this
     *                                 should be modelName/fieldName.
     * @param  string  $title
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function checkbox($fieldName, $title = null, $htmlAttributes = null,
    $return = false)
    {
        $this->setFormTag($fieldName);
        $this->tagValue($fieldName)? $htmlAttributes['checked'] = 'checked': null;
        $title = $title? $title: ucfirst($fieldName);
        return $this->output(sprintf($this->tags['checkbox'], $this->model, $this->field,
        $this->field, $this->field,
        $this->_parseAttributes($htmlAttributes, null, '', ' '), $title), $return);
    }

    /**
     * Creates a link element for CSS stylesheets.
     *
     * @param      string $path           Path to CSS file
     * @param      string $rel            Rel attribute. Defaults to "stylesheet".
     * @param      array   $htmlAttributes Array of HTML attributes.
     * @param      boolean $return         Wheter this method should return a value
     *                                     or output it. This overrides AUTO_OUTPUT.
     * @return     mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                     and $return.
     */
    function css($path, $rel = 'stylesheet', $htmlAttributes = null, $return = false)
    {
        $url = "{$this->base}/".(COMPRESS_CSS? 'c': '')."css/{$path}.css";
        return $this->output(sprintf($this->tags['css'], $rel, $url,
        $this->parseHtmlOptions($htmlAttributes, null, '', ' ')), $return);
    }

    /**
     * Creates file input widget.
     *
     * @param  string  $fieldName      If field is to be used for CRUD, this
     *                                 should be modelName/fieldName.
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function file($fieldName, $htmlAttributes = null, $return = false)
    {
        return $this->output(sprintf($this->tags['file'], $fieldName,
        $this->_parseAttributes($htmlAttributes, null, '', ' ')), $return);
    }

    /**
     * Returns the breadcrumb trail as a sequence of &raquo;-separated links.
     *
     * @param  string  $separator Text to separate crumbs.
     * @param  boolean $return    Wheter this method should return a value
     *                            or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return. If $this->_crumbs is empty, return null.
     */
    function getCrumbs($separator = '&raquo;', $return = false)
    {
        if(count($this->_crumbs))
        {

            $out = array("<a href=\"{$this->base}\">START</a>");
            foreach ($this->_crumbs as $crumb)
            {
                $out[] = "<a href=\"{$this->base}{$crumb[1]}\">{$crumb[0]}</a>";
            }

            return $this->output(join($separator, $out), $return);
        }
        else
        {
            return null;
        }
    }

    /**
     * Creates a hidden input tag.
     *
     * @param  string  $fieldName      If field is to be used for CRUD, this
     *                                 should be modelName/fieldName.
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function hidden($fieldName, $htmlAttributes = null, $return = false)
    {
        $this->setFormTag($fieldName);
        $htmlAttributes['value'] = $value? $value: $this->tagValue($fieldName);
        return $this->output(sprintf($this->tags['hidden'], $this->model, $this->field,
        $this->_parseAttributes($htmlAttributes, null, '', ' ')), $return);
    }


    /**
     * Returns a formatted IMG element.
     *
     * @param  string  $path           Path to the image file.
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function image($path, $htmlAttributes = null, $return = false)
    {
        $url = $this->base.IMAGES_URL.$path;
        $alt = $htmlAttributes['alt'];
        return $this->output(sprintf($this->tags['image'], $url, $alt, $this->parseHtmlOptions($htmlAttributes, null, '', ' ')), $return);
    }

    /**
     * Creates a text input widget.
     *
     * @param  string  $fieldName      If field is to be used for CRUD, this
     *                                 should be modelName/fieldName.
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function input($fieldName, $htmlAttributes = null, $return = false)
    {
        $this->setFormTag($fieldName);

        if (!isset($htmlAttributes['value']))
        {
            $htmlAttributes['value'] = $this->tagValue($fieldName);
        }

        if ($this->tagIsInvalid($this->model, $this->field))
        {
            $htmlAttributes['class'] = 'form_error';
        }

        return $this->output(sprintf($this->tags['input'], $this->model, $this->field,
        $this->_parseAttributes($htmlAttributes, null, '', ' ')), $return);
    }

    /**
     * Creates a set of radio widgets.
     *
     * @param  string  $fieldName      If field is to be used for CRUD, this
     *                                 should be modelName/fieldName.
     * @param  array   $options
     * @param  array   $inbetween
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function radio($fieldName, $options, $inbetween = null, $htmlAttributes = null,
    $return = false)
    {
        $this->setFormTag($fieldName);
        $value = isset($htmlAttributes['value'])? $htmlAttributes['value']: $this->tagValue($fieldName);
        $out = array();
        foreach ($options as $opt_value=>$opt_title)
        {
            $options_here = array('value' => $opt_value);
            $opt_value==$value? $options_here['checked'] = 'checked': null;
            $parsed_options = $this->parseHtmlOptions(array_merge($htmlAttributes, $options_here), null, '', ' ');
            $individual_tag_name = "{$this->field}_{$opt_value}";
            $out[] = sprintf($this->tags['radio'], $individual_tag_name, $this->model, $this->field, $individual_tag_name, $parsed_options, $opt_title);
        }

        $out = join($inbetween, $out);
        return $this->output($out? $out: null, $return);
    }


    /**
    * Returns a row of formatted and named TABLE headers.
    *
    * @param array $names
    * @param array $tr_options
    * @param array $th_options
    * @return string
    */
    function tableHeaders($names, $tr_options=null, $th_options=null)
    {
        $out = array();
        foreach ($names as $arg)
        {
            $out[] = sprintf($this->tags['tableHeader'], $this->parseHtmlOptions($th_options), $arg);
        }

        return sprintf($this->tags['tableHeader'], $this->parseHtmlOptions($tr_options), join(' ', $out));
    }

    /**
 * Returns a formatted string of table rows (TR's with TD's in them).
 *
 * @param array $data Array of table data
 * @param array $tr_options HTML options for TR elements
 * @param array $td_options HTML options for TD elements
 * @return string
 */
    function tableCells($data, $odd_tr_options=null, $even_tr_options=null)
    {
        if (empty($data[0]) || !is_array($data[0]))
        {
            $data = array($data);
        }

        $count=0;
        foreach ($data as $line)
        {
            $count++;
            $cells_out = array();
            foreach ($line as $cell)
            {
                $cells_out[] = sprintf($this->tags['tableCell'], null, $cell);
            }

            $options = $this->parseHtmlOptions($count%2? $odd_tr_options: $even_tr_options);
            $out[] = sprintf($this->tags['tableRow'], $options, join(' ', $cells_out));
        }

        return join("\n", $out);
    }







    /**
 * Returns value of $fieldName. False is the tag does not exist.
 *
 * @param string $fieldName
 * @return unknown Value of the named tag.
 */
    function tagValue ($fieldName)
    {
        $this->setFormTag($fieldName);
        return isset($this->params['data'][$this->model][$this->field])? $this->params['data'][$this->model][$this->field]: false;
    }

    /**
 * Returns false if given FORM field has no errors. Otherwise it returns the constant set in the array Model->validationErrors.
 *
 * @param unknown_type $field
 * @return unknown
 */
    function tagIsInvalid ($model, $field)
    {
        return empty($this->validationErrors[$model][$field])? 0: $this->validationErrors[$model][$field];
    }

    /**
 * Returns number of errors in a submitted FORM.
 *
 * @return int Number of errors
 */
    function validate ()
    {
        $args = func_get_args();
        $errors = call_user_func_array(array(&$this, 'validateErrors'), $args);

        return count($errors);
    }

    /**
 * Validates a FORM according to the rules set up in the Model.
 *
 * @return int Number of errors
 */
    function validateErrors ()
    {
        $objects = func_get_args();
        if (!count($objects)) return false;

        $errors = array();
        foreach ($objects as $object)
        {
            $errors = array_merge($errors, $object->invalidFields($object->data));
        }

        return $this->validationErrors = (count($errors)? $errors: false);
    }

    /**
     * Returns a formatted error message for given FORM field, NULL if no errors.
     *
     * @param string $field  If field is to be used for CRUD, this should be modelName/fieldName
     * @param string $text
     * @return string If there are errors this method returns an error message, else NULL.
     */
    function tagErrorMsg ($field, $text)
    {
        $error = 1;
        $this->setFormTag($field);

        if ($error == $this->tagIsInvalid($this->model, $this->field))
        {
            return sprintf(SHORT_ERROR_MESSAGE, is_array($text)? (empty($text[$error-1])? 'Error in field': $text[$error-1]): $text);
        }
        else
        {
            return null;
        }
    }

    function setFormTag($tagValue)
    {
        return list($this->model, $this->field) = explode("/", $tagValue);
    }

    /**#@-*/

    /*************************************************************************
    * Private methods
    *************************************************************************/

    /**#@+
    * @access private
    */

    /**
     * Returns a space-separated string with items of the $options array. If a
     * key of $options array happens to be one of:
     *   + 'compact'
     *   + 'checked'
     *   + 'declare'
     *   + 'readonly'
     *   + 'disabled'
     *   + 'selected'
     *   + 'defer'
     *   + 'ismap'
     *   + 'nohref'
     *   + 'noshade'
     *   + 'nowrap'
     *   + 'multiple'
     *   + 'noresize'
     *
     * And it's value is one of:
     *   + 1
     *   + true
     *   + 'true'
     *
     * Then the value will be reset to be identical with key's name.
     * If the value is not one of these 3, the parameeter is not outputed.
     *
     * @param  array  $options      Array of options.
     * @param  array  $exclude      Array of options to be excluded.
     * @param  string $insertBefore String to be inserted before options.
     * @param  string $insertAfter  String to be inserted ater options.
     * @return string
     */
    function _parseAttributes($options, $exclude = null, $insertBefore = ' ',
    $insertAfter = null)
    {
        $minimizedAttributes = array(
        'compact',
        'checked',
        'declare',
        'readonly',
        'disabled',
        'selected',
        'defer',
        'ismap',
        'nohref',
        'noshade',
        'nowrap',
        'multiple',
        'noresize');

        if (!is_array($exclude))
        {
            $exclude = array();
        }

        if (is_array($options))
        {
            $out = array();

            foreach ($options as $key => $value)
            {
                if (!in_array($key, $exclude))
                {
                    if (in_array($key, $minimizedAttributes) && ($value === 1 ||
                    $value === true || $value === 'true' || in_array($value,
                    $minimizedAttributes)))
                    {
                        $value = $key;
                    }
                    elseif (in_array($key, $minimizedAttributes))
                    {
                        continue;
                    }
                    $out[] = "{$key}=\"{$value}\"";
                }
            }
            $out = join(' ', $out);
            return $out? $insertBefore.$out.$insertAfter: null;
        }
        else
        {
            return $options? $insertBefore.$options.$insertAfter: null;
        }
    }

    /**#@-*/

    /*************************************************************************
    * Renamed methods
    *************************************************************************/

    /**
     * @deprecated Name changed to 'textarea'. Version 0.9.2.
     * @see        HtmlHelper::textarea()
     * @param      string  $tagName
     * @param      integer $cols
     * @param      integer $rows
     * @param      array   $htmlAttributes Array of HTML attributes.
     * @param      boolean $return         Wheter this method should return a value
     *                                     or output it. This overrides AUTO_OUTPUT.
     * @return     mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                     and $return.
     */
    function areaTag($tagName, $cols = 60, $rows = 10, $htmlAttributes = null, $return = false)
    {
        $htmlAttributes['cols'] = $cols;
        $htmlAttributes['rows'] = $rows;
        return $this->textarea($tagName, $htmlAttributes, $return);
    }

    /**
     * @deprecated Name changed to 'charset'. Version 0.9.2.
     * @see        HtmlHelper::charset()
     * @param      string  $charset
     * @param      boolean $return         Wheter this method should return a value
     *                                     or output it. This overrides AUTO_OUTPUT.
     * @return     mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                     and $return.
     */
    function charsetTag($charset, $return)
    {
        return $this->charset($charset, $return);
    }

    /**
     * @deprecated Name changed to 'checkbox'. Version 0.9.2.
     * @see        HtmlHelper::checkbox()
     * @param      string  $fieldName      If field is to be used for CRUD, this
     *                                     should be modelName/fieldName.
     * @param      string  $title
     * @param      array   $htmlAttributes Array of HTML attributes.
     * @param      boolean $return         Wheter this method should return a value
     *                                     or output it. This overrides AUTO_OUTPUT.
     * @return     mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                     and $return.
     */
    function checkboxTag($fieldName, $title = null, $htmlAttributes = null, $return = false)
    {
        return $this->checkbox($fieldName, $title, $htmlAttributes, $return);
    }

    /**
     * @deprecated Name changed to 'css'. Version 0.9.2.
     * @see        HtmlHelper::css()
     * @param      string $path           Path to CSS file
     * @param      string $rel            Rel attribute. Defaults to "stylesheet".
     * @param      array   $htmlAttributes Array of HTML attributes.
     * @param      boolean $return         Wheter this method should return a value
     *                                     or output it. This overrides AUTO_OUTPUT.
     * @return     mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                     and $return.
     */
    function cssTag($path, $rel = 'stylesheet', $htmlAttributes = null, $return = false)
    {
        return $this->css($path, $rel , $htmlAttributes , $return );
    }

    /**
     * @deprecated Name changed to 'file'. Version 0.9.2.
     * @see HtmlHelper::file()
     * @param  string  $fieldName      If field is to be used for CRUD, this
     *                                 should be modelName/fieldName.
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function fileTag($fieldName, $htmlAttributes = null, $return = false)
    {
        return $this->file($fieldName, $htmlAttributes, $return);
    }

    /**
     * @deprecated Name changed to 'hidden'. Version 0.9.2.
     * @see        HtmlHelper::hidden()
     * @param      string  $fieldName      If field is to be used for CRUD, this
     *                                     should be modelName/fieldName.
     * @param      string  $value
     * @param      array   $htmlAttributes Array of HTML attributes.
     * @param      boolean $return         Wheter this method should return a value
     *                                     or output it. This overrides AUTO_OUTPUT.
     * @return     mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                     and $return.
     */
    //function hiddenTag($fieldName, $value = null, $htmlAttributes = null, $return = false)
    //{
    //    $htmlAttributes['value'] = $value;
    //    return $this->hidden($fieldName, $htmlAttributes, $return);
    //}
    function hiddenTag($tagName, $value=null, $htmlOptions=null)
    {
        $this->setFormTag($tagName);
        $htmlOptions['value'] = $value? $value: $this->tagValue($tagName);
        return $this->output(sprintf($this->tags['hidden'], $this->model, $this->field, $this->parseHtmlOptions($htmlOptions, null, '', ' ')));
    }
    /**
     * @deprecated Name changed to 'image'. Version 0.9.2.
     * @see        HtmlHelper::image()
     * @param      string  $path           Path to the image file.
     * @param      string  $alt
     * @param      array   $htmlAttributes Array of HTML attributes.
     * @param      boolean $return         Wheter this method should return a value
     *                                     or output it. This overrides AUTO_OUTPUT.
     * @return     mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                     and $return.
     */
    function imageTag($path, $alt = null, $htmlAttributes = null, $return = false)
    {
        $htmlAttributes['alt'] = $alt;
        return $this->image($path, $htmlAttributes, $return);
    }

    /**
     * @deprecated Name changed to 'input'. Version 0.9.2.
     * @see HtmlHelper::input()
     * @param      string  $fieldName      If field is to be used for CRUD, this
     *                                     should be modelName/fieldName.
     * @param      string  $value
     * @param      array   $htmlAttributes Array of HTML attributes.
     * @param      boolean $return         Wheter this method should return a value
     *                                     or output it. This overrides AUTO_OUTPUT.
     * @return     mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                     and $return.
     */
    //function inputTag($fieldName, $value = null, $htmlAttributes = null, $return = false)
    //{
    //    $htmlAttributes['value'] = $value;
    //    return $this->input($fieldName, $htmlAttributes, $return);
    //}

   function inputTag($tagName,  $size=20, $htmlOptions=null)
   {
      $this->setFormTag($tagName);
      $htmlOptions['value'] = isset($htmlOptions['value'])? $htmlOptions['value']: $this->tagValue($tagName);
      $this->tagIsInvalid($this->model,$this->field)? $htmlOptions['class'] = 'form_error': null;
      return $this->output(sprintf($this->tags['input'], $this->model, $this->field, $this->parseHtmlOptions($htmlOptions, null, '', ' ')));
   }

    /**
     * @deprecated Unified with 'link'. Version 0.9.2.
     * @see HtmlHelper::link()
     * @param  string  $title          The content of the "a" tag.
     * @param  string  $url
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function linkOut($title, $url = null, $htmlAttributes = null, $return = false)
    {
        return $this->link($title, $url, $htmlAttributes, false, $return);
    }

    /**
     * @deprecated Unified with 'link'. Version 0.9.2.
     * @see HtmlHelper::link()
     * @param  string  $title          The content of the "a" tag.
     * @param  string  $url
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  string  $confirmMessage Confirmation message.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function linkTo($title, $url, $htmlAttributes = null, $confirmMessage = false, $return = false)
    {
        return $this->link($title, $url, $htmlAttributes, $confirmMessage, $return);
    }

    /**
     * @deprecated Name changed to '_parseAttributes'. Version 0.9.2.
     * @see HtmlHelper::_parseAttributes()
     * @param  array  $options      Array of options.
     * @param  array  $exclude      Array of options to be excluded.
     * @param  string $insertBefore String to be inserted before options.
     * @param  string $insertAfter  String to be inserted ater options.
     * @return string
     */
    //function parseHtmlOptions($options, $exclude = null, $insertBefore = ' ', $insertAfter = null)
   // {
   //     $this->_parseAttributes($options, $exclude, $insertBefore, $insertAfter);
   // }

   function parseHtmlOptions($options, $exclude=null, $insert_before=' ', $insert_after=null)
   {
      if (!is_array($exclude)) $exclude = array();

      if (is_array($options))
      {
         $out = array();
         foreach ($options as $k=>$v)
         {
            if (!in_array($k, $exclude))
            {
               $out[] = "{$k}=\"{$v}\"";
            }
         }
         $out = join(' ', $out);
         return $out? $insert_before.$out.$insert_after: null;
      }
      else
      {
         return $options? $insert_before.$options.$insert_after: null;
      }
   }

    /**
     * @deprecated Name changed to 'password'. Version 0.9.2.
     * @see HtmlHelper::password()
     * @param  string  $fieldName      If field is to be used for CRUD, this
     *                                 should be modelName/fieldName.
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function passwordTag($fieldName, $size = 20, $htmlAttributes = null, $return = false)
    {
        $args = func_get_args();
        return call_user_func_array(array(&$this, "password"), $args);
    }

    /**
     * @deprecated Name changed to 'radio'. Version 0.9.2.
     * @see HtmlHelper::radio()
     * @param  string  $fieldName      If field is to be used for CRUD, this
     *                                 should be modelName/fieldName.
     * @param  array   $options
     * @param  array   $inbetween
     * @param  array   $htmlAttributes Array of HTML attributes.
     * @param  boolean $return         Wheter this method should return a value
     *                                 or output it. This overrides AUTO_OUTPUT.
     * @return mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                 and $return.
     */
    function radioTags($fieldName, $options, $inbetween = null, $htmlAttributes = null,
    $return = false)
    {
        return $this->radio($fieldName, $options, $inbetween, $htmlAttributes, $return);
    }

    /**
     * Returns a SELECT element,
     *
     * @param string $fieldName Name attribute of the SELECT
     * @param array $option_elements Array of the OPTION elements (as 'value'=>'Text' pairs) to be used in the SELECT element
     * @param array $select_attr Array of HTML options for the opening SELECT element
     * @param array $optionAttr Array of HTML options for the enclosed OPTION elements
     * @return string Formatted SELECT element
     */
    function selectTag($fieldName, $option_elements, $selected=null, $select_attr=null, $optionAttr=null)
    {
        $this->setFormTag($fieldName);
        if (!is_array($option_elements) || !count($option_elements))
        return null;

        if( isset($select_attr) && array_key_exists( "multiple", $select_attr) )
        {
           $select[] = sprintf($this->tags['selectmultiplestart'], $this->model, $this->field, $this->parseHtmlOptions($select_attr));
        }
        else 
        {
           $select[] = sprintf($this->tags['selectstart'], $this->model, $this->field, $this->parseHtmlOptions($select_attr));
        }
        $select[] = sprintf($this->tags['selectempty'], $this->parseHtmlOptions($optionAttr));

        foreach ($option_elements as $name=>$title)
        {
            $options_here = $optionAttr;
            if ($selected == $name)
            {
               $options_here['selected'] = 'selected';
            } else if ( is_array($selected) && array_key_exists($name, $selected) )
            {
               $options_here['selected'] = 'selected';
            }
            $select[] = sprintf($this->tags['selectoption'], $name, $this->parseHtmlOptions($options_here), $title);
        }

        $select[] = sprintf($this->tags['selectend']);

        return implode("\n", $select);
    }


    /**
     * @deprecated Name changed to 'url'. Version 0.9.2.
     * @see HtmlHelper::url()
     */
    function urlFor($url)
    {
        return $this->url($url);
    }





    /**
     * @deprecated Name changed to 'submit'. Version 0.9.2.
     * @see HtmlHelper::submit()
     */
    function submitTag()
    {
        $args = func_get_args();
        return call_user_func_array(array(&$this, "submit"), $args);
    }

    /*************************************************************************
    * Moved methods
    *************************************************************************/

    /**
     * @deprecated Moved to TextHelper. Version 0.9.2.
     */
    function trim()
    {
        die("Method HtmlHelper::trim() was moved to TextHelper::trim().");
    }

    /**
     * @deprecated Moved to JavascriptHelper. Version 0.9.2.
     */
    function javascriptIncludeTag($url)
    {
        die("Method HtmlHelper::javascriptIncludeTag() was moved to JavascriptHelper::link().");
    }

    /**
     * @deprecated Moved to JavascriptHelper. Version 0.9.2.
     */
    function javascriptTag($script)
    {
        die("Method HtmlHelper::javascriptTag() was moved to JavascriptHelper::codeBlock().");
    }

    /*************************************************************************
    * Deprecated methods
    *************************************************************************/

    /**
     * Returns an HTML FORM element.
     *
     * @param      string $target      URL for the FORM's ACTION attribute.
     * @param      string $type        FORM type (POST/GET).
     * @param      array  $htmlAttributes
     * @return     string An formatted opening FORM tag.
     * @deprecated This is very WYSIWYG unfriendly, use HtmlHelper::url() to get
     *             contents of "action" attribute. Version 0.9.2.
     */
    function formTag($target=null, $type='post', $htmlAttributes=null)
    {
        $htmlAttributes['action'] = $this->UrlFor($target);
        $htmlAttributes['method'] = $type=='get'? 'get': 'post';
        $type == 'file'? $htmlAttributes['enctype'] = 'multipart/form-data': null;

        return sprintf($this->tags['form'], $this->parseHtmlOptions($htmlAttributes, null, ''));
    }

    /**
     * Generates a nested unordered list tree from an array.
     *
     * @param      array   $data
     * @param      array   $htmlAttributes
     * @param      string  $bodyKey
     * @param      string  $childrenKey
     * @param      boolean $return         Wheter this method should return a value
     *                                     or output it. This overrides AUTO_OUTPUT.
     * @return     mixed   Either string or boolean value, depends on AUTO_OUTPUT
     *                     and $return. If $this->_crumbs is empty, return null.
     * @deprecated This seems useless. Version 0.9.2.
     */
    function guiListTree($data, $htmlAttributes = null, $bodyKey = 'body', $childrenKey='children', $return = false)
    {
        $out = "<ul".$this->_parseAttributes($htmlAttributes).">\n";

        foreach ($data as $item)
        {
            $out .= "<li>{$item[$bodyKey]}</li>\n";
            if (isset($item[$childrenKey]) && is_array($item[$childrenKey]) && count($item[$childrenKey]))
            {
                $out .= $this->guiListTree($item[$childrenKey], $htmlAttributes, $bodyKey, $childrenKey);
            }
        }

        $out .= "</ul>\n";

        return $this->output($out, $return);
    }

    /**
     * Returns a mailto: link.
     *
     * @param      string $title Title of the link, or the e-mail address
     *                           (if the same).
     * @param      string $email E-mail address if different from title.
     * @param      array  $options
     * @return     string Formatted A tag
     * @deprecated This should be done using a content filter. Version 0.9.2.
     */
    function linkEmail($title, $email=null, $options=null)
    {
        // if no $email, then title contains the email.
        if (empty($email)) $email = $title;

        $match = array();

        // does the address contain extra attributes?
        preg_match('!^(.*)(\?.*)$!', $email, $match);

        // plaintext
        if (empty($options['encode']) || !empty($match[2]))
        {
            return sprintf($this->tags['mailto'], $email, $this->parseHtmlOptions($options), $title);
        }
        // encoded to avoid spiders
        else
        {
            $email_encoded = null;
            for ($ii=0; $ii < strlen($email); $ii++)
            {
                if(preg_match('!\w!',$email[$ii]))
                {
                    $email_encoded .= '%' . bin2hex($email[$ii]);
                }
                else
                {
                    $email_encoded .= $email[$ii];
                }
            }

            $title_encoded = null;
            for ($ii=0; $ii < strlen($title); $ii++)
            {
                $title_encoded .= preg_match('/^[A-Za-z0-9]$/', $title[$ii])? '&#x' . bin2hex($title[$ii]).';': $title[$ii];
            }

            return sprintf($this->tags['mailto'], $email_encoded, $this->parseHtmlOptions($options, array('encode')), $title_encoded);
        }
    }

    /**
     * Returns a generic HTML tag (no content).
     *
     * Examples:
     *   + <code>tag("br") => <br /></code>
     *   + <code>tag("input", array("type" => "text")) => <input type="text" /></code>
     *
     * @param      string $name Name of HTML element
     * @param      array  $options HTML options
     * @param      bool   $open Is the tag open or closed? (defaults to closed "/>")
     * @return     string The formatted HTML tag
     * @deprecated This seems useless. Version 0.9.2.
     */
    function tag($name, $options=null, $open=false)
    {
        $tag = "<$name ". $this->parseHtmlOptions($options);
        $tag .= $open? ">" : " />";
        return $tag;
    }

    /**
     * Returns a generic HTML tag with content.
     *
     * Examples:
     * <code>
     * content_tag("p", "Hello world!") => <p>Hello world!</p>
     * content_tag("div", content_tag("p", "Hello world!"),
     * array("class" => "strong")) => <div class="strong"><p>Hello world!</p></div>
     * </code>
     *
     * @param  string $name    Name of HTML element
     * @param  array  $options HTML options
     * @param  bool   $open    Is the tag open or closed? (defaults to closed "/>")
     * @return string The formatted HTML tag
     * @deprecated This seems useless. Version 0.9.2.
     */
    function contentTag($name, $content, $options=null)
    {
        return "<$name ". $this->parseHtmlOptions($options). ">$content</$name>";
    }

    function dayOptionTag( $tagName, $value=null, $selected=null, $optionAttr=null)
    {
       $value = isset($value)? $value : $this->tagValue($tagName);
       $dayValue = empty($value) ? date('d') : date('d',strtotime( $value ) );
       $days=array('1'=>'1','2'=>'2','3'=>'3','4'=>'4',
       '5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9',
       '10'=>'10','11'=>'11','12'=>'12',
       '13'=>'13','14'=>'14','15'=>'15',
       '16'=>'16','17'=>'17','18'=>'18',
       '19'=>'19','20'=>'20','21'=>'21',
       '22'=>'22','23'=>'23','24'=>'24',
       '25'=>'25','26'=>'26','27'=>'27',
       '28'=>'28','29'=>'29','30'=>'30','31'=>'31');
       $option = $this->selectTag($tagName.'_day', $days, $dayValue,
       $optionAttr);
       return $option;
    }

    function yearOptionTag( $tagName, $value=null, $minYear=null, $maxYear=null, $selected=null, $optionAttr=null)
    {
       $value = isset($value)? $value : $this->tagValue($tagName);

       $yearValue = empty($value) ? date('Y') : date('Y',strtotime( $value ) );

       $maxYear = is_null($maxYear) ? $yearValue + 10 : $maxYear;

       $minYear = is_null($minYear) ? $yearValue - 10 : $minYear;

       if ( $minYear>$minYear)
       {
          $tmpYear = $minYear;
          $minYear = $maxYear;
          $maxYear = $tmpYear;
       };
       $minYear = $yearValue < $minYear ? $yearValue : $minYear;
       $maxYear = $yearValue > $maxYear ? $yearValue : $maxYear;

       for ( $yearCounter=$minYear; $yearCounter<$maxYear; $yearCounter++)
       {
          $years[$yearCounter]=$yearCounter;
       }

       $option = $this->selectTag($tagName.'_year', $years, $yearValue,
       $optionAttr);
       return $option;
    }

    function monthOptionTag( $tagName, $value=null, $selected=null, $optionAttr=null)
    {
       $value = isset($value)? $value : $this->tagValue($tagName);
       $monthValue = empty($value) ? date('m') : date('m',strtotime( $value ) );
       $months=array('1'=>'January','2'=>'February','3'=>'March',
       '4'=>'April','5'=>'May','6'=>'June','7'=>'July','8'=>'August',
       '9'=>'September','10'=>'October','11'=>'November','12'=>'December');
       $option = $this->selectTag($tagName.'_month', $months, $monthValue,
       $optionAttr);
       return $option;
    }

    function hourOptionTag( $tagName,$value=null,
    $format24Hours = false,
    $selected=null,
    $optionAttr=null )
    {
       $value = isset($value)? $value : $this->tagValue($tagName);
       if ( $format24Hours )
       {
          $hourValue = empty($value) ? date('H') : date('H',strtotime( $value ) );
       }
       else
       {
          $hourValue = empty($value) ? date('g') : date('g',strtotime( $value ) );
       }
       if ( $format24Hours )
       { $hours = array('0'=>'00','1'=>'01','2'=>'02','3'=>'03','4'=>'04',
       '5'=>'05','6'=>'06','7'=>'07','8'=>'08','9'=>'09',
       '10'=>'10','11'=>'11','12'=>'12',
       '13'=>'13','14'=>'14','15'=>'15',
       '16'=>'16','17'=>'17','18'=>'18',
       '19'=>'19','20'=>'20','21'=>'21',
       '22'=>'22','23'=>'23');
       }
       else
       {
          $hours = array('1'=>'1','2'=>'2','3'=>'3','4'=>'4',
          '5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9',
          '10'=>'10','11'=>'11','12'=>'12');
       }

       $option = $this->selectTag($tagName.'_hour', $hours, $hourValue,
       $optionAttr);
       return $option;
    }

    function minuteOptionTag( $tagName, $value=null, $selected=null, $optionAttr=null)
    {
       $value = isset($value)? $value : $this->tagValue($tagName);
       $minValue = empty($value) ? date('i') : date('i',strtotime( $value ) );
       for( $minCount=0; $minCount<61; $minCount++)
       {
          $mins[$minCount] = sprintf('%02d', $minCount);
       }

       $option = $this->selectTag($tagName.'_min', $mins, $minValue,
       $optionAttr);
       return $option;
    }

    function meridianOptionTag( $tagName, $value=null, $selected=null, $optionAttr=null)
    {
       $value = isset($value)? $value : $this->tagValue($tagName);
       $merValue = empty($value) ? date('a') : date('a',strtotime( $value ) );
       $meridians = array('am'=>'am','pm'=>'pm');

       $option = $this->selectTag($tagName.'_meridian', $meridians, $merValue,
       $optionAttr);
       return $option;
    }

    function dateTimeOptionTag( $tagName, $dateFormat = 'DMY', $timeFormat = '12',$selected=null, $optionAttr=null)
    {
       switch ( $dateFormat )
       {
          case 'DMY' :
          $opt = $this->dayOptionTag( $tagName ) . '-' . $this->monthOptionTag( $tagName ) . '-' . $this->yearOptionTag( $tagName );
          break;
          case 'MDY' :
          $opt = $this->monthOptionTag( $tagName ) .'-'.$this->dayOptionTag( $tagName ) . '-' . $this->yearOptionTag($tagName);
          break;
          case 'YMD' :
          $opt = $this->yearOptionTag($tagName) . '-' . $this->monthOptionTag( $tagName ) . '-' . $this->dayOptionTag( $tagName );
          break;
          case 'NONE':
          $opt ='';
          break;
          default:
          $opt = '';
          break;
       }
       switch ($timeFormat)
       {
          case '24':
          $opt .= $this->hourOptionTag( $tagName, true ) . ':' . $this->minuteOptionTag( $tagName );
          break;
          case '12':
          $opt .= $this->hourOptionTag( $tagName ) . ':' . $this->minuteOptionTag( $tagName ) . ' ' . $this->meridianOptionTag($tagName);
          break;
          case 'NONE':
          $opt .='';
          break;
          default :

          break;
       }
       return $opt;
    }
}

?>