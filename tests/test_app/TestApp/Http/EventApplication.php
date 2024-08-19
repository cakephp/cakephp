<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Http;

use Cake\Console\CommandCollection;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use TestApp\Command\DemoCommand;

class EventApplication extends BaseApplication
{
    public function events(EventManagerInterface $eventManager): EventManagerInterface
    {
        $eventManager->on('My.event', function (): void {
        });

        return $eventManager;
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }

    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands->addMany(['ex' => DemoCommand::class]);
    }
}
