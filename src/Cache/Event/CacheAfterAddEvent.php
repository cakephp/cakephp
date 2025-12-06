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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Event;

use Cake\Cache\CacheEngine;
use Cake\Cache\Exception\InvalidArgumentException;
use Cake\Event\Event;
use DateInterval;

/**
 * Class Cache AfterAdd Event
 *
 * @extends \Cake\Event\Event<\Cake\Cache\CacheEngine>
 */
class CacheAfterAddEvent extends Event
{
    public const NAME = 'Cache.afterAdd';

    protected string $key;

    protected mixed $value = null;

    protected DateInterval|int|null $ttl = null;

    /**
     * Constructor
     *
     * @param string $name Name of the event
     * @param \Cake\Cache\CacheEngine $subject The Cache engine instance this event applies to.
     * @param array $data Any value you wish to be transported with this event to it can be read by listeners.
     */
    public function __construct(string $name, CacheEngine $subject, array $data = [])
    {
        if (isset($data['key'])) {
            $this->key = $data['key'];
            unset($data['key']);
        }
        if (isset($data['value'])) {
            $this->value = $data['value'];
            unset($data['value']);
        }
        if (isset($data['success'])) {
            $this->result = $data['success'];
            unset($data['success']);
        }
        if (isset($data['ttl'])) {
            $this->ttl = $data['ttl'];
            unset($data['ttl']);
        }

        parent::__construct($name, $subject, $data);
    }

    /**
     * The result value of the event listeners
     *
     * @return bool|null
     */
    public function getResult(): ?bool
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
        if ($value !== null && !is_bool($value)) {
            throw new InvalidArgumentException(
                'The result for CacheEngine events must be a `bool`.',
            );
        }

        return parent::setResult($value);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return \DateInterval|int|null
     */
    public function getTtl(): DateInterval|int|null
    {
        return $this->ttl;
    }
}
