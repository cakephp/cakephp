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
 * Event fired after artifact cache is cleared.
 *
 * This event is dispatched after artifact files have been deleted and memory
 * caches cleared. Contains the success status of the operation.
 *
 * Stopping this event has no effect as the clearing is already complete.
 *
 * @extends \Cake\Event\Event<\Cake\Attribute\Resolver>
 */
class AfterArtifactsClearEvent extends Event
{
    /**
     * Event name constant
     */
    public const NAME = 'Attribute.Resolver.afterArtifactsClear';

    /**
     * Constructor
     *
     * @param \Cake\Attribute\Resolver $subject The Resolver instance
     * @param bool $success Whether the artifact clearing was successful
     */
    public function __construct(Resolver $subject, bool $success)
    {
        parent::__construct(self::NAME, $subject, ['success' => $success]);
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
     * Returns whether the artifact clearing was successful
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->getData('success');
    }
}
