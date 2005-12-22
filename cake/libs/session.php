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

    function __construct($base = null)
    {
            $this->host = $_SERVER['HTTP_HOST'];
            if (strpos($this->host, ':') !== false)
            {
                $this->host = substr($this->host,0, strpos($this->host, ':'));
            }

            if (empty($this->path))
            {
                $dispatcher =& new Dispatcher();
                $this->path = $dispatcher->baseUrl();
            }
            else
            {
            $this->path = $base;
            }
            if (empty($this->path))
            {
                $this->path = '/';
            }

            $this->ip = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
            $this->userAgent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
            $this->_initSession();
            $this->_begin();
            parent::__construct();
    }

/**
 * Enter description here...
 *
 * @param unknown_type $name
 * @return unknown
 */
    function checkSessionVar($name)
    {
        $expression = "return isset(".$this->_sessionVarNames($name).");";
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
        if($this->checkSessionVar($name))
        {
            $var = $this->_sessionVarNames($name);
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
	    if(!is_array($this->error) || !array_key_exists($errorNumber, $this->error))
	    {
	        return false;
	    }
	    else
	    {
		return $this->error[$errorNumber];
	    }
	}

/**
 * Enter description here...
 *
 * @return unknown
 */
	function getLastError()
	{

	    if($this->lastError)
	    {
	        return $this->getError($this->lastError);
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

        return $this->valid;
    }

/**
 * Enter description here...
 *
 * @param unknown_type $name
 * @return unknown
 */
    function readSessionVar($name = null)
    {
        if(is_null($name))
        {
            return $this->returnSessionVars();
        }

        if($this->checkSessionVar($name))
        {
            $result = eval("return ".$this->_sessionVarNames($name).";");
            return $result;
        }
        $this->_setError(2, "$name doesn't exist");
        return false;
    }

/**
 * Enter description here...
 *
 * @param unknown_type $name
 * @return unknown
 */
    function returnSessionVars()
    {

        if(!empty($_SESSION))
        {
            $result = eval("return ".$_SESSION.";");
            return $result;
        }
        $this->_setError(2, "No Session vars set");
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

        $expression = $this->_sessionVarNames($name);
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

        if (function_exists('session_write_close'))
        {
            session_write_close();
        }

        session_cache_limiter("must-revalidate");
        session_start();
        $this->_new();
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

        switch (CAKE_SECURITY)
        {
            case 'high':
                $this->cookieLifeTime = 0;
                ini_set('session.referer_check', $this->host);
            break;
            case 'medium':
                $this->cookieLifeTime = 7 * 86400;
            break;
            case 'low':
            default :
                $this->cookieLifeTime = 788940000;
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
                ini_set('session.cookie_lifetime', $this->cookieLifeTime);
                ini_set('session.cookie_path', $this->path);
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
                ini_set('session.cookie_lifetime', $this->cookieLifeTime);
                ini_set('session.cookie_path', $this->path);
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
                ini_set('session.cookie_lifetime', $this->cookieLifeTime);
                ini_set('session.cookie_path', $this->path);
                ini_set('session.gc_probability', 1);
                ini_set('session.gc_maxlifetime', Security::inactiveMins() * 60);
            break;
            default :
                $config = CONFIGS.CAKE_SESSION_SAVE.'.php';
                if(is_file($config))
                {
                    require_once($config);
                }
                else
                {
                    ini_set('session.name', CAKE_SESSION_COOKIE);
                    ini_set('session.cookie_lifetime', $this->cookieLifeTime);
                    ini_set('session.cookie_path', $this->path);
                    ini_set('session.gc_probability', 1);
                    ini_set('session.gc_maxlifetime', Security::inactiveMins() * 60);
                }
            break;
        }

    }

/**
 * Enter description here...
 *
 * @access private
 *
 */
    function _new()
    {

        if(!ereg("proxy\.aol\.com$", gethostbyaddr($this->ip)))
        {
            if($this->readSessionVar("Config"))
            {
                if($this->ip == $this->readSessionVar("Config.ip") && $this->userAgent == $this->readSessionVar("Config.userAgent"))
                {
                    $this->valid = true;
                }
                else
                {
                    $this->valid = false;
                    $this->_setError(1, "Session Highjacking Attempted !!!");
                }
            }
           else
           {
               srand((double)microtime() * 1000000);
               $this->writeSessionVar('Config.rand', rand());
               $this->writeSessionVar("Config.ip", $this->ip);
               $this->writeSessionVar("Config.userAgent", $this->userAgent);
               $this->valid = true;
           }
       }
       else
       {
           if(!$this->readSessionVar("Config"))
           {
               srand((double)microtime() * 1000000);
               $this->writeSessionVar('Config.rand', rand());
               $this->writeSessionVar("Config.ip", $this->ip);
               $this->writeSessionVar("Config.userAgent", $this->userAgent);
           }
           $this->valid = true;
       }

        if(CAKE_SECURITY == 'high')
        {
            $this->_regenerateId();
        }
        header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
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
 *
 * @access private
 *
 */
    function _regenerateId()
    {

        $oldSessionId = session_id();
        session_regenerate_id();
        $newSessid = session_id();
          if (function_exists('session_write_close'))
          {
              if(CAKE_SECURITY == 'high')
              {
                  if (isset($_COOKIE[session_name()]))
                  {
                  setcookie(CAKE_SESSION_COOKIE, '', time()-42000, $this->path);
                  }
                  $file = ini_get('session.save_path')."/sess_$oldSessionId";
                  @unlink($file);
              }
              session_write_close();
              $this->_initSession();
              session_id($newSessid);
              session_start();
          }
    }

/**
 * Enter description here...
 *
 * @access private
 *
 */
    function _renew()
    {
        $this->_regenerateId();
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
        $this->setError(3, "$name is not a string");
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

        if($this->error === false)
        {
            $this->error = array();
        }

        $this->error[$errorNumber] = $errorMessage;
        $this->lastError = $errorNumber;
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