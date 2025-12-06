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
use Cake\Event\Event;

/**
 * Class Cache BeforeDelete Event
 *
 * @extends \Cake\Event\Event<\Cake\Cache\CacheEngine>
 */
class CacheBeforeDeleteEvent extends Event
{
    public const NAME = 'Cache.beforeDelete';

    protected string $key;

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

        parent::__construct($name, $subject, $data);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }
}
