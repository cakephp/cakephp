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
 * Author(s): Larry E. Masters aka PhpNut <nut@phpnut.com>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Authors/Developers
 * @link         https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package      cake
 * @subpackage   cake.cake.libs
 * @since        CakePHP v .0.10.0.1222
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
 * @subpackage cake.cake.libs
 * @since      CakePHP v .0.10.0.1222
 */
class CakeSession extends Object
{
/**
 * Enter description here...
 *
 * @var unknown_type
 */
 	var $valid      = false;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $error      = false;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $ip         = false;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $userAgent  = false;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $path       = false;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $lastError  = null;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $sessionId     = null;
    
/**
 * Enter description here...
 *
 * @return unknown
 */
    function &getInstance($base = null)
    {
        static $instance = array();
        
        if (!$instance)
        {
            $instance[0] =& new CakeSession;
            $instance[0]->host = $_SERVER['HTTP_HOST'];
            if (strpos($instance[0]->host, ':') !== false)
            {
                $instance[0]->host = substr($instance[0]->host,0, strpos($instance[0]->host, ':'));
            }
            
            $instance[0]->path = $base;
            
            if (empty($instance[0]->path))
            {
                $instance[0]->path = '/';
            }
            
            $instance[0]->ip = $_SERVER['REMOTE_ADDR'];
            $instance[0]->userAgent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
            
            $instance[0]->_initSession();
        }
        return $instance[0];
    }

/**
 * Enter description here...
 *
 * @param unknown_type $name
 * @return unknown
 */
    function checkSessionVar($name)
    {
        $cakeSession =& CakeSession::getInstance();
        $expression = "return isset(".$cakeSession->_sessionVarNames($name).");";
        return eval($expression);
    }
    
/**
 * Enter description here...
 *
 * @param unknown_type $name
 * @return unknown
 */
    function delSessionVar($name)
    {
        $cakeSession =& CakeSession::getInstance();
        if($cakeSession->check($name))
        {
            $var = $cakeSession->_sessionVarNames($name);
            eval("unset($var);");
            return true;
        }
        $this->_setError(2, "$name doesn't exist");
        return false;
    }
    
/**
 * Enter description here...
 *
 * @param unknown_type $errorNumber
 * @return unknown
 */
    function getError($errorNumber)
    {
        $cakeSession =& CakeSession::getInstance();
	    if(!is_array($cakeSession->error) || !array_key_exists($errorNumber, $cakeSession->error))
	    {
	        return false;
	    }
	    else
	    {
		return $cakeSession->error[$errorNumber];
	    }
	}
	
/**
 * Enter description here...
 *
 * @return unknown
 */
	function getLastError()
	{
        $cakeSession =& CakeSession::getInstance();
	    if($cakeSession->lastError)
	    {
	        return $cakeSession->getError($cakeSession->lastError);
	    }
	    else
	    {
	        return false;
	    }
	}
    
/**
 * Enter description here...
 *
 * @return unknown
 */
    function isValid()
    {
        $cakeSession =& CakeSession::getInstance();
        return $cakeSession->valid;
    }
    
/**
 * Enter description here...
 *
 * @param unknown_type $name
 * @return unknown
 */
    function readSessionVar($name)
    {
        $cakeSession =& CakeSession::getInstance();
        if($cakeSession->checkSessionVar($name))
        {
            $result = eval("return ".$cakeSession->_sessionVarNames($name).";");
            return $result;
        }
        $cakeSession->_setError(2, "$name doesn't exist");
        return false;
    }
    
/**
 * Enter description here...  
 *
 * @param unknown_type $name
 * @param unknown_type $value
 */
    function writeSessionVar($name, $value)
    {
        $cakeSession =& CakeSession::getInstance();
        $expression = $cakeSession->_sessionVarNames($name);
        $expression .= " = \$value;";
        eval($expression);
    }  

/**
 * Enter description here...
 *
 * @access private
 */
    function _begin()
    {
        $cakeSession =& CakeSession::getInstance();
        session_cache_limiter("must-revalidate");    
        session_start();
        header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        $cakeSession->sessionId = session_id();
        
        if($cakeSession->_isActiveSession() == false)
        {
            $cakeSession->_new();
        }
        else
        {
            $cakeSession->_renew();
        }
    }
   
/**
 * Enter description here...
 *
 * @access private
 */
    function _close()
    {
        echo "<pre>";
        echo "CakeSession::_close() Not Implemented Yet";
        echo "</pre>";
        die();
    }
    
/**
 * Enter description here...
 *
 * @access private
 */
    function _destroy()
    {
        echo "<pre>";
        echo "CakeSession::_destroy() Not Implemented Yet";
        echo "</pre>";
        die();
    }
    
/**
 * Enter description here...
 *
 * @access private
 */
    function _gc()
    {
        echo "<pre>";
        echo "CakeSession::_gc() Not Implemented Yet";
        echo "</pre>";
        die();
    }
    
/**
 * Enter description here...
 *
 * @access private
 */
    function _initSession()
    {
        $cakeSession =& CakeSession::getInstance();
        switch (CAKE_SECURITY)
        {
            case 'high':
                $cookieLifeTime = 0;
                ini_set('session.referer_check', $cakeSession->host);
            break;
            case 'medium':
                $cookieLifeTime = 7 * 86400;
            break;
            case 'low':
            default :
                $cookieLifeTime = 788940000;
            break;
        }
        
        switch (CAKE_SESSION_SAVE)
        {
            case 'cake':
                ini_set('session.use_trans_sid', 0);
                ini_set('url_rewriter.tags', '');
                ini_set('session.serialize_handler', 'php');
                ini_set('session.use_cookies', 1);
                ini_set('session.name', CAKE_SESSION_COOKIE);
                ini_set('session.cookie_lifetime', $cookieLifeTime);
                ini_set('session.cookie_path', $cakeSession->path);
                ini_set('session.gc_probability', 1);
                ini_set('session.gc_maxlifetime', Security::inactiveMins() * 60);
                ini_set('session.auto_start', 0);
                ini_set('session.save_path', TMP.'sessions');
            break;
            case 'database':
                ini_set('session.use_trans_sid', 0);
                ini_set('url_rewriter.tags', '');
                ini_set('session.save_handler', 'user');
                ini_set('session.serialize_handler', 'php');
                ini_set('session.use_cookies', 1);
                ini_set('session.name', CAKE_SESSION_COOKIE);
                ini_set('session.cookie_lifetime', $cookieLifeTime);
                ini_set('session.cookie_path', $cakeSession->path);
                ini_set('session.gc_probability', 1);
                ini_set('session.gc_maxlifetime', Security::inactiveMins() * 60);
                ini_set('session.auto_start', 0);
                session_set_save_handler(array('CakeSession', '_open'),
                                         array('CakeSession', '_close'),
                                         array('CakeSession', '_read'),
                                         array('CakeSession', '_write'),
                                         array('CakeSession', '_destroy'),
                                         array('CakeSession', '_gc'));
            break;
            case 'php':
                ini_set('session.name', CAKE_SESSION_COOKIE);
                ini_set('session.cookie_lifetime', $cookieLifeTime);
                ini_set('session.cookie_path', $cakeSession->path);
                ini_set('session.gc_maxlifetime', Security::inactiveMins() * 60);
            break;
            default :
                $config = CONFIGS.CAKE_SESSION_SAVE.'.php.';
                if(is_file($config))
                {
                    require_once($config);
                }
                else
                {
                    ini_set('session.name', CAKE_SESSION_COOKIE);
                    ini_set('session.cookie_lifetime', $cookieLifeTime);
                    ini_set('session.cookie_path', $cakeSession->path);
                    ini_set('session.gc_maxlifetime', Security::inactiveMins() * 60); 
                }               
            break;
        }
        
        $cakeSession->_begin();
    }
    
/**
 * Enter description here...
 *
 * @access private
 * @return unknown
 */
    function _isActiveSession()
    { 
        return false; 
    }
    
/**
 * Enter description here...
 *
 * @access private
 * 
 */
    function _new()
    {
        $cakeSession =& CakeSession::getInstance();

        if(!ereg("proxy\.aol\.com$", gethostbyaddr($cakeSession->ip)))
        {
            if($cakeSession->readSessionVar("Config"))
            {
                if($cakeSession->ip == $cakeSession->readSessionVar("Config.ip") && $cakeSession->userAgent == $cakeSession->readSessionVar("Config.userAgent"))
                {
                    $cakeSession->valid = true;
                }
                else
                {
                    $cakeSession->valid = false;
                    $cakeSession->_setError(1, "Session Highjacking Attempted !!!");
                }
            }
           else
           {
               srand((double)microtime() * 1000000);
               $cakeSession->writeSessionVar('Config.rand', rand());				
               $cakeSession->writeSessionVar("Config.ip", $cakeSession->ip);
               $cakeSession->writeSessionVar("Config.userAgent", $cakeSession->userAgent);
               $cakeSession->valid = true;
           }
       }
       else
       {
           if(!$cakeSession->readSessionVar("Config"))
           {
               srand((double)microtime() * 1000000);
               $cakeSession->writeSessionVar('Config.rand', rand());				
               $cakeSession->writeSessionVar("Config.ip", $cakeSession->ip);
               $cakeSession->writeSessionVar("Config.userAgent", $cakeSession->userAgent);
           }
           $cakeSession->valid = true;
       }
    }
    
/**
 * Enter description here...
 *
 * @access private
 * 
 */
    function _open()
    {
        echo "<pre>";
        echo "CakeSession::_open() Not Implemented Yet";
        echo "</pre>";
        die();
    }
    
/**
 * Enter description here...
 *
 * @access private
 * 
 */
    function _read()
    {
        echo "<pre>";
        echo "CakeSession::_read() Not Implemented Yet";
        echo "</pre>";
        die();
    }
    
/**
 * Enter description here...
 *
 * @access private
 * 
 */
    function _renew()
    {
        return true;
    }
    
/**
 * Enter description here...
 *
 * @param unknown_type $name
 * @return unknown
 * @access private
 */
    function _sessionVarNames($name)
    {
        $cakeSession =& CakeSession::getInstance();
        if(is_string($name))
        {
            if(strpos($name, "."))
            {
                $names = explode(".", $name);
            }
            else
            {
                $names = array($name);
            }
            $expression = $expression = "\$_SESSION";
            
            foreach($names as $item)
            {
                $expression .= is_numeric($item) ? "[$item]" : "['$item']";
            }
            return $expression;
        }
        $cakeSession->setError(3, "$name is not a string");
        return false;
    }
    
/**
 * Enter description here...
 *
 * @param unknown_type $errorNumber
 * @param unknown_type $errorMessage
 * @access private
 */
    function _setError($errorNumber, $errorMessage)
    {
        $cakeSession =& CakeSession::getInstance();
        if($cakeSession->error === false)
        {
            $cakeSession->error = array();
        }
        
        $cakeSession->error[$errorNumber] = $errorMessage;
        $cakeSession->lastError = $errorNumber;
    }
    
/**
 * Enter description here...
 *
 * @access private
 */
    function _write()
    {
        echo "<pre>";
        echo "CakeSession::_write() Not Implemented Yet";
        echo "</pre>";
        die();
    }
} 
?>