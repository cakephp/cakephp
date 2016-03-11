<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.10.8
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Controller\Exception\AuthSecurityException;
use Cake\Controller\Exception\SecurityException;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Exception\BadRequestException;
use Cake\Network\Request;
use Cake\Utility\Hash;
use Cake\Utility\Security;

/**
 * The Security Component creates an easy way to integrate tighter security in
 * your application. It provides methods for various tasks like:
 *
 * - Restricting which HTTP methods your application accepts.
 * - Form tampering protection
 * - Requiring that SSL be used.
 * - Limiting cross controller communication.
 *
 * @link http://book.cakephp.org/3.0/en/controllers/components/security.html
 */
class SecurityComponent extends Component
{

    /**
     * Default config
     *
     * - `blackHoleCallback` - The controller method that will be called if this
     *   request is black-hole'd.
     * - `requireSecure` - List of actions that require an SSL-secured connection.
     * - `requireAuth` - List of actions that require a valid authentication key. Deprecated as of 3.2.2
     * - `allowedControllers` - Controllers from which actions of the current
     *   controller are allowed to receive requests.
     * - `allowedActions` - Actions from which actions of the current controller
     *   are allowed to receive requests.
     * - `unlockedFields` - Form fields to exclude from POST validation. Fields can
     *   be unlocked either in the Component, or with FormHelper::unlockField().
     *   Fields that have been unlocked are not required to be part of the POST
     *   and hidden unlocked fields do not have their values checked.
     * - `unlockedActions` - Actions to exclude from POST validation checks.
     *   Other checks like requireAuth(), requireSecure() etc. will still be applied.
     * - `validatePost` -  Whether to validate POST data. Set to false to disable
     *   for data coming from 3rd party services, etc.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'blackHoleCallback' => null,
        'requireSecure' => [],
        'requireAuth' => [],
        'allowedControllers' => [],
        'allowedActions' => [],
        'unlockedFields' => [],
        'unlockedActions' => [],
        'validatePost' => true
    ];

    /**
     * Holds the current action of the controller
     *
     * @var string
     */
    protected $_action = null;

    /**
     * Request object
     *
     * @var \Cake\Network\Request
     */
    public $request;

    /**
     * The Session object
     *
     * @var \Cake\Network\Session
     */
    public $session;

    /**
     * Component startup. All security checking happens here.
     *
     * @param \Cake\Event\Event $event An Event instance
     * @return mixed
     */
    public function startup(Event $event)
    {
        $controller = $event->subject();
        $this->session = $this->request->session();
        $this->_action = $this->request->params['action'];
        $hasData = !empty($this->request->data);
        try {
            $this->_secureRequired($controller);
            $this->_authRequired($controller);

            $isNotRequestAction = (
                !isset($controller->request->params['requested']) ||
                $controller->request->params['requested'] != 1
            );

            if ($this->_action === $this->_config['blackHoleCallback']) {
                throw new AuthSecurityException(sprintf('Action %s is defined as the blackhole callback.', $this->_action));
            }

            if (!in_array($this->_action, (array)$this->_config['unlockedActions']) &&
                $hasData && $isNotRequestAction
            ) {
                if ($this->_config['validatePost']) {
                    $this->_validatePost($controller);
                }
            }
        } catch (SecurityException $se) {
            $this->blackHole($controller, $se->getType(), $se);
        }

        $this->generateToken($controller->request);
        if ($hasData && is_array($controller->request->data)) {
            unset($controller->request->data['_Token']);
        }
    }

    /**
     * Events supported by this component.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Controller.startup' => 'startup',
        ];
    }

    /**
     * Sets the actions that require a request that is SSL-secured, or empty for all actions
     *
     * @param string|array $actions Actions list
     * @return void
     */
    public function requireSecure($actions = null)
    {
        $this->_requireMethod('Secure', (array)$actions);
    }

    /**
     * Sets the actions that require whitelisted form submissions.
     *
     * Adding actions with this method will enforce the restrictions
     * set in SecurityComponent::$allowedControllers and
     * SecurityComponent::$allowedActions.
     *
     * @param string|array $actions Actions list
     * @return void
     * @deprecated 3.2.2 This feature is confusing and not useful.
     */
    public function requireAuth($actions)
    {
        $this->_requireMethod('Auth', (array)$actions);
    }

