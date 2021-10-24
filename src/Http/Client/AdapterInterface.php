<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Client;

use Psr\Http\Message\RequestInterface;

/**
 * Http client adapter interface.
 */
interface AdapterInterface
{
    /**
     * Send a request and get a response back.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request object to send.
     * @param array<string, mixed> $options Array of options for the stream.
     * @return array<\Cake\Http\Client\Response> Array of populated Response objects
     */
    public function send(RequestInterface $request, array $options): array;
}
