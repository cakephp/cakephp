<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;

/**
 * Use HTTP caching headers to see if rendering can be skipped.
 *
 * Checks if the response can be considered different according to the request
 * headers, and caching headers in the response. If the response was not modified,
 * then the controller and view render process is skipped. And the client will get a
 * response with an empty body and a "304 Not Modified" header.
 *
 * To use this component your controller actions must set either the `Last-Modified`
 * or `Etag` header. Without one of these headers being set this component
 * will have no effect.
 */
class CheckHttpCacheComponent extends Component
{
    /**
     * Before Render hook
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event The Controller.beforeRender event.
     * @return void
     */
    public function beforeRender(EventInterface $event): void
    {
        $controller = $this->getController();
        $response = $controller->getResponse();
        $request = $controller->getRequest();
        if (!$response->isNotModified($request)) {
            return;
        }

        $controller->setResponse($response->withNotModified());
        $event->stopPropagation();
    }
}
