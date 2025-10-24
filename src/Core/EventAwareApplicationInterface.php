<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Cake\Event\EventManagerInterface;

interface EventAwareApplicationInterface
{
    /**
     * Register application events.
     *
     * @param \Cake\Event\EventManagerInterface $eventManager The global event manager to register listeners on
     * @return \Cake\Event\EventManagerInterface
     */
    public function events(EventManagerInterface $eventManager): EventManagerInterface;

    /**
     * @param \Cake\Event\EventManagerInterface $eventManager The global event manager to register listeners on
     * @return \Cake\Event\EventManagerInterface
     */
    public function pluginEvents(EventManagerInterface $eventManager): EventManagerInterface;
}
