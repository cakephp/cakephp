<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Controller\Exception\AuthSecurityException;
use Cake\Controller\Exception\SecurityException;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Cake\Utility\Security;

/**
 * The Security Component creates an easy way to integrate tighter security in
 * your application. It provides methods for these tasks:
 *
 * - Form tampering protection.
 * - Requiring that SSL be used.
 *
 * @link https://book.cakephp.org/4/en/controllers/components/security.html
 * @deprecated 4.0.0 Use {@link FormProtectionComponent} instead, for form tampering protection
 *   or {@link HttpsEnforcerMiddleware} to enforce use of HTTPS (SSL) for requests.
 */
class SecurityComponent extends Component
{
    /**
     * Default message used for exceptions thrown
     *
     * @var string
     */
    public const DEFAULT_EXCEPTION_MESSAGE = 'The request has been black-holed';

    /**
     * Default config
     *
     * - `blackHoleCallback` - The controller method that will be called if this
     *   request is black-hole'd.
     * - `requireSecure` - List of actions that require an SSL-secured connection.
     * - `unlockedFields` - Form fields to exclude from POST validation. Fields can
     *   be unlocked either in the Component, or with FormHelper::unlockField().
     *   Fields that have been unlocked are not required to be part of the POST
     *   and hidden unlocked fields do not have their values checked.
     * - `unlockedActions` - Actions to exclude from POST validation checks.
     *   Other checks like requireSecure() etc. will still be applied.
     * - `validatePost` - Whether to validate POST data. Set to false to disable
     *   for data coming from 3rd party services, etc.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'blackHoleCallback' => null,
        'requireSecure' => [],
        'unlockedFields' => [],
        'unlockedActions' => [],
        'validatePost' => true,
    ];

    /**
     * Holds the current action of the controller
     *
     * @var string
     */
    protected $_action;

    /**
     * Component startup. All security checking happens here.
     *
     * @param \Cake\Event\EventInterface $event An Event instance
     * @return \Cake\Http\Response|null
     */
    public function startup(EventInterface $event): ?Response
    {
        /** @var \Cake\Controller\Controller $controller */
        $controller = $event->getSubject();
        $request = $controller->getRequest();
        $this->_action = $request->getParam('action');
        $hasData = ($request->getData() || $request->is(['put', 'post', 'delete', 'patch']));
        try {
            $this->_secureRequired($controller);

            if ($this->_action === $this->_config['blackHoleCallback']) {
                throw new AuthSecurityException(sprintf(
                    'Action %s is defined as the blackhole callback.',
                    $this->_action
                ));
            }

            if (
                !in_array($this->_action, (array)$this->_config['unlockedActions'], true) &&
                $hasData &&
                $this->_config['validatePost']
            ) {
                $this->_validatePost($controller);
            }
        } catch (SecurityException $se) {
            return $this->blackHole($controller, $se->getType(), $se);
        }

        $request = $this->generateToken($request);
        if ($hasData && is_array($controller->getRequest()->getData())) {
            $request = $request->withoutData('_Token');
        }
        $controller->setRequest($request);

        return null;
    }

    /**
     * Events supported by this component.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'Controller.startup' => 'startup',
        ];
    }

    /**
     * Sets the actions that require a request that is SSL-secured, or empty for all actions
     *
     * @param array<string>|string|null $actions Actions list
     * @return void
     */
    public function requireSecure($actions = null): void
    {
        $actions = (array)$actions;
        $this->setConfig('requireSecure', empty($actions) ? ['*'] : $actions);
    }

    /**
     * Black-hole an invalid request with a 400 error or custom callback. If SecurityComponent::$blackHoleCallback
     * is specified, it will use this callback by executing the method indicated in $error
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @param string $error Error method
     * @param \Cake\Controller\Exception\SecurityException|null $exception Additional debug info describing the cause
     * @return mixed If specified, controller blackHoleCallback's response, or no return otherwise
     * @see \Cake\Controller\Component\SecurityComponent::$blackHoleCallback
     * @link https://book.cakephp.org/4/en/controllers/components/security.html#handling-blackhole-callbacks
     * @throws \Cake\Http\Exception\BadRequestException
     */
    public function blackHole(Controller $controller, string $error = '', ?SecurityException $exception = null)
    {
        if (!$this->_config['blackHoleCallback']) {
            $this->_throwException($exception);
        }

        return $this->_callback($controller, $this->_config['blackHoleCallback'], [$error, $exception]);
    }

