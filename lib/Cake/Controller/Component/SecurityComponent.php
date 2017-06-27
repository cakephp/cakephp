<?php
/**
 * Security Component
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
 * @package       Cake.Controller.Component
 * @since         CakePHP(tm) v 0.10.8.2156
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Component', 'Controller');
App::uses('CakeText', 'Utility');
App::uses('Hash', 'Utility');
App::uses('Security', 'Utility');

/**
 * The Security Component creates an easy way to integrate tighter security in
 * your application. It provides methods for various tasks like:
 *
 * - Restricting which HTTP methods your application accepts.
 * - CSRF protection.
 * - Form tampering protection
 * - Requiring that SSL be used.
 * - Limiting cross controller communication.
 *
 * @package       Cake.Controller.Component
 * @link https://book.cakephp.org/2.0/en/core-libraries/components/security-component.html
 */
class SecurityComponent extends Component {

/**
 * Default message used for exceptions thrown
 */
	const DEFAULT_EXCEPTION_MESSAGE = 'The request has been black-holed';

/**
 * The controller method that will be called if this request is black-hole'd
 *
 * @var string
 */
	public $blackHoleCallback = null;

/**
 * List of controller actions for which a POST request is required
 *
 * @var array
 * @deprecated 3.0.0 Use CakeRequest::allowMethod() instead.
 * @see SecurityComponent::requirePost()
 */
	public $requirePost = array();

/**
 * List of controller actions for which a GET request is required
 *
 * @var array
 * @deprecated 3.0.0 Use CakeRequest::allowMethod() instead.
 * @see SecurityComponent::requireGet()
 */
	public $requireGet = array();

/**
 * List of controller actions for which a PUT request is required
 *
 * @var array
 * @deprecated 3.0.0 Use CakeRequest::allowMethod() instead.
 * @see SecurityComponent::requirePut()
 */
	public $requirePut = array();

/**
 * List of controller actions for which a DELETE request is required
 *
 * @var array
 * @deprecated 3.0.0 Use CakeRequest::allowMethod() instead.
 * @see SecurityComponent::requireDelete()
 */
	public $requireDelete = array();

/**
 * List of actions that require an SSL-secured connection
 *
 * @var array
 * @see SecurityComponent::requireSecure()
 */
	public $requireSecure = array();

/**
 * List of actions that require a valid authentication key
 *
 * @var array
 * @see SecurityComponent::requireAuth()
 * @deprecated 2.8.1 This feature is confusing and not useful.
 */
	public $requireAuth = array();

/**
 * Controllers from which actions of the current controller are allowed to receive
 * requests.
 *
 * @var array
 * @see SecurityComponent::requireAuth()
 */
	public $allowedControllers = array();

/**
 * Actions from which actions of the current controller are allowed to receive
 * requests.
 *
 * @var array
 * @see SecurityComponent::requireAuth()
 */
	public $allowedActions = array();

/**
 * Deprecated property, superseded by unlockedFields.
 *
 * @var array
 * @deprecated 3.0.0 Superseded by unlockedFields.
 * @see SecurityComponent::$unlockedFields
 */
	public $disabledFields = array();

/**
 * Form fields to exclude from POST validation. Fields can be unlocked
 * either in the Component, or with FormHelper::unlockField().
 * Fields that have been unlocked are not required to be part of the POST
 * and hidden unlocked fields do not have their values checked.
 *
 * @var array
 */
	public $unlockedFields = array();

/**
 * Actions to exclude from CSRF and POST validation checks.
 * Other checks like requireAuth(), requireSecure(),
 * requirePost(), requireGet() etc. will still be applied.
 *
 * @var array
 */
	public $unlockedActions = array();

/**
 * Whether to validate POST data. Set to false to disable for data coming from 3rd party
 * services, etc.
 *
 * @var bool
 */
	public $validatePost = true;

/**
 * Whether to use CSRF protected forms. Set to false to disable CSRF protection on forms.
 *
 * @var bool
 * @see http://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)
 * @see SecurityComponent::$csrfExpires
 */
	public $csrfCheck = true;

/**
 * The duration from when a CSRF token is created that it will expire on.
 * Each form/page request will generate a new token that can only be submitted once unless
 * it expires. Can be any value compatible with strtotime()
 *
 * @var string
 */
	public $csrfExpires = '+30 minutes';

/**
 * Controls whether or not CSRF tokens are use and burn. Set to false to not generate
 * new tokens on each request. One token will be reused until it expires. This reduces
 * the chances of users getting invalid requests because of token consumption.
 * It has the side effect of making CSRF less secure, as tokens are reusable.
 *
 * @var bool
 */
	public $csrfUseOnce = true;

/**
 * Control the number of tokens a user can keep open.
 * This is most useful with one-time use tokens. Since new tokens
 * are created on each request, having a hard limit on the number of open tokens
 * can be useful in controlling the size of the session file.
 *
 * When tokens are evicted, the oldest ones will be removed, as they are the most likely
 * to be dead/expired.
 *
 * @var int
 */
	public $csrfLimit = 100;

/**
 * Other components used by the Security component
 *
 * @var array
 */
	public $components = array('Session');

/**
 * Holds the current action of the controller
 *
 * @var string
 */
	protected $_action = null;

/**
 * Request object
 *
 * @var CakeRequest
 */
	public $request;

/**
 * Component startup. All security checking happens here.
 *
 * @param Controller $controller Instantiating controller
 * @throws AuthSecurityException
 * @return void
 */
	public function startup(Controller $controller) {
		$this->request = $controller->request;
		$this->_action = $controller->request->params['action'];
		$hasData = ($controller->request->data || $controller->request->is(array('put', 'post', 'delete', 'patch')));
		try {
			$this->_methodsRequired($controller);
			$this->_secureRequired($controller);
			$this->_authRequired($controller);

			$isNotRequestAction = (
				!isset($controller->request->params['requested']) ||
				$controller->request->params['requested'] != 1
			);

			if ($this->_action === $this->blackHoleCallback) {
				throw new AuthSecurityException(sprintf('Action %s is defined as the blackhole callback.', $this->_action));
			}

			if (!in_array($this->_action, (array)$this->unlockedActions) && $hasData && $isNotRequestAction) {
				if ($this->validatePost) {
					$this->_validatePost($controller);
				}
				if ($this->csrfCheck) {
					$this->_validateCsrf($controller);
				}
			}

		} catch (SecurityException $se) {
			return $this->blackHole($controller, $se->getType(), $se);
		}

		$this->generateToken($controller->request);
		if ($hasData && is_array($controller->request->data)) {
			unset($controller->request->data['_Token']);
		}
	}

/**
 * Sets the actions that require a POST request, or empty for all actions
 *
 * @return void
 * @deprecated 3.0.0 Use CakeRequest::onlyAllow() instead.
 * @link https://book.cakephp.org/2.0/en/core-libraries/components/security-component.html#SecurityComponent::requirePost
 */
	public function requirePost() {
		$args = func_get_args();
		$this->_requireMethod('Post', $args);
	}

/**
 * Sets the actions that require a GET request, or empty for all actions
 *
 * @deprecated 3.0.0 Use CakeRequest::onlyAllow() instead.
 * @return void
 */
	public function requireGet() {
		$args = func_get_args();
		$this->_requireMethod('Get', $args);
	}

/**
 * Sets the actions that require a PUT request, or empty for all actions
 *
 * @deprecated 3.0.0 Use CakeRequest::onlyAllow() instead.
 * @return void
 */
	public function requirePut() {
		$args = func_get_args();
		$this->_requireMethod('Put', $args);
	}

/**
 * Sets the actions that require a DELETE request, or empty for all actions
 *
 * @deprecated 3.0.0 Use CakeRequest::onlyAllow() instead.
 * @return void
 */
	public function requireDelete() {
		$args = func_get_args();
		$this->_requireMethod('Delete', $args);
	}

/**
 * Sets the actions that require a request that is SSL-secured, or empty for all actions
 *
 * @return void
 * @link https://book.cakephp.org/2.0/en/core-libraries/components/security-component.html#SecurityComponent::requireSecure
 */
	public function requireSecure() {
		$args = func_get_args();
		$this->_requireMethod('Secure', $args);
	}

/**
 * Sets the actions that require whitelisted form submissions.
 *
 * Adding actions with this method will enforce the restrictions
 * set in SecurityComponent::$allowedControllers and
 * SecurityComponent::$allowedActions.
 *
 * @return void
 * @link https://book.cakephp.org/2.0/en/core-libraries/components/security-component.html#SecurityComponent::requireAuth
 */
	public function requireAuth() {
		$args = func_get_args();
		$this->_requireMethod('Auth', $args);
	}

/**
 * Black-hole an invalid request with a 400 error or custom callback. If SecurityComponent::$blackHoleCallback
 * is specified, it will use this callback by executing the method indicated in $error
 *
 * @param Controller $controller Instantiating controller
 * @param string $error Error method
 * @param SecurityException|null $exception Additional debug info describing the cause
 * @return mixed If specified, controller blackHoleCallback's response, or no return otherwise
 * @see SecurityComponent::$blackHoleCallback
 * @link https://book.cakephp.org/2.0/en/core-libraries/components/security-component.html#handling-blackhole-callbacks
 * @throws BadRequestException
 */
	public function blackHole(Controller $controller, $error = '', SecurityException $exception = null) {
		if (!$this->blackHoleCallback) {
			$this->_throwException($exception);
		}
		return $this->_callback($controller, $this->blackHoleCallback, array($error));
	}

/**
 * Check debug status and throw an Exception based on the existing one
 *
 * @param SecurityException|null $exception Additional debug info describing the cause
 * @throws BadRequestException
 * @return void
 */
	protected function _throwException($exception = null) {
		if ($exception !== null) {
			if (!Configure::read('debug') && $exception instanceof SecurityException) {
				$exception->setReason($exception->getMessage());
				$exception->setMessage(self::DEFAULT_EXCEPTION_MESSAGE);
			}
			throw $exception;
		}
		throw new BadRequestException(self::DEFAULT_EXCEPTION_MESSAGE);
	}

/**
 * Sets the actions that require a $method HTTP request, or empty for all actions
 *
 * @param string $method The HTTP method to assign controller actions to
 * @param array $actions Controller actions to set the required HTTP method to.
 * @return void
 */
	protected function _requireMethod($method, $actions = array()) {
		if (isset($actions[0]) && is_array($actions[0])) {
			$actions = $actions[0];
		}
		$this->{'require' . $method} = (empty($actions)) ? array('*') : $actions;
	}

/**
 * Check if HTTP methods are required
 *
 * @param Controller $controller Instantiating controller
 * @throws SecurityException
 * @return bool True if $method is required
 */
	protected function _methodsRequired(Controller $controller) {
		foreach (array('Post', 'Get', 'Put', 'Delete') as $method) {
			$property = 'require' . $method;
			if (is_array($this->$property) && !empty($this->$property)) {
				$require = $this->$property;
				if (in_array($this->_action, $require) || $this->$property === array('*')) {
					if (!$controller->request->is($method)) {
						throw new SecurityException(
							sprintf('The request method must be %s', strtoupper($method))
						);
					}
				}
			}
		}
		return true;
	}

/**
 * Check if access requires secure connection
 *
 * @param Controller $controller Instantiating controller
 * @throws SecurityException
 * @return bool True if secure connection required
 */
	protected function _secureRequired(Controller $controller) {
		if (is_array($this->requireSecure) && !empty($this->requireSecure)) {
			$requireSecure = $this->requireSecure;

			if (in_array($this->_action, $requireSecure) || $this->requireSecure === array('*')) {
				if (!$controller->request->is('ssl')) {
					throw new SecurityException(
						'Request is not SSL and the action is required to be secure'
					);
				}
			}
		}
		return true;
	}

/**
 * Check if authentication is required
 *
 * @param Controller $controller Instantiating controller
 * @return bool|null True if authentication required
 * @throws AuthSecurityException
 * @deprecated 2.8.1 This feature is confusing and not useful.
 */
	protected function _authRequired(Controller $controller) {
		if (is_array($this->requireAuth) && !empty($this->requireAuth) && !empty($controller->request->data)) {
			$requireAuth = $this->requireAuth;

			if (in_array($controller->request->params['action'], $requireAuth) || $this->requireAuth === array('*')) {
				if (!isset($controller->request->data['_Token'])) {
					throw new AuthSecurityException('\'_Token\' was not found in request data.');
				}

				if ($this->Session->check('_Token')) {
					$tData = $this->Session->read('_Token');

					if (!empty($tData['allowedControllers']) &&
						!in_array($controller->request->params['controller'], $tData['allowedControllers'])) {
						throw new AuthSecurityException(
							sprintf(
								'Controller \'%s\' was not found in allowed controllers: \'%s\'.',
								$controller->request->params['controller'],
								implode(', ', (array)$tData['allowedControllers'])
							)
						);
					}
					if (!empty($tData['allowedActions']) &&
						!in_array($controller->request->params['action'], $tData['allowedActions'])
					) {
						throw new AuthSecurityException(
							sprintf(
								'Action \'%s::%s\' was not found in allowed actions: \'%s\'.',
								$controller->request->params['controller'],
								$controller->request->params['action'],
								implode(', ', (array)$tData['allowedActions'])
							)
						);
					}
				} else {
					throw new AuthSecurityException('\'_Token\' was not found in session.');
				}
			}
		}
		return true;
	}

/**
 * Validate submitted form
 *
 * @param Controller $controller Instantiating controller
 * @throws AuthSecurityException
 * @return bool true if submitted form is valid
 */
	protected function _validatePost(Controller $controller) {
		$token = $this->_validToken($controller);
		$hashParts = $this->_hashParts($controller);
		$check = Security::hash(implode('', $hashParts), 'sha1');

		if ($token === $check) {
			return true;
		}

		$msg = self::DEFAULT_EXCEPTION_MESSAGE;
		if (Configure::read('debug')) {
			$msg = $this->_debugPostTokenNotMatching($controller, $hashParts);
		}

		throw new AuthSecurityException($msg);
	}

/**
 * Check if token is valid
 *
 * @param Controller $controller Instantiating controller
 * @throws AuthSecurityException
 * @throws SecurityException
 * @return string fields token
 */
	protected function _validToken(Controller $controller) {
		$check = $controller->request->data;

		$message = '\'%s\' was not found in request data.';
		if (!isset($check['_Token'])) {
			throw new AuthSecurityException(sprintf($message, '_Token'));
		}
		if (!isset($check['_Token']['fields'])) {
			throw new AuthSecurityException(sprintf($message, '_Token.fields'));
		}
		if (!isset($check['_Token']['unlocked'])) {
			throw new AuthSecurityException(sprintf($message, '_Token.unlocked'));
		}
		if (Configure::read('debug') && !isset($check['_Token']['debug'])) {
			throw new SecurityException(sprintf($message, '_Token.debug'));
		}
		if (!Configure::read('debug') && isset($check['_Token']['debug'])) {
			throw new SecurityException('Unexpected \'_Token.debug\' found in request data');
		}

		$token = urldecode($check['_Token']['fields']);
		if (strpos($token, ':')) {
			list($token, ) = explode(':', $token, 2);
		}

		return $token;
	}

/**
 * Return hash parts for the Token generation
 *
 * @param Controller $controller Instantiating controller
 * @return array
 */
	protected function _hashParts(Controller $controller) {
		$fieldList = $this->_fieldsList($controller->request->data);
		$unlocked = $this->_sortedUnlocked($controller->request->data);

		return array(
			$controller->request->here(),
			serialize($fieldList),
			$unlocked,
			Configure::read('Security.salt')
		);
	}

/**
 * Return the fields list for the hash calculation
 *
 * @param array $check Data array
 * @return array
 */
	protected function _fieldsList(array $check) {
		$locked = '';
		$token = urldecode($check['_Token']['fields']);
		$unlocked = $this->_unlocked($check);

		if (strpos($token, ':')) {
			list($token, $locked) = explode(':', $token, 2);
		}
		unset($check['_Token'], $check['_csrfToken']);

		$locked = explode('|', $locked);
		$unlocked = explode('|', $unlocked);

		$fields = Hash::flatten($check);
		$fieldList = array_keys($fields);
		$multi = $lockedFields = array();
		$isUnlocked = false;

		foreach ($fieldList as $i => $key) {
			if (preg_match('/(\.\d+){1,10}$/', $key)) {
				$multi[$i] = preg_replace('/(\.\d+){1,10}$/', '', $key);
				unset($fieldList[$i]);
			} else {
				$fieldList[$i] = (string)$key;
			}
		}
		if (!empty($multi)) {
			$fieldList += array_unique($multi);
		}

		$unlockedFields = array_unique(
			array_merge((array)$this->disabledFields, (array)$this->unlockedFields, $unlocked)
		);

		foreach ($fieldList as $i => $key) {
			$isLocked = (is_array($locked) && in_array($key, $locked));

			if (!empty($unlockedFields)) {
				foreach ($unlockedFields as $off) {
					$off = explode('.', $off);
					$field = array_values(array_intersect(explode('.', $key), $off));
					$isUnlocked = ($field === $off);
					if ($isUnlocked) {
						break;
					}
				}
			}

			if ($isUnlocked || $isLocked) {
				unset($fieldList[$i]);
				if ($isLocked) {
					$lockedFields[$key] = $fields[$key];
				}
			}
		}
		sort($fieldList, SORT_STRING);
		ksort($lockedFields, SORT_STRING);
		$fieldList += $lockedFields;

		return $fieldList;
	}

/**
 * Get the unlocked string
 *
 * @param array $data Data array
 * @return string
 */
	protected function _unlocked(array $data) {
		return urldecode($data['_Token']['unlocked']);
	}

/**
 * Get the sorted unlocked string
 *
 * @param array $data Data array
 * @return string
 */
	protected function _sortedUnlocked($data) {
		$unlocked = $this->_unlocked($data);
		$unlocked = explode('|', $unlocked);
		sort($unlocked, SORT_STRING);

		return implode('|', $unlocked);
	}

/**
 * Create a message for humans to understand why Security token is not matching
 *
 * @param Controller $controller Instantiating controller
 * @param array $hashParts Elements used to generate the Token hash
 * @return string Message explaining why the tokens are not matching
 */
	protected function _debugPostTokenNotMatching(Controller $controller, $hashParts) {
		$messages = array();
		$expectedParts = json_decode(urldecode($controller->request->data['_Token']['debug']), true);
		if (!is_array($expectedParts) || count($expectedParts) !== 3) {
			return 'Invalid security debug token.';
		}
		$expectedUrl = Hash::get($expectedParts, 0);
		$url = Hash::get($hashParts, 0);
		if ($expectedUrl !== $url) {
			$messages[] = sprintf('URL mismatch in POST data (expected \'%s\' but found \'%s\')', $expectedUrl, $url);
		}
		$expectedFields = Hash::get($expectedParts, 1);
		$dataFields = Hash::get($hashParts, 1);
		if ($dataFields) {
			$dataFields = unserialize($dataFields);
		}
		$fieldsMessages = $this->_debugCheckFields(
			$dataFields,
			$expectedFields,
			'Unexpected field \'%s\' in POST data',
			'Tampered field \'%s\' in POST data (expected value \'%s\' but found \'%s\')',
			'Missing field \'%s\' in POST data'
		);
		$expectedUnlockedFields = Hash::get($expectedParts, 2);
		$dataUnlockedFields = Hash::get($hashParts, 2) ?: array();
		if ($dataUnlockedFields) {
			$dataUnlockedFields = explode('|', $dataUnlockedFields);
		}
		$unlockFieldsMessages = $this->_debugCheckFields(
			$dataUnlockedFields,
			$expectedUnlockedFields,
			'Unexpected unlocked field \'%s\' in POST data',
			null,
			'Missing unlocked field: \'%s\''
		);

		$messages = array_merge($messages, $fieldsMessages, $unlockFieldsMessages);

		return implode(', ', $messages);
	}

/**
 * Iterates data array to check against expected
 *
 * @param array $dataFields Fields array, containing the POST data fields
 * @param array $expectedFields Fields array, containing the expected fields we should have in POST
 * @param string $intKeyMessage Message string if unexpected found in data fields indexed by int (not protected)
 * @param string $stringKeyMessage Message string if tampered found in data fields indexed by string (protected)
 * @param string $missingMessage Message string if missing field
 * @return array Messages
 */
	protected function _debugCheckFields($dataFields, $expectedFields = array(), $intKeyMessage = '', $stringKeyMessage = '', $missingMessage = '') {
		$messages = $this->_matchExistingFields($dataFields, $expectedFields, $intKeyMessage, $stringKeyMessage);
		$expectedFieldsMessage = $this->_debugExpectedFields($expectedFields, $missingMessage);
		if ($expectedFieldsMessage !== null) {
			$messages[] = $expectedFieldsMessage;
		}

		return $messages;
	}

/**
 * Manually add CSRF token information into the provided request object.
 *
 * @param CakeRequest $request The request object to add into.
 * @return bool
 */
	public function generateToken(CakeRequest $request) {
		if (isset($request->params['requested']) && $request->params['requested'] === 1) {
			if ($this->Session->check('_Token')) {
				$request->params['_Token'] = $this->Session->read('_Token');
			}
			return false;
		}
		$authKey = hash('sha512', Security::randomBytes(16), false);
		$token = array(
			'key' => $authKey,
			'allowedControllers' => $this->allowedControllers,
			'allowedActions' => $this->allowedActions,
			'unlockedFields' => array_merge($this->disabledFields, $this->unlockedFields),
			'csrfTokens' => array()
		);

		$tokenData = array();
		if ($this->Session->check('_Token')) {
			$tokenData = $this->Session->read('_Token');
			if (!empty($tokenData['csrfTokens']) && is_array($tokenData['csrfTokens'])) {
				$token['csrfTokens'] = $this->_expireTokens($tokenData['csrfTokens']);
			}
		}
		if ($this->csrfUseOnce || empty($token['csrfTokens'])) {
			$token['csrfTokens'][$authKey] = strtotime($this->csrfExpires);
		}
		if (!$this->csrfUseOnce) {
			$csrfTokens = array_keys($token['csrfTokens']);
			$authKey = $csrfTokens[0];
			$token['key'] = $authKey;
			$token['csrfTokens'][$authKey] = strtotime($this->csrfExpires);
		}
		$this->Session->write('_Token', $token);
		$request->params['_Token'] = array(
			'key' => $token['key'],
			'unlockedFields' => $token['unlockedFields']
		);
		return true;
	}

/**
 * Validate that the controller has a CSRF token in the POST data
 * and that the token is legit/not expired. If the token is valid
 * it will be removed from the list of valid tokens.
 *
 * @param Controller $controller A controller to check
 * @throws SecurityException
 * @return bool Valid csrf token.
 */
	protected function _validateCsrf(Controller $controller) {
		$token = $this->Session->read('_Token');
		$requestToken = $controller->request->data('_Token.key');

		if (!$requestToken) {
			throw new SecurityException('Missing CSRF token');
		}

		if (!isset($token['csrfTokens'][$requestToken])) {
			throw new SecurityException('CSRF token mismatch');
		}

		if ($token['csrfTokens'][$requestToken] < time()) {
			throw new SecurityException('CSRF token expired');
		}

		if ($this->csrfUseOnce) {
			$this->Session->delete('_Token.csrfTokens.' . $requestToken);
		}
		return true;
	}

/**
 * Expire CSRF nonces and remove them from the valid tokens.
 * Uses a simple timeout to expire the tokens.
 *
 * @param array $tokens An array of nonce => expires.
 * @return array An array of nonce => expires.
 */
	protected function _expireTokens($tokens) {
		$now = time();
		foreach ($tokens as $nonce => $expires) {
			if ($expires < $now) {
				unset($tokens[$nonce]);
			}
		}
		$overflow = count($tokens) - $this->csrfLimit;
		if ($overflow > 0) {
			$tokens = array_slice($tokens, $overflow + 1, null, true);
		}
		return $tokens;
	}

/**
 * Calls a controller callback method
 *
 * @param Controller $controller Controller to run callback on
 * @param string $method Method to execute
 * @param array $params Parameters to send to method
 * @return mixed Controller callback method's response
 * @throws BadRequestException When a the blackholeCallback is not callable.
 */
	protected function _callback(Controller $controller, $method, $params = array()) {
		if (!is_callable(array($controller, $method))) {
			throw new BadRequestException(__d('cake_dev', 'The request has been black-holed'));
		}
		return call_user_func_array(array(&$controller, $method), empty($params) ? null : $params);
	}

/**
 * Generate array of messages for the existing fields in POST data, matching dataFields in $expectedFields
 * will be unset
 *
 * @param array $dataFields Fields array, containing the POST data fields
 * @param array &$expectedFields Fields array, containing the expected fields we should have in POST
 * @param string $intKeyMessage Message string if unexpected found in data fields indexed by int (not protected)
 * @param string $stringKeyMessage Message string if tampered found in data fields indexed by string (protected)
 * @return array Error messages
 */
	protected function _matchExistingFields($dataFields, &$expectedFields, $intKeyMessage, $stringKeyMessage) {
		$messages = array();
		foreach ((array)$dataFields as $key => $value) {
			if (is_int($key)) {
				$foundKey = array_search($value, (array)$expectedFields);
				if ($foundKey === false) {
					$messages[] = sprintf($intKeyMessage, $value);
				} else {
					unset($expectedFields[$foundKey]);
				}
			} elseif (is_string($key)) {
				if (isset($expectedFields[$key]) && $value !== $expectedFields[$key]) {
					$messages[] = sprintf($stringKeyMessage, $key, $expectedFields[$key], $value);
				}
				unset($expectedFields[$key]);
			}
		}

		return $messages;
	}

/**
 * Generate debug message for the expected fields
 *
 * @param array $expectedFields Expected fields
 * @param string $missingMessage Message template
 * @return string Error message about expected fields
 */
	protected function _debugExpectedFields($expectedFields = array(), $missingMessage = '') {
		if (count($expectedFields) === 0) {
			return null;
		}

		$expectedFieldNames = array();
		foreach ((array)$expectedFields as $key => $expectedField) {
			if (is_int($key)) {
				$expectedFieldNames[] = $expectedField;
			} else {
				$expectedFieldNames[] = $key;
			}
		}

		return sprintf($missingMessage, implode(', ', $expectedFieldNames));
	}

}
