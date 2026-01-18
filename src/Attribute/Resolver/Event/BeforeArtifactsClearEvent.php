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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Attribute\Resolver\Event;

use Cake\Attribute\Resolver;
use Cake\Event\Event;

/**
 * Event fired before artifact cache is cleared.
 *
 * This event is dispatched when clearArtifacts() is called, before any
 * artifact files are deleted or memory caches cleared.
 *
 * Stopping this event will prevent artifact clearing.
 *
 * @extends \Cake\Event\Event<\Cake\Attribute\Resolver>
 */
class BeforeArtifactsClearEvent extends Event
{
    /**
     * Event name constant
     */
    public const NAME = 'Attribute.Resolver.beforeArtifactsClear';

    /**
     * Constructor
     *
     * @param \Cake\Attribute\Resolver $subject The Resolver instance
     */
    public function __construct(Resolver $subject)
    {
        parent::__construct(self::NAME, $subject);
    }

    /**
     * Returns the Resolver subject
     *
     * @return \Cake\Attribute\Resolver
     */
    public function getSubject(): Resolver
    {
        return parent::getSubject();
    }
}