    /**
     * Check debug status and throw an Exception based on the existing one
     *
     * @param \Cake\Controller\Exception\SecurityException|null $exception Additional debug info describing the cause
     * @throws \Cake\Http\Exception\BadRequestException
     * @return void
     */
    protected function _throwException(?SecurityException $exception = null): void
    {
        if ($exception !== null) {
            if (!Configure::read('debug')) {
                $exception->setReason($exception->getMessage());
                $exception->setMessage(static::DEFAULT_EXCEPTION_MESSAGE);
            }
            throw $exception;
        }
        throw new BadRequestException(static::DEFAULT_EXCEPTION_MESSAGE);
    }

    /**
     * Check if access requires secure connection
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @return void
     * @throws \Cake\Controller\Exception\SecurityException
     */
    protected function _secureRequired(Controller $controller): void
    {
        if (
            empty($this->_config['requireSecure']) ||
            !is_array($this->_config['requireSecure'])
        ) {
            return;
        }

        $requireSecure = $this->_config['requireSecure'];
        if (
            ($requireSecure[0] === '*' ||
                in_array($this->_action, $requireSecure, true)
            ) &&
            !$controller->getRequest()->is('ssl')
        ) {
            throw new SecurityException(
                'Request is not SSL and the action is required to be secure'
            );
        }
    }

    /**
     * Validate submitted form
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @return void
     * @throws \Cake\Controller\Exception\AuthSecurityException
     */
    protected function _validatePost(Controller $controller): void
    {
        $token = $this->_validToken($controller);
        $hashParts = $this->_hashParts($controller);
        $check = hash_hmac('sha1', implode('', $hashParts), Security::getSalt());

        if (hash_equals($check, $token)) {
            return;
        }

        $msg = static::DEFAULT_EXCEPTION_MESSAGE;
        if (Configure::read('debug')) {
            $msg = $this->_debugPostTokenNotMatching($controller, $hashParts);
        }

        throw new AuthSecurityException($msg);
    }

    /**
     * Check if token is valid
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @throws \Cake\Controller\Exception\SecurityException
     * @return string fields token
     */
    protected function _validToken(Controller $controller): string
    {
        $check = $controller->getRequest()->getData();

        $message = '\'%s\' was not found in request data.';
        if (!isset($check['_Token'])) {
            throw new AuthSecurityException(sprintf($message, '_Token'));
        }
        if (!isset($check['_Token']['fields'])) {
            throw new AuthSecurityException(sprintf($message, '_Token.fields'));
        }
        if (!is_string($check['_Token']['fields'])) {
            throw new AuthSecurityException("'_Token.fields' is invalid.");
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
            [$token, ] = explode(':', $token, 2);
        }

        return $token;
    }

    /**
     * Return hash parts for the Token generation
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @return array<string>
     */
    protected function _hashParts(Controller $controller): array
    {
        $request = $controller->getRequest();

        // Start the session to ensure we get the correct session id.
        $session = $request->getSession();
        $session->start();

        $data = (array)$request->getData();
        $fieldList = $this->_fieldsList($data);
        $unlocked = $this->_sortedUnlocked($data);

        return [
            Router::url($request->getRequestTarget()),
            serialize($fieldList),
            $unlocked,
            $session->id(),
        ];
    }

