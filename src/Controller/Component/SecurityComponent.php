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
     * - `requireAuth` - List of actions that require a valid authentication key.
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
     * @param Event $event An Event instance
     * @return mixed
     */
    public function startup(Event $event)
    {
        $controller = $event->subject();
        $this->session = $this->request->session();
        $this->_action = $this->request->params['action'];
        $this->_secureRequired($controller);
        $this->_authRequired($controller);

        $isPost = $this->request->is(['post', 'put']);
        $isNotRequestAction = (
            !isset($controller->request->params['requested']) ||
            $controller->request->params['requested'] != 1
        );

        if ($this->_action === $this->_config['blackHoleCallback']) {
            return $this->blackHole($controller, 'auth');
        }

        if (!in_array($this->_action, (array)$this->_config['unlockedActions']) &&
            $isPost && $isNotRequestAction
        ) {
            if ($this->_config['validatePost'] &&
                $this->_validatePost($controller) === false
            ) {
                return $this->blackHole($controller, 'auth');
            }
        }
        $this->generateToken($controller->request);
        if ($isPost && is_array($controller->request->data)) {
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
     */
    public function requireAuth($actions)
    {
        $this->_requireMethod('Auth', (array)$actions);
    }

    /**
     * Black-hole an invalid request with a 400 error or custom callback. If SecurityComponent::$blackHoleCallback
     * is specified, it will use this callback by executing the method indicated in $error
     *
     * @param Controller $controller Instantiating controller
     * @param string $error Error method
     * @return mixed If specified, controller blackHoleCallback's response, or no return otherwise
     * @see SecurityComponent::$blackHoleCallback
     * @link http://book.cakephp.org/3.0/en/controllers/components/security.html#handling-blackhole-callbacks
     * @throws \Cake\Network\Exception\BadRequestException
     */
    public function blackHole(Controller $controller, $error = '')
    {
        if (!$this->_config['blackHoleCallback']) {
            throw new BadRequestException('The request has been black-holed');
        }
        return $this->_callback($controller, $this->_config['blackHoleCallback'], [$error]);
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
     * @param Controller $controller Instantiating controller
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
                    if (!$this->blackHole($controller, 'secure')) {
                        return null;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Check if authentication is required
     *
     * @param Controller $controller Instantiating controller
     * @return bool true if authentication required
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
                    if (!$this->blackHole($controller, 'auth')) {
                        return false;
                    }
                }

                if ($this->session->check('_Token')) {
                    $tData = $this->session->read('_Token');

                    if (!empty($tData['allowedControllers']) &&
                        !in_array($this->request->params['controller'], $tData['allowedControllers']) ||
                        !empty($tData['allowedActions']) &&
                        !in_array($this->request->params['action'], $tData['allowedActions'])
                    ) {
                        if (!$this->blackHole($controller, 'auth')) {
                            return false;
                        }
                    }
                } else {
                    if (!$this->blackHole($controller, 'auth')) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Validate submitted form
     *
     * @param Controller $controller Instantiating controller
     * @return bool true if submitted form is valid
     */
    protected function _validatePost(Controller $controller)
    {
        if (empty($controller->request->data)) {
            return true;
        }
        $check = $controller->request->data;

        if (!isset($check['_Token']) ||
            !isset($check['_Token']['fields']) ||
            !isset($check['_Token']['unlocked'])
        ) {
            return false;
        }

        $locked = '';
        $token = urldecode($check['_Token']['fields']);
        $unlocked = urldecode($check['_Token']['unlocked']);

        if (strpos($token, ':')) {
            list($token, $locked) = explode(':', $token, 2);
        }
        unset($check['_Token'], $check['_csrfToken']);

        $locked = explode('|', $locked);
        $unlocked = explode('|', $unlocked);

        $lockedFields = [];
        $fields = Hash::flatten($check);
        $fieldList = array_keys($fields);
        $multi = [];

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
        sort($unlocked, SORT_STRING);
        sort($fieldList, SORT_STRING);
        ksort($lockedFields, SORT_STRING);

        $fieldList += $lockedFields;
        $unlocked = implode('|', $unlocked);
        $hashParts = [
            $controller->request->here(),
            serialize($fieldList),
            $unlocked,
            Security::salt()
        ];
        $check = Security::hash(implode('', $hashParts), 'sha1');
        return ($token === $check);
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
     * @param Controller $controller Controller to run callback on
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