    /**
     * Black-hole an invalid request with a 400 error or custom callback. If SecurityComponent::$blackHoleCallback
     * is specified, it will use this callback by executing the method indicated in $error
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @param string $error Error method
     * @param \Cake\Controller\Exception\SecurityException $exception Additional debug info describing the cause,
     * debug mode only
     * @return mixed If specified, controller blackHoleCallback's response, or no return otherwise
     * @see SecurityComponent::$blackHoleCallback
     * @link http://book.cakephp.org/3.0/en/controllers/components/security.html#handling-blackhole-callbacks
     * @throws \Cake\Network\Exception\BadRequestException
     */
    public function blackHole(Controller $controller, $error = '', SecurityException $exception = null)
    {
        if (!$this->_config['blackHoleCallback']) {
            if (Configure::read('debug') && $exception !== null) {
                throw $exception;
            }
            throw new BadRequestException('The request has been black-holed');
        }
        return $this->_callback($controller, $this->_config['blackHoleCallback'], [$error, $exception]);
    }

    /**
     * Sets the actions that require a $method HTTP request, or empty for all actions
     *
     * @param string $method The HTTP method to assign controller actions to
     * @param array $actions Controller actions to set the required HTTP method to.
     * @return void
     */
    protected function _requireMethod($method, $actions = [])
    {
        if (isset($actions[0]) && is_array($actions[0])) {
            $actions = $actions[0];
        }
        $this->config('require' . $method, (empty($actions)) ? ['*'] : $actions);
    }

