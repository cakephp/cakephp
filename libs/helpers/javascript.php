<?php
/* SVN FILE: $Id$ */

/**
 * Javascript Helper class file.
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
 * Javascript Helper class for easy use of javascript.
 *
 * JavascriptHelper encloses all methods needed while working with javascripts.
 *
 * @package    cake
 * @subpackage cake.libs.helpers
 * @since      CakePHP v 0.9.2
 */
class JavascriptHelper extends Helper
{
    /**
     * Returns a JavaScript script tag.
     *
     * @param  string $script The JavaScript to be wrapped in SCRIPT tags.
     * @return string The full SCRIPT element, with the JavaScript inside it.
     */
    function codeBlock($script)
    {
        return sprintf(TAG_JAVASCRIPT, $script);
    }

    /**
     * Returns a JavaScript include tag
     *
     * @param  string $url URL to JavaScript file.
     * @return string
     */
    function link($url)
    {
        return sprintf(TAG_JAVASCRIPT_INCLUDE, $this->base.$url);
    }
}

?>