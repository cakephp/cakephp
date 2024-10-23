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
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\TableEvents;

use ArrayObject;
use Cake\Event\EventInterface;

interface BeforeMarshalEventInterface {

    /**
     * The Model.beforeMarshal event is fired before request data is converted into entities.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Model event.
     * @param \ArrayObject<string, mixed> $data Data to be saved.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void;
}