    /**
     * Check if access requires secure connection
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @return bool true if secure connection required
     */
    protected function _secureRequired(Controller $controller)
    {
        if (is_array($this->_config['requireSecure']) &&
            !empty($this->_config['requireSecure'])
        ) {
            $requireSecure = $this->_config['requireSecure'];

            if (in_array($this->_action, $requireSecure) || $requireSecure === ['*']) {
                if (!$this->request->is('ssl')) {
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
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @return bool true if authentication required
     * @deprecated 3.2.2 This feature is confusing and not useful.
     */
    protected function _authRequired(Controller $controller)
    {
        if (is_array($this->_config['requireAuth']) &&
            !empty($this->_config['requireAuth']) &&
            !empty($this->request->data)
        ) {
            $requireAuth = $this->_config['requireAuth'];

            if (in_array($this->request->params['action'], $requireAuth) || $requireAuth == ['*']) {
                if (!isset($controller->request->data['_Token'])) {
                    throw new AuthSecurityException(sprintf('\'%s\' was not found in request data.', '_Token'));
                }

                if ($this->session->check('_Token')) {
                    $tData = $this->session->read('_Token');

                    if (!empty($tData['allowedControllers']) &&
                        !in_array($this->request->params['controller'], $tData['allowedControllers'])) {
                        throw new AuthSecurityException(
                            sprintf(
                                'Controller \'%s\' was not found in allowed controllers: \'%s\'.',
                                $this->request->params['controller'],
                                implode(', ', (array)$tData['allowedControllers'])
                            )
                        );
                    }
                    if (!empty($tData['allowedActions']) &&
                        !in_array($this->request->params['action'], $tData['allowedActions'])
                    ) {
                        throw new AuthSecurityException(
                            sprintf(
                                'Action \'%s::%s\' was not found in allowed actions: \'%s\'.',
                                $this->request->params['controller'],
                                $this->request->params['action'],
                                implode(', ', (array)$tData['allowedActions'])
                            )
                        );
                    }
                } else {
                    throw new AuthSecurityException(sprintf('\'%s\' was not found in session.', '_Token'));
                }
            }
        }
        return true;
    }

    /**
     * Validate submitted form
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @throws \Cake\Controller\Exception\SecurityException
     * @return bool true if submitted form is valid
     */
    protected function _validatePost(Controller $controller)
    {
        if (empty($controller->request->data)) {
            return true;
        }
        $token = $this->_validToken($controller);
        $hashParts = $this->_hashParts($controller);
        $check = Security::hash(implode('', $hashParts), 'sha1');

        if ($token === $check) {
            return true;
        }

        if (Configure::read('debug')) {
            $msg = $this->_debugPostTokenNotMatching($controller, $hashParts);
            throw new SecurityException($msg);
        }

        return false;
    }

    /**
     * Check if token is valid
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @throws \Cake\Controller\Exception\SecurityException
     * @return String fields token
     */
    protected function _validToken(Controller $controller)
    {
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

        $token = urldecode($check['_Token']['fields']);
        if (strpos($token, ':')) {
            list($token, $locked) = explode(':', $token, 2);
        }
        return $token;
    }

    /**
     * Return hash parts for the Token generation
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @return array
     */
    protected function _hashParts(Controller $controller)
    {
        $fieldList = $this->_fieldsList($controller->request->data);
        $unlocked = $this->_unlocked($controller->request->data);
        return [
            $controller->request->here(),
            serialize($fieldList),
            $unlocked,
            Security::salt()
        ];
    }

    /**
     * Return the fields list for the hash calculation
     *
     * @param array $check Data array
     * @return array
     */
    protected function _fieldsList(array $check)
    {
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
        $multi = $lockedFields = [];
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
            array_merge((array)$this->config('disabledFields'), (array)$this->_config['unlockedFields'], $unlocked)
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
     * @param array $check Data array
     * @return string
     */
    protected function _unlocked(array $check)
    {
        return urldecode($check['_Token']['unlocked']);
    }

    /**
     * Create a message for humans to understand why Security token is not matching
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @param array $hashParts Elements used to generate the Token hash
     * @return array Messages to explain why token is not matching
     */
    protected function _debugPostTokenNotMatching(Controller $controller, $hashParts)
    {
        $messages = [];
        $expectedParts = json_decode(urldecode($controller->request->data['_Token']['debug']), true);
        if (!is_array($expectedParts) || count($expectedParts) !== 3) {
            return 'Invalid security debug token.';
        }
        if ($hashParts[0] !== $expectedParts[0]) {
            $messages[] = sprintf('URL mismatch in POST data (expected \'%s\' but found \'%s\')', $expectedParts[0], $hashParts[0]);
        }
        $expectedFields = $expectedParts[1];
        $dataFields = unserialize($hashParts[1]);
        $fieldsMessages = $this->_debugCheckFields(
            $dataFields,
            $expectedFields,
            'Unexpected field \'%s\' in POST data',
            'Tampered field \'%s\' in POST data (expected value \'%s\' but found \'%s\')',
            'Missing field \'%s\' in POST data'
        );
        $expectedUnlockedFields = Hash::get($expectedParts, 2);
        $dataUnlockedFields = Hash::get($hashParts, 2) ?: [];
        if (!empty($dataUnlockedFields)) {
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
    protected function _debugCheckFields($dataFields, $expectedFields = [], $intKeyMessage = '', $stringKeyMessage = '', $missingMessage = '')
    {
        $messages = [];
        foreach ($dataFields as $key => $value) {
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
        if (count($expectedFields) > 0) {
            $messages[] = sprintf($missingMessage, implode(', ', $expectedFields));
        }
        return $messages;
    }

    /**
     * Manually add form tampering prevention token information into the provided
     * request object.
     *
     * @param \Cake\Network\Request $request The request object to add into.
     * @return bool
     */
    public function generateToken(Request $request)
    {
        if (isset($request->params['requested']) && $request->params['requested'] === 1) {
            if ($this->session->check('_Token')) {
                $request->params['_Token'] = $this->session->read('_Token');
            }
            return false;
        }
        $token = [
            'allowedControllers' => $this->_config['allowedControllers'],
            'allowedActions' => $this->_config['allowedActions'],
            'unlockedFields' => $this->_config['unlockedFields'],
        ];

        $this->session->write('_Token', $token);
        $request->params['_Token'] = [
            'unlockedFields' => $token['unlockedFields']
        ];
        return true;
    }

    /**
     * Calls a controller callback method
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @param string $method Method to execute
     * @param array $params Parameters to send to method
     * @return mixed Controller callback method's response
     * @throws \Cake\Network\Exception\BadRequestException When a the blackholeCallback is not callable.
     */
    protected function _callback(Controller $controller, $method, $params = [])
    {
        if (!is_callable([$controller, $method])) {
            throw new BadRequestException('The request has been black-holed');
        }
        return call_user_func_array([&$controller, $method], empty($params) ? null : $params);
    }
}
