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
namespace Cake\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Describe the interface between a controller
 * and the surrounding Http libraries.
 */
interface ControllerInterface
{
    /**
     * Perform the startup process for this controller.
     * Fire the Components and Controller callbacks in the correct order.
     *
     * - Initializes components, which fires their `initialize` callback
     * - Calls the controller `beforeFilter`.
     * - triggers Component `startup` methods.
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function startupProcess(): ?ResponseInterface;

    /**
     * Perform the various shutdown processes for this controller.
     * Fire the Components and Controller callbacks in the correct order.
     *
     * - triggers the component `shutdown` callback.
     * - calls the Controller's `afterFilter` method.
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function shutdownProcess(): ?ResponseInterface;

    /**
     * Dispatches the controller action. Checks that the action
     * exists and isn't private.
     *
     * @return \Psr\Http\Message\ResponseInterface The resulting response.
     * @throws \Cake\Controller\Exception\MissingActionException If controller action is not found.
     * @throws \UnexpectedValueException If return value of action method is not null or ResponseInterface instance.
     */
    public function invokeAction(): ?ResponseInterface;

    /**
     * Gets the response instance.
     *
     * @return \Cake\Http\Response
     */
    public function getResponse(): Response;
}
