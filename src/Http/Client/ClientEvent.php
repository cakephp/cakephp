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
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Client;

use Cake\Event\Event;
use Cake\Http\Client;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;

/**
 * Class Client Event
 *
 * @extends \Cake\Event\Event<\Cake\Http\Client>
 */
class ClientEvent extends Event
{
    /**
     * Constructor
     *
     * @param string $name Name of the event
     * @param \Cake\Http\Client $subject The Http Client instance this event applies to.
     * @param array $data Any value you wish to be transported
     *   with this event to it can be read by listeners.
     */
    public function __construct(string $name, Client $subject, array $data = [])
    {
        if (isset($data['response'])) {
            $this->result = $data['response'];
            unset($data['response']);
        }

        parent::__construct($name, $subject, $data);
    }

    /**
     * The result value of the event listeners
     *
     * @return \Cake\Http\Client\Response|null
     */
    public function getResult(): ?Response
    {
        return $this->result;
    }

    /**
     * Listeners can attach a result value to the event.
     *
     * @param mixed $value The value to set.
     * @return $this
     */
    public function setResult(mixed $value = null)
    {
        if ($value !== null && !$value instanceof Response) {
            throw new InvalidArgumentException(
                'The result for Http Client events must be a `Cake\Http\Client\Response` instance.',
            );
        }

        return parent::setResult($value);
    }

    /**
     * Set request instance.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @return $this
     */
    public function setRequest(RequestInterface $request)
    {
        $this->_data['request'] = $request;

        return $this;
    }

    /**
     * Get the request instance.
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->_data['request'];
    }

    /**
     * Set the adapter options.
     *
     * @return $this
     */
    public function setAdapterOptions(array $options = [])
    {
        $this->_data['adapterOptions'] = $options;

        return $this;
    }

    /**
     * Get the adapter options.
     *
     * @return array
     */
    public function getAdapterOptions(): array
    {
        return $this->_data['adapterOptions'];
    }
}
