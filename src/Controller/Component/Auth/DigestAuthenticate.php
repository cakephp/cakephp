<?php
/**
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\Auth\BasicAuthenticate;
use Cake\Network\Request;
use Cake\Network\Response;

/**
 * Digest Authentication adapter for AuthComponent.
 *
 * Provides Digest HTTP authentication support for AuthComponent. Unlike most AuthComponent adapters,
 * DigestAuthenticate requires a special password hash that conforms to RFC2617. You can create this
 * password using `DigestAuthenticate::password()`. If you wish to use digest authentication alongside other
 * authentication methods, its recommended that you store the digest authentication separately.
 *
 * Clients using Digest Authentication must support cookies. Since AuthComponent identifies users based
 * on Session contents, clients without support for cookies will not function properly.
 *
 * ### Using Digest auth
 *
 * In your controller's components array, add auth + the required config
 * {{{
 *	public $components = array(
 *		'Auth' => array(
 *			'authenticate' => array('Digest')
 *		)
 *	);
 * }}}
 *
 * In your login function just call `$this->Auth->login()` without any checks for POST data. This
 * will send the authentication headers, and trigger the login dialog in the browser/client.
 *
 * ### Generating passwords compatible with Digest authentication.
 *
 * Due to the Digest authentication specification, digest auth requires a special password value. You
 * can generate this password using `DigestAuthenticate::password()`
 *
 * `$digestPass = DigestAuthenticate::password($username, env('SERVER_NAME'), $password);`
 *
 * Its recommended that you store this digest auth only password separate from password hashes used for other
 * login methods. For example `User.digest_pass` could be used for a digest password, while `User.password` would
 * store the password hash for use with other methods like Basic or Form.
 */
class DigestAuthenticate extends BasicAuthenticate {

/**
 * Default config for this object.
 *
 * - `fields` The fields to use to identify a user by.
 * - `userModel` The model name of the User, defaults to Users.
 * - `scope` Additional conditions to use when looking up and authenticating users,
 *    i.e. `array('User.is_active' => 1).`
 * - `recursive` The value of the recursive key passed to find(). Defaults to 0.
 * - `contain` Extra models to contain and store in session.
 * - `realm` The realm authentication is for, Defaults to the servername.
 * - `nonce` A nonce used for authentication. Defaults to `uniqid()`.
 * - `qop` Defaults to auth, no other values are supported at this time.
 * - `opaque` A string that must be returned unchanged by clients.
 *    Defaults to `md5($config['realm'])`
 *
 * @var array
 */
	protected $_defaultConfig = [
		'fields' => [
			'username' => 'username',
			'password' => 'password'
		],
		'userModel' => 'Users',
		'scope' => [],
		'recursive' => 0,
		'contain' => null,
		'realm' => null,
		'qop' => 'auth',
		'nonce' => null,
		'opaque' => null,
		'passwordHasher' => 'Blowfish',
	];

/**
 * Get a user based on information in the request. Used by cookie-less auth for stateless clients.
 *
 * @param \Cake\Network\Request $request Request object.
 * @return mixed Either false or an array of user information
 */
	public function getUser(Request $request) {
		$digest = $this->_getDigest($request);
		if (empty($digest)) {
			return false;
		}

		list(, $model) = pluginSplit($this->_config['userModel']);
		$user = $this->_findUser($digest['username']);
		if (empty($user)) {
			return false;
		}

		$field = $this->_config['fields']['password'];
		$password = $user[$field];
		unset($user[$field]);

		$hash = $this->generateResponseHash($digest, $password, $request->env('REQUEST_METHOD'));
		if ($digest['response'] === $hash) {
			return $user;
		}
		return false;
	}

/**
 * Gets the digest headers from the request/environment.
 *
 * @param \Cake\Network\Request $request Request object.
 * @return array Array of digest information.
 */
	protected function _getDigest(Request $request) {
		$digest = $request->env('PHP_AUTH_DIGEST');
		if (empty($digest) && function_exists('apache_request_headers')) {
			$headers = apache_request_headers();
			if (!empty($headers['Authorization']) && substr($headers['Authorization'], 0, 7) === 'Digest ') {
				$digest = substr($headers['Authorization'], 7);
			}
		}
		if (empty($digest)) {
			return false;
		}
		return $this->parseAuthData($digest);
	}

/**
 * Parse the digest authentication headers and split them up.
 *
 * @param string $digest The raw digest authentication headers.
 * @return array An array of digest authentication headers
 */
	public function parseAuthData($digest) {
		if (substr($digest, 0, 7) === 'Digest ') {
			$digest = substr($digest, 7);
		}
		$keys = $match = array();
		$req = array('nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1);
		preg_match_all('/(\w+)=([\'"]?)([a-zA-Z0-9@=.\/_-]+)\2/', $digest, $match, PREG_SET_ORDER);

		foreach ($match as $i) {
			$keys[$i[1]] = $i[3];
			unset($req[$i[1]]);
		}

		if (empty($req)) {
			return $keys;
		}
		return null;
	}

/**
 * Generate the response hash for a given digest array.
 *
 * @param array $digest Digest information containing data from DigestAuthenticate::parseAuthData().
 * @param string $password The digest hash password generated with DigestAuthenticate::password()
 * @param string $method Request method
 * @return string Response hash
 */
	public function generateResponseHash($digest, $password, $method) {
		return md5(
			$password .
			':' . $digest['nonce'] . ':' . $digest['nc'] . ':' . $digest['cnonce'] . ':' . $digest['qop'] . ':' .
			md5($method . ':' . $digest['uri'])
		);
	}

/**
 * Creates an auth digest password hash to store
 *
 * @param string $username The username to use in the digest hash.
 * @param string $password The unhashed password to make a digest hash for.
 * @param string $realm The realm the password is for.
 * @return string the hashed password that can later be used with Digest authentication.
 */
	public static function password($username, $password, $realm) {
		return md5($username . ':' . $realm . ':' . $password);
	}

/**
 * Generate the login headers
 *
 * @param \Cake\Network\Request $request Request object.
 * @return string Headers for logging in.
 */
	public function loginHeaders(Request $request) {
		$realm = $this->_config['realm'] ?: $request->env('SERVER_NAME');

		$options = array(
			'realm' => $realm,
			'qop' => $this->_config['qop'],
			'nonce' => $this->_config['nonce'] ?: uniqid(''),
			'opaque' => $this->_config['opaque'] ?: md5($realm)
		);

		$opts = array();
		foreach ($options as $k => $v) {
			$opts[] = sprintf('%s="%s"', $k, $v);
		}
		return 'WWW-Authenticate: Digest ' . implode(',', $opts);
	}

}
