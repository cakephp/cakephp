<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		https://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {

    public $components = array(
        'Flash',
        'Auth' => array(
            'loginRedirect' => array(
                'controller' => 'posts',
                'action' => 'index'
            ),
            'logoutRedirect' => array(
                'controller' => 'pages',
                'action' => 'display',
                'home'
            ),
            'authenticate' => array(
                'Form' => array(
                    'passwordHasher' => 'Blowfish'
                )
			),
			'authorize' => array(
                'Actions' => array('actionPath' => 'controllers')
			),
			'Session'
		)
	);

	public $helpers = array('Html', 'Form', 'Session');

	function beforeFilter(){
		if(Configure::read('DebugKit')){
			$debugkit = array('DebugKit.Toolbar' => array('history' => 10));
			$this->components = Hash::merge($this->components, $debugkit);
		}
		//Configure AuthComponent
		$this->Auth->loginAction = array(
			'controller' => 'users',
			'action' => 'login'
		);
		$this->Auth->logoutRedirect = array(
			'controller' => 'users',
			'action' => 'login'
		);
		$this->Auth->loginRedirect = array(
			'controller' => 'posts',
			'action' => 'add'
		);
	}
}