    /**
     * Return the fields list for the hash calculation
     *
     * @param array $check Data array
     * @return array
     */
    protected function _fieldsList(array $check): array
    {
        $locked = '';
        $token = urldecode($check['_Token']['fields']);
        $unlocked = $this->_unlocked($check);

        if (strpos($token, ':')) {
            [, $locked] = explode(':', $token, 2);
        }
        unset($check['_Token']);

        $locked = $locked ? explode('|', $locked) : [];
        $unlocked = $unlocked ? explode('|', $unlocked) : [];

        $fields = Hash::flatten($check);
        $fieldList = array_keys($fields);
        $multi = $lockedFields = [];
        $isUnlocked = false;

        foreach ($fieldList as $i => $key) {
            if (is_string($key) && preg_match('/(\.\d+){1,10}$/', $key)) {
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
            array_merge(
                (array)$this->_config['unlockedFields'],
                $unlocked
            )
        );

        foreach ($fieldList as $i => $key) {
            $isLocked = in_array($key, $locked, true);

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
    protected function _unlocked(array $data): string
    {
        return urldecode($data['_Token']['unlocked']);
    }

    /**
     * Get the sorted unlocked string
     *
     * @param array $data Data array
     * @return string
     */
    protected function _sortedUnlocked(array $data): string
    {
        $unlocked = $this->_unlocked($data);
        $unlocked = explode('|', $unlocked);
        sort($unlocked, SORT_STRING);

        return implode('|', $unlocked);
    }

    /**
     * Create a message for humans to understand why Security token is not matching
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @param array<string> $hashParts Elements used to generate the Token hash
     * @return string Message explaining why the tokens are not matching
     */
    protected function _debugPostTokenNotMatching(Controller $controller, array $hashParts): string
    {
        $messages = [];
        $expectedParts = json_decode(urldecode($controller->getRequest()->getData('_Token.debug')), true);
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
            $dataFields = unserialize($dataFields, ['allowed_classes' => false]);
        }
        $fieldsMessages = $this->_debugCheckFields(
            $dataFields,
            $expectedFields,
            'Unexpected field \'%s\' in POST data',
            'Tampered field \'%s\' in POST data (expected value \'%s\' but found \'%s\')',
            'Missing field \'%s\' in POST data'
        );
        $expectedUnlockedFields = Hash::get($expectedParts, 2);
        $dataUnlockedFields = Hash::get($hashParts, 2) ?: null;
        if ($dataUnlockedFields) {
            $dataUnlockedFields = explode('|', $dataUnlockedFields);
        }
        $unlockFieldsMessages = $this->_debugCheckFields(
            (array)$dataUnlockedFields,
            $expectedUnlockedFields,
            'Unexpected unlocked field \'%s\' in POST data',
            '',
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
     * @param string $stringKeyMessage Message string if tampered found in
     *  data fields indexed by string (protected).
     * @param string $missingMessage Message string if missing field
     * @return array<string> Messages
     */
    protected function _debugCheckFields(
        array $dataFields,
        array $expectedFields = [],
        string $intKeyMessage = '',
        string $stringKeyMessage = '',
        string $missingMessage = ''
    ): array {
        $messages = $this->_matchExistingFields($dataFields, $expectedFields, $intKeyMessage, $stringKeyMessage);
        $expectedFieldsMessage = $this->_debugExpectedFields($expectedFields, $missingMessage);
        if ($expectedFieldsMessage !== null) {
            $messages[] = $expectedFieldsMessage;
        }

        return $messages;
    }

    /**
     * Manually add form tampering prevention token information into the provided
     * request object.
     *
     * @param \Cake\Http\ServerRequest $request The request object to add into.
     * @return \Cake\Http\ServerRequest The modified request.
     */
    public function generateToken(ServerRequest $request): ServerRequest
    {
        $token = [
            'unlockedFields' => $this->_config['unlockedFields'],
        ];

        return $request->withAttribute('formTokenData', [
            'unlockedFields' => $token['unlockedFields'],
        ]);
    }

    /**
     * Calls a controller callback method
     *
     * @param \Cake\Controller\Controller $controller Instantiating controller
     * @param string $method Method to execute
     * @param array $params Parameters to send to method
     * @return mixed Controller callback method's response
     * @throws \Cake\Http\Exception\BadRequestException When a the blackholeCallback is not callable.
     */
    protected function _callback(Controller $controller, string $method, array $params = [])
    {
        $callable = [$controller, $method];

        if (!is_callable($callable)) {
            throw new BadRequestException('The request has been black-holed');
        }

        return $callable(...$params);
    }

    /**
     * Generate array of messages for the existing fields in POST data, matching dataFields in $expectedFields
     * will be unset
     *
     * @param array $dataFields Fields array, containing the POST data fields
     * @param array $expectedFields Fields array, containing the expected fields we should have in POST
     * @param string $intKeyMessage Message string if unexpected found in data fields indexed by int (not protected)
     * @param string $stringKeyMessage Message string if tampered found in
     *   data fields indexed by string (protected)
     * @return array<string> Error messages
     */
    protected function _matchExistingFields(
        array $dataFields,
        array &$expectedFields,
        string $intKeyMessage,
        string $stringKeyMessage
    ): array {
        $messages = [];
        foreach ($dataFields as $key => $value) {
            if (is_int($key)) {
                $foundKey = array_search($value, $expectedFields, true);
                if ($foundKey === false) {
                    $messages[] = sprintf($intKeyMessage, $value);
                } else {
                    unset($expectedFields[$foundKey]);
                }
            } else {
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
     * @return string|null Error message about expected fields
     */
    protected function _debugExpectedFields(array $expectedFields = [], string $missingMessage = ''): ?string
    {
        if (count($expectedFields) === 0) {
            return null;
        }

        $expectedFieldNames = [];
        foreach ($expectedFields as $key => $expectedField) {
            if (is_int($key)) {
                $expectedFieldNames[] = $expectedField;
            } else {
                $expectedFieldNames[] = $key;
            }
        }

        return sprintf($missingMessage, implode(', ', $expectedFieldNames));
    }
}
