<?php
/* SVN FILE: $Id$ */

/**
 * Number Helper.
 * 
 * Methods to make numbers more readable.
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
 * @author       Christian Gillen aka kodos <christian@crew.lu>
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
 * Number helper library.
 *
 * Methods to make numbers more readable.
 *
 * @package cake
 * @subpackage cake.libs.helpers
 * @since CakePHP v 0.9.2
 *
 */
class NumberHelper extends Helper
{
    /**
     * Formats a number with a level of precision.
     *
     * @param  float   $number    A floating point number.
     * @param  integer $precision The precision of the returned number.
     * @return float Enter description here...
     * @static 
     */
    function precision($number, $precision = 3)
    {
        return sprintf("%01.{$precision}f", $number);
    }

    /**
     * Returns a formatted-for-humans file size.
     *
     * @param integer $length Size in bytes
     * @return string Human readable size
     * @static 
     */
    function toReadableSize($size)
    {
        switch ($size)
        {
            case 1: return '1 Byte';
            case $size < 1024: return $size . ' Bytes';
            case $size < 1024 * 1024: return NumberHelper::precision($size / 1024, 0) . ' KB';
            case $size < 1024 * 1024 * 1024: return NumberHelper::precision($size / 1024 / 1024, 2) . ' MB';
            case $size < 1024 * 1024 * 1024 * 1024: return NumberHelper::precision($size / 1024 / 1024 / 1024, 2) . ' GB';
            case $size < 1024 * 1024 * 1024 * 1024 * 1024: return NumberHelper::precision($size / 1024 / 1024 / 1024 / 1024, 2) . ' TB';
        }
    }

    /**
     * Formats a number into a percentage string.
     *
     * @param float $number A floating point number
     * @param integer $precision The precision of the returned number
     * @return string Percentage string
     * @static 
     */
    function toPercentage($number, $precision =  2)
    {
        return NumberHelper::precision($number, $precision) . '%';
    }
}

?>
