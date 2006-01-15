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
 * Copyright (c) 2005, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2005, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs.controller.components
 * @since        CakePHP v 0.10.0.1232
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
 * @subpackage cake.cake.libs.controller.components
 * @since      CakePHP v 0.10.0.1232
 *
 */
class SessionComponent extends Object
{

/**
 * Enter description here...
 *
 */
    function __construct ($base = null)
    {
        $this->CakeSession = New CakeSession($base);
        parent::__construct();
    }

/**
 * Enter description here...
 *
 * Use like this. $this->Session->write('Controller.sessKey', 'session value');
 *
 * @param unknown_type $name
 * @param unknown_type $value
 * @return unknown
 */
    function write($name, $value)
    {
        return $this->CakeSession->writeSessionVar($name, $value);
    }

/**
 * Enter description here...
 *
 * Use like this. $this->Session->read('Controller.sessKey');
 * Calling the method without a param will return all session vars
 *
 * @param unknown_type $name
 * @return unknown
 */
    function read($name = null)
    {
        return $this->CakeSession->readSessionVar($name);
    }

/**
 * Enter description here...
 *
 * Use like this. $this->Session->del('Controller.sessKey');
 *
 * @param unknown_type $name
 * @return unknown
 */
    function del($name)
    {
        return $this->CakeSession->delSessionVar($name);
    }

/**
 * Enter description here...
 *
 * Use like this. $this->Session->check('Controller.sessKey');
 *
 * @param unknown_type $name
 * @return unknown
 */
    function check($name)
    {
        return $this->CakeSession->checkSessionVar($name);
    }

/**
 * Enter description here...
 *
 * Use like this. $this->Session->error();
 *
 * @return string Last session error
 */
    function error()
    {
        return $this->CakeSession->getLastError();
    }

/**
 * Enter description here...
 *
 * Use like this. $this->Session->setFlash('This has been saved');
 *
 * @return string Last session error
 */
    function setFlash($flashMessage)
    {
        $this->write('Message.flash', $flashMessage);
    }

/**
 * Enter description here...
 *
 * Use like this. $this->Session->flash();
 *
 * @return
 */
    function flash()
    {
        if($this->check('Message.flash'))
        {
            echo '<div class="message">'.$this->read('Message.flash').'</div>';
            $this->del('Message.flash');
        }
        else
        {
            return false;
        }

    }

/**
 * Enter description here...
 *
 * Use like this. $this->Session->valid();
 * This will return true if session is valid
 * false if session is invalid
 *
 * @return boolean
 */
    function renew()
    {
        $this->CakeSession->renew();
    }

/**
 * Enter description here...
 *
 * Use like this. $this->Session->valid();
 * This will return true if session is valid
 * false if session is invalid
 *
 * @return boolean
 */
    function valid()
    {
        return $this->CakeSession->isValid();
    }

}
?>