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
use Cake\Attribute\Resolver\AttributeCollection;
use Cake\Event\Event;

/**
 * Event fired after attribute resolution completes.
 *
 * This event is dispatched after all attributes have been resolved, either from
 * artifact cache or fresh scanning. Contains the final AttributeCollection.
 *
 * Stopping this event has no effect as the resolution is already complete.
 *
 * @extends \Cake\Event\Event<\Cake\Attribute\Resolver>
 */
class AfterResolveEvent extends Event
{
    /**
     * Event name constant
     */
    public const NAME = 'Attribute.Resolver.afterResolve';

    /**
     * Constructor
     *
     * @param \Cake\Attribute\Resolver $subject The Resolver instance
     * @param \Cake\Attribute\Resolver\AttributeCollection $collection The resolved attributes
     */
    public function __construct(Resolver $subject, AttributeCollection $collection)
    {
        parent::__construct(self::NAME, $subject, ['collection' => $collection]);
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

    /**
     * Returns the resolved attribute collection
     *
     * @return \Cake\Attribute\Resolver\AttributeCollection
     */
    public function getCollection(): AttributeCollection
    {
        return $this->getData('collection');
    }
}
