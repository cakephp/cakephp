<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Http;

use Cake\Console\CommandCollection;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TestApp\Command\DemoCommand;

class EventApplication extends BaseApplication
{
    public function events(EventManagerInterface $eventManager)
    {
        $eventManager->on('My.event', function () {
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

    public function __invoke(ServerRequestInterface $req, ResponseInterface $res, callable $next): ResponseInterface
    {
        return $res;
    }
}
