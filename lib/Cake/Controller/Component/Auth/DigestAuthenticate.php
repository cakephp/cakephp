<?php
/**
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('BaseAuthenticate', 'Controller/Component/Auth');

/**
 * Digest Authentication adapter for AuthComponent.
 *
 * Provides Digest HTTP authentication support for AuthComponent.  Unlike most AuthComponent adapters,
 * DigestAuthenticate requires a special password hash that conforms to RFC2617.  You can create this
 * password using `DigestAuthenticate::password()`.  If you wish to use digest authentication alongside other
 * authentication methods, its recommended that you store the digest authentication separately.
 *
 * Clients using Digest Authentication  must support cookies.  Since AuthComponent identifies users based
 * on Session contents, clients without support for cookies will not function properly.
 *
 * ### Using Digest auth
 *
 * In your controller's components array, add auth + the required settings.
 * {{{
 *	public $components = array(
 *		'Auth' => array(
 *			'authenticate' => array('Digest')
 *		)
 *	);
 * }}}
 *
 * In your login function just call `$this->Auth->login()` without any checks for POST data.  This
 * will send the authentication headers, and trigger the login dialog in the browser/client.
 *
 * ### Generating passwords compatible with Digest authentication.
 *
 * Due to the Digest authentication specification, digest auth requires a special password value.  You
 * can generate this password using `DigestAuthenticate::password()`
 *
 * `$digestPass = DigestAuthenticate::password($username, env('SERVER_NAME'), $password);`
 *
 * Its recommended that you store this digest auth only password separate from password hashes used for other
 * login methods.  For example `User.digest_pass` could be used for a digest password, while `User.password` would
 * store the password hash for use with other methods like Basic or Form.
 *
 * @package       Cake.Controller.Component.Auth
 * @since 2.0
 */
class DigestAuthenticate extends BaseAuthenticate {
/**
 * Settings for this object.
 *
 * - `fields` The fields to use to identify a user by.
 * - `userModel` The model name of the User, defaults to User.
 * - `scope` Additional conditions to use when looking up and authenticating users,
 *    i.e. `array('User.is_active' => 1).`
 * - `realm` The realm authentication is for, Defaults to the servername.
 * - `nonce` A nonce used for authentication.  Defaults to `uniqid()`.
 * - `qop` Defaults to auth, no other values are supported at this time.
 * - `opaque` A string that must be returned unchanged by clients. Defaults to `md5($settings['realm'])`
 *
 * @var array
 */
	public $settings = array(
		'fields' => array(
			'username' => 'username',
			'password' => 'password'
		),
		'userModel' => 'User',
		'scope' => array(),
		'realm' => '',
		'qop' => 'auth',
		'nonce' => '',
		'opaque' => ''
	);

/**
 * Constructor, completes configuration for digest authentication.
 *
 * @param ComponentCollection $collection The Component collection used on this request.
 * @param array $settings An array of settings.
 */
	public function __construct(ComponentCollection $collection, $settings) {
		parent::__construct($collection, $settings);
		if (empty($this->settings['realm'])) {
			$this->settings['realm'] = env('SERVER_NAME');
		}
		if (empty($this->settings['nonce'])) {
			$this->settings['nonce'] = uniqid('');
		}
		if (empty($this->settings['opaque'])) {
			$this->settings['opaque'] = md5($this->settings['realm']);
		}
	}

/**
 * Authenticate a user using Digest HTTP auth.  Will use the configured User model and attempt a
 * login using Digest HTTP auth.
 *
 * @param CakeRequest $request The request to authenticate with.
 * @param CakeResponse $response The response to add headers to.
 * @return mixed Either false on failure, or an array of user data on success.
 */
	public function authenticate(CakeRequest $request, CakeResponse $response) {
		$user = $this->getUser($request);

		if (empty($user)) {
			$response->header($this->loginHeaders());
			$response->statusCode(401);
			$response->send();
			return false;
		}
		return $user;
	}

/**
 * Get a user based on information in the request.  Used by cookie-less auth for stateless clients.
 *
 * @param CakeRequest $request Request object.
 * @return mixed Either false or an array of user information
 */
	public function getUser($request) {
		$digest = $this->_getDigest();
		if (empty($digest)) {
			return false;
		}
		$user = $this->_findUser($digest['username'], null);
		if (empty($user)) {
			return false;
		}
		$password = $user[$this->settings['fields']['password']];
		unset($user[$this->settings['fields']['password']]);
		if ($digest['response'] === $this->generateResponseHash($digest, $password)) {
			return $user;
		}
		return false;
	}

/**
 * Find a user record using the standard options.
 *
 * @param string $username The username/identifier.
 * @param string $password Unused password, digest doesn't require passwords.
 * @return Mixed Either false on failure, or an array of user data.
 */
	protected function _findUser($username, $password) {
		$userModel = $this->settings['userModel'];
		list($plugin, $model) = pluginSplit($userModel);
		$fields = $this->settings['fields'];

		$conditions = array(
			$model . '.' . $fields['username'] => $username,
		);
		if (!empty($this->settings['scope'])) {
			$conditions = array_merge($conditions, $this->settings['scope']);
		}
		$result = ClassRegistry::init($userModel)->find('first', array(
			'conditions' => $conditions,
			'recursive' => 0
		));
		if (empty($result) || empty($result[$model])) {
			return false;
		}
		return $result[$model];
	}

/**
 * Gets the digest headers from the request/environment.
 *
 * @return array Array of digest information.
 */
	protected function _getDigest() {
		$digest = env('PHP_AUTH_DIGEST');
		if (empty($digest) && function_exists('apache_request_headers')) {
			$headers = apache_request_headers();
			if (!empty($headers['Authorization']) && substr($headers['Authorization'], 0, 7) == 'Digest ') {
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
		if (substr($digest, 0, 7) == 'Digest ') {
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
 * @return string Response hash
 */
	public function generateResponseHash($digest, $password) {
		return md5(
			$password .
			':' . $digest['nonce'] . ':' . $digest['nc'] . ':' . $digest['cnonce'] . ':' . $digest['qop'] . ':' .
			md5(env('REQUEST_METHOD') . ':' . $digest['uri'])
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
 * @return string Headers for logging in.
 */
	public function loginHeaders() {
		$options = array(
			'realm' => $this->settings['realm'],
			'qop' => $this->settings['qop'],
			'nonce' => $this->settings['nonce'],
			'opaque' => $this->settings['opaque']
		);
		$opts = array();
		foreach ($options as $k => $v) {
			$opts[] = sprintf('%s="%s"', $k, $v);
		}
		return 'WWW-Authenticate: Digest ' . implode(',', $opts);
	}
}