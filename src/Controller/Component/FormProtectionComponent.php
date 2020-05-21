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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Form\FormProtector;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\Routing\Router;
use Closure;

/**
 * Protects against form tampering. It ensures that:
 *
 * - Form's action (URL) is not modified.
 * - Unknown / extra fields are not added to the form.
 * - Existing fields have not been removed from the form.
 * - Values of hidden inputs have not been changed.
 *
 * @psalm-property array{validate:bool, unlockedFields:array, unlockedActions:array, validationFailureCallback:?\Closure} $_config
 */
class FormProtectionComponent extends Component
{
    /**
     * Default message used for exceptions thrown.
     *
     * @var string
     */
    public const DEFAULT_EXCEPTION_MESSAGE = 'Form tampering protection token validation failed.';

    /**
     * Default config
     *
     * - `validate` - Whether to validate request body / data. Set to false to disable
     *   for data coming from 3rd party services, etc.
     * - `unlockedFields` - Form fields to exclude from validation. Fields can
     *   be unlocked either in the Component, or with FormHelper::unlockField().
     *   Fields that have been unlocked are not required to be part of the POST
     *   and hidden unlocked fields do not have their values checked.
     * - `unlockedActions` - Actions to exclude from POST validation checks.
     * - `validationFailureCallback` - Callback to call in case of validation
     *   failure. Must be a valid Closure. Unset by default in which case
     *   exception is thrown on validation failure.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'validate' => true,
        'unlockedFields' => [],
        'unlockedActions' => [],
        'validationFailureCallback' => null,
    ];

    /**
     * Component startup.
     *
     * Token check happens here.
     *
     * @param \Cake\Event\EventInterface $event An Event instance
     * @return \Cake\Http\Response|null
     */
    public function startup(EventInterface $event): ?Response
    {
        $request = $this->getController()->getRequest();
        $data = $request->getParsedBody();
        $hasData = ($data || $request->is(['put', 'post', 'delete', 'patch']));

        if (
            !in_array($request->getParam('action'), $this->_config['unlockedActions'], true)
            && $hasData
            && $this->_config['validate']
        ) {
            $session = $request->getSession();
            $session->start();
            $url = Router::url($request->getRequestTarget());

            $formProtector = new FormProtector($this->_config);
            $isValid = $formProtector->validate($data, $url, $session->id());

            if (!$isValid) {
                return $this->validationFailure($formProtector);
            }
        }

        $token = [
            'unlockedFields' => $this->_config['unlockedFields'],
        ];
        $request = $request->withAttribute('formTokenData', [
            'unlockedFields' => $token['unlockedFields'],
        ]);

        if (is_array($data)) {
            unset($data['_Token']);
            $request = $request->withParsedBody($data);
        }

        $this->getController()->setRequest($request);

        return null;
    }

    /**
     * Events supported by this component.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [
            'Controller.startup' => 'startup',
        ];
    }

    /**
     * Throws a 400 - Bad request exception or calls custom callback.
     *
     * If `validationFailureCallback` config is specified, it will use this
     * callback by executing the method passing the argument as exception.
     *
     * @param \Cake\Form\FormProtector $formProtector Form Protector instance.
     * @return \Cake\Http\Response|null If specified, validationFailureCallback's response, or no return otherwise.
     * @throws \Cake\Http\Exception\BadRequestException
     */
    protected function validationFailure(FormProtector $formProtector): ?Response
    {
        if (Configure::read('debug')) {
            $exception = new BadRequestException($formProtector->getError());
        } else {
            $exception = new BadRequestException(static::DEFAULT_EXCEPTION_MESSAGE);
        }

        if ($this->_config['validationFailureCallback']) {
            return $this->executeCallback($this->_config['validationFailureCallback'], $exception);
        }

        throw $exception;
    }

    /**
     * Execute callback.
     *
     * @param \Closure $callback A valid callable
     * @param \Cake\Http\Exception\BadRequestException $exception Exception instance.
     * @return \Cake\Http\Response|null
     */
    protected function executeCallback(Closure $callback, BadRequestException $exception): ?Response
    {
        return $callback($exception);
    }
}
