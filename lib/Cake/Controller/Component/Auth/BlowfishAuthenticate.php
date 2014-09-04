<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of the files must retain the above copyright notice.
 *
 * @copyright	  Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link	      http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('FormAuthenticate', 'Controller/Component/Auth');

/**
 * An authentication adapter for AuthComponent. Provides the ability to authenticate using POST data using Blowfish
 * hashing. Can be used by configuring AuthComponent to use it via the AuthComponent::$authenticate setting.
 *
 * {{{
 * 	$this->Auth->authenticate = array(
 * 		'Blowfish' => array(
 * 			'scope' => array('User.active' => 1)
 * 		)
 * 	)
 * }}}
 *
 * When configuring BlowfishAuthenticate you can pass in settings to which fields, model and additional conditions
 * are used. See FormAuthenticate::$settings for more information.
 *
 * For initial password hashing/creation see Security::hash(). Other than how the password is initially hashed,
 * BlowfishAuthenticate works exactly the same way as FormAuthenticate.
 *
 * @package	Cake.Controller.Component.Auth
 * @since CakePHP(tm) v 2.3
 * @see	AuthComponent::$authenticate
 * @deprecated 3.0.0 Since 2.4. Just use FormAuthenticate with 'passwordHasher' setting set to 'Blowfish'
 */
class BlowfishAuthenticate extends FormAuthenticate {

/**
 * Constructor. Sets default passwordHasher to Blowfish
 *
 * @param ComponentCollection $collection The Component collection used on this request.
 * @param array $settings Array of settings to use.
 */
	public function __construct(ComponentCollection $collection, $settings) {
		$this->settings['passwordHasher'] = 'Blowfish';
		parent::__construct($collection, $settings);
	}

}
