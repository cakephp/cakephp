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
 * Copyright (c) 2006, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs.view.helpers
 * @since        CakePHP v 1.0.0.2277
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */


/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package    cake
 * @subpackage cake.cake.libs.view.helpers
 * @since      CakePHP v 1.0.0.2277
 *
 */
class CacheHelper extends Helper
{
    var $replace = array();
    var $match = array();
    var $view;

    function cache($file, $out, $cache = false)
    {
        if(is_array($this->cacheAction))
        {
            $check = str_replace('/', '_', $this->here);
            $replace = str_replace('/', '_', $this->base);
            $check = str_replace($replace, '', $check);
            $check = str_replace('_'.$this->controllerName.'_', '', $check);
            $pos = strpos($check, '_');
            $pos1 = strrpos($check, '_');
            if($pos1 > 0)
            {
                $check = substr($check, 0, $pos1);
            }
            if ($pos !== false)
            {
                $check = substr($check, 1);
            }
            $keys = str_replace('/', '_', array_keys($this->cacheAction));
            $key = preg_grep("/^$check/", array_values($keys));
            if(isset($key['0']))
            {
                $key = str_replace('_', '/', $key['0']);
            }
            else
            {
                $key = 'index';
            }

            if(isset($this->cacheAction[$key]))
            {
                $cacheTime = $this->cacheAction[$key];
            }
            else
            {
                $cacheTime = 0;
            }
        }
        else
        {
            $cacheTime = $this->cacheAction;
        }
        if($cacheTime != '' && $cacheTime > 0)
        {
            $this->__parseFile($file, $out);

            if($cache === true)
            {
                $cached = $this->__parseOutput($out);
                $this->__writeFile($cached, $cacheTime);
            }
            return $out;
        }
        else
        {
            return $out;
        }
    }


    function __parseFile($file, $cache)
    {
        if(is_file($file))
        {
            $file = file_get_contents($file);
        }
        else if($file = fileExistsInPath($file))
        {
            $file = file_get_contents($file);
        }

        preg_match_all('/(?P<found><cake:nocache>(?:.*|(?:[\\S\\s]*[^\\S]))<\/cake:nocache>)/i', $cache, $oresult, PREG_PATTERN_ORDER);
        preg_match_all('/<cake:nocache>(?P<replace>(?:.*|(?:[\\S\\s]*[^\\S])))<\/cake:nocache>/i', $file, $result, PREG_PATTERN_ORDER);

        if(!empty($result['replace']))
        {
            $count = 0;
            foreach($result['replace'] as $result)
            {
                $this->replace[] = $result;
                $this->match[] = $oresult['found'][$count];
                $count++;
            }
        }
    }

    function __parseOutput($cache)
    {
        $count = 0;
        if(!empty($this->match))
        {
            foreach($this->match as $found)
            {
                $cache = str_replace($found, $this->replace[$count], $cache);
                $count++;
            }
            return $cache;
        }
        return $cache;
    }

    function __writeFile($file, $timestamp)
    {
        $now = time();
        if (is_numeric($timestamp))
        {
            $cacheTime = $now + $timestamp;
        }
        else
        {
            $cacheTime = $now + strtotime($timestamp);
        }
        $result = preg_replace('/\/\//', '/', $this->here);
        $cache = str_replace('/', '_', $result.'.php');
        $cache = str_replace('favicon.ico', '', $cache);
        $file = '<!--cachetime:'.$cacheTime.'-->'.
                '<?php loadController(\''.$this->view->name.'\'); ?>'.
                '<?php $this->controller = new '.$this->view->name.'Controller(); ?>'.
                '<?php $this->helpers = unserialize(\''. serialize($this->view->helpers).'\'); ?>'.
                '<?php $this->webroot = \''. $this->view->webroot.'\'; ?>'.
                '<?php $this->data  = unserialize(\''. serialize($this->view->data).'\'); ?>'.
                '<?php $loaded = array(); ?>'.
                '<?php $this->_loadHelpers($loaded, $this->helpers); ?>'.$file;
        return cache('views'.DS.$cache, $file, $timestamp);
    }
}
?>